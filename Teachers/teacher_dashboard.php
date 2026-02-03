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
.profile-card, .notice-card, .class-card, .subject-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    transition: transform 0.3s, box-shadow 0.3s;
}
.profile-card:hover, .notice-card:hover, .class-card:hover, .subject-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}
.profile-card p {
    margin: 8px 0;
}
.notice-card h4 {
    margin: 0 0 8px 0;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}
.notice-card small {
    color: #777;
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

    <!-- Notices -->
    <div class="section">
        <h2>Recent Notices</h2>
        <?php if ($notices && $notices->num_rows>0): ?>
            <?php while($n=$notices->fetch_assoc()): ?>
            <div class="notice-card">
                <h4><i class="fa fa-bell"></i> <?= htmlspecialchars($n['title']); ?></h4>
                <p><?= nl2br(htmlspecialchars($n['message'])); ?></p>
                <small>Posted on: <?= date('d M Y, h:i A', strtotime($n['created_at'])); ?></small>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No notices available.</p>
        <?php endif; ?>
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
