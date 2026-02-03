<?php
session_start();

// DB Connection
$conn = new mysqli("localhost", "root", "", "sms");
if ($conn->connect_error) {
    die("Database Connection failed");
}

// Get inputs
$email = trim($_POST['email']);
$password = trim($_POST['password']);

// Prepared statement (SECURE)
$stmt = $conn->prepare("
    SELECT student_id, name, email, password, class_id
    FROM students
    WHERE email = ?
    LIMIT 1
");

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($student = $result->fetch_assoc()) {

    if (password_verify($password, $student['password'])) {

        // ✅ Store session
        $_SESSION['student_id']    = $student['student_id'];
        $_SESSION['student_name']  = $student['name'];
        $_SESSION['student_email'] = $student['email'];
        $_SESSION['class_id']      = $student['class_id'];

        header("Location: student_dashboard.php");
        exit;

    } else {
        echo "<script>alert('Invalid Password'); window.location.href='Student.php';</script>";
    }

} else {
    echo "<script>alert('Email not found'); window.location.href='Student.php';</script>";
}

$stmt->close();
$conn->close();
?>
