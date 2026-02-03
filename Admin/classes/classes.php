<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

include '../../Database/db_connect.php';

// Fetch classes with teacher, student count, subject count
$sql = "
    SELECT 
        c.class_id,
        c.class_name,
        t.name AS teacher_name,
        COUNT(DISTINCT s.student_id) AS total_students,
        COUNT(DISTINCT sub.subject_id) AS total_subjects
    FROM classes c
    LEFT JOIN class_teachers ct ON c.class_id = ct.class_id
    LEFT JOIN teachers t ON ct.teacher_id = t.teacher_id
    LEFT JOIN students s ON s.class_id = c.class_id
    LEFT JOIN subjects sub ON sub.class_id = c.class_id
    GROUP BY c.class_id, c.class_name, t.name
    ORDER BY c.class_id ASC
";

$result = $conn->query($sql);
if (!$result) die("SQL Error: " . $conn->error);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Classes</title>
<style>
/* ===== Body & Layout ===== */
body { margin: 0; font-family: 'Inter', sans-serif; display: flex; background: #f0f2f5; }

/* ===== Sidebar ===== */
.sidebar {
    width: 240px;
    background: #1f2937;
    color: #fff;
    height: 100vh;
    position: fixed;
    top: 0; left: 0;
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
    margin-left: 240px;
    padding: 20px 30px;
    flex: 1;
}

/* ===== Header ===== */
.header {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    margin-bottom: 25px;
}
.header h1 {
    margin: 0;
    font-size: 22px;
    color: #111;
}

/* ===== Add Button ===== */
.btn {
    padding: 8px 14px;
    background: #3b82f6;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
}
.btn:hover { background: #2563eb; }

/* ===== Classes Table ===== */
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 6px 15px rgba(0,0,0,0.05);
}
th, td {
    padding: 12px 15px;
    text-align: center;
    border-bottom: 1px solid #e5e7eb;
}
th {
    background: #3b82f6;
    color: #fff;
    font-weight: 600;
}
tr:hover { background: #f1f5f9; }

/* ===== Action Buttons ===== */
.btn-sm {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    margin-right: 5px;
    transition: 0.3s;
}
.btn-sm.edit { background: #facc15; color: #111; }
.btn-sm.edit:hover { background: #eab308; }
.btn-sm.delete { background: #ef4444; color: #fff; }
.btn-sm.delete:hover { background: #dc2626; }
.btn-sm.view { background: #3b82f6; color: #fff; }
.btn-sm.view:hover { background: #2563eb; }

/* ===== Responsive ===== */
@media(max-width:768px){
    .main { margin-left: 0; padding: 15px; }
    table, .header { font-size: 14px; }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="../index.php">🏠 Home</a>
    <a href="../Manage_student/Managestudent.php">📚 Manage Students</a>
    <a href="../Manage_Teachers/Teachersshow.php">👨‍🏫 Manage Teachers</a>
    <a href="classes.php">🏫 Manage Classes</a>
    <a href="../subjects.php">📖 Manage Subjects</a>
    <a href="../Managebook.php">📚 Manage Books</a>
    <a href="../add_student.php">➕ Add Student</a>
    <a href="../add_teacher.php">➕ Add Teacher</a>
    <a href="../Add_exam/add_exam.php">➕ Add Exam</a>
    <a href="../admin_approve_results.php">✅ Approve Results</a>
    <a href="../logout.php" class="logout">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="main">
    <div class="header">
        <h1>📚 Manage Classes</h1>
        <a class="btn" href="add_class.php">➕ Add New Class</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Class Name</th>
                <th>Teacher</th>
                <th>Total Students</th>
                <th>Total Subjects</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['class_id']; ?></td>
                    <td><?= htmlspecialchars($row['class_name']); ?></td>
                    <td><?= $row['teacher_name'] ?? 'Unassigned'; ?></td>
                    <td><?= $row['total_students']; ?></td>
                    <td><?= $row['total_subjects']; ?></td>
                    <td>
                        <a class="btn-sm edit" href="edit_class.php?id=<?= $row['class_id']; ?>">✏ Edit</a>
                        <a class="btn-sm delete" href="delete_class.php?id=<?= $row['class_id']; ?>" onclick="return confirm('Delete this class?')">🗑 Delete</a>
                        <a class="btn-sm view" href="view_students.php?id=<?= $row['class_id']; ?>">👨‍🎓 Students</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No classes found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
<?php $conn->close(); ?>
