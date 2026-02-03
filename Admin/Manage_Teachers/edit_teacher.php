<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include '../../Database/db_connect.php';

if (!isset($_GET['teacher_id'])) {
    header("Location: Teachersshow.php");
    exit;
}

$teacher_id = $_GET['teacher_id'];

// Fetch teacher info
$sql = "SELECT * FROM teachers WHERE teacher_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();
if (!$teacher) die("Teacher not found.");

// Fetch class teacher (single class if any)
$class_teacher_class = null;
$ct = $conn->prepare("SELECT class_id FROM class_teachers WHERE teacher_id = ?");
$ct->bind_param("s", $teacher_id);
$ct->execute();
$res_ct = $ct->get_result();
if ($row = $res_ct->fetch_assoc()) {
    $class_teacher_class = $row['class_id'];
}
$ct->close();

// Fetch mapping: class_id => [subject_id, ...] from class_subject_teachers
$assigned_subjects_for_class = [];
$map = $conn->prepare("SELECT class_id, subject_id FROM class_subject_teachers WHERE teacher_id = ?");
$map->bind_param("s", $teacher_id);
$map->execute();
$res_map = $map->get_result();
while ($r = $res_map->fetch_assoc()) {
    $cid = $r['class_id'];
    $sid = $r['subject_id'];
    if (!isset($assigned_subjects_for_class[$cid])) $assigned_subjects_for_class[$cid] = [];
    $assigned_subjects_for_class[$cid][] = $sid;
}
$map->close();

// Determine teaching classes (distinct class ids from class_subject_teachers)
$teaching_classes = array_keys($assigned_subjects_for_class);

// Fetch all classes & subjects
$all_classes = $conn->query("SELECT class_id, class_name FROM classes");
$all_subjects = [];
$subjectQuery = $conn->query("SELECT subject_id, subject_name FROM subjects");
while ($s = $subjectQuery->fetch_assoc()) {
    $all_subjects[] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Teacher</title>
<style>
<style>
/* Global */
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }

body {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    align-items: center;
}

/* Header (now full width) */
#header {
    width: 100%;
    height: 60px;
    background: #00bfff;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: bold;
    box-shadow: 0 2px 10px rgba(0,0,0,0.15);
    position: fixed;
    top: 0;
    left: 0;
    z-index: 100;
}

/* Container centered below header */
.container {
    background: #fff;
    padding: 35px 40px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    width: 900px;
    margin-top: 100px; /* below header */
    margin-bottom: 40px;
    animation: fadeIn 0.8s ease-in-out;
}

.container h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #333;
}

/* Messages */
.msg { text-align:center; margin-bottom:15px; font-weight:bold; }
.error { color:#dc3545; }
.success { color:#28a745; }

/* Form styling */
form label { display:block; margin-top:15px; font-weight:bold; color:#555; }
form input, form select {
    width:100%;
    padding:12px;
    margin-top:8px;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:14px;
    transition: 0.3s;
}
form input:focus, form select:focus {
    border:1px solid #4facfe;
    box-shadow: 0 0 8px rgba(79,172,254,0.3);
}
form input[type="checkbox"] { width:auto; margin-right:8px; }

form button {
    width:100%;
    padding:14px;
    margin-top:25px;
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    color:#fff;
    font-size:16px;
    font-weight:bold;
    border:none;
    border-radius:8px;
    cursor:pointer;
    transition:0.3s;
}
form button:hover {
    background: linear-gradient(135deg, #00f2fe, #4facfe);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Subjects section */
.class-box {
    border:1px solid #ddd;
    padding:15px;
    border-radius:10px;
    margin-bottom:15px;
    background:#f9f9f9;
}
.class-title { font-weight:bold; margin-bottom:10px; }
.subjects-list label { display:block; font-weight:normal; margin-bottom:5px; }

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>


<div id="header">Edit Teacher</div>

<div class="container">
    <h2>Edit Teacher — <?= htmlspecialchars($teacher['name']) ?></h2>

    <?php if(isset($_SESSION['error'])): ?>
        <p class="msg error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>
    <?php if(isset($_SESSION['success'])): ?>
        <p class="msg success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
    <?php endif; ?>

    <form action="edit_teacher_process.php" method="POST" id="editTeacherForm">
        <input type="hidden" name="original_teacher_id" value="<?= htmlspecialchars($teacher_id) ?>">

        <input type="text" name="teacher_id" placeholder="Teacher ID" value="<?= htmlspecialchars($teacher['teacher_id']) ?>" required>
        <input type="text" name="name" placeholder="Full Name" value="<?= htmlspecialchars($teacher['name']) ?>" required>
        <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($teacher['email']) ?>" required>
        <input type="password" name="password" placeholder="Password (leave blank to keep current)">
        <input type="text" name="specialization" placeholder="Specialization" value="<?= htmlspecialchars($teacher['specialization']) ?>" required>

        <label>
            <input type="checkbox" id="is_class_teacher" name="is_class_teacher" value="1" <?= $class_teacher_class ? 'checked' : '' ?>>
            Make this teacher a Class Teacher
        </label>

        <div id="class_teacher_select" style="display:<?= $class_teacher_class ? 'block' : 'none' ?>; margin-top:10px;">
            <label for="class_teacher_class">Select Class:</label>
            <select name="class_teacher_class" id="class_teacher_class">
                <option value="">-- Select Class --</option>
                <?php
                $classQuery = $conn->query("SELECT class_id, class_name FROM classes");
                while ($row = $classQuery->fetch_assoc()) {
                    $cid = $row['class_id'];
                    $sel = ($class_teacher_class && $class_teacher_class == $cid) ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($cid, ENT_QUOTES) . "' $sel>" . htmlspecialchars($row['class_name']) . "</option>";
                }
                ?>
            </select>
        </div>

        <label>Assign Subjects to Classes:</label>
        <div class="class-subject-container">
            <?php
            $classes = $conn->query("SELECT class_id, class_name FROM classes");
            while ($class = $classes->fetch_assoc()) {
                $cid = $class['class_id'];
                $isTeaching = in_array($cid, $teaching_classes, true);
                echo "<div class='class-box'>";
                echo "<div class='class-title'><label><input type='checkbox' class='teaching_class_checkbox' name='teaching_classes[]' value='" . htmlspecialchars($cid, ENT_QUOTES) . "' " . ($isTeaching ? 'checked' : '') . "> " . htmlspecialchars($class['class_name']) . "</label></div>";
                echo "<div class='subjects-list' style='margin-left:18px; margin-top:6px;'>";
                foreach ($all_subjects as $sub) {
    $sid = $sub['subject_id'];
    $checked = '';
    if (isset($assigned_subjects_for_class[$cid]) && in_array($sid, $assigned_subjects_for_class[$cid], false)) {
        $checked = 'checked';
    }
    echo "<label><input type='checkbox' class='subject-checkbox' data-class='{$cid}' name='subjects_for_class[{$cid}][]' value='" . htmlspecialchars($sid, ENT_QUOTES) . "' $checked> " . htmlspecialchars($sub['subject_name']) . "</label>";
}

                echo "</div>";
                echo "</div>";
            }
            ?>
        </div>

        <button type="submit">💾 Update Teacher</button>
        <a href="Teachersshow.php" style="display:block; text-align:center; margin-top:15px; color:#007bff; text-decoration:none;">← Back to Teacher List</a>
    </form>
</div>

<script>
const classTeacherCheckbox = document.getElementById('is_class_teacher');
const classTeacherSelectDiv = document.getElementById('class_teacher_select');
const classTeacherSelect = document.getElementById('class_teacher_class');

classTeacherCheckbox.addEventListener('change', function() {
    classTeacherSelectDiv.style.display = this.checked ? 'block' : 'none';
    if (!this.checked) classTeacherSelect.value = '';
});

document.querySelectorAll('.subject-checkbox').forEach(function(cb) {
    cb.addEventListener('change', function() {
        const classId = this.getAttribute('data-class');
        if (this.checked) {
            const classBox = document.querySelector('.class-box input.teaching_class_checkbox[value="' + classId + '"]');
            if (classBox && !classBox.checked) classBox.checked = true;
        }
    });
});

// Optional validation: ensure class checkbox if subjects selected
document.getElementById('editTeacherForm').addEventListener('submit', function(e) {
    const classBoxes = document.querySelectorAll('.class-box');
    let valid = true;

    classBoxes.forEach(box => {
        const classCheckbox = box.querySelector('.teaching_class_checkbox');
        const subjectChecks = box.querySelectorAll('input.subject-checkbox[data-class]');
        let anySubjectChecked = false;

        subjectChecks.forEach(s => { 
            if(s.checked) anySubjectChecked = true; 
        });

        // If any subject is selected but class is not checked, invalid
        if(anySubjectChecked && !classCheckbox.checked) {
            valid = false;
            classCheckbox.focus(); // optional: focus on the checkbox
        }
    });

    if(!valid){
        e.preventDefault();
        alert("If you select subjects for a class, make sure the corresponding class checkbox is checked.");
    }
});

</script>

</body>
</html>
