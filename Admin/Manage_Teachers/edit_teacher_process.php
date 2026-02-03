<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include '../../Database/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Teachersshow.php");
    exit;
}

$original_teacher_id = $_POST['original_teacher_id'];
$new_teacher_id = trim($_POST['teacher_id']);
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$password = trim($_POST['password']);
$specialization = trim($_POST['specialization']);
$is_class_teacher = isset($_POST['is_class_teacher']);
$class_teacher_class = $_POST['class_teacher_class'] ?? null;

$teaching_classes = $_POST['teaching_classes'] ?? [];
$subjects_for_class = $_POST['subjects_for_class'] ?? [];

// 1️⃣ Fetch old teacher data
$stmt = $conn->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
$stmt->bind_param("s", $original_teacher_id);
$stmt->execute();
$old_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$old_data) {
    $_SESSION['error'] = "Teacher not found!";
    header("Location: Teachersshow.php");
    exit;
}

// 2️⃣ Update teacher info if changed
$changes = [];
$params = [];
$types = '';

if ($new_teacher_id !== $old_data['teacher_id']) { $changes[]="teacher_id=?"; $params[]=$new_teacher_id; $types.="s"; }
if ($name !== $old_data['name']) { $changes[]="name=?"; $params[]=$name; $types.="s"; }
if ($email !== $old_data['email']) { $changes[]="email=?"; $params[]=$email; $types.="s"; }
if (!empty($password)) { $hashed = password_hash($password,PASSWORD_DEFAULT); $changes[]="password=?"; $params[]=$hashed; $types.="s"; }
if ($specialization !== $old_data['specialization']) { $changes[]="specialization=?"; $params[]=$specialization; $types.="s"; }

if(!empty($changes)){
    $sql="UPDATE teachers SET ".implode(",",$changes)." WHERE teacher_id=?";
    $params[] = $original_teacher_id;
    $types.="s";
    $upd = $conn->prepare($sql);
    $upd->bind_param($types,...$params);
    $upd->execute();
    $upd->close();
}

// 3️⃣ Handle Class Teacher
$conn->query("DELETE FROM class_teachers WHERE teacher_id='".$conn->real_escape_string($original_teacher_id)."'");
if($is_class_teacher && !empty($class_teacher_class)){
    $ins = $conn->prepare("INSERT INTO class_teachers (teacher_id,class_id) VALUES (?,?)");
    $ins->bind_param("ss",$new_teacher_id,$class_teacher_class);
    $ins->execute();
    $ins->close();
}

// 4️⃣ Handle Teaching Classes & Subjects
$existing = [];
$q = $conn->prepare("SELECT class_id, subject_id FROM class_subject_teachers WHERE teacher_id=?");
$q->bind_param("s",$original_teacher_id);
$q->execute();
$res=$q->get_result();
while($r=$res->fetch_assoc()) $existing[$r['class_id']][]=$r['subject_id'];
$q->close();

// Remove deselected
foreach($existing as $cid=>$slist){
    foreach($slist as $sid){
        if(!isset($subjects_for_class[$cid]) || !in_array($sid,$subjects_for_class[$cid])){
            $del=$conn->prepare("DELETE FROM class_subject_teachers WHERE teacher_id=? AND class_id=? AND subject_id=?");
            $del->bind_param("sss",$original_teacher_id,$cid,$sid);
            $del->execute(); $del->close();
        }
    }
}

// Add new
foreach($subjects_for_class as $cid=>$slist){
    foreach($slist as $sid){
        if(!isset($existing[$cid]) || !in_array($sid,$existing[$cid])){
            $ins=$conn->prepare("INSERT INTO class_subject_teachers (teacher_id,class_id,subject_id) VALUES (?,?,?)");
            $ins->bind_param("sss",$new_teacher_id,$cid,$sid);
            $ins->execute(); $ins->close();
        }
    }
}

// 5️⃣ If teacher_id changed, update references
if($new_teacher_id!==$original_teacher_id){
    $conn->query("UPDATE class_subject_teachers SET teacher_id='$new_teacher_id' WHERE teacher_id='$original_teacher_id'");
    $conn->query("UPDATE class_teachers SET teacher_id='$new_teacher_id' WHERE teacher_id='$original_teacher_id'");
}

$_SESSION['success']="Teacher updated successfully!";
header("Location: edit_teacher.php?teacher_id=".urlencode($new_teacher_id));
exit;
