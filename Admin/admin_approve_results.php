<?php
session_start();
include '../Database/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle approval form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve'])) {
    $result_ids = $_POST['result_id'] ?? [];
    if(!empty($result_ids)) {
        $stmt = $conn->prepare("UPDATE results SET status='Approved' WHERE result_id=?");
        foreach ($result_ids as $rid) {
            $stmt->bind_param("i", $rid);
            $stmt->execute();
        }
    }
    header("Location: admin_approve_results.php?msg=" . urlencode("✅ Selected results approved!"));
    exit;
}

// Fetch all pending results grouped by class
$sql = "SELECT r.result_id, r.marks_obtained, r.average_marks, e.exam_date, e.term,
               s.subject_name, c.class_name, st.name as student_name
        FROM results r
        JOIN exams e ON r.exam_id=e.exam_id
        JOIN subjects s ON e.subject_id=s.subject_id
        JOIN classes c ON e.class_id=c.class_id
        JOIN students st ON r.student_id=st.student_id
        WHERE r.status='Pending'
        ORDER BY c.class_name, e.term, e.exam_date ASC";

$results = $conn->query($sql);
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Approve Results</title>
<style>
/* ===== Body & Layout ===== */
body {
    margin: 0; 
    font-family: 'Inter', sans-serif; 
    background: #f0f2f5; 
    display: flex;
}

/* ===== Sidebar ===== */
.sidebar {
    width: 250px; /* Fixed width */
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

/* ===== Main Content ===== */
.main {
    margin-left: 250px; /* Match sidebar width */
    width: calc(100% - 250px);
    padding: 100px 30px 30px;
    display: flex;
    justify-content: center;
}

/* ===== Card ===== */
.card {
    width: 700px;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

/* ===== Form ===== */
form label { display: block; margin-top: 10px; font-weight: 500; }
select {
    width: 100%; 
    padding: 10px; 
    margin-top: 5px; 
    margin-bottom: 20px;
    border-radius: 6px; 
    border: 1px solid #ccc;
}
/* ===== Main Container ===== */
.container {
    margin-left: 250px;       /* Leave space for sidebar */
    padding: 40px 30px;       /* Top/bottom and left/right padding */
    width: calc(100% - 250px); /* Full width minus sidebar */
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

/* ===== Table Wrapper ===== */
.table-wrapper {
    overflow-x: auto;
}

/* ===== Responsive ===== */
@media(max-width:768px){
    .container {
        margin-left: 0;
        width: 100%;
        padding: 20px 15px;
    }
    table, select { font-size: 14px; }
}

/* ===== Table ===== */
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
th, td { padding: 12px; border: 1px solid #e5e7eb; text-align: center; }
th { background: #3b82f6; color: #fff; font-weight: 600; }
tr:hover { background: #f1f5f9; }

/* ===== Empty message ===== */
.empty { text-align: center; padding: 20px; color: #6b7280; font-size: 15px; }

/* ===== Responsive ===== */
@media(max-width:768px){
    .main { margin-left: 0; padding: 120px 15px 15px; width: 100%; }
    .sidebar { width: 100%; height: auto; position: relative; padding-top: 15px; }
    table, select { font-size: 14px; }
}
</style>

</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="../Admin/index.php">🏠 Home</a>
    <a href="./Manage_student/Managestudent.php">📚 Manage Students</a>
    <a href="./Manage_Teachers/Teachersshow.php">👨‍🏫 Manage Teachers</a>
    <a href="./classes/classes.php">🏫 Manage Classes</a>
    <a href="./subjects.php">📖 Manage Subjects</a>
    <a href="./Managebook.php">📚 Manage Books</a>
    <a href="./add_student.php">➕ Add Student</a>
    <a href="./add_teacher.php">➕ Add Teacher</a>
    <a href="./Add_exam/add_exam.php">➕ Add Exam</a>
    <a href="./admin_approve_results.php">✅ Approve Results</a>
    <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="container">
    <h2>✅ Approve Pending Results (Class-wise)</h2>
    <?php if($msg) echo "<p style='color:green;font-weight:500;'>{$msg}</p>"; ?>

    <form method="POST">
        <div class="table-wrapper">
        <table>
            <tr>
                <th>Select</th>
                <th>Student</th>
                <th>Class</th>
                <th>Subject</th>
                <th>Term</th>
                <th>Exam Date</th>
                <th>Marks</th>
                <th>Average</th>
            </tr>

            <?php if($results->num_rows==0): ?>
            <tr><td colspan="8">No pending results.</td></tr>
            <?php else: ?>
            <?php while($r=$results->fetch_assoc()): ?>
            <tr>
                <td><input type="checkbox" name="result_id[]" value="<?= $r['result_id'] ?>"></td>
                <td><?= htmlspecialchars($r['student_name']) ?></td>
                <td><?= htmlspecialchars($r['class_name']) ?></td>
                <td><?= htmlspecialchars($r['subject_name']) ?></td>
                <td><?= htmlspecialchars($r['term']) ?></td>
                <td><?= htmlspecialchars($r['exam_date']) ?></td>
                <td><?= htmlspecialchars($r['marks_obtained']) ?></td>
                <td><?= number_format($r['average_marks'],2) ?></td>
            </tr>
            <?php endwhile; ?>
            <?php endif; ?>
        </table>
        </div>
        <br>
        <button type="submit" name="approve" class="btn">Approve Selected Results</button>
    </form>
</div>

</body>
</html>
