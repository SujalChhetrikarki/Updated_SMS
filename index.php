<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Management System</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

:root{
  --primary:#3b82f6;
  --dark:#0f172a;
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

/* ===== GLASS CARD ===== */
.card{
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

.card img{
  width:90px;
  height:90px;
  border-radius:20px;
  margin-bottom:18px;
}

.card h2{
  font-size:24px;
  margin-bottom:10px;
}

.card p{
  font-size:14px;
  opacity:.8;
  margin-bottom:30px;
}

/* ===== FORM ===== */
select{
  width:100%;
  padding:14px;
  border-radius:14px;
  border:none;
  outline:none;
  font-size:15px;
  margin-bottom:20px;
}

button{
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
  margin-bottom:12px;
}

button:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 30px rgba(0,0,0,.3);
}

/* Secondary Button */
.secondary-btn{
  background:rgba(255,255,255,0.2);
  border:1px solid rgba(255,255,255,0.4);
}

.secondary-btn:hover{
  background:rgba(255,255,255,0.3);
}

/* ===== ABOUT ===== */
.about{
  max-width:900px;
  margin:60px auto 30px;
  text-align:center;
  opacity:.9;
}

.about h3{
  font-size:26px;
  margin-bottom:14px;
}

.about p{
  max-width:720px;
  margin:0 auto 10px;
  line-height:1.7;
  font-size:15px;
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
    <img src="Images/logo.jpg" alt="Logo">
    <div>
      <h1>Student Management</h1>
      <span>Diversity Academy</span>
    </div>
  </div>

  <nav>
    <a href="index.php">Home</a>
    <a href="Students/student.php">Student</a>
    <a href="Teachers/teacher.php">Teacher</a>
    <a href="Admin/admin.php">Admin</a>
  </nav>
</header>

<main>
  <div class="card">
    <img src="Images/logo.jpg" alt="Logo">
    <h2>Welcome Back</h2>
    <p>Select your role to continue</p>

    <select id="role">
      <option value="student">Student</option>
      <option value="teacher">Teacher</option>
    </select>

    <button onclick="login()">Enter System</button>


  </div>
</main>

<section class="about">
  <h3>About Diversity Academy</h3>
  <p>
    Diversity Academy empowers learners through technology-driven education,
    fostering innovation, responsibility, and academic excellence.
  </p>
      <!-- Pre Admission Button -->
    <button class="secondary-btn"
      onclick="location.href='Admin/PreRegistration/preregistration.php'">
      Pre-Admission
    </button>
</section>

<footer>
  © 2026 Diversity Academy • Student Management System
</footer>

<script>
function login(){
  const role=document.getElementById("role").value;

  if(role==="student")
    location.href="Students/student.php";
  else if(role==="teacher")
    location.href="Teachers/teacher.php";
}
</script>

</body>
</html>
