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
        /* ===== Reset & Body ===== */
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            display: flex;
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

        .sidebar a:hover {
            background: #3b82f6;
            color: #fff;
        }

        .sidebar a.logout {
            background: #ef4444;
        }

        .sidebar a.logout:hover {
            background: #f87171;
        }

        /* ===== Main Content ===== */
        .main {
            margin-left: 240px;
            padding: 30px;
            flex: 1;
        }

        /* ===== Header ===== */
        .main h1 {
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 28px;
            color: #1f2937;
            font-weight: 600;
        }

        /* ===== Form Card ===== */
        form {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            max-width: 500px;
        }

        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
        }

        form input,
        form select {
            width: 100%;
            padding: 10px 14px;
            margin-bottom: 20px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        form input:focus,
        form select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        form button {
            width: 100%;
            background: #3b82f6;
            color: #fff;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.3s;
        }

        form button:hover {
            background: #2563eb;
        }

        /* ===== Error Message ===== */
        .error {
            color: #dc2626;
            background: #fee2e2;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
            font-weight: 500;
        }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main {
                margin-left: 200px;
                padding: 20px;
            }
            .main h1 {
                font-size: 22px;
            }
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
    <a href="classes.php">🏫 Manage Classes</a>
    <a href="../subjects.php">📖 Manage Subjects</a>
    <a href="../Managebook.php">📚 Manage Books</a>
    <a href="../add_student.php">➕ Add Student</a>
    <a href="../add_teacher.php">➕ Add Teacher</a>
    <a href="../Add_exam/add_exam.php">➕ Add Exam</a>
    <a href="../admin_approve_results.php">✅ Approve Results</a>
    <a href="../PreAdmissions.php">📝 Pre-Admissions</a>
    <a href="../logout.php" class="logout">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="main">
    <h1>➕ Add New Class</h1>

    <?php if (!empty($error)): ?>
        <div class="error"><?= $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="class_name">Class Name</label>
        <input type="text" id="class_name" name="class_name" placeholder="e.g. Class 10-A" required>

        <label for="teacher_id">Assign Teacher (optional)</label>
        <select name="teacher_id" id="teacher_id">
            <option value="">-- Select Teacher --</option>
            <?php while ($t = $teachers->fetch_assoc()): ?>
                <option value="<?= $t['teacher_id']; ?>"><?= htmlspecialchars($t['name']); ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit">💾 Save Class</button>
    </form>
</div>

</body>
</html>
<?php $conn->close(); ?>
