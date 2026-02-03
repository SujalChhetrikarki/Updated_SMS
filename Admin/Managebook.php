<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include '../Database/db_connect.php';

/* =========================
   FETCH CLASSES
========================= */
$classes = $conn->query("SELECT class_id, class_name FROM classes ORDER BY class_name");

/* =========================
   FETCH SUBJECTS BY CLASS
========================= */
$subjects = null;
$selected_class = '';

if (isset($_GET['class_id']) && $_GET['class_id'] !== '') {
    $selected_class = intval($_GET['class_id']);

    $stmt = $conn->prepare("
        SELECT subject_id, subject_name
        FROM subjects
        WHERE class_id = ?
        ORDER BY subject_name
    ");
    $stmt->bind_param("i", $selected_class);
    $stmt->execute();
    $subjects = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Class-wise Subjects</title>
<style>
/* ===== Body & Layout ===== */
body {
    margin: 0; font-family: 'Inter', sans-serif; background: #f0f2f5; display: flex;
}

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
.header {
    position: fixed; top: 0; left: 240px; right: 0;
    height: 80px; background: #3b82f6; color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 22px; font-weight: 500; z-index: 10;
}

/* ===== Main Content ===== */
.main {
    margin-left: 240px; width: calc(100% - 240px); padding: 100px 30px 30px;
    display: flex; justify-content: center;
}

/* ===== Card ===== */
.card {
    width: 700px; background: #fff; padding: 30px; border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

/* ===== Form ===== */
form label { display: block; margin-top: 10px; font-weight: 500; }
select {
    width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 20px;
    border-radius: 6px; border: 1px solid #ccc;
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
    .main { margin-left: 0; padding: 120px 15px 15px; }
    table, select { font-size: 14px; }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="index.php">🏠 Home</a>
    <a href="../Admin/Manage_student/Managestudent.php">📚 Manage Students</a>
    <a href="./Manage_Teachers/Teachersshow.php">👨‍🏫 Manage Teachers</a>
    <a href="./classes/classes.php">🏫 Manage Classes</a>
    <a href="subjects.php">📖 Manage Subjects</a>
    <a href="Managebook.php">📚 Manage Books</a>
    <a href="add_student.php">➕ Add Student</a>
    <a href="add_teacher.php">➕ Add Teacher</a>
    <a href="./Add_exam/add_exam.php">➕ Add Exam</a>
    <a href="admin_approve_results.php">✅ Approve Results</a>
    <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="main">
<div class="card">
    <h2>📖 Class-wise Subjects</h2>

    <!-- Class Selection -->
    <form method="GET">
        <label>Select Class</label>
        <select name="class_id" onchange="this.form.submit()">
            <option value="">-- Choose Class --</option>
            <?php while ($c = $classes->fetch_assoc()): ?>
                <option value="<?= $c['class_id']; ?>"
                    <?= ($selected_class == $c['class_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['class_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>

    <!-- Subject Table -->
    <?php if ($subjects !== null): ?>
        <?php if ($subjects->num_rows > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Subject Name</th>
                </tr>
                <?php while ($s = $subjects->fetch_assoc()): ?>
                    <tr>
                        <td><?= $s['subject_id']; ?></td>
                        <td><?= htmlspecialchars($s['subject_name']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <div class="empty">No subjects assigned to this class.</div>
        <?php endif; ?>
    <?php endif; ?>

</div>
</div>

</body>
</html>
