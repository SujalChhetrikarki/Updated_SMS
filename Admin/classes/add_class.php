<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

include '../../Database/db_connect.php';

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name = mysqli_real_escape_string($conn, $_POST['class_name']);
    $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : NULL;

    if (!empty($class_name)) {
        // Insert class
        $sql = "INSERT INTO classes (class_name) VALUES ('$class_name')";
        if ($conn->query($sql)) {
            $class_id = $conn->insert_id;

            // Assign teacher if selected
            if ($teacher_id) {
                $conn->query("INSERT INTO class_teachers (class_id, teacher_id) VALUES ($class_id, $teacher_id)");
            }

            header("Location: classes.php?success=1");
            exit;
        } else {
            $error = "Error: " . $conn->error;
        }
    } else {
        $error = "Class name cannot be empty!";
    }
}

// ✅ Fetch available teachers
$teachers = $conn->query("SELECT teacher_id, name FROM teachers ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Class</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f4f6f9; display: flex; }
        .sidebar { width: 220px; background: #111; color: #fff; height: 100vh; position: fixed; left: 0; top: 0; padding-top: 20px; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; font-size: 20px; color: #00bfff; }
        .sidebar a { display: block; padding: 12px 20px; margin: 8px 15px; background: #222; color: #fff; text-decoration: none; border-radius: 6px; transition: 0.3s; }
        .sidebar a:hover { background: #00bfff; color: #111; }
        .sidebar a.logout { background: #dc3545; }
        .sidebar a.logout:hover { background: #ff4444; color: #fff; }
        .container { margin-left: 240px; padding: 20px; flex: 1; }
        form { background: #fff; padding: 20px; border-radius: 8px; max-width: 500px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #00bfff; color: #fff; border: none; padding: 10px 16px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #2980b9; }
        .error { color: red; margin-bottom: 10px; }
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
  <a href="../add_student.php">➕ Add Student</a>
  <a href="../add_teacher.php">➕ Add Teacher</a>
  <a href="../Add_exam/add_exam.php">➕ Add Exam</a>
  <a href="../admin_approve_results.php">✅ Approve Results</a>
  <a href="../logout.php" class="logout">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="container">
    <h1>➕ Add New Class</h1>

    <?php if (!empty($error)): ?>
        <div class="error"><?= $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="class_name">Class Name</label>
        <input type="text" id="class_name" name="class_name" required>

        <label for="teacher_id">Assign Teacher (optional)</label>
        <select name="teacher_id" id="teacher_id">
            <option value="">-- Select Teacher --</option>
            <?php while ($t = $teachers->fetch_assoc()): ?>
                <option value="<?= $t['teacher_id']; ?>"><?= htmlspecialchars($t['name']); ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Save Class</button>
    </form>

    <br>
</div>

</body>
</html>
<?php $conn->close(); ?>
