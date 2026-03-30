<?php
session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "sms");
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

// Get inputs
$email = trim($_POST['email']);
$password = trim($_POST['password']);

// ✅ NEW: Input validation
if (empty($email) || empty($password)) {
    $_SESSION['error'] = "❌ Email and password are required";
    header("Location: teacher.php");
    exit;
}

// ✅ FIXED QUERY (teacher_id instead of id)
$sql = "
    SELECT teacher_id, name, email, password, specialization
    FROM teachers
    WHERE email = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

// 🔴 VERY IMPORTANT: check prepare()
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($teacher = $result->fetch_assoc()) {

    if (password_verify($password, $teacher['password'])) {

        // ✅ Session data
        $_SESSION['teacher_id'] = $teacher['teacher_id'];
        $_SESSION['teacher_name'] = $teacher['name'];
        $_SESSION['teacher_email'] = $teacher['email'];
        $_SESSION['specialization'] = $teacher['specialization'];

        unset($_SESSION['error']);

        header("Location: teacher_dashboard.php");
        exit;

    } else {
        // ✅ NEW: Session-based error instead of alert
        $_SESSION['error'] = "❌ Invalid Password. Please try again.";
        header("Location: teacher.php");
        exit;
    }

} else {
    // ✅ NEW: Session-based error instead of alert
    $_SESSION['error'] = "❌ Email not found. Please check and try again.";
    header("Location: teacher.php");
    exit;
}

$stmt->close();
$conn->close();
?>
