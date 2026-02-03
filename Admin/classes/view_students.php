<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

include '../../Database/db_connect.php';

// Check class_id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: classes.php");
    exit;
}
$class_id = intval($_GET['id']);

// Fetch class info with teacher
$class_sql = "
    SELECT c.class_name, t.name AS teacher_name 
    FROM classes c 
    LEFT JOIN class_teachers ct ON c.class_id = ct.class_id
    LEFT JOIN teachers t ON ct.teacher_id = t.teacher_id
    WHERE c.class_id = ?
";
$stmt = $conn->prepare($class_sql);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$class_result = $stmt->get_result();
$class_info = $class_result->fetch_assoc();
$stmt->close();

if (!$class_info) {
    $_SESSION['error'] = "Class not found!";
    header("Location: classes.php");
    exit;
}

// Fetch students in this class
$sql = "SELECT student_id, name, email, date_of_birth, gender 
        FROM students 
        WHERE class_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();
$total_students = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Students</title>
<style>
/* Global */
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }

body {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    display: flex;
    justify-content: center;
    padding: 50px 0;
    min-height: 100vh;
}

.container {
    background: #fff;
    padding: 30px 40px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    width: 90%;
    max-width: 1000px;
    animation: fadeIn 0.8s ease-in-out;
}

h1 {
    margin-bottom: 15px;
    text-align: center;
    color: #333;
}

p { margin: 5px 0 15px; font-size: 16px; color: #555; }

.btn {
    display: inline-block;
    padding: 10px 18px;
    background: #4facfe;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
    margin-bottom: 15px;
}

.btn:hover {
    background: #007bff;
    transform: translateY(-2px);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    border-radius: 8px;
    overflow: hidden;
}

thead {
    background: #4facfe;
    color: white;
}

th, td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #ddd;
    font-size: 15px;
}

tr:hover { background: #f1f1f1; }

.btn-sm {
    padding: 8px 12px;
    font-size: 14px;
    text-decoration: none;
    font-weight: bold;
    border-radius: 6px;
    transition: 0.3s;
    display: inline-block;
}

.danger {
    background: #ff4d4d;
    color: #fff;
}

.danger:hover {
    background: #e60000;
    transform: translateY(-2px);
}

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(-10px);}
    to {opacity: 1; transform: translateY(0);}
}
</style>
</head>
<body>
<div class="container">
    <h1>👨‍🎓 Students in <?= htmlspecialchars($class_info['class_name']); ?></h1>
    <p><strong>Class Teacher:</strong> <?= $class_info['teacher_name'] ?? 'Unassigned'; ?></p>
    <p><strong>Total Students:</strong> <?= $total_students; ?></p>

    <a class="btn" href="classes.php">⬅ Back to Classes</a>

    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>DOB</th>
                <th>Gender</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($total_students > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_id']); ?></td>
                    <td><?= htmlspecialchars($row['name']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= htmlspecialchars($row['date_of_birth']); ?></td>
                    <td><?= htmlspecialchars($row['gender']); ?></td>
                    <td>
                        <a class="btn-sm danger" href="delete_student.php?student_id=<?= $row['student_id']; ?>&class_id=<?= $class_id; ?>" 
                           onclick="return confirm('Are you sure you want to delete this student?');">🗑 Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No students found in this class.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>
