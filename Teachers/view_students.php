<?php
session_start();
include '../Database/db_connect.php';

// Get class_id from request (GET or POST)
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    die("Class ID not provided.");
}

// Fetch class name
$sql = "SELECT class_name FROM classes WHERE class_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();
$classRow = $result->fetch_assoc();
$class_name = $classRow['class_name'] ?? 'Unknown Class';

// Fetch students in this class
$sqlStudents = "SELECT student_id, name FROM students WHERE class_id = ?";
$stmtStudents = $conn->prepare($sqlStudents);
$stmtStudents->bind_param("i", $class_id);
$stmtStudents->execute();
$students = $stmtStudents->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Students in <?= htmlspecialchars($class_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f6f9;
            margin: 0;
            color: #333;
        }
        header {
            background: linear-gradient(90deg,#0066cc,#004aad);
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        header h1 {
            margin: 0;
            font-size: 24px;
        }
        header a.logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }
        header a.logout-btn:hover { background: #b02a37; }

        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
        }

        h2 {
            color: #004aad;
            margin-bottom: 20px;
            border-left: 5px solid #0066cc;
            padding-left: 10px;
            font-weight: 600;
        }

        .student-card {
            background: white;
            padding: 15px 20px;
            margin-bottom: 12px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .student-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        .student-card small {
            color: #777;
            font-size: 13px;
        }

        .btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 16px;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.3s;
        }
        .btn:hover { background: #0056b3; }

        .empty {
            text-align: center;
            color: #7f8c8d;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>

<header>
    <h1><i class="fa fa-users"></i> Students</h1>
    <a href="logout.php" class="logout-btn"><i class="fa fa-sign-out-alt"></i> Logout</a>
</header>

<div class="container">
    <h2>Students in <?= htmlspecialchars($class_name); ?> (ID: <?= htmlspecialchars($class_id); ?>)</h2>

    <?php if ($students->num_rows > 0): ?>
        <?php while ($row = $students->fetch_assoc()): ?>
            <div class="student-card">
                <span><?= htmlspecialchars($row['name']); ?></span>
                <small>ID: <?= $row['student_id']; ?></small>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="empty">No students in this class.</p>
    <?php endif; ?>

    <a href="teacher_dashboard.php" class="btn"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
</div>

</body>
</html>
