<?php
/**
 * Approve Pre-Admission Application
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login_process.php');
    exit();
}

include '../Database/db_connect.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if ($id <= 0) {
    header('Location: PreAdmissions.php');
    exit();
}

// Get application details
$sql = "SELECT * FROM pre_admission WHERE id = $id";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    header('Location: PreAdmissions.php');
    exit();
}

$app = $result->fetch_assoc();

// Update application status to APPROVED
$notes_db = $conn->real_escape_string($notes);
$update_sql = "UPDATE pre_admission SET application_status = 'APPROVED', notes = '$notes_db', updated_at = NOW() WHERE id = $id";

if ($conn->query($update_sql)) {
    // Redirect to view page with success message
    header('Location: view_pre_admission.php?id=' . $id . '&status=approved');
    exit();
} else {
    echo '<div style="text-align:center; padding:50px;"><h2 style="color:red;">Error</h2><p>' . $conn->error . '</p><a href="view_pre_admission.php?id=' . $id . '">Back</a></div>';
}

$conn->close();
?>
