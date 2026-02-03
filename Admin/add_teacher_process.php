<?php
session_start();
include '../Database/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: add_teacher.php");
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password_raw = $_POST['password'] ?? '';
$specialization = trim($_POST['specialization'] ?? '');
$is_class_teacher = isset($_POST['is_class_teacher']) ? 1 : 0;
$class_teacher_class = ($_POST['class_teacher_class'] ?? '') !== '' ? (int)$_POST['class_teacher_class'] : null;

$teaching_classes = $_POST['teaching_classes'] ?? [];
$subjects_for_class = $_POST['subjects_for_class'] ?? [];

if ($name === '' || $email === '' || $password_raw === '' || $specialization === '') {
    $_SESSION['error'] = "Please fill all required fields.";
    header("Location: add_teacher.php");
    exit;
}

if ($is_class_teacher && !$class_teacher_class) {
    $_SESSION['error'] = "Please select a class for Class Teacher.";
    header("Location: add_teacher.php");
    exit;
}

$conn->begin_transaction();

try {

    // 🔍 Check existing class teacher
    if ($is_class_teacher) {
        $check = $conn->prepare("SELECT COUNT(*) FROM class_teachers WHERE class_id = ?");
        $check->bind_param("i", $class_teacher_class);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if ($count > 0) {
            throw new Exception("Selected class already has a Class Teacher.");
        }
    }

    // 🧑 Insert teacher
    $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("
        INSERT INTO teachers (name, email, password, specialization, is_class_teacher)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssi", $name, $email, $password_hash, $specialization, $is_class_teacher);
    $stmt->execute();

    // 🔑 AUTO teacher ID
    $teacher_id = $conn->insert_id;
    $stmt->close();

    // 🏫 Class Teacher Mapping
    if ($is_class_teacher) {
        $stmt = $conn->prepare("
            INSERT INTO class_teachers (class_id, teacher_id)
            VALUES (?, ?)
        ");
        $stmt->bind_param("ii", $class_teacher_class, $teacher_id);
        $stmt->execute();
        $stmt->close();
    }

    // 📚 Subject Assignments
    $cst = $conn->prepare("
        INSERT INTO class_subject_teachers (class_id, subject_id, teacher_id)
        VALUES (?, ?, ?)
    ");

    $ts = $conn->prepare("
        INSERT IGNORE INTO teacher_subjects (teacher_id, subject_id)
        VALUES (?, ?)
    ");

    foreach ($teaching_classes as $cid) {
        $cid = (int)$cid;
        $subjects = $subjects_for_class[$cid] ?? [];

        foreach ($subjects as $sid) {
            $sid = (int)$sid;

            $cst->bind_param("iii", $cid, $sid, $teacher_id);
            $cst->execute();

            $ts->bind_param("ii", $teacher_id, $sid);
            $ts->execute();
        }
    }

    $cst->close();
    $ts->close();

    $conn->commit();
    $_SESSION['success'] = "Teacher added successfully!";
    header("Location: add_teacher.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
    header("Location: add_teacher.php");
    exit;
}
?>
