<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include '../../Database/db_connect.php';

// Fetch all teachers
$sql = "SELECT * FROM teachers ORDER BY name ASC";
$result = $conn->query($sql);
if (!$result) die("Error fetching teachers: " . $conn->error);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Teachers</title>
<style>
/* ===== Body & Reset ===== */
body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    display: flex;
    background: #f0f2f5;
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

/* ===== Teachers Table ===== */
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
.action-btn {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    margin-right: 5px;
    transition: 0.3s;
}
.edit-btn { background: #facc15; color: #111; }
.edit-btn:hover { background: #eab308; }
.delete-btn { background: #ef4444; color: #fff; }
.delete-btn:hover { background: #dc2626; }

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
    <a href="./Teachersshow.php">👨‍🏫 Manage Teachers</a>
    <a href="../Classes/classes.php">🏫 Manage Classes</a>
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
        <h1>👨‍🏫 Manage Teachers</h1>
        <a href="../add_teacher.php" class="btn">➕ Add New Teacher</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Teacher ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Specialization</th>
                <th>Class Teacher?</th>
                <th>Assigned Classes</th>
                <th>Assigned Subjects</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($teacher = $result->fetch_assoc()): ?>
                <?php
                $tid = $teacher['teacher_id'];
                $is_class_teacher = $teacher['is_class_teacher'] ? "✅" : "❌";

                $sql_class_subjects = "SELECT c.class_name, s.subject_name
                                       FROM class_subject_teachers cst
                                       JOIN classes c ON cst.class_id = c.class_id
                                       JOIN subjects s ON cst.subject_id = s.subject_id
                                       WHERE cst.teacher_id = ?";
                $stmt_cs = $conn->prepare($sql_class_subjects);
                $stmt_cs->bind_param("s", $tid);
                $stmt_cs->execute();
                $res_cs = $stmt_cs->get_result();

                $classes_arr = [];
                $subjects_arr = [];
                while ($row = $res_cs->fetch_assoc()) {
                    if (!in_array($row['class_name'], $classes_arr)) $classes_arr[] = $row['class_name'];
                    if (!in_array($row['subject_name'], $subjects_arr)) $subjects_arr[] = $row['subject_name'];
                }
                $classes_str = !empty($classes_arr) ? implode(", ", $classes_arr) : "-";
                $subjects_str = !empty($subjects_arr) ? implode(", ", $subjects_arr) : "-";
                ?>
                <tr>
                    <td><?= htmlspecialchars($teacher['teacher_id']) ?></td>
                    <td><?= htmlspecialchars($teacher['name']) ?></td>
                    <td><?= htmlspecialchars($teacher['email']) ?></td>
                    <td><?= htmlspecialchars($teacher['specialization']) ?></td>
                    <td><?= $is_class_teacher ?></td>
                    <td><?= htmlspecialchars($classes_str) ?></td>
                    <td><?= htmlspecialchars($subjects_str) ?></td>
                    <td>
                        <a href="edit_teacher.php?teacher_id=<?= urlencode($tid) ?>" class="action-btn edit-btn">✏ Edit</a>
                        <a href="delete_teacher.php?teacher_id=<?= urlencode($tid) ?>" class="action-btn delete-btn"
                           onclick="return confirm('Are you sure you want to delete this teacher?');">🗑 Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
