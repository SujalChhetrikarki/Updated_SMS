<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}
include '../../Database/db_connect.php';

if (isset($_GET['id'])) {
    $class_id = intval($_GET['id']);
    
    // ✅ Start transaction for cascading deletes
    $conn->begin_transaction();
    
    try {
        // 1️⃣ Get all students in this class
        $stmt_students = $conn->prepare("SELECT student_id FROM students WHERE class_id = ?");
        $stmt_students->bind_param("i", $class_id);
        $stmt_students->execute();
        $students_result = $stmt_students->get_result();
        $student_ids = [];
        while ($row = $students_result->fetch_assoc()) {
            $student_ids[] = $row['student_id'];
        }
        $stmt_students->close();
        
        // 2️⃣ Delete attendance records for all students in this class
        if (!empty($student_ids)) {
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
            $types = str_repeat('s', count($student_ids));
            $stmt_att = $conn->prepare("DELETE FROM attendance WHERE student_id IN ($placeholders)");
            $stmt_att->bind_param($types, ...$student_ids);
            $stmt_att->execute();
            $stmt_att->close();
            
            // 3️⃣ Delete results for all students in this class
            $stmt_res = $conn->prepare("DELETE FROM results WHERE student_id IN ($placeholders)");
            $stmt_res->bind_param($types, ...$student_ids);
            $stmt_res->execute();
            $stmt_res->close();
            
            // 4️⃣ Delete all students in this class
            $stmt_del_std = $conn->prepare("DELETE FROM students WHERE class_id = ?");
            $stmt_del_std->bind_param("i", $class_id);
            $stmt_del_std->execute();
            $stmt_del_std->close();
        }
        
        // 5️⃣ Delete exams for this class
        $stmt_exams = $conn->prepare("DELETE FROM exams WHERE class_id = ?");
        $stmt_exams->bind_param("i", $class_id);
        $stmt_exams->execute();
        $stmt_exams->close();
        
        // 6️⃣ Delete class_subject_teachers records for this class
        $stmt_cst = $conn->prepare("DELETE FROM class_subject_teachers WHERE class_id = ?");
        $stmt_cst->bind_param("i", $class_id);
        $stmt_cst->execute();
        $stmt_cst->close();
        
        // 7️⃣ Delete class_teachers records for this class
        $stmt_ct = $conn->prepare("DELETE FROM class_teachers WHERE class_id = ?");
        $stmt_ct->bind_param("i", $class_id);
        $stmt_ct->execute();
        $stmt_ct->close();
        
        // 8️⃣ Delete subjects for this class
        $stmt_subj = $conn->prepare("DELETE FROM subjects WHERE class_id = ?");
        $stmt_subj->bind_param("i", $class_id);
        $stmt_subj->execute();
        $stmt_subj->close();
        
        // 9️⃣ Finally, delete the class itself
        $stmt = $conn->prepare("DELETE FROM classes WHERE class_id = ?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $stmt->close();
        
        // ✅ Commit transaction
        $conn->commit();
        $_SESSION['success'] = "✅ Class and all related data deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "❌ Error: " . $e->getMessage();
    }
}
header("Location: Classes.php");
exit;
