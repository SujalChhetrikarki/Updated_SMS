<?php
session_start();
include '../../Database/db_connect.php';

// ✅ Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit;
}

// ✅ Validate student_id
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    die("❌ Error: Student ID missing or invalid.");
}

$student_id = $_GET['student_id']; // Example: "N1"

// ✅ Check if student exists
$stmt_check = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
$stmt_check->bind_param("s", $student_id);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows === 0) {
    die("⚠️ Student not found.");
}

// ✅ Begin transaction
$conn->begin_transaction();

try {
    // 1️⃣ (Optional) Delete related results if they exist
    if ($conn->query("SHOW TABLES LIKE 'results'")->num_rows > 0) {
        $stmt_results = $conn->prepare("DELETE FROM results WHERE student_id = ?");
        $stmt_results->bind_param("s", $student_id);
        $stmt_results->execute();
    }

    // 2️⃣ Delete student from students table
    $stmt_delete = $conn->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt_delete->bind_param("s", $student_id);
    $stmt_delete->execute();

    // ✅ Commit transaction
    $conn->commit();

    // ✅ Redirect with success message
    header("Location: Managestudent.php?msg=" . urlencode("✅ Student deleted successfully."));
    exit;

} catch (Exception $e) {
    // ❌ Rollback on error
    $conn->rollback();
    die("❌ Error deleting student: " . $e->getMessage());
}
?>
