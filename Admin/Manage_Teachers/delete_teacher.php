<?php
session_start();
include '../../Database/db_connect.php';

// ✅ Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// ✅ Validate teacher_id from GET
if (!isset($_GET['teacher_id']) || empty($_GET['teacher_id'])) {
    die("Teacher ID is missing or invalid.");
}

$teacher_id = $_GET['teacher_id'];

// ✅ Check if teacher exists
$stmt_check = $conn->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
$stmt_check->bind_param("s", $teacher_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    die("Teacher not found.");
}

// ✅ Start transaction
$conn->begin_transaction();

try {
    // 1️⃣ Delete teacher assignments from class_teachers
    $stmt_ct = $conn->prepare("DELETE FROM class_teachers WHERE teacher_id = ?");
    $stmt_ct->bind_param("s", $teacher_id);
    $stmt_ct->execute();

    // 2️⃣ Delete teacher assignments from teacher_subjects (or class_subject_teachers)
    $stmt_ts = $conn->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?");
    $stmt_ts->bind_param("s", $teacher_id);
    $stmt_ts->execute();

    // 3️⃣ Delete the teacher
    $stmt_delete = $conn->prepare("DELETE FROM teachers WHERE teacher_id = ?");
    $stmt_delete->bind_param("s", $teacher_id);
    $stmt_delete->execute();

    // ✅ Commit transaction
    $conn->commit();

    header("Location: Teachersshow.php?msg=" . urlencode("✅ Teacher deleted successfully."));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("Error deleting teacher: " . $e->getMessage());
}
?>
