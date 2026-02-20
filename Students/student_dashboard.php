<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

include '../Database/db_connect.php';
include '../includes/ranking_algorithms.php';

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

// ✅ Get selected term from query parameter
$selected_term = $_GET['term'] ?? 'all';

// ✅ Fetch available terms for student
$terms_sql = "SELECT DISTINCT e.term FROM results r 
              JOIN exams e ON r.exam_id = e.exam_id 
              WHERE r.student_id = ? AND r.status = 'Approved' 
              ORDER BY e.term";
$stmt_terms = $conn->prepare($terms_sql);
$stmt_terms->bind_param("s", $_SESSION['student_id']);
$stmt_terms->execute();
$terms_result = $stmt_terms->get_result();
$available_terms = [];
while ($term_row = $terms_result->fetch_assoc()) {
    $available_terms[] = $term_row['term'];
}
$stmt_terms->close();

// ✅ Fetch student's marks and grade (with optional term filter) - Works with any max marks!
$marks_sql = "SELECT 
              IFNULL(ROUND(AVG((r.marks_obtained / e.max_marks) * 100), 2), 0) AS percentage_avg,
              IFNULL(ROUND(AVG(r.marks_obtained), 2), 0) AS avg_marks,
              COUNT(*) as result_count,
              GROUP_CONCAT(DISTINCT e.max_marks) as max_marks_list
              FROM results r
              JOIN exams e ON r.exam_id = e.exam_id
              WHERE r.student_id = ? AND r.status = 'Approved'";
if ($selected_term !== 'all') {
    $marks_sql .= " AND e.term = ?";
}
$stmt_marks = $conn->prepare($marks_sql);
if ($selected_term !== 'all') {
    $stmt_marks->bind_param("ss", $_SESSION['student_id'], $selected_term);
} else {
    $stmt_marks->bind_param("s", $_SESSION['student_id']);
}
$stmt_marks->execute();
$marks_result = $stmt_marks->get_result();
$marks_data = $marks_result->fetch_assoc();
$percentage_avg = $marks_data['percentage_avg'];
$avg_marks = $marks_data['avg_marks'];
$result_count = $marks_data['result_count'];
$max_marks_list = $marks_data['max_marks_list'];
// Use percentage for grading (0-100 scale)
$grade_info = getGrade($percentage_avg);
$stmt_marks->close();

// ✅ Fetch student's rank in their class using sorting algorithms
$class_id = $student['class_id'] ?? null;
$student_rank = 0;
$total_class_students = 0;
$algorithm_used = 'usort'; // Can be: usort, quicksort, mergesort, heapsort, countingsort

if ($class_id) {
    // Fetch all students in class with their average marks
    $rank_sql = "
        SELECT s.student_id, s.name, ROUND(AVG(r.marks_obtained), 2) AS avg_marks
        FROM students s
        LEFT JOIN results r ON s.student_id = r.student_id AND r.status = 'Approved'
        WHERE s.class_id = ?
        GROUP BY s.student_id";
    
    $stmt_rank = $conn->prepare($rank_sql);
    $stmt_rank->bind_param("i", $class_id);
    $stmt_rank->execute();
    $rank_result = $stmt_rank->get_result();
    
    // Fetch all students data
    $students_data = [];
    while ($row = $rank_result->fetch_assoc()) {
        $students_data[] = $row;
    }
    $stmt_rank->close();
    
    // Use ranking algorithm (can be changed: quicksort, mergesort, heapsort, countingsort)
    if (!empty($students_data)) {
        $ranking_result = StudentRankingAlgorithms::getRankWithMetrics(
            $students_data, 
            $_SESSION['student_id'], 
            $algorithm_used
        );
        
        $student_rank = $ranking_result['rank'];
        $total_class_students = $ranking_result['total'];
    }
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-left: 4px solid #667eea;
            padding: 18px 20px;
            margin-bottom: 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            color: white;
            position: relative;
            overflow: hidden;
        }
        .notice-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(20px, -20px);
        }
        .notice-card h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            z-index: 1;
        }
        .notice-card h3::before {
            content: '📢';
            font-size: 22px;
        }
        .notice-card p {
            margin: 10px 0;
            color: rgba(255, 255, 255, 0.95);
            line-height: 1.6;
            font-size: 15px;
            position: relative;
            z-index: 1;
        }
        .notice-card small {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            display: block;
            margin-top: 10px;
            position: relative;
            z-index: 1;
        }
        .notice-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(0,0,0,0.1);
        }
        .notice-header h2 {
            margin: 0;
            font-size: 22px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
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
        
        /* Vibrant Notice Animations */
        @keyframes slideInRight {
            from {
                transform: translateX(360px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 6px 20px rgba(0, 191, 255, 0.25), inset 0 0 20px rgba(0, 255, 255, 0.1);
            }
            50% {
                box-shadow: 0 8px 28px rgba(0, 191, 255, 0.4), inset 0 0 30px rgba(0, 255, 255, 0.2);
            }
        }
        @keyframes float-gentle {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-4px);
            }
        }
        .notice-vibrant {
            animation: slideInRight 0.6s ease-out, pulse-glow 2.5s ease-in-out infinite, float-gentle 3s ease-in-out infinite;
        }
        .notice-vibrant:hover {
            box-shadow: 0 10px 32px rgba(0, 191, 255, 0.35), inset 0 0 40px rgba(0, 255, 255, 0.25) !important;
            transform: translateY(-6px) !important;
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

        <!-- Notices - Corner Alert (Top Right) - Vibrant -->
<?php if ($notices && $notices->num_rows > 0): ?>

    <?php 
    $topOffset = 20; // Starting top position
    $gap = 140;      // Space between notices (adjust if needed)
    ?>

    <?php while ($notice = $notices->fetch_assoc()): ?>
        
        <div class="notice-vibrant" 
             style="position: fixed; 
                    top: <?= $topOffset ?>px; 
                    right: 20px; 
                    width: 320px; 
                    background: linear-gradient(135deg, #00bfff 0%, #0095cc 100%);
                    border-radius: 12px; 
                    padding: 16px 18px; 
                    box-shadow: 0 6px 20px rgba(0, 191, 255, 0.25), 
                                inset 0 0 20px rgba(0, 255, 255, 0.1); 
                    border-right: 4px solid #00ffff; 
                    border-top: 2px solid #00ffff; 
                    z-index: 999; 
                    display: flex; 
                    align-items: flex-start; 
                    gap: 12px; 
                    backdrop-filter: blur(10px);">

            <span style="font-size: 26px; flex-shrink: 0; margin-top: 2px; animation: bounce 2s infinite;">📢</span>

            <div style="flex: 1; min-width: 0;">
                <h4 style="margin: 0 0 8px 0; color: #fff; font-size: 16px; font-weight: 800; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    <?= htmlspecialchars($notice['title']); ?>
                </h4>

                <p style="margin: 0; color: rgba(255,255,255,0.95); font-size: 13px; line-height: 1.5; font-weight: 500;">
                    <?= substr(htmlspecialchars($notice['message']), 0, 90) . (strlen($notice['message']) > 90 ? '...' : ''); ?>
                </p>

                <small style="color: rgba(255,255,255,0.8); font-size: 12px; margin-top: 6px; display: block; font-weight: 600;">
                    🕒 <?= date('d M Y, h:i', strtotime($notice['created_at'])); ?>
                </small>
            </div>

            <div style="width: 3px; height: 100%; background: linear-gradient(to bottom, #00ffff, #0095cc, transparent); border-radius: 2px; opacity: 0.7;"></div>

        </div>

        <?php $topOffset += $gap; ?>

    <?php endwhile; ?>

    <style>
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
    </style>

<?php endif; ?>
        <!-- Term Filter -->
        <?php if (!empty($available_terms)): ?>
        <div class="card" style="margin-bottom: 15px;">
            <form method="GET" style="display: flex; align-items: center; gap: 10px;">
                <label style="font-weight: bold;">📚 Filter by Term:</label>
                <select name="term" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ddd; cursor: pointer;">
                    <option value="all" <?php echo $selected_term === 'all' ? 'selected' : ''; ?>>All Terms</option>
                    <?php foreach ($available_terms as $term_opt): ?>
                        <option value="<?php echo htmlspecialchars($term_opt); ?>" <?php echo $selected_term === $term_opt ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($term_opt); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>📌 Student Information
            <?php if ($selected_term !== 'all'): ?>
                <span style="font-size: 14px; color: #666;">(Viewing: <?php echo htmlspecialchars($selected_term); ?>)</span>
            <?php else: ?>
                <span style="font-size: 14px; color: #666;">(Overall)</span>
            <?php endif; ?>
            </h2>
            <table>
                <tr><th>Student ID</th><td><?php echo htmlspecialchars($student['student_id']); ?></td></tr>
                <tr><th>Name</th><td><?php echo htmlspecialchars($student['name']); ?></td></tr>
                <tr><th>Email</th><td><?php echo htmlspecialchars($student['email']); ?></td></tr>
                <tr><th>Date of Birth</th><td><?php echo htmlspecialchars($student['date_of_birth']); ?></td></tr>
                <tr><th>Gender</th><td><?php echo htmlspecialchars($student['gender']); ?></td></tr>
                <tr><th>Class</th><td><?php echo htmlspecialchars($student['class_name']); ?></td></tr>
                <tr><th>Class Teacher</th><td><?php echo !empty($student['teacher_name']) ? htmlspecialchars($student['teacher_name']) : 'Not Assigned'; ?></td></tr>
                <?php if ($result_count > 0): ?>
                <tr><th>Marks <?php echo $selected_term !== 'all' ? '(' . htmlspecialchars($selected_term) . ')' : '(Overall)'; ?></th><td><?php echo number_format($avg_marks, 2); ?> marks | <strong><?php echo number_format($percentage_avg, 2); ?>%</strong> - <span class="grade-badge" style="background-color: <?php echo $grade_info['color']; ?>;"><?php echo $grade_info['grade']; ?></span></td></tr>
                <?php endif; ?>
                <tr><th>Class Ranking</th><td><span class="rank-badge"><?php echo $student_rank > 0 ? "Rank #" . $student_rank . " out of " . $total_class_students : "No ranking data"; ?></span></td></tr>
            </table>
        </div>

        <div class="card">
            <h2>📊 Grading Scale (Based on Percentage)</h2>
            <table style="width: 100%; text-align: center;">
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
            <p style="font-size: 12px; color: #666; margin-top: 10px; text-align: center;">
                💡 <em>Note: All grades are calculated based on percentage, which works with any exam maximum marks (50, 100, 200, etc.)</em>
            </p>
        </div>
    </div>
</body>
</html>
