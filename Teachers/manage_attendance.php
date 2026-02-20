<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit;
}

include '../Database/db_connect.php';

$teacher_id = $_SESSION['teacher_id'];
$class_id = $_GET['class_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');

if (!$class_id) {
    die("❌ Invalid request: Missing class ID.");
}

// Fetch subjects assigned to this teacher & class
$stmt = $conn->prepare("
    SELECT s.subject_id, s.subject_name
    FROM subjects s
    JOIN class_subject_teachers cst ON cst.subject_id = s.subject_id
    WHERE cst.teacher_id = ? AND cst.class_id = ?
");
$stmt->bind_param("ii", $teacher_id, $class_id);
$stmt->execute();
$subjects = $stmt->get_result();
$stmt->close();

// Default subject (first one assigned)
$subject_id = $subjects->fetch_assoc()['subject_id'] ?? null;
$subjects->data_seek(0); // reset pointer

// Fetch students in this class
$stmt = $conn->prepare("SELECT student_id, name FROM students WHERE class_id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();

// Handle attendance submission
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {

    $subject_id = $_POST['subject_id'] ?? null;
    if (!$subject_id) die("<p style='color:red;'>❌ Please select a subject before saving attendance.</p>");

    foreach ($_POST['attendance'] as $student_id => $status) {

        // Validate student belongs to class
        $stmt_check = $conn->prepare("SELECT student_id FROM students WHERE student_id = ? AND class_id = ?");
        $stmt_check->bind_param("si", $student_id, $class_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if($res_check->num_rows > 0){
            $stmt_insert = $conn->prepare("
                INSERT INTO attendance (student_id, class_id, subject_id, date, status)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status)
            ");
            $stmt_insert->bind_param("siiss", $student_id, $class_id, $subject_id, $date, $status);
            $stmt_insert->execute();
            $stmt_insert->close();
        }

        $stmt_check->close();
    }

    $msg = "✅ Attendance saved successfully!";
}

// Fetch existing attendance
$attendance = [];
if ($subject_id) {
    $stmt2 = $conn->prepare("
        SELECT s.student_id, s.name, COALESCE(a.status, 'Absent') AS status
        FROM students s
        LEFT JOIN attendance a 
            ON s.student_id = a.student_id 
            AND a.class_id = ? 
            AND a.subject_id = ? 
            AND a.date = ?
        WHERE s.class_id = ?
        ORDER BY s.name
    ");
    $stmt2->bind_param("iisi", $class_id, $subject_id, $date, $class_id);
    $stmt2->execute();
    $attendance = $stmt2->get_result();
    $stmt2->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Attendance</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { font-family: 'Inter', sans-serif; background: #f4f6f9; margin: 0; color: #333; }
header { background: linear-gradient(90deg,#0066cc,#004aad); color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
header h1 { margin: 0; font-size: 24px; }
header a.logout-btn { background: #dc3545; color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-weight: bold; transition: 0.3s; }
header a.logout-btn:hover { background: #b02a37; }

.container { max-width: 1100px; margin: 30px auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
h2 { color: #004aad; margin-bottom: 20px; border-left: 5px solid #0066cc; padding-left: 10px; font-weight: 600; }

.date-form { margin-bottom: 20px; }
.date-form input[type="date"], .date-form select { padding: 6px 10px; border-radius: 5px; border: 1px solid #ccc; }
.date-form button { padding: 6px 12px; background: #007bff; color: #fff; border: none; border-radius: 6px; cursor: pointer; transition: 0.3s; }
.date-form button:hover { background: #0056b3; }

.success { color: green; font-weight: bold; margin-bottom: 15px; }

table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { border: 1px solid #ddd; padding: 12px; text-align: center; }
th { background: #007bff; color: white; font-weight: 600; }
tr:nth-child(even) { background: #f9f9f9; }
tr:hover { background: #f1f1f1; }

input[type="radio"] { cursor: pointer; }
.btn { padding: 8px 14px; background: #007bff; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin-right: 10px; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; }
.btn:hover { background: #0056b3; }

.empty { text-align: center; font-style: italic; color: #7f8c8d; padding: 20px; }
</style>
</head>
<body>

<header>
    <h1><i class="fa fa-check-square"></i> Manage Attendance</h1>
    <a href="logout.php" class="logout-btn"><i class="fa fa-sign-out-alt"></i> Logout</a>
</header>

<div class="container">

<h2>🗓 Attendance for Class ID: <?= htmlspecialchars($class_id); ?></h2>

<!-- Date Selector -->
<form method="GET" class="date-form">
    <input type="hidden" name="class_id" value="<?= htmlspecialchars($class_id); ?>">
    <label><strong>Select Date:</strong></label>
    <input type="date" name="date" value="<?= htmlspecialchars($date); ?>" max="<?= date('Y-m-d') ?>">
    <button type="submit"><i class="fa fa-calendar"></i> Go</button>
</form>

<?php if ($msg) echo "<p class='success'>$msg</p>"; ?>

<?php if (!empty($attendance) && $attendance->num_rows>0): ?>
<form method="POST">
<input type="hidden" name="subject_id" value="<?= $subject_id ?>">

<table>
<tr>
    <th>Student Name</th>
    <th>Present</th>
    <th>Absent</th>
</tr>
<?php while ($row = $attendance->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><input type="radio" name="attendance[<?= $row['student_id'] ?>]" value="Present" <?= $row['status'] == 'Present' ? 'checked' : '' ?>></td>
    <td><input type="radio" name="attendance[<?= $row['student_id'] ?>]" value="Absent" <?= $row['status'] == 'Absent' ? 'checked' : '' ?>></td>
</tr>
<?php endwhile; ?>
</table>
<br>
<button type="submit" class="btn"><i class="fa fa-save"></i> Save Attendance</button>
<a href="teacher_dashboard.php" class="btn"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
</form>
<?php else: ?>
<p class="empty">⚠ No students found for this class.</p>
<?php endif; ?>

</div>
</body>
</html>
