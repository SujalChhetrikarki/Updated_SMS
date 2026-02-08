<?php
session_start();
include '../../Database/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

// Handle Add Exam Form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_exam'])) {
    $class_ids = [ $_POST['class_id'] ];
$subject_ids = [ $_POST['subject_id'] ];
    $exam_date = $_POST['exam_date'];
    $max_marks = $_POST['max_marks'];
    $term = $_POST['term'];

    $inserted = 0;
    foreach ($class_ids as $class_id) {
        foreach ($subject_ids as $subject_id) {
            $sql = "INSERT INTO exams (subject_id, class_id, exam_date, max_marks, term) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("iisis", $subject_id, $class_id, $exam_date, $max_marks, $term);
                if ($stmt->execute()) $inserted++;
            }
        }
    }
    header("Location: add_exam.php?msg=" . urlencode("✅ {$inserted} Exam(s) added successfully!"));
    exit;
}

// Fetch classes and subjects
$classes = $conn->query("SELECT class_id, class_name FROM classes ORDER BY class_name");
$subjects = $conn->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name");

// Fetch all exams
$exams_result = $conn->query("
    SELECT e.exam_id, e.exam_date, e.max_marks, e.term, c.class_name, s.subject_name
    FROM exams e
    JOIN classes c ON e.class_id=c.class_id
    JOIN subjects s ON e.subject_id=s.subject_id
    ORDER BY e.term, e.exam_date ASC
");

$msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Add Exams</title>
<style>
body { font-family: Arial; margin: 0; background: #f4f6f9; }
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

.container { margin-left: 240px; padding: 20px; }
.header { background: #00bfff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 22px; color: #333; }

form { max-width:600px; margin:auto; padding:20px; background:#fff; border:1px solid #ccc; border-radius:10px; }
label { font-weight:bold; margin-top:10px; display:block; }
select, input { width:100%; margin-bottom:10px; padding:8px; }
.btn { padding:10px; background:#00bfff; color:#fff; border:none; cursor:pointer; border-radius:5px; }
.btn:hover { background:#2980b9; }
table { width:90%; margin:30px auto; border-collapse:collapse; background:#fff; }
th, td { border:1px solid #ccc; padding:8px; text-align:center; }
th { background:#00bfff; }
.clean-select {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background-color: #fff;
    font-size: 15px;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='gray' viewBox='0 0 16 16'%3E%3Cpath d='M1.5 5.5l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 14px;
}

.clean-select:focus {
    outline: none;
    border-color: #00bfff;
    box-shadow: 0 0 0 2px rgba(0,191,255,0.2);
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
    <a href="../classes/classes.php">🏫 Manage Classes</a>
    <a href="../subjects.php">📖 Manage Subjects</a>
    <a href="../Managebook.php">📚 Manage Books</a>
    <a href="../add_student.php">➕ Add Student</a>
    <a href="../add_teacher.php">➕ Add Teacher</a>
    <a href="add_exam.php">➕ Add Exam</a>
    <a href="../admin_approve_results.php">✅ Approve Results</a>
    <a href="../logout.php" class="logout">🚪 Logout</a>
  </div>

<!-- Main Content -->
<div class="container">
    <div class="header">
        <h1>Admin Panel - Add Exam</h1>
    </div>

    <h2>➕ Add Term-wise Exam</h2>
    <?php if($msg) echo "<p style='color:green; text-align:center;'>{$msg}</p>"; ?>

   <label>Select Class</label>
<select name="class_id" required class="clean-select">
    <option value="">-- Select Class --</option>
    <?php if ($classes && $classes->num_rows > 0): ?>
        <?php while($c = $classes->fetch_assoc()): ?>
            <option value="<?= $c['class_id'] ?>">
                <?= htmlspecialchars($c['class_name']) ?>
            </option>
        <?php endwhile; ?>
    <?php else: ?>
        <option disabled>No Classes Available</option>
    <?php endif; ?>
</select>


       <label>Select Subject</label>
<select name="subject_id" required class="clean-select">
    <option value="">-- Select Subject --</option>
    <?php if ($subjects && $subjects->num_rows > 0): ?>
        <?php while($s = $subjects->fetch_assoc()): ?>
            <option value="<?= $s['subject_id'] ?>">
                <?= htmlspecialchars($s['subject_name']) ?>
            </option>
        <?php endwhile; ?>
    <?php else: ?>
        <option disabled>No Subjects Available</option>
    <?php endif; ?>
</select>


        <label>Exam Date</label>
        <input type="date" name="exam_date" required>

        <label>Maximum Marks</label>
        <input type="number" name="max_marks" min="1" required>

<label>Term</label>
<input type="text" name="term" placeholder="e.g. Term 1 / Mid Term / Final Exam" required>


        <button type="submit" name="add_exam" class="btn">Add Exam</button>
    </form>

    <h2>📅 Upcoming Exams</h2>
    <table>
    <tr><th>Exam ID</th><th>Class</th><th>Subject</th><th>Exam Date</th><th>Max Marks</th><th>Term</th><th>Edit</th><th>Delete</th></tr>
    <?php if ($exams_result && $exams_result->num_rows > 0): ?>
        <?php while($exam=$exams_result->fetch_assoc()): ?>
        <tr>
            <td><?= $exam['exam_id'] ?></td>
            <td><?= htmlspecialchars($exam['class_name']) ?></td>
            <td><?= htmlspecialchars($exam['subject_name']) ?></td>
            <td><?= $exam['exam_date'] ?></td>
            <td><?= $exam['max_marks'] ?></td>
            <td><?= $exam['term'] ?></td>
            <td><a href="edit_exam.php?exam_id=<?= $exam['exam_id'] ?>">Edit</a> </td>
            <td><a href="delete_exam.php?exam_id=<?= $exam['exam_id'] ?>" onclick="return confirm('Are you sure you want to delete this exam?');">Delete</a></td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="6">No Exams Scheduled</td></tr>
    <?php endif; ?>
    </table>
</div>

</body>
</html>
