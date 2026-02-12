<?php
/**
 * View Full Pre-Admission Details
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

$sql = "SELECT * FROM pre_admission WHERE id = $id";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    header('Location: PreAdmissions.php');
    exit();
}

$app = $result->fetch_assoc();

// Get payment info if exists
$payment_sql = "SELECT * FROM payment_logs WHERE student_email = '" . $conn->real_escape_string($app['student_email']) . "' ORDER BY created_at DESC LIMIT 1";
$payment_result = $conn->query($payment_sql);
$payment_info = $payment_result && $payment_result->num_rows > 0 ? $payment_result->fetch_assoc() : null;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application - <?php echo htmlspecialchars($app['student_name']); ?></title>
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
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .header a {
            background: #6b7280;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .header a:hover {
            background: #4b5563;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            background: #f9fafb;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: #1f2937;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            border-left: 3px solid #3b82f6;
            padding-left: 15px;
        }
        
        .detail-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 16px;
            color: #1f2937;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-paid {
            background: #ede9fe;
            color: #6d28d9;
        }
        
        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
        
        .btn-approve {
            background: #10b981;
            color: white;
        }
        
        .btn-approve:hover {
            background: #059669;
        }
        
        .btn-reject {
            background: #ef4444;
            color: white;
        }
        
        .btn-reject:hover {
            background: #dc2626;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 15px;
        }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            resize: vertical;
            min-height: 100px;
            font-family: Arial, sans-serif;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #1f2937;
        }
        
        .info-box {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .info-box.warning {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }
    </style>
</head>
<body>

<div class="container">
    
    <!-- Header -->
    <div class="header">
        <h1><?php echo htmlspecialchars($app['student_name']); ?></h1>
        <a href="PreAdmissions.php">← Back to List</a>
    </div>
    
    <!-- Personal Information -->
    <div class="card">
        <div class="card-header">Personal Information</div>
        <div class="card-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Student Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($app['student_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-value"><a href="mailto:<?php echo htmlspecialchars($app['student_email']); ?>" style="color: #3b82f6;">
                        <?php echo htmlspecialchars($app['student_email']); ?>
                    </a></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Phone Number</div>
                    <div class="detail-value"><?php echo htmlspecialchars($app['phone'] ?: 'N/A'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Date of Birth</div>
                    <div class="detail-value"><?php echo $app['date_of_birth'] ? date('F d, Y', strtotime($app['date_of_birth'])) : 'N/A'; ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Gender</div>
                    <div class="detail-value"><?php echo htmlspecialchars($app['gender'] ?: 'N/A'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Address</div>
                    <div class="detail-value"><?php echo htmlspecialchars($app['address'] ?: 'N/A'); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Father/Mother Information -->
    <div class="card">
        <div class="card-header">Guardian Information</div>
        <div class="card-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Father's Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($app['father_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Mother's Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($app['mother_name'] ?: 'N/A'); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Admission Information -->
    <div class="card">
        <div class="card-header">Admission Details</div>
        <div class="card-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Admission Fee</div>
                    <div class="detail-value">NPR <?php echo number_format($app['admission_fee'], 2); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Application Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?php echo strtolower($app['application_status']); ?>">
                            <?php echo $app['application_status']; ?>
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Payment Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?php echo strtolower($app['payment_status']); ?>">
                            <?php echo $app['payment_status']; ?>
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Application Date</div>
                    <div class="detail-value"><?php echo date('F d, Y H:i', strtotime($app['created_at'])); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Information -->
    <?php if ($payment_info): ?>
        <div class="card">
            <div class="card-header">Payment Transaction</div>
            <div class="card-body">
                <?php if ($payment_info['status'] === 'COMPLETE'): ?>
                    <div class="info-box">
                        Payment has been successfully completed for this student.
                    </div>
                <?php else: ?>
                    <div class="info-box warning">
                        Payment status: <strong><?php echo $payment_info['status']; ?></strong>
                    </div>
                <?php endif; ?>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Transaction UUID</div>
                        <div class="detail-value"><?php echo htmlspecialchars($payment_info['transaction_uuid']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Amount Paid</div>
                        <div class="detail-value">NPR <?php echo number_format($payment_info['amount'], 2); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Payment Status</div>
                        <div class="detail-value">
                            <span class="status-badge status-<?php echo strtolower($payment_info['status']); ?>">
                                <?php echo $payment_info['status']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Reference ID</div>
                        <div class="detail-value"><?php echo htmlspecialchars($payment_info['ref_id'] ?: 'Pending'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Payment Date</div>
                        <div class="detail-value"><?php echo date('F d, Y H:i', strtotime($payment_info['created_at'])); ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="info-box warning">
                    No payment transaction found for this student yet.
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Admin Notes & Actions -->
    <?php if ($app['application_status'] === 'PENDING'): ?>
        <div class="card">
            <div class="card-header">Admin Actions</div>
            <div class="card-body">
                <form method="POST" action="approve_pre_admission.php">
                    <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                    <div class="form-group">
                        <label>Add Admin Notes (Optional)</label>
                        <textarea name="notes"><?php echo htmlspecialchars($app['notes'] ?: ''); ?></textarea>
                    </div>
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this application?');">Approve Application</button>
                        <a href="reject_pre_admission.php?id=<?php echo $app['id']; ?>" class="btn btn-reject" onclick="return confirm('Reject this application?');">Reject Application</a>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">Admin Notes</div>
            <div class="card-body">
                <div class="detail-item">
                    <div class="detail-label">Current Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?php echo strtolower($app['application_status']); ?>">
                            <?php echo $app['application_status']; ?>
                        </span>
                    </div>
                </div>
                <?php if (!empty($app['notes'])): ?>
                    <div style="margin-top: 15px; padding: 15px; background: #f9fafb; border-radius: 6px;">
                        <div class="detail-label">Admin Notes</div>
                        <div style="color: #1f2937; margin-top: 8px;"><?php echo nl2br(htmlspecialchars($app['notes'])); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
</div>

<?php $conn->close(); ?>

</body>
</html>
