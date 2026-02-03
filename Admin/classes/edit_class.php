<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

include '../../Database/db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: ManageClasses.php");
    exit;
}

$class_id = intval($_GET['id']);

// Fetch class details
$stmt = $conn->prepare("SELECT * FROM classes WHERE class_id=?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

// Fetch assigned teacher (if any)
$stmt2 = $conn->prepare("SELECT teacher_id FROM class_teachers WHERE class_id=?");
$stmt2->bind_param("i", $class_id);
$stmt2->execute();
$assigned_teacher = $stmt2->get_result()->fetch_assoc();
$assigned_teacher_id = $assigned_teacher['teacher_id'] ?? null;

// Fetch all teachers for dropdown
$teachers = $conn->query("SELECT * FROM teachers ORDER BY name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name = trim($_POST['class_name']);
    $teacher_id = intval($_POST['teacher_id']);

    // Update class name
    $stmt = $conn->prepare("UPDATE classes SET class_name=? WHERE class_id=?");
    $stmt->bind_param("si", $class_name, $class_id);
    $stmt->execute();

    // Update class_teacher assignment
    if ($assigned_teacher_id) {
        $stmt = $conn->prepare("UPDATE class_teachers SET teacher_id=? WHERE class_id=?");
        $stmt->bind_param("ii", $teacher_id, $class_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO class_teachers (class_id, teacher_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $class_id, $teacher_id);
        $stmt->execute();
    }

    header("Location: classes.php?msg=updated");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Class</title>
<style>
/* Global */
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }

body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    background: #fff;
    padding: 30px 40px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    width: 420px;
    text-align: center;
    animation: fadeIn 0.8s ease-in-out;
}

h1 { margin-bottom: 20px; color: #333; }

label { 
    display: block; 
    margin-top: 15px; 
    font-weight: bold; 
    text-align: left; 
    color: #555; 
}

input, select {
    width: 100%;
    padding: 12px;
    margin-top: 8px;
    border-radius: 8px;
    border: 1px solid #ccc;
    outline: none;
    transition: border 0.3s;
}

input:focus, select:focus {
    border: 1px solid #4facfe;
    box-shadow: 0 0 8px rgba(79,172,254,0.3);
}

button {
    margin-top: 20px;
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    background: linear-gradient(135deg, #00f2fe, #4facfe);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

a {
    display: inline-block;
    margin-top: 15px;
    text-decoration: none;
    color: #4facfe;
    font-weight: bold;
    transition: 0.3s;
}

a:hover { color: #007bff; }

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(-10px);}
    to {opacity: 1; transform: translateY(0);}
}
</style>
</head>
<body>
<div class="container">
    <h1>✏ Edit Class</h1>

    <form method="post">
        <label>Class Name:</label>
        <input type="text" name="class_name" value="<?= htmlspecialchars($class['class_name']); ?>" required>

        <label>Assign Teacher:</label>
        <select name="teacher_id" required>
            <option value="">-- Select Teacher --</option>
            <?php while($t = $teachers->fetch_assoc()): ?>
                <option value="<?= $t['teacher_id']; ?>" <?= ($t['teacher_id']==$assigned_teacher_id)?'selected':''; ?>>
                    <?= htmlspecialchars($t['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit">💾 Update Class</button>
    </form>

    <a href="classes.php">⬅ Back to Classes</a>
</div>
</body>
</html>
