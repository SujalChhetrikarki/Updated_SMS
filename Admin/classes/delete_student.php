<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

include '../../Database/db_connect.php';

if (isset($_GET['student_id']) && isset($_GET['class_id'])) {
    $student_id = intval($_GET['student_id']);
    $class_id = intval($_GET['class_id']);

    $stmt = $conn->prepare("DELETE FROM students WHERE student_id=?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    header("Location: view_students.php?id=$class_id&msg=deleted");
    exit;
} else {
    header("Location: classes.php");
    exit;
}
?>
