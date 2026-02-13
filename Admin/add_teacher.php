<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include '../Database/db_connect.php';

/* =========================
   FETCH CLASSES & SUBJECTS
========================= */
$classes = $conn->query("SELECT class_id, class_name FROM classes ORDER BY class_name");

$subjectsAll = [];
$result = $conn->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $subjectsAll[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Teacher</title>

    <style>
        /* ===== Body & Layout ===== */
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            display: flex;
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
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: #3b82f6;
        }

        .sidebar a.logout {
            background: #ef4444;
        }

        .sidebar a.logout:hover {
            background: #f87171;
        }

        /* ===== Header ===== */
        .header {
            position: fixed;
            top: 0;
            left: 240px;
            right: 0;
            height: 80px;
            background: #3b82f6;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 500;
            z-index: 10;
        }

        /* ===== Main Content ===== */
        .main {
            margin-left: 240px;
            width: calc(100% - 240px);
            padding: 100px 30px 30px;
            display: flex;
            justify-content: center;
        }

        /* ===== Card ===== */
        .card {
            width: 700px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            text-align: center;
            margin-top: 0;
            margin-bottom: 25px;
            color: #1f2937;
            font-size: 24px;
        }

        /* ===== Form ===== */
        form label {
            display: block;
            margin-top: 10px;
            font-weight: 500;
        }

        form input,
        form select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        form input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }

        form button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background: #3b82f6;
            color: #fff;
            font-weight: 500;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        form button:hover {
            background: #2563eb;
        }

        /* ===== Class & Subject Boxes ===== */
        .class-box {
            border: 1px solid #e5e7eb;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 12px;
            background: #fafafa;
        }

        .class-title {
            font-weight: 500;
            margin-bottom: 6px;
        }

        .subjects-list label {
            display: block;
            font-weight: 400;
            margin-bottom: 3px;
        }

        /* ===== Messages ===== */
        .msg {
            text-align: center;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .error {
            color: #ef4444;
        }

        .success {
            color: #10b981;
        }

        /* ===== Notes ===== */
        .note {
            font-size: 13px;
            color: #6b7280;
            margin-top: 5px;
        }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            .main {
                margin-left: 0;
                padding: 120px 15px 15px;
            }
        }
    </style>
</head>

<body>

<!-- ===== Sidebar ===== -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="index.php">🏠 Home</a>
    <a href="./Manage_student/Managestudent.php">📚 Manage Students</a>
    <a href="./Manage_Teachers/Teachersshow.php">👨‍🏫 Manage Teachers</a>
    <a href="./classes/classes.php">🏫 Manage Classes</a>
    <a href="./subjects.php">📖 Manage Subjects</a>
    <a href="./Managebook.php">📚 Manage Books</a>
    <a href="add_student.php">➕ Add Student</a>
    <a href="add_teacher.php">➕ Add Teacher</a>
    <a href="./Add_exam/add_exam.php">➕ Add Exam</a>
    <a href="./admin_approve_results.php">✅ Approve Results</a>
    <a href="./logout.php" class="logout">🚪 Logout</a>
</div>

<!-- ===== Header ===== -->
<div class="header">➕ Add New Teacher</div>

<!-- ===== Main Content ===== -->
<div class="main">
    <div class="card">

        <h2>Register Teacher</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <p class="msg error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <p class="msg success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>

        <form action="add_teacher_process.php" method="POST" id="addTeacherForm">

            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="specialization" placeholder="Specialization (e.g. Math, Science)" required>

            <label>
                <input type="checkbox" id="is_class_teacher" name="is_class_teacher" value="1">
                Make this teacher a Class Teacher (only one class)
            </label>

            <div id="class_teacher_select" style="display:none; margin-top:10px;">
                <label>Select Class (Class Teacher):</label>
                <select name="class_teacher_class" id="class_teacher_class">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['class_id']; ?>">
                            <?= htmlspecialchars($c['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="note">If checked, teacher will be class teacher of the selected class only.</p>
            </div>

            <label>Assign Subjects to Classes (Teaching Roles):</label>

            <?php foreach ($classes as $class): ?>
                <div class="class-box">
                    <div class="class-title">
                        <label>
                            <input type="checkbox"
                                   class="teaching_class_checkbox"
                                   name="teaching_classes[]"
                                   value="<?= $class['class_id']; ?>">
                            <?= htmlspecialchars($class['class_name']); ?>
                        </label>
                    </div>

                    <div class="subjects-list" style="margin-left:18px;">
                        <?php foreach ($subjectsAll as $sub): ?>
                            <label>
                                <input type="checkbox"
                                       name="subjects_for_class[<?= $class['class_id']; ?>][]"
                                       value="<?= $sub['subject_id']; ?>">
                                <?= htmlspecialchars($sub['subject_name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit">➕ Add Teacher</button>
        </form>

        <p class="note">
            Class Teacher role is limited to one class. Teacher can still teach subjects in multiple classes.
        </p>

    </div>
</div>

<script>
    const chk = document.getElementById('is_class_teacher');
    const selectDiv = document.getElementById('class_teacher_select');
    const selectBox = document.getElementById('class_teacher_class');

    chk.addEventListener('change', function () {
        selectDiv.style.display = this.checked ? 'block' : 'none';
        if (!this.checked) selectBox.value = '';
    });

    document.getElementById('addTeacherForm').addEventListener('submit', function (e) {
        const boxes = document.querySelectorAll('.class-box');
        let valid = true;

        boxes.forEach(box => {
            const classChk = box.querySelector('.teaching_class_checkbox');
            const subjects = box.querySelectorAll('input[type="checkbox"][name^="subjects_for_class"]');
            let anySub = false;

            subjects.forEach(s => {
                if (s.checked) anySub = true;
            });

            if (anySub && !classChk.checked) {
                valid = false;
                classChk.focus();
            }
        });

        if (!valid) {
            e.preventDefault();
            alert("If you select subjects for a class, make sure the corresponding class checkbox is checked.");
        }
    });
</script>

</body>
</html>
