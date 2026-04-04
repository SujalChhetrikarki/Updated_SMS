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
.student-box { 
    width: 100%;
    max-width: 1400px;
    background:#fff; 
    padding:25px; 
    border-radius:12px; 
    box-shadow:0 4px 20px rgba(0,0,0,0.15); 
    margin:30px auto;
}
.student-box h3 { text-align:center; margin-bottom:20px; color:#333; }
.student-grid { 
    display:grid; 
    grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); 
    gap:15px;
    width: 100%;
}
.student-card {
    background:#f8f9fa; 
    border-radius:10px; 
    padding:15px; 
    border-left:5px solid #3b82f6; 
    transition:0.3s;
}
.student-card:hover { transform:translateY(-5px); box-shadow:0 6px 15px rgba(0,0,0,0.15); }
.student-card h4 { margin:0 0 8px; color:#3b82f6; font-size:16px; }
.student-card p { margin:4px 0; font-size:13px; color:#555; }
.empty { text-align:center; font-size:15px; color:#6b7280; padding: 40px; grid-column: 1/-1; }

/* ===== Tabs ===== */
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e5e7eb;
}
.tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.3s;
    font-size: 16px;
}
.tab-btn:hover { color: #3b82f6; }
.tab-btn.active { color: #3b82f6; border-bottom-color: #3b82f6; }
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}

/* ===== File Upload ===== */
.file-upload-area {
    border: 2px dashed #3b82f6;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    background: #f0f9ff;
    cursor: pointer;
    transition: all 0.3s;
}
.file-upload-area:hover {
    background: #e0f2fe;
    border-color: #2563eb;
}
.file-upload-area input[type="file"] {
    display: none;
}
.upload-icon {
    font-size: 40px;
    margin-bottom: 10px;
}
.upload-text {
    color: #3b82f6;
    font-weight: 500;
    margin-bottom: 5px;
}
.upload-hint {
    color: #6b7280;
    font-size: 13px;
}

/* ===== Class Filter ===== */
.filter-box { display:flex; gap:10px; justify-content:center; margin-bottom:20px; flex-wrap: wrap; }
.filter-box select { width:250px; padding: 10px; border: 1px solid #ccc; border-radius: 6px; }
.filter-box button { width:auto; padding:10px 30px; }

@media(max-width:1024px){
    .student-box { padding: 20px; margin: 20px auto; }
    .student-grid { grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap: 12px; }
    .filter-box { flex-wrap: wrap; }
    .filter-box select { width: 100%; max-width: 250px; }
}

@media(max-width:768px){
    .main { padding:100px 15px 15px; }
    .student-grid { grid-template-columns: repeat(auto-fill, minmax(200px,1fr)); gap: 10px; }
    .student-box { padding: 15px; margin: 15px 0; }
    .filter-box { flex-direction: column; align-items: stretch; }
    .filter-box select, .filter-box button { width: 100%; }
}

@media(max-width:480px){
    .student-grid { grid-template-columns: 1fr; }
    .container { padding: 15px 20px; margin-bottom: 20px; }
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
<h2>Student Management</h2>

<?php if(isset($_SESSION['error'])): ?>
<p class="msg error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
<?php endif; ?>
<?php if(isset($_SESSION['success'])): ?>
<p class="msg success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
<?php endif; ?>

<?php if(isset($_SESSION['bulk_errors']) && !empty($_SESSION['bulk_errors'])): ?>
<div style="background: #fee; padding: 15px; border-radius: 6px; margin-bottom: 15px; max-height: 300px; overflow-y: auto;">
    <h4 style="margin-top: 0; color: #ef4444;">Import Errors:</h4>
    <ul style="margin: 0; padding-left: 20px; color: #ef4444; font-size: 13px;">
    <?php foreach($_SESSION['bulk_errors'] as $error): ?>
        <li><?= htmlspecialchars($error) ?></li>
    <?php endforeach; ?>
    </ul>
</div>
<?php unset($_SESSION['bulk_errors']); endif; ?>

<!-- Tabs -->
<div class="tabs">
    <button class="tab-btn active" onclick="switchTab('single')">Single Student</button>
    <button class="tab-btn" onclick="switchTab('bulk')">Bulk Upload</button>
</div>

<!-- Single Student Tab -->
<div id="single" class="tab-content active">
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
</div>

<!-- Bulk Upload Tab -->
<div id="bulk" class="tab-content">
<form action="bulk_add_students.php" method="POST" enctype="multipart/form-data">
<div class="file-upload-area" onclick="document.getElementById('csvFile').click();">
    <div class="upload-icon">📁</div>
    <div class="upload-text">Click to upload CSV file</div>
    <div class="upload-hint">or drag and drop (.csv, max 5MB)</div>
    <input type="file" id="csvFile" name="csv_file" accept=".csv" required>
</div>

<div id="fileInfo" style="margin-top: 15px; padding: 10px; background: #f3f4f6; border-radius: 6px; display: none;">
    <p><strong>Selected file:</strong> <span id="fileName"></span></p>
</div>

<p style="font-size: 13px; color: #6b7280; margin-top: 15px;">
    <strong>CSV Format:</strong> name, email, password, class_id, date_of_birth, gender<br>
    <strong>Example:</strong><br>
Rachana KC,Rachanakc@gmail.com,Pass123!,1,2010-05-15,Female <br>
Sujal Chhetri Karki,Sujalchhetri@gmail.com,Pass456!,2,2009-03-20,Male
</p>

<a href="SAMPLE_STUDENTS.csv" download style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #10b981; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 500; transition: 0.3s;">📥 Download Sample CSV</a>

<button type="submit" style="margin-top: 20px;">Import Students</button>
</form>
</div>

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
// Tab switching
function switchTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tab).classList.add('active');
    event.target.classList.add('active');
}

// File upload handling
const csvFile = document.getElementById('csvFile');
const fileInfo = document.getElementById('fileInfo');

csvFile.addEventListener('change', function() {
    if (this.files.length > 0) {
        document.getElementById('fileName').textContent = this.files[0].name;
        fileInfo.style.display = 'block';
    }
});

// Drag and drop
const uploadArea = document.querySelector('.file-upload-area');

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.style.background = '#e0f2fe';
    uploadArea.style.borderColor = '#2563eb';
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.style.background = '#f0f9ff';
    uploadArea.style.borderColor = '#3b82f6';
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    const files = e.dataTransfer.files;
    if (files.length > 0 && files[0].name.endsWith('.csv')) {
        csvFile.files = files;
        const event = new Event('change', { bubbles: true });
        csvFile.dispatchEvent(event);
    } else {
        alert('Please drop a CSV file');
    }
});

function showStudents() {
    const selected = document.getElementById("classFilter").value;
    const container = document.getElementById("studentContainer");
    const grid = container.querySelector(".student-grid");
    const cards = document.querySelectorAll(".student-card");
    
    container.style.display = "block";
    let anyVisible = false;
    
    cards.forEach(card => {
        if(selected === "" || card.dataset.class === selected) {
            card.style.display = "block";
            anyVisible = true;
        } else {
            card.style.display = "none";
        }
    });
    
    // Show/hide empty message without destroying the grid
    let emptyMsg = grid.querySelector(".empty-message");
    if(!anyVisible) {
        if(!emptyMsg) {
            emptyMsg = document.createElement("div");
            emptyMsg.className = "empty empty-message";
            emptyMsg.textContent = "No students found for this class.";
            grid.appendChild(emptyMsg);
        }
        emptyMsg.style.display = "block";
    } else {
        if(emptyMsg) emptyMsg.style.display = "none";
    }
}
</script>

</body>
</html>
