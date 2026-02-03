<?php
session_start();
include '../Database/db_connect.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = trim($_POST['admin_id']);
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    if ($password !== $confirm) {
        echo "<script>alert('Passwords do not match!'); window.location='admin_register.php';</script>";
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO admins (admin_id, name, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $admin_id, $name, $email, $hashed_password);

    if ($stmt->execute()) {
        echo "<script>alert('Admin registered successfully!'); window.location='admin.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Registration</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

:root{
  --primary:#3b82f6;
  --glass:rgba(255,255,255,0.18);
}

*{margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif;}

body{
  min-height:100vh;
  background:
    radial-gradient(circle at top right,#60a5fa,transparent 40%),
    radial-gradient(circle at bottom left,#22d3ee,transparent 40%),
    linear-gradient(135deg,#0ea5e9,#6366f1);
  display:flex;
  flex-direction:column;
  color:#fff;
}

/* ===== HEADER ===== */
header{
  padding:18px 60px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}

.brand{
  display:flex;
  align-items:center;
  gap:14px;
}

.brand img{
  width:44px;
  height:44px;
  border-radius:12px;
}

.brand h1{
  font-size:18px;
  font-weight:600;
}

.brand span{
  font-size:13px;
  opacity:.7;
}

nav a{
  margin-left:28px;
  text-decoration:none;
  color:#fff;
  font-weight:500;
  opacity:.85;
}

nav a:hover{
  opacity:1;
}

/* ===== MAIN ===== */
main{
  flex:1;
  display:flex;
  justify-content:center;
  align-items:center;
  padding:40px 20px;
}

/* ===== GLASS REGISTER CARD ===== */
.register-card{
  width:400px;
  padding:42px;
  border-radius:22px;
  background:var(--glass);
  backdrop-filter:blur(18px);
  box-shadow:0 25px 60px rgba(0,0,0,.35);
  text-align:center;
  animation:slideUp .8s ease;
}

@keyframes slideUp{
  from{opacity:0; transform:translateY(40px);}
  to{opacity:1; transform:translateY(0);}
}

.register-card h2{
  font-size:24px;
  margin-bottom:10px;
}

.register-card input{
  width:100%;
  padding:14px;
  margin-bottom:20px;
  border:none;
  border-radius:14px;
  outline:none;
  font-size:15px;
}

.register-card button{
  width:100%;
  padding:14px;
  border-radius:14px;
  border:none;
  background:linear-gradient(135deg,#3b82f6,#6366f1);
  color:#fff;
  font-size:16px;
  font-weight:600;
  cursor:pointer;
  transition:.3s;
}

.register-card button:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 30px rgba(0,0,0,.3);
}

.login-link{
  display:block;
  margin-top:15px;
  padding:12px;
  border-radius:14px;
  text-decoration:none;
  background:linear-gradient(135deg,#10b981,#3b82f6);
  color:#fff;
  font-weight:600;
  transition:.3s;
}

.login-link:hover{
  transform:translateY(-2px);
  box-shadow:0 10px 25px rgba(0,0,0,.25);
}

/* ===== FOOTER ===== */
footer{
  padding:16px;
  text-align:center;
  font-size:13px;
  opacity:.75;
}
</style>
</head>
<body>

<header>
  <div class="brand">
    <img src="../Images/logo.jpg" alt="Logo">
    <div>
      <h1>Student Management</h1>
      <span>Diversity Academy</span>
    </div>
  </div>

  <nav>
    <a href="../index.php">Home</a>
    <a href="../Students/student.php">Student</a>
    <a href="../Teachers/teacher.php">Teacher</a>
    <a href="../Admin/admin.php">Admin</a>
  </nav>
</header>

<main>
  <div class="register-card">
    <h2>Admin Registration</h2>

    <a href="../index.php">
      <img src="../Images/logo.jpg" alt="Logo" style="width:80px; height:80px; margin:0 auto 20px; border-radius:12px;">
    </a>

    <form method="post" action="">
      <input type="text" name="admin_id" placeholder="Admin ID" required>
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      <button type="submit">Register</button>
    </form>

    <a href="admin.php" class="login-link">Already have an account? Login</a>
  </div>
</main>

<footer>
  © 2026 Diversity Academy • Student Management System
</footer>

</body>
</html>
