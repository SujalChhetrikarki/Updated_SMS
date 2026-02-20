<?php
session_start();
if(!isset($_SESSION['student_id'])) { 
    header("Location: student_login.php"); 
    exit; 
}
include '../Database/db_connect.php';

$student_id = $_SESSION['student_id'];

// ✅ Fetch student info
$stmt = $conn->prepare("SELECT s.name, s.class_id, c.class_name 
                        FROM students s 
                        JOIN classes c ON s.class_id=c.class_id 
                        WHERE s.student_id=?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$class_id = $student['class_id'];

// ✅ Get selected term from dropdown
$selected_term = $_GET['term'] ?? '';

// ✅ Fetch approved results of this student, optionally filter by term
$query = "
SELECT r.marks_obtained, r.average_marks, e.exam_date, e.term, sub.subject_name, e.max_marks
FROM results r
JOIN exams e ON r.exam_id=e.exam_id
JOIN subjects sub ON e.subject_id=sub.subject_id
WHERE r.student_id=? AND r.status='Approved'
";
if($selected_term != '') {
    $query .= " AND e.term = ?";
}
$query .= " ORDER BY e.term, e.exam_date";

if($selected_term != '') {
    $stmt_res = $conn->prepare($query);
    $stmt_res->bind_param("ss", $student_id, $selected_term);
} else {
    $stmt_res = $conn->prepare($query);
    $stmt_res->bind_param("s", $student_id);
}
$stmt_res->execute();
$results = $stmt_res->get_result();

// ✅ Calculate student overall average (based on percentage)
$total_percentage = 0;
$total_subjects = 0;
$rows = [];
while($r = $results->fetch_assoc()) {
    $percentage = ($r['max_marks'] > 0) ? (($r['marks_obtained'] / $r['max_marks']) * 100) : 0;
    $r['percentage'] = $percentage;
    $rows[] = $r;
    $total_percentage += $percentage;
    $total_subjects++;
}
$overall_avg = ($total_subjects > 0) ? ($total_percentage / $total_subjects) : 0;

// ✅ Fetch all students in same class with averages
$sql_class = "
SELECT s.student_id, 
       IFNULL(ROUND(AVG(r.marks_obtained),2),0) as avg_marks
FROM students s
LEFT JOIN results r ON s.student_id=r.student_id AND r.status='Approved'
WHERE s.class_id=?
GROUP BY s.student_id
ORDER BY avg_marks DESC";
$stmt_class = $conn->prepare($sql_class);
$stmt_class->bind_param("s", $class_id);
$stmt_class->execute();
$class_results = $stmt_class->get_result();

$rank = 0;
$position = 0;
$total_students = $class_results->num_rows;

// ✅ Assign rank (simple ranking)
while($row = $class_results->fetch_assoc()) {
    $rank++;
    if($row['student_id'] == $student_id) {
        $position = $rank;
        break;
    }
}

// ✅ Fetch distinct terms for this student
$stmt_terms = $conn->prepare("
    SELECT DISTINCT e.term 
    FROM results r
    JOIN exams e ON r.exam_id = e.exam_id
    WHERE r.student_id = ? AND r.status='Approved'
    ORDER BY e.term
");
$stmt_terms->bind_param("s", $student_id);
$stmt_terms->execute();
$terms_result = $stmt_terms->get_result();

$terms = [];
while($t = $terms_result->fetch_assoc()){
    $terms[] = $t['term'];
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

// ✅ Get overall grade
$overall_grade = getGrade($overall_avg);
$stmt_terms->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Report Card - <?= htmlspecialchars($student['name']) ?> - Dignity Academy</title>
<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #eef2f7;
    padding: 20px;
    margin: 0;
}

.report-card {
    max-width: 900px;
    margin: 40px auto;
    background: #fff;
    border: 3px solid #00bfff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.25);
}

.school-header {
    text-align: center;
    border-bottom: 3px solid #00bfff;
    padding-bottom: 15px;
    margin-bottom: 25px;
}

.school-header h1 {
    margin: 0;
    font-size: 36px;
    font-weight: bold;
    color: #00bfff;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.school-header p {
    margin: 5px 0;
    font-size: 15px;
    color: #555;
}

.student-info {
    margin-bottom: 25px;
    font-size: 17px;
    line-height: 1.6;
}

.student-info strong {
    color: #00bfff;
}

h2 {
    text-align: center;
    color: #2c3e50;
    margin: 20px 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

th, td {
    border: 1px solid #333;
    padding: 12px;
    text-align: center;
    font-size: 14px;
}

th {
    background: #00bfff;
    color: #fff;
    font-size: 15px;
}

tfoot td {
    font-weight: bold;
    background: #ecf0f1;
}

.footer {
    display: flex;
    justify-content: space-between;
    margin-top: 50px;
    font-size: 15px;
}

.signature {
    text-align: center;
    width: 200px;
}

.signature p {
    margin: 60px 0 5px;
}

.print-btn {
    display: block;
    text-align: center;
    margin: 20px auto;
}

.print-btn button {
    background: #00bfff;
    color: #fff;
    border: none;
    padding: 12px 25px;
    font-size: 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}

.print-btn button:hover {
    background: #1a252f;
}

.sidebar {
    width: 220px;
    background: #00bfff;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
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

.grade-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    color: white;
    font-weight: bold;
}

.grading-scale-table {
    margin-top: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

/* Print Styles */
@media print {
    .sidebar,
    .print-btn,
    form,
    .date-form {
        display: none !important;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .report-card {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 25px !important;
        border: 3px solid #000 !important;
        box-shadow: none !important;
        border-radius: 0;
    }

    .school-header {
        page-break-after: avoid;
        border-bottom-width: 3px !important;
    }

    .student-info {
        page-break-after: avoid;
    }

    h2, h3 {
        page-break-after: avoid;
    }

    table {
        page-break-inside: avoid;
        border-collapse: collapse !important;
        width: 100% !important;
    }

    table th, table td {
        border: 1px solid #000 !important;
        padding: 10px !important;
        background-color: inherit !important;
    }

    table th {
        background-color: #00bfff !important;
        color: #fff !important;
    }

    .grading-scale-table {
        page-break-before: always;
        background: #fff !important;
        border: 3px solid #000 !important;
        border-radius: 0;
        margin: 0 !important;
        padding: 20px !important;
    }

    .footer {
        margin-top: 40px;
        page-break-after: avoid;
    }

    @page {
        size: A4;
        margin: 12mm;
    }
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

<div class="report-card">
    <div class="school-header">
        <h1>Dignity Academy</h1>
        <p>Excellence in Education | Kathmandu, Nepal</p>
    </div>

    <!-- Term Filter Dropdown -->
    <form method="GET" class="date-form" style="margin-bottom: 15px;">
        <input type="hidden" name="class_id" value="<?= htmlspecialchars($class_id) ?>">
        <label><strong>Select Term:</strong></label>
        <select name="term" onchange="this.form.submit()">
            <option value="">All Terms</option>
            <?php foreach($terms as $term_option): ?>
                <option value="<?= htmlspecialchars($term_option) ?>" <?= ($selected_term == $term_option) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($term_option) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="student-info">
        <p><strong>Student Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
        <p><strong>Class:</strong> <?= htmlspecialchars($student['class_name']) ?></p>
        <p><strong>Position:</strong> <?= $position ?> out of <?= $total_students ?> students</p>
    </div>

    <h2>📄 Official Term-wise Exam Results</h2>

    <?php if(empty($rows)): ?>
        <p style="text-align: center; color: #666; font-size: 16px; padding: 20px;">No results approved yet.</p>
    <?php else: ?>
        <?php 
        // Group results by term
        $grouped_by_term = [];
        foreach ($rows as $r) {
            $term = $r['term'];
            if (!isset($grouped_by_term[$term])) {
                $grouped_by_term[$term] = [];
            }
            $grouped_by_term[$term][] = $r;
        }
        
        // Display each term separately
        foreach ($grouped_by_term as $term_name => $term_results):
            $term_total_percentage = 0;
            foreach ($term_results as $result) {
                $term_total_percentage += $result['percentage'];
            }
            $term_avg = count($term_results) > 0 ? $term_total_percentage / count($term_results) : 0;
            $term_grade = getGrade($term_avg);
        ?>
            <div style="margin-bottom: 30px;">
                <h3 style="background: #00bfff; color: white; padding: 12px; border-radius: 6px; margin: 0 0 15px 0;">
                    📚 <?= htmlspecialchars($term_name) ?>
                </h3>
                
                <table style="margin-bottom: 15px;">
                    <tr>
                        <th>Subject</th>
                        <th>Exam Date</th>
                        <th>Marks Obtained</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                    </tr>
                    <?php foreach ($term_results as $r): 
                        $grade_info = getGrade($r['percentage']);
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($r['subject_name']) ?></td>
                            <td><?= htmlspecialchars($r['exam_date']) ?></td>
                            <td><?= htmlspecialchars($r['marks_obtained']) ?> / <?= htmlspecialchars($r['max_marks']) ?></td>
                            <td><strong><?= number_format($r['percentage'], 2) ?>%</strong></td>
                            <td><span class="grade-badge" style="background-color: <?= $grade_info['color'] ?>"><?= $grade_info['grade'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <tfoot>
                        <tr>
                            <td colspan="4"><strong><?= htmlspecialchars($term_name) ?> Average (%):</strong></td>
                            <td>
                                <span class="grade-badge" style="background-color: <?= $term_grade['color'] ?>">
                                    <?= $term_grade['grade'] ?> (<?= number_format($term_avg, 2) ?>%)
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endforeach; ?>
        
        <div style="background: #f0f0f0; padding: 15px; border-radius: 8px; margin-top: 20px;">
            <h3 style="margin: 0 0 10px 0;">📊 Overall Performance</h3>
            <p style="margin: 0; font-size: 16px;">
                <strong>Overall Average:</strong> 
                <span class="grade-badge" style="background-color: <?= $overall_grade['color'] ?>">
                    <?= $overall_grade['grade'] ?> (<?= number_format($overall_avg, 2) ?>%)
                </span>
            </p>
        </div>

        <!-- Grading Scale -->
        <div class="grading-scale-table">
            <h3 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #00bfff;">📊 Grading Scale (Based on Percentage)</h3>
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
    <?php endif; ?>

    <div class="footer" style="margin-top: 50px; padding-top: 30px; border-top: 2px solid #ccc; display: flex; justify-content: space-between;">
        <div style="text-align: center; width: 30%;">
            <p style="margin: 50px 0 0 0; font-size: 13px;">__________________</p>
            <p style="margin: 5px 0; font-size: 13px; font-weight: bold;">Class Teacher</p>
        </div>
        <div style="text-align: center; width: 30%;">
            <p style="margin: 50px 0 0 0; font-size: 13px;">__________________</p>
            <p style="margin: 5px 0; font-size: 13px;">Examination Date</p>
        </div>
        <div style="text-align: center; width: 30%;">
            <p style="margin: 50px 0 0 0; font-size: 13px;">__________________</p>
            <p style="margin: 5px 0; font-size: 13px; font-weight: bold;">Principal</p>
            <p style="margin: 3px 0; font-size: 12px;">Sujal Chhetri Karki</p>
        </div>
    </div>
</div>

<div class="print-btn" style="text-align: center; margin: 30px auto; padding: 20px; background: #f0f8ff; border-radius: 8px; border: 2px solid #00bfff;">
    <button onclick="window.print()" style="background: #00bfff; color: #fff; border: none; padding: 15px 30px; font-size: 16px; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s;">
        🖨️ Print Report Card
    </button>
    <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">Click to print this report card as a PDF or physical copy</p>
</div>

</body>
</html>
