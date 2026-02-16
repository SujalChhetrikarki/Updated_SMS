<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

include '../Database/db_connect.php';

// ✅ Check DB connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// ✅ Fetch Notices
$notice_sql = "
    SELECT title, message, created_at 
    FROM notices 
    WHERE target IN ('students', 'both')
    ORDER BY created_at DESC 
    LIMIT 5
";
$notices = $conn->query($notice_sql);

// ✅ Fetch student info
$sql = "SELECT s.student_id, s.name, s.email, s.date_of_birth, s.gender, c.class_name, t.name AS teacher_name
        FROM students s
        LEFT JOIN classes c ON s.class_id = c.class_id
        LEFT JOIN class_teachers ct ON c.class_id = ct.class_id
        LEFT JOIN teachers t ON ct.teacher_id = t.teacher_id
        WHERE s.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['student_id']);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// ✅ Fetch attendance summary
$attendance_sql = "SELECT date, status FROM attendance WHERE student_id = ? ORDER BY date ASC";
$stmt2 = $conn->prepare($attendance_sql);
$attendance_data = [];
if ($stmt2) {
    $stmt2->bind_param("s", $_SESSION['student_id']);
    $stmt2->execute();
    $res = $stmt2->get_result();
    while ($row = $res->fetch_assoc()) {
        $attendance_data[] = $row;
    }
    $stmt2->close();
}

$present = $absent = $late = 0;
foreach ($attendance_data as $a) {
    switch (strtolower($a['status'])) {
        case 'present': $present++; break;
        case 'absent': $absent++; break;
        case 'late': $late++; break;
    }
}

// ✅ Grading Function - Converts marks to letter grades
function getGrade($marks) {
    if ($marks >= 90) return ['grade' => 'A+', 'color' => '#10b981'];      // A+ (90-100)
    if ($marks >= 85) return ['grade' => 'A', 'color' => '#059669'];       // A  (85-89)
    if ($marks >= 80) return ['grade' => 'A-', 'color' => '#0d9488'];      // A- (80-84)
    if ($marks >= 75) return ['grade' => 'B+', 'color' => '#2563eb'];      // B+ (75-79)
    if ($marks >= 70) return ['grade' => 'B', 'color' => '#1e40af'];       // B  (70-74)
    if ($marks >= 65) return ['grade' => 'B-', 'color' => '#1e3a8a'];      // B- (65-69)
    if ($marks >= 60) return ['grade' => 'C+', 'color' => '#ea580c'];      // C+ (60-64)
    if ($marks >= 55) return ['grade' => 'C', 'color' => '#c2410c'];       // C  (55-59)
    if ($marks >= 50) return ['grade' => 'C-', 'color' => '#b45309'];      // C- (50-54)
    if ($marks >= 40) return ['grade' => 'D', 'color' => '#ea8500'];       // D  (40-49)
    return ['grade' => 'F', 'color' => '#dc2626'];                         // F  (0-39)
}

// ✅ Fetch student's overall marks and grade
$marks_sql = "SELECT IFNULL(ROUND(AVG(r.marks_obtained), 2), 0) AS avg_marks
              FROM results r
              WHERE r.student_id = ? AND r.status = 'Approved'";
$stmt_marks = $conn->prepare($marks_sql);
$stmt_marks->bind_param("s", $_SESSION['student_id']);
$stmt_marks->execute();
$marks_result = $stmt_marks->get_result();
$marks_data = $marks_result->fetch_assoc();
$avg_marks = $marks_data['avg_marks'];
$grade_info = getGrade($avg_marks);
$stmt_marks->close();

// ✅ Fetch student's rank in their class
$class_id = $student['class_id'] ?? null;
$student_rank = 0;
$total_class_students = 0;

if ($class_id) {
    $rank_sql = "
        SELECT s.student_id, ROUND(AVG(r.marks_obtained), 2) AS avg_marks
        FROM students s
        LEFT JOIN results r ON s.student_id = r.student_id AND r.status = 'Approved'
        WHERE s.class_id = ?
        GROUP BY s.student_id
        ORDER BY avg_marks DESC";
    
    $stmt_rank = $conn->prepare($rank_sql);
    $stmt_rank->bind_param("i", $class_id);
    $stmt_rank->execute();
    $rank_result = $stmt_rank->get_result();
    $total_class_students = $rank_result->num_rows;
    
    $rank = 1;
    while ($rank_row = $rank_result->fetch_assoc()) {
        if ($rank_row['student_id'] == $_SESSION['student_id']) {
            $student_rank = $rank;
            break;
        }
        $rank++;
    }
    $stmt_rank->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f8f9fc;
            color: #333;
        }
        .sidebar {
            width: 220px;
            background: #00bfff;
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            padding: 20px 15px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar h2 {
            color: #fff;
            text-align: center;
            margin-bottom: 30px;
        }
        .sidebar a {
            display: block;
            color: #fff;
            padding: 12px;
            margin: 8px 0;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.2);
        }
        .main {
            margin-left: 240px;
            padding: 30px;
        }
        .header {
            background: #00bfff;
            color: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .card h2 {
            margin-top: 0;
            color: #007bff;
            font-size: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        th {
            background: #f1f5fb;
            color: #333;
        }
        .notice-card {
            background: #fdfdfd;
            border-left: 4px solid #007bff;
            padding: 12px 15px;
            margin-bottom: 12px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .notice-card h3 {
            margin: 0;
            font-size: 16px;
            color: #007bff;
        }
        .notice-card p {
            margin: 5px 0;
            color: #555;
        }
        .notice-card small {
            color: #777;
        }
        .chart-container {
            width: 300px;
            height: 300px;
            margin: 0 auto;
        }
        .grade-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            color: white;
            font-weight: bold;
            font-size: 18px;
            text-align: center;
            min-width: 50px;
        }
        .rank-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            background: #3b82f6;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>📚 Dashboard</h2>
        <a href="student_dashboard.php">🏠 Home</a>
        <a href="attendance.php">📅 Attendance</a>
        <a href="results.php">📊 Results</a>
        <a href="profile.php">👤 Profile</a>
        <a href="change_password.php">🔑 Change Password</a>
        <a href="logout.php" class="logout">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="header">🎓 Welcome, <?php echo htmlspecialchars($student['name']); ?></div>

        <div class="card">
            <h2>📌 Student Information</h2>
            <table>
                <tr><th>Student ID</th><td><?php echo htmlspecialchars($student['student_id']); ?></td></tr>
                <tr><th>Name</th><td><?php echo htmlspecialchars($student['name']); ?></td></tr>
                <tr><th>Email</th><td><?php echo htmlspecialchars($student['email']); ?></td></tr>
                <tr><th>Date of Birth</th><td><?php echo htmlspecialchars($student['date_of_birth']); ?></td></tr>
                <tr><th>Gender</th><td><?php echo htmlspecialchars($student['gender']); ?></td></tr>
                <tr><th>Class</th><td><?php echo htmlspecialchars($student['class_name']); ?></td></tr>
                <tr><th>Class Teacher</th><td><?php echo !empty($student['teacher_name']) ? htmlspecialchars($student['teacher_name']) : 'Not Assigned'; ?></td></tr>
                <tr><th>Overall Marks</th><td><?php echo number_format($avg_marks, 2); ?>/100 - <span class="grade-badge" style="background-color: <?php echo $grade_info['color']; ?>;"><?php echo $grade_info['grade']; ?></span></td></tr>
                <tr><th>Class Ranking</th><td><span class="rank-badge"><?php echo $student_rank > 0 ? "Rank #" . $student_rank . " out of " . $total_class_students : "No ranking data"; ?></span></td></tr>
            </table>
        </div>

        <div class="card">
            <h2>📢 Latest Notices</h2>
            <?php
            if ($notices && $notices->num_rows > 0) {
                while ($notice = $notices->fetch_assoc()) {
                    echo "<div class='notice-card'>";
                    echo "<h3>" . htmlspecialchars($notice['title']) . "</h3>";
                    echo "<p>" . nl2br(htmlspecialchars($notice['message'])) . "</p>";
                    echo "<small>🕒 " . date('d M Y, h:i A', strtotime($notice['created_at'])) . "</small>";
                    echo "</div>";
                }
            } else {
                echo "<p>No new notices.</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>
