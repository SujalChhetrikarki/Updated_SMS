<?php
session_start();
include '../../Database/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$exam_id = $_GET['exam_id'] ?? 0;

$min_date = new DateTime('tomorrow');
$max_date = new DateTime('today');
$max_date->modify('+2 months');
$min_date_str = $min_date->format('Y-m-d');
$max_date_str = $max_date->format('Y-m-d');
$max_allowed_marks = 100;

/* Fetch exam */
$stmt = $conn->prepare("SELECT * FROM exams WHERE exam_id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

if (!$exam) die("Exam not found.");

/* Handle update */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $exam_date = $_POST['exam_date'];
    $max_marks = $_POST['max_marks'];

    if (empty($exam_date) || $exam_date < $min_date_str || $exam_date > $max_date_str) {
        header("Location: edit_exam.php?exam_id=" . urlencode($exam_id) . "&msg=" . urlencode("❌ Exam date must be between {$min_date_str} and {$max_date_str}."));
        exit;
    }

    if (filter_var($max_marks, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => $max_allowed_marks]]) === false) {
        header("Location: edit_exam.php?exam_id=" . urlencode($exam_id) . "&msg=" . urlencode("❌ Maximum marks must be a whole number between 1 and {$max_allowed_marks}."));
        exit;
    }

    $stmt = $conn->prepare("UPDATE exams SET exam_date = ?, max_marks = ? WHERE exam_id = ?");
    $stmt->bind_param("sii", $exam_date, $max_marks, $exam_id);

    if ($stmt->execute()) {
        header("Location: add_exam.php?msg=" . urlencode("✅ Exam updated successfully!"));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Exam</title>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f4f6f9;
}

/* Sidebar */
.sidebar {
    width: 220px;
    background: #111;
    color: #fff;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    padding-top: 20px;
}
.sidebar h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 20px;
    color: #00bfff;
}
.sidebar a {
    display: block;
    padding: 12px 20px;
    margin: 8px 15px;
    background: #222;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
}
.sidebar a:hover {
    background: #00bfff;
    color: #111;
}
.sidebar a.logout {
    background: #dc3545;
}
.sidebar a.logout:hover {
    background: #ff4444;
    color: #fff;
}

/* Main content */
.container {
    margin-left: 240px;
    padding: 30px;
}

.header {
    background: #00bfff;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    margin-bottom: 25px;
}

.header h1 {
    margin: 0;
    font-size: 22px;
    color: #333;
}

/* Card */
.card {
    max-width: 500px;
    margin: auto;
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

label {
    font-weight: bold;
    display: block;
    margin-top: 15px;
}

input {
    width: 100%;
    padding: 8px;
    margin-top: 6px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.btn {
    margin-top: 20px;
    padding: 10px;
    width: 100%;
    background: #00bfff;
    color: #fff;
    border: none;
    cursor: pointer;
    border-radius: 6px;
    font-size: 16px;
}

.btn:hover {
    background: #2980b9;
}

.back-link {
    display: block;
    margin-top: 15px;
    text-align: center;
    text-decoration: none;
    color: #00bfff;
    font-weight: bold;
}
.back-link:hover {
    text-decoration: underline;
}
</style>
</head>

<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="../index.php">🏠 Home</a>
    <a href="../Manage_student/Managestudent.php">📚 Manage Students</a>
    <a href="../Manage_Teachers/Teachersshow.php">👨‍🏫 Manage Teachers</a>
    <a href="../classes/classes.php">🏫 Manage Classes</a>
    <a href="../subjects.php">📖 Manage Subjects</a>
    <a href="../add_student.php">➕ Add Student</a>
    <a href="../add_teacher.php">➕ Add Teacher</a>
    <a href="add_exam.php">➕ Add Exam</a>
    <a href="../admin_approve_results.php">✅ Approve Results</a>
    <a href="../logout.php" class="logout">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="container">
    <div class="header">
        <h1>Edit Exam</h1>
    </div>

    <div class="card">
        <h3 style="text-align:center;">Exam ID: <?= htmlspecialchars($exam_id) ?></h3>

        <form method="POST">
            <label>Exam Date</label>
            <input type="date" name="exam_date" value="<?= htmlspecialchars($exam['exam_date']) ?>" min="<?= $min_date_str ?>" max="<?= $max_date_str ?>" required>
            <small style="display:block; margin-bottom:10px; color:#555;">Allowed exam dates: <?= $min_date_str ?> to <?= $max_date_str ?></small>

            <label>Maximum Marks</label>
            <input type="number" name="max_marks" value="<?= htmlspecialchars($exam['max_marks']) ?>" min="1" max="<?= $max_allowed_marks ?>" required>
            <small style="display:block; margin-bottom:10px; color:#555;">Allowed max marks: 1 to <?= $max_allowed_marks ?>.</small>

            <button type="submit" class="btn">Update Exam</button>
        </form>

        <a class="back-link" href="add_exam.php">⬅ Back to Exams</a>
    </div>
</div>

</body>
</html>
