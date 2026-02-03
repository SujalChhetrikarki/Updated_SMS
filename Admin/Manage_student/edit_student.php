<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

include '../../Database/db_connect.php';

if (!isset($_GET['student_id'])) {
    header("Location: Managestudent.php");
    exit;
}

$student_id = intval($_GET['student_id']);

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Fetch classes for dropdown
$classes = $conn->query("SELECT * FROM classes");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $class_id = intval($_POST['class_id']);
    $password = trim($_POST['password'] ?? '');

    if ($password !== '') {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE students SET name=?, email=?, class_id=?, password=? WHERE student_id=?");
        $stmt->bind_param("ssisi", $name, $email, $class_id, $hashed_password, $student_id);
    } else {
        // No password change
        $stmt = $conn->prepare("UPDATE students SET name=?, email=?, class_id=? WHERE student_id=?");
        $stmt->bind_param("ssii", $name, $email, $class_id, $student_id);
    }

    $stmt->execute();
    $stmt->close();

    header("Location: Managestudent.php?msg=updated");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Student</title>
<style>
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
label { display: block; margin-top: 15px; font-weight: bold; text-align: left; color: #555; }
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
    <h1>✏ Edit Student</h1>
    <form method="post">
        <label>Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($student['name']); ?>" required>

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($student['email']); ?>" required>

        <label>Class:</label>
        <select name="class_id" required>
            <?php while($c = $classes->fetch_assoc()): ?>
                <option value="<?= $c['class_id']; ?>" <?= ($c['class_id']==$student['class_id'])?'selected':''; ?>>
                    <?= htmlspecialchars($c['class_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Password (leave blank to keep current):</label>
        <input type="password" name="password" placeholder="New Password">

        <button type="submit">💾 Update Student</button>
    </form>
    <a href="Managestudent.php">⬅ Back</a>
</div>
</body>
</html>
