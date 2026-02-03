<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit;
}
include '../Database/db_connect.php';
$teacher_id = $_SESSION['teacher_id'];
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM teachers WHERE teacher_id = ?");
    $stmt->bind_param("s", $teacher_id);
    $stmt->execute();
    $stmt->bind_result($hashed);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($current, $hashed)) {
        $message = "❌ Current password is incorrect!";
    } elseif ($new !== $confirm) {
        $message = "⚠ New passwords do not match!";
    } else {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE teachers SET password = ? WHERE teacher_id = ?");
        $update->bind_param("ss", $new_hash, $teacher_id);
        if ($update->execute()) {
            $message = "✅ Password changed successfully!";
        } else {
            $message = "❌ Error updating password.";
        }
        $update->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Teacher - Change Password</title>
<style>
body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f9fafc;
    margin: 0;
    color: #333;
}
header {
    background: #0066cc;
    color: white;
    padding: 15px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
header h1 {
    margin: 0;
    font-size: 24px;
}
.logout-btn {
    background: #dc3545;
    color: white;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
}
.logout-btn:hover {
    background: #b52a37;
}
.container {
    max-width: 500px;
    margin: 50px auto;
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
h2 {
    color: #0066cc;
    text-align: center;
    margin-bottom: 25px;
}
label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
}
input {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
}
button {
    width: 100%;
    padding: 12px;
    background: #0066cc;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
}
button:hover {
    background: #004d99;
}
.msg {
    text-align: center;
    font-weight: bold;
    margin-bottom: 15px;
}
.success { color: green; }
.error { color: red; }

.back-link {
    display: block;
    text-align: center;
    margin-top: 15px;
    text-decoration: none;
    color: #0066cc;
    font-weight: 500;
}
.back-link:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<header>
    <h1>Teacher Dashboard</h1>
    <a href="logout.php" class="logout-btn">Logout</a>
</header>

<div class="container">
    <h2>Change Password</h2>

    <?php if ($message): ?>
        <div class="msg <?php echo (strpos($message, '✅') !== false) ? 'success' : 'error'; ?>">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>Current Password</label>
        <input type="password" name="current_password" required>

        <label>New Password</label>
        <input type="password" name="new_password" required>

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>

        <button type="submit">Update Password</button>
    </form>

    <a href="teacher_dashboard.php" class="back-link">⬅ Back to Dashboard</a>
</div>

</body>
</html>
