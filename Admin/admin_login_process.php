<?php
// Extend session lifetime (7 days)
$lifetime = 60 * 60 * 24 * 7;

session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();
include '../Database/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = trim($_POST['email']);   // ✅ matches DB column
    $password = trim($_POST['password']);

    // ✅ NEW: Input validation
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "❌ Email and password are required";
        header("Location: admin.php");
        exit();
    }

    $stmt = $conn->prepare("
        SELECT admin_id, name, email, password
        FROM admins
        WHERE email = ?
        LIMIT 1
    ");

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        if (password_verify($password, $row['password'])) {

            // ✅ Session values
            $_SESSION['admin_id']    = $row['admin_id'];
            $_SESSION['admin_name']  = $row['name'];
            $_SESSION['admin_email'] = $row['email'];

            unset($_SESSION['error']);

            header("Location: index.php");
            exit();

        } else {
            // ✅ NEW: Session-based error instead of query string
            $_SESSION['error'] = "❌ Invalid Password. Please try again.";
            header("Location: admin.php");
            exit();
        }

    } else {
        // ✅ NEW: Session-based error instead of query string
        $_SESSION['error'] = "❌ Email not found. Please check and try again.";
        header("Location: admin.php");
        exit();
    }
}
?>
