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
   ADD SUBJECT
========================= */
if (isset($_POST['add_subject'])) {
    $new_subject = trim($_POST['new_subject']);
    $class_id = intval($_POST['class_id']);

    if (!empty($new_subject) && $class_id > 0) {
        $stmt = $conn->prepare(
            "INSERT INTO subjects (subject_name, class_id) VALUES (?, ?)"
        );
        $stmt->bind_param("si", $new_subject, $class_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Subject added successfully!";
        } else {
            $_SESSION['error'] = "Error adding subject: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Please enter subject name and select a class.";
    }

    header("Location: subjects.php");
    exit;
}

/* =========================
   DELETE SUBJECT
========================= */
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM subjects WHERE subject_id = $del_id");
    $_SESSION['success'] = "Subject deleted successfully!";
    header("Location: subjects.php");
    exit;
}

/* =========================
   FETCH SUBJECTS WITH CLASS
========================= */
$subjects = $conn->query("
    SELECT s.subject_id, s.subject_name, c.class_name
    FROM subjects s
    JOIN classes c ON s.class_id = c.class_id
    ORDER BY s.subject_id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Subjects</title>
<style>
/* ===== Body & Layout ===== */
body { display: flex; margin: 0; font-family: 'Inter', sans-serif; background: #f0f2f5; }

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
    height: 80px; background: #3b82f6; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; font-weight: 500;
}

/* ===== Main Content ===== */
.main {
    margin-left: 240px; width: calc(100% - 240px);
    padding: 100px 30px 30px;
    display: flex; justify-content: center;
}

/* ===== Container ===== */
.container {
    width: 700px; background: #fff; padding: 30px;
    border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

/* ===== Messages ===== */
.success { color: #16a34a; margin-bottom: 15px; font-weight: 500; }
.error { color: #dc2626; margin-bottom: 15px; font-weight: 500; }

/* ===== Form ===== */
form label { display: block; margin-top: 15px; font-weight: 500; }
form input, form select { width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #ccc; }
form button {
    margin-top: 20px; padding: 12px; width: 100%;
    background: #3b82f6; color: #fff; border: none; border-radius: 6px;
    cursor: pointer; font-weight: 500;
}
form button:hover { background: #2563eb; }

/* ===== Table ===== */
table { width: 100%; border-collapse: collapse; margin-top: 25px; border-radius: 12px; overflow: hidden; }
th, td { padding: 12px 15px; text-align: center; border-bottom: 1px solid #e5e7eb; }
th { background: #3b82f6; color: #fff; font-weight: 600; }
tr:hover { background: #f1f5f9; }

/* ===== Delete Button ===== */
a.delete {
    background: #ef4444; color: #fff; padding: 6px 12px; border-radius: 6px;
    text-decoration: none; font-weight: 500; transition: 0.3s;
}
a.delete:hover { background: #dc2626; }

/* ===== Responsive ===== */
@media(max-width:768px){
    .main { margin-left: 0; padding: 120px 15px 15px; }
    table, form input, form select, form button { font-size: 14px; }
}
</style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="index.php">🏠 Home</a>
    <a href="../Admin/Manage_student/Managestudent.php">📚 Manage Students</a>
    <a href="../Admin/Manage_Teachers/Teachersshow.php">👨‍🏫 Manage Teachers</a>
    <a href="../Admin/Classes/classes.php">🏫 Manage Classes</a>
    <a href="subjects.php">📖 Manage Subjects</a>
    <a href="./Managebook.php">📚 Manage Books</a>
    <a href="../Admin/add_student.php">➕ Add Student</a>
    <a href="../Admin/add_teacher.php">➕ Add Teacher</a>
    <a href="../Admin/Add_exam/add_exam.php">➕ Add Exam</a>
    <a href="../Admin/admin_approve_results.php">✅ Approve Results</a>
    <a href="../Admin/logout.php" class="logout">🚪 Logout</a>
</div>

<div class="header">Subject Management</div>

<div class="main">
<div class="container">

<?php if(isset($_SESSION['success'])): ?>
<p class="success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
<p class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
<?php endif; ?>

<!-- ADD SUBJECT FORM -->
<form method="POST">
    <label>Subject Name</label>
    <input type="text" name="new_subject" required>

    <label>Class</label>
    <select name="class_id" required>
        <option value="">Select Class</option>
        <?php while($c = $classes->fetch_assoc()): ?>
            <option value="<?= $c['class_id']; ?>"><?= htmlspecialchars($c['class_name']); ?></option>
        <?php endwhile; ?>
    </select>

    <button type="submit" name="add_subject">Add Subject</button>
</form>

<!-- SUBJECT LIST -->
<table>
<tr>
    <th>ID</th>
    <th>Subject</th>
    <th>Class</th>
    <th>Action</th>
</tr>
<?php if($subjects->num_rows > 0): ?>
<?php while($s = $subjects->fetch_assoc()): ?>
<tr>
    <td><?= $s['subject_id']; ?></td>
    <td><?= htmlspecialchars($s['subject_name']); ?></td>
    <td><?= htmlspecialchars($s['class_name']); ?></td>
    <td>
        <a class="delete" href="subjects.php?delete_id=<?= $s['subject_id']; ?>" onclick="return confirm('Delete this subject?');">Delete</a>
    </td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="4">No subjects found</td></tr>
<?php endif; ?>
</table>

</div>
</div>

</body>
</html>
