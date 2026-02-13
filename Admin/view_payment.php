<?php
/**
 * View Payment Details
 * Display detailed information about a specific payment transaction
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login_process.php');
    exit();
}

include '../Database/db_connect.php';

// Get payment ID from URL
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($payment_id <= 0) {
    die("Invalid payment ID");
}

// Fetch payment details
$sql = "SELECT * FROM payment_logs WHERE id = $payment_id";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    die("Payment not found");
}

$payment = $result->fetch_assoc();

// Handle refund action
$refund_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'refund') {
    $refund_reason = isset($_POST['refund_reason']) ? trim($_POST['refund_reason']) : 'Admin refund request';
    
    // Update payment status to REFUNDED
    $update_sql = "UPDATE payment_logs SET 
                   status = 'REFUNDED', 
                   notes = CONCAT(IFNULL(notes, ''), '\n\nRefund: $refund_reason\nRefunded on: ' . NOW())
                   WHERE id = $payment_id";
    
    if ($conn->query($update_sql)) {
        $refund_message = "<div style='background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px;'><strong>✓ Payment refunded successfully!</strong></div>";
        
        // Refresh payment data
        $result = $conn->query($sql);
        $payment = $result->fetch_assoc();
    } else {
        $refund_message = "<div style='background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px;'><strong>✗ Error refunding payment:</strong> " . $conn->error . "</div>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #f3f4f6;
            color: #1f2937;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            font-size: 24px;
            color: #1f2937;
        }
        
        .back-btn {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: #2563eb;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            color: #1f2937;
            text-align: right;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-complete {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-refunded {
            background: #fef3c7;
            color: #92400e;
        }
        
        .amount-display {
            font-size: 24px;
            font-weight: 700;
            color: #10b981;
        }
        
        .code-block {
            background: #f3f4f6;
            padding: 12px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            word-break: break-all;
            color: #374151;
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
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-refund {
            background: #ef4444;
            color: white;
        }
        
        .btn-refund:hover {
            background: #dc2626;
        }
        
        .btn-print {
            background: #6b7280;
            color: white;
        }
        
        .btn-print:hover {
            background: #4b5563;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1f2937;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            color: #374151;
            font-size: 14px;
        }
        
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
            resize: vertical;
            min-height: 80px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-confirm {
            background: #ef4444;
            color: white;
        }
        
        .btn-confirm:hover {
            background: #dc2626;
        }
        
        .btn-cancel {
            background: #d1d5db;
            color: #1f2937;
        }
        
        .btn-cancel:hover {
            background: #9ca3af;
        }
        
        .notes-section {
            background: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            font-size: 13px;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>

<div class="container">
    
    <!-- Header -->
    <div class="header">
        <h1>Payment Details</h1>
        <a href="PreAdmissions.php" class="back-btn">← Back to Payments</a>
    </div>
    
    <?php echo $refund_message; ?>
    
    <!-- Student Information -->
    <div class="card">
        <h2>Student Information</h2>
        <div class="detail-row">
            <span class="detail-label">Student Name</span>
            <span class="detail-value"><?php echo htmlspecialchars($payment['student_name']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Email Address</span>
            <span class="detail-value"><a href="mailto:<?php echo htmlspecialchars($payment['student_email']); ?>" style="color: #3b82f6; text-decoration: none;"><?php echo htmlspecialchars($payment['student_email']); ?></a></span>
        </div>
    </div>
    
    <!-- Payment Information -->
    <div class="card">
        <h2>Payment Information</h2>
        <div class="detail-row">
            <span class="detail-label">Amount</span>
            <span class="detail-value" style="font-size: 20px; color: #10b981;"><strong>NPR <?php echo number_format($payment['amount'], 2); ?></strong></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Payment Status</span>
            <span class="detail-value">
                <span class="status-badge status-<?php echo strtolower($payment['status']); ?>">
                    <?php echo htmlspecialchars($payment['status']); ?>
                </span>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Payment Method</span>
            <span class="detail-value"><?php echo htmlspecialchars($payment['payment_method'] ?: 'N/A'); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Payment Date</span>
            <span class="detail-value"><?php echo date('M d, Y H:i:s', strtotime($payment['created_at'])); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Last Updated</span>
            <span class="detail-value"><?php echo date('M d, Y H:i:s', strtotime($payment['updated_at'])); ?></span>
        </div>
    </div>
    
    <!-- Transaction Details -->
    <div class="card">
        <h2>Transaction Details</h2>
        <div class="detail-row">
            <span class="detail-label">Transaction UUID</span>
            <span class="detail-value"></span>
        </div>
        <div style="margin-bottom: 15px;">
            <div class="code-block"><?php echo htmlspecialchars($payment['transaction_uuid']); ?></div>
        </div>
        <div class="detail-row">
            <span class="detail-label">eSewa Reference ID</span>
            <span class="detail-value"><?php echo htmlspecialchars($payment['ref_id'] ?: 'Pending'); ?></span>
        </div>
    </div>
    
    <!-- Notes -->
    <?php if (!empty($payment['notes'])): ?>
    <div class="card">
        <h2>Admin Notes</h2>
        <div class="notes-section"><?php echo htmlspecialchars($payment['notes']); ?></div>
    </div>
    <?php endif; ?>
    
    <!-- Actions -->
    <div class="card">
        <h2>Actions</h2>
        <div class="action-buttons">
            <button class="btn btn-print" onclick="window.print()">🖨️ Print Receipt</button>
            <?php if ($payment['status'] !== 'REFUNDED'): ?>
                <button class="btn btn-refund" onclick="openRefundModal()">↩️ Refund Payment</button>
            <?php else: ?>
                <span style="padding: 10px; color: #6b7280; font-size: 14px;">This payment has already been refunded.</span>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<!-- Refund Modal -->
<div id="refundModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">Refund Payment</div>
        <form method="POST">
            <input type="hidden" name="action" value="refund">
            <div class="form-group">
                <label>Refund Reason (Optional)</label>
                <textarea name="refund_reason" placeholder="Enter reason for refund..."></textarea>
            </div>
            <div class="modal-buttons">
                <button type="submit" class="btn-confirm">Confirm Refund</button>
                <button type="button" class="btn-cancel" onclick="closeRefundModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRefundModal() {
    if (confirm('Are you sure you want to refund this payment? This action cannot be undone.')) {
        document.getElementById('refundModal').classList.add('active');
    }
}

function closeRefundModal() {
    document.getElementById('refundModal').classList.remove('active');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('refundModal');
    if (event.target === modal) {
        modal.classList.remove('active');
    }
}
</script>

</body>
</html>

<?php $conn->close(); ?>
