<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}
include '../../Database/db_connect.php';

if (isset($_GET['id'])) {
    $class_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM classes WHERE class_id = ?");
    $stmt->bind_param("i", $class_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Class deleted!";
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
    }
}
header("Location: Classes.php");
exit;
