<?php
session_start();
include '../../Database/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$exam_id = $_GET['exam_id'] ?? 0;

if ($exam_id) {
    $sql = "DELETE FROM exams WHERE exam_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
}

header("Location: add_exam.php?msg=" . urlencode("✅ Exam deleted successfully!"));
exit;
