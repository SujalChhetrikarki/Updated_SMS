<?php
/**
 * Reject Pre-Admission Application
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login_process.php');
    exit();
}

include '../Database/db_connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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

// Handle form submission
$rejection_reason = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rejection_reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    
    if (empty($rejection_reason)) {
        $error = "Please provide a reason for rejection.";
    } else {
        // Update application status to REJECTED
        $reason_db = $conn->real_escape_string($rejection_reason);
        $update_sql = "UPDATE pre_admission SET application_status = 'REJECTED', notes = CONCAT('Rejection Reason: ', '$reason_db'), updated_at = NOW() WHERE id = $id";
        
        if ($conn->query($update_sql)) {
            header('Location: PreAdmissions.php?status=rejected');
            exit();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reject Application</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #f3f4f6;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .card h1 {
            color: #ef4444;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .card p {
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        .info-block {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .info-block strong {
            display: block;
            color: #991b1b;
            margin-bottom: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-family: Arial, sans-serif;
            resize: vertical;
            min-height: 120px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-reject {
            background: #ef4444;
            color: white;
        }
        
        .btn-reject:hover {
            background: #dc2626;
        }
        
        .btn-cancel {
            background: #d1d5db;
            color: #1f2937;
        }
        
        .btn-cancel:hover {
            background: #9ca3af;
        }
        
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #ef4444;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h1>Reject Application</h1>
        <p>You are about to reject this pre-admission application.</p>
        
        <div class="info-block">
            <strong>Student Name:</strong>
            <?php echo htmlspecialchars($app['student_name']); ?>
        </div>
        
        <div class="info-block">
            <strong>Email:</strong>
            <?php echo htmlspecialchars($app['student_email']); ?>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="reason">Reason for Rejection <span style="color: #ef4444;">*</span></label>
                <textarea id="reason" name="reason" placeholder="Please provide a detailed reason for rejecting this application..." required><?php echo htmlspecialchars($rejection_reason); ?></textarea>
            </div>
            
            <div class="button-group">
                <a href="view_pre_admission.php?id=<?php echo $id; ?>" class="btn btn-cancel">Cancel</a>
                <button type="submit" class="btn btn-reject">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<?php $conn->close(); ?>

</body>
</html>
