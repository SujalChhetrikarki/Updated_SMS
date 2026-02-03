<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

include '../../Database/db_connect.php';

if (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
    $class_id = intval($_GET['class_id']);

    // Delete all students in that class
    $stmt = $conn->prepare("DELETE FROM students WHERE class_id=?");
    $stmt->bind_param("i", $class_id);

    if ($stmt->execute()) {
        header("Location: Managestudent.php?msg=Students+deleted+successfully");
        exit;
    } else {
        die("Error deleting students: " . $conn->error);
    }
} else {
    die("No class selected.");
}
?>
