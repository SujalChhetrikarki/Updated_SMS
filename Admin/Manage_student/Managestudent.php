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

// Fetch students with performance (with percentage-based calculation)
$sql = "
    SELECT s.student_id, s.name, s.email, s.class_id, c.class_name,
           IFNULL(ROUND(AVG((r.marks_obtained / e.max_marks) * 100), 2), 0) as percentage_avg,
           IFNULL(ROUND(AVG(r.average_marks), 2), 0) as avg_marks
    FROM students s
    JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN results r ON s.student_id = r.student_id
    LEFT JOIN exams e ON r.exam_id = e.exam_id
    WHERE s.name LIKE '%$search%'
    GROUP BY s.student_id, s.name, s.email, s.class_id, c.class_name
    ORDER BY percentage_avg DESC
";
$students = $conn->query($sql);
if (!$students) die("SQL Error: " . $conn->error);

// ✅ Grading Function - Converts percentage to letter grades
function getGrade($marks) {
    if ($marks >= 90) return ['grade' => 'A+', 'color' => '#10b981'];
    if ($marks >= 85) return ['grade' => 'A', 'color' => '#059669'];
    if ($marks >= 80) return ['grade' => 'A-', 'color' => '#0d9488'];
    if ($marks >= 75) return ['grade' => 'B+', 'color' => '#2563eb'];
    if ($marks >= 70) return ['grade' => 'B', 'color' => '#1e40af'];
    if ($marks >= 65) return ['grade' => 'B-', 'color' => '#1e3a8a'];
    if ($marks >= 60) return ['grade' => 'C+', 'color' => '#ea580c'];
    if ($marks >= 55) return ['grade' => 'C', 'color' => '#c2410c'];
    if ($marks >= 50) return ['grade' => 'C-', 'color' => '#b45309'];
    if ($marks >= 40) return ['grade' => 'D', 'color' => '#ea8500'];
    return ['grade' => 'F', 'color' => '#dc2626'];
}
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

.grade-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 12px;
    color: white;
    font-weight: bold;
    font-size: 13px;
}

.grading-scale {
    background: #f0f8ff;
    border: 2px solid #3b82f6;
    border-radius: 12px;
    padding: 20px;
    margin-top: 30px;
}

.grading-scale h3 {
    margin-top: 0;
    color: #1f2937;
}

.grading-scale table {
    width: 100%;
    margin-top: 15px;
}

.grading-scale table th, .grading-scale table td {
    padding: 10px;
    text-align: center;
    border: 1px solid #ddd;
}
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
                <th>Performance</th>
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
                            $percentage = $row['percentage_avg'];
                            $grade_info = getGrade($percentage);
                            if ($percentage > 0) {
                                echo "<span class='grade-badge' style='background-color: " . $grade_info['color'] . "'>";
                                echo $grade_info['grade'] . " (" . number_format($percentage, 2) . "%)</span>";
                            } else {
                                echo "<span class='grade-badge' style='background-color: #9ca3af;'>No Data</span>";
                            }
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

    <!-- Grading Scale Reference -->
    <div class="grading-scale">
        <h3>📊 Grading Scale (Based on Percentage)</h3>
        <table>
            <tr>
                <th>Percentage Range (%)</th>
                <th>Grade</th>
                <th>Percentage Range (%)</th>
                <th>Grade</th>
            </tr>
            <tr>
                <td>90-100%</td>
                <td><span class="grade-badge" style="background-color: #10b981;">A+</span></td>
                <td>65-69%</td>
                <td><span class="grade-badge" style="background-color: #1e3a8a;">B-</span></td>
            </tr>
            <tr>
                <td>85-89%</td>
                <td><span class="grade-badge" style="background-color: #059669;">A</span></td>
                <td>60-64%</td>
                <td><span class="grade-badge" style="background-color: #ea580c;">C+</span></td>
            </tr>
            <tr>
                <td>80-84%</td>
                <td><span class="grade-badge" style="background-color: #0d9488;">A-</span></td>
                <td>55-59%</td>
                <td><span class="grade-badge" style="background-color: #c2410c;">C</span></td>
            </tr>
            <tr>
                <td>75-79%</td>
                <td><span class="grade-badge" style="background-color: #2563eb;">B+</span></td>
                <td>50-54%</td>
                <td><span class="grade-badge" style="background-color: #b45309;">C-</span></td>
            </tr>
            <tr>
                <td>70-74%</td>
                <td><span class="grade-badge" style="background-color: #1e40af;">B</span></td>
                <td>40-49%</td>
                <td><span class="grade-badge" style="background-color: #ea8500;">D</span></td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td>0-39%</td>
                <td><span class="grade-badge" style="background-color: #dc2626;">F</span></td>
            </tr>
        </table>
        <p style="font-size: 12px; color: #666; margin-top: 15px; text-align: center;">
            💡 <em>Note: Performance is calculated based on percentage from all exams, works with any exam maximum marks (50, 100, 200, etc.)</em>
        </p>
    </div>
</div>

</body>
</html>
