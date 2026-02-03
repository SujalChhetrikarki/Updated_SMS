<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit;
}

include '../Database/db_connect.php';

$teacher_id = $_SESSION['teacher_id'];
$exam_id = $_GET['exam_id'] ?? 0;

/* 1️⃣ Fetch exam details + class and subject (verify teacher) */
$stmt = $conn->prepare("
    SELECT e.exam_id, e.exam_date, e.max_marks, e.term,
           c.class_id, c.class_name,
           s.subject_id, s.subject_name
    FROM exams e
    JOIN classes c ON e.class_id = c.class_id
    JOIN subjects s ON e.subject_id = s.subject_id
    JOIN class_subject_teachers cst
        ON cst.class_id = e.class_id AND cst.subject_id = e.subject_id
    WHERE e.exam_id = ? AND cst.teacher_id = ?
");
$stmt->bind_param("ii", $exam_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<h3 style='color:red;'>❌ Access Denied or Exam Not Found.</h3>");
}

$exam = $result->fetch_assoc();
$stmt->close();

/* 2️⃣ Fetch students in this class */
$stmt = $conn->prepare("SELECT student_id, name FROM students WHERE class_id = ?");
$stmt->bind_param("i", $exam['class_id']);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();

/* 3️⃣ Handle form submission */
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($_POST['marks'] as $student_id => $marks) {

        $marks = trim($marks);
        if ($marks === '') continue;

        /* INSERT / UPDATE marks — FIXED BINDING */
        $stmt = $conn->prepare("
            INSERT INTO results (student_id, exam_id, marks_obtained, status)
            VALUES (?, ?, ?, 'Pending')
            ON DUPLICATE KEY UPDATE
                marks_obtained = VALUES(marks_obtained),
                status = 'Pending'
        ");
        // 🔧 FIX: student_id is VARCHAR
        $stmt->bind_param("sid", $student_id, $exam_id, $marks);
        $stmt->execute();
        $stmt->close();

        /* UPDATE progressive average marks — FIXED BINDING */
        $stmt_avg = $conn->prepare("
            UPDATE results r
            JOIN (
                SELECT r2.result_id,
                       ROUND((
                           SELECT AVG(r3.marks_obtained)
                           FROM results r3
                           JOIN exams e3 ON r3.exam_id = e3.exam_id
                           WHERE r3.student_id = r2.student_id
                             AND e3.subject_id = e2.subject_id
                             AND e3.exam_date <= e2.exam_date
                       ), 2) AS avg_marks
                FROM results r2
                JOIN exams e2 ON r2.exam_id = e2.exam_id
                WHERE r2.student_id = ?
            ) t ON r.result_id = t.result_id
            SET r.average_marks = t.avg_marks
            WHERE r.student_id = ?
        ");
        // 🔧 FIX: both are VARCHAR
        $stmt_avg->bind_param("ss", $student_id, $student_id);
        $stmt_avg->execute();
        $stmt_avg->close();
    }
    $success = "✅ Marks updated successfully! Average marks calculated automatically.";
}
/* 4️⃣ Fetch existing marks */
$existing_marks = [];
$stmt = $conn->prepare("SELECT student_id, marks_obtained FROM results WHERE exam_id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $existing_marks[$row['student_id']] = $row['marks_obtained'];
}
$stmt->close();

/* 5️⃣ Re-fetch students */
$stmt = $conn->prepare("SELECT student_id, name FROM students WHERE class_id = ?");
$stmt->bind_param("i", $exam['class_id']);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Add/Update Marks - <?= htmlspecialchars($exam['subject_name']) ?></title>

<style>
body { font-family: "Segoe UI", Arial; background: #f4f6fa; margin: 0; padding: 0; color: #333; }
header { background: #0066cc; color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
header h1 { margin: 0; font-size: 24px; }
header a.logout-btn { background: #dc3545; color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-weight: bold; }
header a.logout-btn:hover { background: #b02a37; }
.container { max-width: 1100px; margin: 30px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
h2 { color: #0066cc; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
th { background: #007bff; color: #fff; }
input[type="number"] { width: 60px; padding: 5px; }
.btn { background: #28a745; color: #fff; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
.btn:hover { background: #218838; }
.success { color: green; margin-top: 10px; font-weight: bold; }
.back-btn { display: inline-block; background: #007bff; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; margin-bottom: 15px; }
.back-btn:hover { background: #0056b3; }
</style>
</head>

<body>

<header>
<h1>Add / Update Marks</h1>
<a href="logout.php" class="logout-btn">Logout</a>
</header>

<div class="container">
<a class="back-btn" href="manage_marks.php?class_id=<?= $exam['class_id'] ?>&subject_id=<?= $exam['subject_id'] ?>">⬅ Back to Results</a>

<h2>Add / Update Marks for <?= htmlspecialchars($exam['subject_name']) ?> — <?= htmlspecialchars($exam['class_name']) ?></h2>
<p>
Exam Date: <?= htmlspecialchars($exam['exam_date']) ?> |
Term: <?= htmlspecialchars($exam['term']) ?> |
Max Marks: <?= htmlspecialchars($exam['max_marks']) ?>
</p>

<?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>

<form method="post">
<table>
<tr>
<th>Student ID</th>
<th>Student Name</th>
<th>Marks Obtained</th>
</tr>

<?php while ($student = $students->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($student['student_id']) ?></td>
<td><?= htmlspecialchars($student['name']) ?></td>
<td>
<input type="number"
       step="0.01"
       min="0"
       max="<?= $exam['max_marks'] ?>"
       name="marks[<?= htmlspecialchars($student['student_id']) ?>]"
       value="<?= $existing_marks[$student['student_id']] ?? '' ?>">
</td>
</tr>
<?php endwhile; ?>

</table>
<br>
<button type="submit" class="btn">Save Marks</button>
</form>
</div>

</body>
</html>
