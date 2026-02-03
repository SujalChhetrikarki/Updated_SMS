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
SELECT r.marks_obtained, r.average_marks, e.exam_date, e.term, sub.subject_name
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

// ✅ Calculate student overall average
$total_marks = 0;
$total_subjects = 0;
$rows = [];
while($r = $results->fetch_assoc()) {
    $rows[] = $r;
    $total_marks += $r['marks_obtained'];
    $total_subjects++;
}
$overall_avg = ($total_subjects > 0) ? ($total_marks / $total_subjects) : 0;

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
$stmt_terms->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Result Sheet - Dignity Academy</title>
<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #eef2f7;
    padding: 20px;
}
.report-card {
    max-width: 900px;
    margin: auto;
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
    color:#2c3e50;
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
@media print {
    body {
        background: none;
        padding: 0;
    }
    .print-btn {
        display: none;
    }
    .report-card {
        box-shadow: none;
        border: 2px solid #00bfff;
        margin: 0;
        width: 100%;
    }
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
.date-form select {
    padding: 5px 10px;
    border-radius: 5px;
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

    <table>
        <tr>
            <th>Term</th>
            <th>Subject</th>
            <th>Exam Date</th>
            <th>Marks Obtained</th>
            <th>Average Marks</th>
        </tr>
        <?php if(empty($rows)): ?>
            <tr><td colspan="5">No results approved yet.</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['term']) ?></td>
                    <td><?= htmlspecialchars($r['subject_name']) ?></td>
                    <td><?= htmlspecialchars($r['exam_date']) ?></td>
                    <td><?= htmlspecialchars($r['marks_obtained']) ?></td>
                    <td><?= number_format($r['average_marks'],2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tfoot>
                <tr>
                    <td colspan="4">Overall Average</td>
                    <td><?= number_format($overall_avg,2) ?></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>

    <div class="footer">
        <div class="signature">
            <p>__________________</p>
            <p>Class Teacher</p>
        </div>
        <div class="signature">
            <p>__________________</p>
            <p><strong>Sujal Chhetri Karki</strong><br>Principal</p>
        </div>
    </div>
</div>

<div class="print-btn">
    <button onclick="window.print()">🖨️ Print Result</button>
</div>

</body>
</html>
