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
$classes = $conn->query("SELECT * FROM classes ORDER BY class_id ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $class_id = intval($_POST['class_id']);
    $password = trim($_POST['password'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    // Validate required fields
    if (empty($name) || empty($email) || !$class_id) {
        $error = "Name, email, and class are required.";
    } else if (!empty($date_of_birth)) {
        // Validate and convert date format if provided
        $date_formats = ['Y-m-d', 'd-m-Y', 'm-d-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'n/d/Y', 'j/n/Y', 'n/j/Y', 'd-m-Y', 'j-n-Y', 'n-j-Y'];
        $date_obj = null;
        $validated_dob = null;
        
        foreach ($date_formats as $format) {
            $date_obj = DateTime::createFromFormat($format, $date_of_birth);
            if ($date_obj !== false) {
                $formatted = $date_obj->format($format);
                if ($formatted === $date_of_birth) {
                    $validated_dob = $date_obj->format('Y-m-d');
                    break;
                }
            }
        }
        
        if ($validated_dob === null || strtotime($validated_dob) > time()) {
            $error = "Invalid date of birth. Use format: YYYY-MM-DD, DD-MM-YYYY, or M/D/YYYY (e.g., 1/3/2010 or 01/03/2010).";
        } else {
            $date_of_birth = $validated_dob;
        }
    }

    if (!isset($error)) {
        if ($password !== '') {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE students SET name=?, email=?, class_id=?, password=?, date_of_birth=?, gender=? WHERE student_id=?");
            $stmt->bind_param("ssisisi", $name, $email, $class_id, $hashed_password, $date_of_birth, $gender, $student_id);
        } else {
            // No password change
            $stmt = $conn->prepare("UPDATE students SET name=?, email=?, class_id=?, date_of_birth=?, gender=? WHERE student_id=?");
            $stmt->bind_param("ssissi", $name, $email, $class_id, $date_of_birth, $gender, $student_id);
        }

        $stmt->execute();
        $stmt->close();

        header("Location: Managestudent.php?msg=updated");
        exit;
    }
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
    <?php if(isset($error)): ?>
        <p style="color: #ef4444; margin: 10px 0; padding: 10px; background: #fee; border-radius: 6px;">
            <?= htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>
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

        <label>Date of Birth (Optional - formats: YYYY-MM-DD, DD-MM-YYYY, or M/D/YYYY):</label>
        <input type="text" name="date_of_birth" value="<?= htmlspecialchars($student['date_of_birth']); ?>" placeholder="e.g., 1/3/2010, 2010-05-15, or 01/03/2010">

        <label>Gender:</label>
        <select name="gender">
            <option value="">-- Select Gender --</option>
            <option value="Male" <?= ($student['gender']=='Male')?'selected':''; ?>>Male</option>
            <option value="Female" <?= ($student['gender']=='Female')?'selected':''; ?>>Female</option>
            <option value="Other" <?= ($student['gender']=='Other')?'selected':''; ?>>Other</option>
        </select>

        <label>Password (leave blank to keep current):</label>
        <input type="password" name="password" placeholder="New Password">

        <button type="submit">💾 Update Student</button>
    </form>
    <a href="Managestudent.php">⬅ Back</a>
</div>
</body>
</html>
