<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);

    // Set your secret admin code here
    $secret_code = "Veda1234"; // Change this to your desired secret code

    if ($code === $secret_code) {
        $_SESSION['allow_admin_register'] = true;
        header("Location: adminregister.php");
        exit;
    } else {
        echo "<script>alert('❌ Invalid Access Code! You are not authorized.'); window.location='admin.php';</script>";
        exit;
    }
} else {
    header("Location: adminlogin.php");
    exit;
}
?>
