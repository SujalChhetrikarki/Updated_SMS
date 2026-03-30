<?php
session_start();

// DB Connection
$conn = new mysqli("localhost", "root", "", "sms");
if ($conn->connect_error) {
    $_SESSION['error'] = "Database Connection failed";
    header("Location: Student.php");
    exit;
}

// Get inputs
$email = trim($_POST['email']);
$password = trim($_POST['password']);

// Validate inputs
if (empty($email) || empty($password)) {
    $_SESSION['error'] = "❌ Email and password are required";
    header("Location: Student.php");
    exit;
}

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
        
        // Clear error
        unset($_SESSION['error']);

        header("Location: student_dashboard.php");
        exit;

    } else {
        $_SESSION['error'] = "❌ Invalid Password. Please try again.";
        header("Location: Student.php");
        exit;
    }

} else {
    $_SESSION['error'] = "❌ Email not found. Please check and try again.";
    header("Location: Student.php");
    exit;
}

$stmt->close();
$conn->close();
?>
