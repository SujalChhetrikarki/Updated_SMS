<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

include '../../Database/db_connect.php';

// Get search input
$search = "";
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

// Fetch students with performance
$sql = "
    SELECT s.student_id, s.name, s.email, s.class_id, c.class_name,
           IFNULL(ROUND(AVG(r.average_marks),2), 0) as avg_marks
    FROM students s
    JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN results r ON s.student_id = r.student_id
    WHERE s.name LIKE '%$search%'
    GROUP BY s.student_id, s.name, s.email, s.class_id, c.class_name
    ORDER BY avg_marks DESC
";
$students = $conn->query($sql);
if (!$students) die("SQL Error: " . $conn->error);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Students</title>
<style>
/* ===== Reset & Body ===== */
body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    display: flex;
    background: #f0f2f5;
    min-height: 100vh;
    color: #333;
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
    background: #fff;
    padding: 20px 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
}
.header h1 {
    margin: 0;
    font-size: 22px;
    color: #111;
}

/* ===== Search Box ===== */
.search-box {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.search-box input {
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
    flex: 1;
}
.search-box button, .search-box a {
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
}
.search-box button { background: #3b82f6; color: #fff; }
.search-box a { background: #6b7280; color: #fff; }
.search-box button:hover { background: #2563eb; }
.search-box a:hover { background: #4b5563; }

/* ===== Students Table ===== */
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
tr:hover { background: #f1f5f9; border-radius: 12px; }

/* ===== Buttons ===== */
.btn {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    display: inline-block;
    transition: 0.3s;
}
.btn.edit { background: #facc15; color: #111; }
.btn.delete { background: #ef4444; color: #fff; }
.btn.edit:hover { background: #eab308; }
.btn.delete:hover { background: #dc2626; }

/* ===== Responsive ===== */
@media(max-width:768px){
    .main { margin-left: 0; padding: 15px; }
    .sidebar { width: 100%; height: auto; position: relative; }
    table, .header { font-size: 14px; }
}
</style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="../index.php">🏠 Home</a>
    <a href="../Manage_student/Managestudent.php">📚 Manage Students</a>
    <a href="../Manage_Teachers/Teachersshow.php">👨‍🏫 Manage Teachers</a>
    <a href="../classes/classes.php">🏫 Manage Classes</a>
    <a href="../subjects.php">📖 Manage Subjects</a>
    <a href="../Managebook.php">📚 Manage Books</a>
    <a href="../add_student.php">➕ Add Student</a>
    <a href="../add_teacher.php">➕ Add Teacher</a>
    <a href="../Add_exam/add_exam.php">➕ Add Exam</a>
    <a href="../admin_approve_results.php">✅ Approve Results</a>
    <a href="../logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main">
    <div class="header">
        <h1>👨‍🎓 Manage Students</h1>

        <!-- Search Form -->
        <form method="get" action="" class="search-box">
            <input type="text" name="search" placeholder="🔍 Search by Name" value="<?= htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
            <a href="Managestudent.php">Reset</a>
        </form>
    </div>

    <!-- Students Table -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Class</th>
                <th>Performance (Avg Marks)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($students->num_rows > 0): ?>
                <?php while ($row = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['student_id']; ?></td>
                        <td><?= htmlspecialchars($row['name']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td><?= htmlspecialchars($row['class_name']); ?></td>
                        <td>
                            <?php
                            $performance = $row['avg_marks'];
                            if ($performance >= 75) echo "🌟 Excellent ($performance%)";
                            elseif ($performance >= 50) echo "👍 Good ($performance%)";
                            elseif ($performance > 0) echo "⚠ Needs Improvement ($performance%)";
                            else echo "❌ No Results";
                            ?>
                        </td>
                        <td>
                            <a href="edit_student.php?student_id=<?= $row['student_id']; ?>" class="btn edit">✏ Edit</a>
                            <a href="delete_student.php?student_id=<?= urlencode($row['student_id']); ?>" class="btn delete"
                               onclick="return confirm('Are you sure you want to delete this student?');">🗑 Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">No matching students found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
