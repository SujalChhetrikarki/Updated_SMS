<?php
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include '../Database/db_connect.php';

// Accept only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: add_student.php");
    exit;
}

// Collect and sanitize input
$name          = trim($_POST['name'] ?? '');
$email         = trim($_POST['email'] ?? '');
$password_raw  = $_POST['password'] ?? '';
$class_id      = intval($_POST['class_id'] ?? 0);
$date_of_birth = $_POST['date_of_birth'] ?? '';
$gender        = $_POST['gender'] ?? '';

// Validate required fields
if (
    empty($name) ||
    empty($email) ||
    empty($password_raw) ||
    empty($class_id) ||
    empty($date_of_birth) ||
    empty($gender)
) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: add_student.php");
    exit;
}

// Validate gender
$allowed_genders = ['Male', 'Female', 'Other'];
if (!in_array($gender, $allowed_genders)) {
    $_SESSION['error'] = "Invalid gender selected.";
    header("Location: add_student.php");
    exit;
}

// Check for duplicate email
$check = $conn->prepare("SELECT student_id FROM students WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Email already exists.";
    $check->close();
    header("Location: add_student.php");
    exit;
}
$check->close();

// Hash password
$password = password_hash($password_raw, PASSWORD_DEFAULT);

// Insert student (student_id is AUTO_INCREMENT)
$sql = "INSERT INTO students (name, email, password, class_id, date_of_birth, gender)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['error'] = "Prepare failed: " . $conn->error;
    header("Location: add_student.php");
    exit;
}

$stmt->bind_param(
    "sssiss",
    $name,
    $email,
    $password,
    $class_id,
    $date_of_birth,
    $gender
);

if ($stmt->execute()) {
    // Optional: get auto-generated student_id
    $new_student_id = $stmt->insert_id;

    $_SESSION['success'] = "Student added successfully. Student ID: " . $new_student_id;
    header("Location: add_student.php");
    exit;
} else {
    $_SESSION['error'] = "Database error: " . $stmt->error;
    header("Location: add_student.php");
    exit;
}

$stmt->close();
$conn->close();
?>
