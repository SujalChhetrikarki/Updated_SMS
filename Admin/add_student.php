<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include '../Database/db_connect.php';

// Fetch classes
$classes = [];
$sql = "SELECT class_id, class_name FROM classes ORDER BY class_name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Fetch all students with class info
$students = [];
$sql = "
    SELECT s.student_id, s.name, s.email, s.gender, s.date_of_birth, c.class_name
    FROM students s
    JOIN classes c ON s.class_id = c.class_id
    ORDER BY c.class_name, s.name
";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Student</title>
<style>
/* ===== Body & Layout ===== */
body { margin:0; font-family: 'Inter', sans-serif; background: #f0f2f5; display:flex; }

/* ===== Sidebar ===== */
.sidebar {
    width: 240px;
    background: #1f2937;
    color: #fff;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding-top: 30px;
    display: flex;
    flex-direction: column;
}
.sidebar h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 22px;
    color: #3b82f6;
}
.sidebar a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    margin: 4px 12px;
    background: #374151;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s;
}
.sidebar a:hover { background: #3b82f6; color: #fff; }
.sidebar a.logout { background: #ef4444; }
.sidebar a.logout:hover { background: #f87171; }

/* ===== Header ===== */
#header {
    position: fixed; top: 0; left: 240px; right: 0;
    height: 60px; background:#3b82f6; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:20px; font-weight:500; z-index:10;
}

/* ===== Main Content ===== */
.main {
    margin-left: 240px; /* keep space for sidebar */
    padding: 30px;
    flex: 1;

    /* Center the Add Student form vertically and horizontally */
    display: flex;
    justify-content: center;  /* horizontal centering */
    align-items: center;      /* vertical centering */
    min-height: calc(100vh - 60px); /* full height minus header */
    flex-direction: column;   /* stack form and student viewer */
}

/* ===== Form Card ===== */
.container {
    max-width: 650px;
    width: 100%;
    background: #fff;
    padding: 25px 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    margin-bottom: 40px;
}

h2 { text-align:center; margin-bottom:20px; color:#333; }
input, select { width:100%; padding:10px; margin:8px 0; border:1px solid #ccc; border-radius:6px; }
button { width:100%; padding:12px; background:#3b82f6; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:500; }
button:hover { background:#2563eb; }
.msg { text-align:center; margin-bottom:15px; }
.error { color:#ef4444; }
.success { color:#10b981; }
a.back { display:inline-block; margin-top:10px; text-decoration:none; color:#3b82f6; }

/* ===== Student Viewer ===== */
.student-box { max-width:1000px; background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.15); margin:30px auto; }
.student-box h3 { text-align:center; margin-bottom:20px; color:#333; }
.student-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:20px; }
.student-card {
    background:#f8f9fa; border-radius:10px; padding:15px; border-left:5px solid #3b82f6; transition:0.3s;
}
.student-card:hover { transform:translateY(-5px); box-shadow:0 6px 15px rgba(0,0,0,0.15); }
.student-card h4 { margin:0 0 8px; color:#3b82f6; }
.student-card p { margin:4px 0; font-size:14px; color:#333; }
.empty { text-align:center; font-size:15px; color:#6b7280; }

/* ===== Class Filter ===== */
.filter-box { display:flex; gap:10px; justify-content:center; margin-bottom:20px; }
.filter-box select { width:200px; }
.filter-box button { width:auto; padding:10px 20px; }

@media(max-width:768px){
    .main { padding:100px 15px 15px; }
    .student-grid { grid-template-columns: repeat(auto-fill, minmax(200px,1fr)); }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
<h2>Admin Panel</h2>
<a href="index.php">🏠 Home</a>
<a href="./Manage_student/Managestudent.php">📚 Manage Students</a>
<a href="./Manage_Teachers/Teachersshow.php">👨‍🏫 Manage Teachers</a>
<a href="./classes/classes.php">🏫 Manage Classes</a>
<a href="./subjects.php">📖 Manage Subjects</a>
<a href="./Managebook.php">📚 Manage Books</a>
<a href="add_student.php">➕ Add Student</a>
<a href="./add_teacher.php">➕ Add Teacher</a>
<a href="./Add_exam/add_exam.php">➕ Add Exam</a>
<a href="./admin_approve_results.php">✅ Approve Results</a>
<a href="./logout.php" class="logout">🚪 Logout</a>
</div>

<div id="header">Admin Panel - Add Student</div>

<div class="main">

<!-- Add Student Form -->
<div class="container">
<h2>Register New Student</h2>

<?php if(isset($_SESSION['error'])): ?>
<p class="msg error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
<?php endif; ?>
<?php if(isset($_SESSION['success'])): ?>
<p class="msg success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
<?php endif; ?>

<form action="add_student_process.php" method="POST">
<input type="text" name="name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required>

<label>Class</label>
<select name="class_id" required>
<option value="">-- Select Class --</option>
<?php foreach($classes as $class): ?>
<option value="<?= $class['class_id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
<?php endforeach; ?>
</select>

<label>Date of Birth</label>
<input type="date" name="date_of_birth" max="<?= date('Y-m-d') ?>" required>

<label>Gender</label>
<select name="gender" required>
<option value="">-- Select Gender --</option>
<option value="Male">Male</option>
<option value="Female">Female</option>
<option value="Other">Other</option>
</select>

<button type="submit">Add Student</button>
</form>

<a class="back" href="students.php">⬅ Back to Manage Students</a>
</div>

<!-- Student Viewer -->
<div class="student-box">
<h3>View Students</h3>
<div class="filter-box">
<select id="classFilter">
<option value="">-- Select Class --</option>
<?php foreach($classes as $class): ?>
<option value="<?= htmlspecialchars($class['class_name']) ?>"><?= htmlspecialchars($class['class_name']) ?></option>
<?php endforeach; ?>
</select>
<button onclick="showStudents()">Show Students</button>
</div>

<div id="studentContainer" style="display:none;">
<div class="student-grid">
<?php foreach($students as $student): ?>
<div class="student-card" data-class="<?= htmlspecialchars($student['class_name']) ?>">
<h4><?= htmlspecialchars($student['name']) ?></h4>
<p><b>ID:</b> <?= $student['student_id'] ?></p>
<p><b>Class:</b> <?= htmlspecialchars($student['class_name']) ?></p>
<p><b>Email:</b> <?= htmlspecialchars($student['email']) ?></p>
<p><b>Gender:</b> <?= $student['gender'] ?></p>
<p><b>DOB:</b> <?= $student['date_of_birth'] ?></p>
</div>
<?php endforeach; ?>
</div>
</div>
</div>

<script>
function showStudents() {
    const selected = document.getElementById("classFilter").value;
    const container = document.getElementById("studentContainer");
    const cards = document.querySelectorAll(".student-card");
    container.style.display = "block";
    let anyVisible = false;
    cards.forEach(card=>{
        if(selected==="" || card.dataset.class===selected){
            card.style.display="block";
            anyVisible=true;
        } else card.style.display="none";
    });
    if(!anyVisible) container.innerHTML='<div class="empty">No students found for this class.</div>';
}
</script>

</body>
</html>
