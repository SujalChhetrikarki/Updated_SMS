<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit;
}

include '../Database/db_connect.php';

$teacher_id = $_SESSION['teacher_id'];
$subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';
$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : '';

if (!$subject_id || !$exam_id) {
    die("<p style='color:red;text-align:center;'>❌ Invalid request. Subject or Exam not specified.</p>");
}

// Fetch subject and class info (verify teacher)
$sql_check = "SELECT s.class_id, s.subject_name, c.class_name
              FROM subjects s
              JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
              JOIN classes c ON s.class_id = c.class_id
              WHERE s.subject_id = ? AND ts.teacher_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $subject_id, $teacher_id);
$stmt_check->execute();
$subject = $stmt_check->get_result()->fetch_assoc();
if (!$subject) die("<p style='color:red;text-align:center;'>❌ Unauthorized access.</p>");

// Fetch exam info
$sql_exam = "SELECT * FROM exams WHERE exam_id = ? AND subject_id = ?";
$stmt_exam = $conn->prepare($sql_exam);
$stmt_exam->bind_param("ii", $exam_id, $subject_id);
$stmt_exam->execute();
$exam = $stmt_exam->get_result()->fetch_assoc();
if (!$exam) die("<p style='color:red;text-align:center;'>❌ Exam not found.</p>");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_results'])) {

    foreach ($_POST['marks'] as $student_id => $theory) {
        $practical = $_POST['practical'][$student_id] ?? 0;
        $total = $theory + $practical;
        $average = $total / 2;

        $stmt_check_res = $conn->prepare("SELECT result_id FROM results WHERE student_id = ? AND exam_id = ?");
        $stmt_check_res->bind_param("si", $student_id, $exam_id);
        $stmt_check_res->execute();
        $res_exist = $stmt_check_res->get_result()->fetch_assoc();

        if ($res_exist) {
            $stmt_update = $conn->prepare("UPDATE results SET marks_obtained=?, practical_marks=?, total_marks=?, average_marks=? WHERE result_id=?");
            $stmt_update->bind_param("iiiid", $theory, $practical, $total, $average, $res_exist['result_id']);
            $stmt_update->execute();
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO results (student_id, exam_id, marks_obtained, practical_marks, total_marks, average_marks) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("siiiid", $student_id, $exam_id, $theory, $practical, $total, $average);
            if (!$stmt_insert->execute()) {
                echo "<p style='color:red;'>Error inserting student ID $student_id: " . $stmt_insert->error . "</p>";
            }
        }
    }

    header("Location: manage_results.php?subject_id=$subject_id&exam_id=$exam_id&msg=" . urlencode("Results saved successfully!"));
    exit;
}

// Fetch students in this class
$stmt_students = $conn->prepare("SELECT * FROM students WHERE class_id=? ORDER BY name ASC");
$stmt_students->bind_param("i", $subject['class_id']);
$stmt_students->execute();
$students = $stmt_students->get_result();

$msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Results</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f7f7f7;
    padding: 20px;
}
.container {
    max-width: 900px;
    margin: 0 auto;
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
h2 {
    text-align: center;
    margin-bottom: 20px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
th, td {
    border: 1px solid #ccc;
    padding: 10px;
    text-align: center;
}
th {
    background-color: #007BFF;
    color: white;
}
input[type=number] {
    width: 80px;
    padding: 5px;
    text-align: center;
}
button {
    display: block;
    width: 180px;
    margin: 0 auto;
    padding: 10px;
    background-color: #28A745;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}
button:hover {
    background-color: #218838;
}
.msg {
    text-align: center;
    color: green;
    font-weight: bold;
    margin-bottom: 20px;
}
a.back {
    display: block;
    text-align: center;
    margin-top: 20px;
    text-decoration: none;
    color: #007BFF;
}
a.back:hover {
    text-decoration: underline;
}
</style>
</head>
<body>
<div class="container">
<h2>📊 Manage Results for <?= htmlspecialchars($subject['subject_name']); ?> (Class: <?= htmlspecialchars($subject['class_name']); ?>, Exam Date: <?= $exam['exam_date']; ?>)</h2>

<?php if($msg) echo "<div class='msg'>{$msg}</div>"; ?>

<form method="POST">
<table>
<tr>
    <th>Student ID</th>
    <th>Name</th>
    <th>Theory Marks</th>
    <th>Practical Marks</th>
</tr>
<?php while ($s = $students->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($s['student_id']); ?></td>
    <td><?= htmlspecialchars($s['name']); ?></td>
    <td><input type="number" name="marks[<?= $s['student_id']; ?>]" min="0" max="<?= $exam['max_marks']; ?>"></td>
    <td><input type="number" name="practical[<?= $s['student_id']; ?>]" min="0" max="<?= $exam['max_marks']; ?>"></td>
</tr>
<?php endwhile; ?>
</table>
<button type="submit" name="save_results">💾 Save Results</button>
</form>

<a href="teacher_dashboard.php" class="back">⬅ Back to Dashboard</a>
</div>
</body>
</html>
