<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit;
}

include '../Database/db_connect.php';
$teacher_id = $_SESSION['teacher_id'];

// Fetch Teacher Details
$stmt = $conn->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch Notices
$notices = $conn->query("
    SELECT title, message, created_at 
    FROM notices 
    WHERE target IN ('teachers', 'both')
    ORDER BY created_at DESC 
    LIMIT 5
");

// Fetch Classes
$stmt_classes = $conn->prepare("
    SELECT c.class_id, c.class_name, c.class_teacher_id
    FROM classes c
    JOIN class_teachers ct ON c.class_id = ct.class_id
    WHERE ct.teacher_id = ?
");
$stmt_classes->bind_param("s", $teacher_id);
$stmt_classes->execute();
$classes = $stmt_classes->get_result();
$stmt_classes->close();

// Fetch Subjects
$stmt_subjects = $conn->prepare("
    SELECT s.subject_id, s.subject_name, c.class_id, c.class_name
    FROM class_subject_teachers cst
    JOIN subjects s ON cst.subject_id = s.subject_id
    JOIN classes c ON cst.class_id = c.class_id
    WHERE cst.teacher_id = ?
");
$stmt_subjects->bind_param("s", $teacher_id);
$stmt_subjects->execute();
$subjects = $stmt_subjects->get_result();
$stmt_subjects->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Teacher Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
    font-family: 'Inter', sans-serif;
    margin: 0;
    background: #f4f6f9;
    color: #333;
}
header {
    background: linear-gradient(90deg,#0066cc,#004aad);
    color: white;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}
header h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
}
.logout-btn {
    background: #dc3545;
    padding: 10px 18px;
    border-radius: 6px;
    color: white;
    font-weight: 600;
    text-decoration: none;
    transition: 0.3s;
}
.logout-btn:hover {
    background: #b02a37;
}
.container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 20px;
}
.section {
    margin-bottom: 40px;
}
.section h2 {
    color: #004aad;
    font-weight: 600;
    margin-bottom: 20px;
    border-left: 5px solid #0066cc;
    padding-left: 10px;
}
.profile-card, .class-card, .subject-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    transition: transform 0.3s, box-shadow 0.3s;
}
.profile-card:hover, .class-card:hover, .subject-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}
.profile-card p {
    margin: 8px 0;
}
.notice-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15);
    margin-bottom: 20px;
    transition: transform 0.3s, box-shadow 0.3s;
    color: white;
    position: relative;
    overflow: hidden;
}
.notice-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transform: translate(30px, -30px);
}
.notice-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.25);
}
.notice-card h4 {
    margin: 0 0 12px 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
    color: #fff;
    position: relative;
    z-index: 1;
}
.notice-card p {
    margin: 12px 0;
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
    margin-top: 12px;
    position: relative;
    z-index: 1;
}
.class-card, .subject-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.class-card .info, .subject-card .info {
    flex: 1;
}
.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    color: white;
    font-weight: 600;
}
.badge.class-teacher { background: #28a745; }
.badge.subject-teacher { background: #17a2b8; }
.btn {
    background: #007bff;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
    margin-left: 5px;
    transition: 0.3s;
}
.btn:hover {
    background: #0056b3;
}
.table-container {
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}
th, td {
    padding: 12px 15px;
    text-align: left;
}
th {
    background: #f1f1f1;
    font-weight: 600;
}
tr:hover { background: #f9f9f9; }
@media(max-width:768px){
    .class-card, .subject-card {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
</head>
<body>

<header>
    <h1>Teacher Dashboard</h1>
    <a href="logout.php" class="logout-btn"><i class="fa fa-sign-out-alt"></i> Logout</a>
</header>

<div class="container">

    <!-- Notices - Corner Alert (Top Right) - Vibrant -->
    <?php 
    $latest_notice = null;
    if ($notices && $notices->num_rows > 0) {
        $latest_notice = $notices->fetch_assoc();
    }
    ?>
    <?php if ($latest_notice): ?>
    <div class="notice-vibrant-teacher" style="position: fixed; top: 75px; right: 20px; width: 320px; background: linear-gradient(135deg, #0066cc 0%, #004aad 100%); border-radius: 12px; padding: 16px 18px; box-shadow: 0 6px 20px rgba(0, 102, 204, 0.25), inset 0 0 20px rgba(0, 150, 255, 0.1); border-right: 4px solid #00bfff; border-top: 2px solid #00d4ff; z-index: 999; display: flex; align-items: flex-start; gap: 12px; backdrop-filter: blur(10px);">
        <span style="font-size: 26px; flex-shrink: 0; margin-top: 2px; animation: bounce-teacher 2s infinite;">📢</span>
        <div style="flex: 1; min-width: 0;">
            <h4 style="margin: 0 0 8px 0; color: #fff; font-size: 16px; font-weight: 800; text-shadow: 0 2px 4px rgba(0,0,0,0.2);"><?= htmlspecialchars($latest_notice['title']); ?></h4>
            <p style="margin: 0; color: rgba(255,255,255,0.95); font-size: 13px; line-height: 1.5; font-weight: 500;"><?= substr(htmlspecialchars($latest_notice['message']), 0, 90) . (strlen($latest_notice['message']) > 90 ? '...' : ''); ?></p>
            <small style="color: rgba(255,255,255,0.8); font-size: 12px; margin-top: 6px; display: block; font-weight: 600;">🕒 <?= date('d M Y, h:i', strtotime($latest_notice['created_at'])); ?></small>
        </div>
        <div style="width: 3px; height: 100%; background: linear-gradient(to bottom, #00d4ff, #004aad, transparent); border-radius: 2px; opacity: 0.7;"></div>
    </div>
    <style>
        @keyframes slideInRight-teacher {
            from {
                transform: translateX(360px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes pulse-glow-teacher {
            0%, 100% {
                box-shadow: 0 6px 20px rgba(0, 102, 204, 0.25), inset 0 0 20px rgba(0, 150, 255, 0.1);
            }
            50% {
                box-shadow: 0 8px 28px rgba(0, 102, 204, 0.4), inset 0 0 30px rgba(0, 150, 255, 0.2);
            }
        }
        @keyframes float-gentle-teacher {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-4px);
            }
        }
        .notice-vibrant-teacher {
            animation: slideInRight-teacher 0.6s ease-out, pulse-glow-teacher 2.5s ease-in-out infinite, float-gentle-teacher 3s ease-in-out infinite !important;
        }
        .notice-vibrant-teacher:hover {
            box-shadow: 0 10px 32px rgba(0, 102, 204, 0.35), inset 0 0 40px rgba(0, 150, 255, 0.25) !important;
            transform: translateY(-6px) !important;
        }
        @keyframes bounce-teacher {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
    </style>
    <?php endif; ?>

    <!-- Profile -->
    <div class="section">
        <h2>Profile</h2>
        <div class="profile-card">
            <p><strong><i class="fa fa-user"></i> Name:</strong> <?= htmlspecialchars($teacher['name']); ?></p>
            <p><strong><i class="fa fa-envelope"></i> Email:</strong> <?= htmlspecialchars($teacher['email']); ?></p>
            <p><strong><i class="fa fa-graduation-cap"></i> Specialization:</strong> <?= htmlspecialchars($teacher['specialization']); ?></p>
            <a class="btn" href="change_password.php"><i class="fa fa-key"></i> Change Password</a>
        </div>
    </div>

    <!-- Classes -->
    <div class="section">
        <h2>Your Classes</h2>
        <?php if($classes->num_rows>0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Class ID</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row=$classes->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['class_name']); ?></td>
                        <td><?= $row['class_id']; ?></td>
                        <td>
                            <?php if($row['class_teacher_id']==$teacher_id): ?>
                                <span class="badge class-teacher">Class Teacher</span>
                            <?php else: ?>
                                <span class="badge subject-teacher">Subject Teacher</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn" href="view_students.php?class_id=<?= $row['class_id']; ?>"><i class="fa fa-users"></i> Students</a>
                            <a class="btn" href="manage_attendance.php?class_id=<?= $row['class_id']; ?>"><i class="fa fa-check-square"></i> Attendance</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p>No classes assigned yet.</p>
        <?php endif; ?>
    </div>

    <!-- Subjects -->
    <div class="section">
        <h2>Your Subjects</h2>
        <?php if($subjects->num_rows>0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Class</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row=$subjects->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['subject_name']); ?></td>
                        <td><?= htmlspecialchars($row['class_name']); ?></td>
                        <td>
                            <a class="btn" href="manage_marks.php?class_id=<?= $row['class_id']; ?>&subject_id=<?= $row['subject_id']; ?>"><i class="fa fa-pen"></i> Manage Marks</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p>No subjects assigned yet.</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
