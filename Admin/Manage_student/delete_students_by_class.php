<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

include '../../Database/db_connect.php';

if (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
    $class_id = intval($_GET['class_id']);

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
        }
        
        // 4️⃣ Delete all students in that class
        $stmt = $conn->prepare("DELETE FROM students WHERE class_id=?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $stmt->close();
        
        // ✅ Commit transaction
        $conn->commit();
        header("Location: Managestudent.php?msg=" . urlencode("✅ All students and related data deleted successfully"));
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("❌ Error deleting students: " . $e->getMessage());
    }
} else {
    die("No class selected.");
}
?>
