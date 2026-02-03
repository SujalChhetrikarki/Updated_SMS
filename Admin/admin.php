<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

:root{
  --primary:#3b82f6;
  --glass:rgba(255,255,255,0.18);
}

*{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:'Inter',sans-serif;
}

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

/* ===== GLASS LOGIN CARD ===== */
.login-card{
  width:380px;
  padding:42px;
  border-radius:22px;
  background:var(--glass);
  backdrop-filter:blur(18px);
  box-shadow:0 25px 60px rgba(0,0,0,.35);
  text-align:center;
  animation:slideUp .8s ease;
}

@keyframes slideUp{
  from{
    opacity:0;
    transform:translateY(40px);
  }
  to{
    opacity:1;
    transform:translateY(0);
  }
}

.login-card h2{
  font-size:24px;
  margin-bottom:10px;
}

.login-card p{
  font-size:14px;
  opacity:.8;
  margin-bottom:30px;
}

.login-card input[type="email"],
.login-card input[type="password"]{
  width:100%;
  padding:14px;
  margin-bottom:26px;
  border:none;
  border-radius:14px;
  outline:none;
  font-size:15px;
}

.login-card button{
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

.login-card button:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 30px rgba(0,0,0,.3);
}

.signup-btn{
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

.signup-btn:hover{
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
  <div class="login-card">
    <h2>Admin Login</h2>
    
    <a href="../index.php">
      <img src="../Images/logo.jpg" alt="Logo" style="width:80px; height:80px; margin:0 auto 20px; border-radius:12px;">
    </a>

    <form action="admin_login_process.php" method="post">
      <input type="email" name="email" placeholder="Admin Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>

    <a href="#" class="signup-btn" id="secureSignUp">Sign Up as Admin</a>
    <p style="margin-top:10px; font-size:13px; opacity:.8;">⚠ Only authorized administrators are allowed.</p>

    <!-- Hidden form for admin access code -->
    <form action="verify_admin_code.php" method="POST" id="codeForm" style="display:none;">
      <input type="hidden" name="code" id="hiddenCode">
    </form>
  </div>
</main>

<footer>
  © 2026 Diversity Academy • Student Management System
</footer>

<script>
  // Secure Admin Signup Code
  document.getElementById("secureSignUp").addEventListener("click", function (e) {
      e.preventDefault();
      const code = prompt("🔒 Enter Admin Access Code:");
      if (code) {
          document.getElementById("hiddenCode").value = code;
          document.getElementById("codeForm").submit();
      }
  });
</script>

</body>
</html>
