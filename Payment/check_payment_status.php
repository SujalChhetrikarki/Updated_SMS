<?php
/**
 * Payment Status Checker - Admin Utility
 * Allows admins to check payment status for pending transactions
 * Can verify payments with eSewa if no response was received initially
 */

include 'esewa_config.php';

$check_result = null;
$error_message = '';

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transaction_uuid'])) {
    $transaction_uuid = trim($_POST['transaction_uuid']);
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    
    if (empty($transaction_uuid) || $amount <= 0) {
        $error_message = "Invalid transaction UUID or amount.";
    } else {
        // Check local database first
        $sql = "SELECT * FROM payment_logs WHERE transaction_uuid = '" . $conn->real_escape_string($transaction_uuid) . "' LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $local_record = $result->fetch_assoc();
            
            if ($local_record['status'] === 'COMPLETE') {
                // Already marked as complete
                $check_result = array(
                    'source' => 'LOCAL',
                    'status' => 'COMPLETE',
                    'data' => $local_record,
                    'message' => 'Payment already verified and confirmed in system.'
                );
            } else if ($local_record['status'] === 'PENDING') {
                // Check with eSewa API for status update
                $status_check_url = $status_check_url . "?product_code=EPAYTEST&total_amount=" . $amount . "&transaction_uuid=" . urlencode($transaction_uuid);
                
                // Make cURL request to eSewa
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $status_check_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For test environment
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code === 200) {
                    $esewa_response = json_decode($response, true);
                    
                    if ($esewa_response && isset($esewa_response['status'])) {
                        // Update database if status changed
                        if ($esewa_response['status'] === 'COMPLETE') {
                            $update_sql = "UPDATE payment_logs SET status='COMPLETE', ref_id='" . $conn->real_escape_string($esewa_response['ref_id']) . "' WHERE transaction_uuid='" . $conn->real_escape_string($transaction_uuid) . "'";
                            $conn->query($update_sql);
                        }
                        
                        $check_result = array(
                            'source' => 'ESEWA_API',
                            'status' => $esewa_response['status'],
                            'data' => $esewa_response,
                            'message' => 'Status verified from eSewa API.'
                        );
                    } else {
                        $error_message = "Invalid response from eSewa API.";
                    }
                } else {
                    $error_message = "Could not connect to eSewa API (HTTP " . $http_code . "). Database shows: " . $local_record['status'];
                    $check_result = array(
                        'source' => 'LOCAL_FALLBACK',
                        'status' => $local_record['status'],
                        'data' => $local_record,
                        'message' => 'eSewa API unreachable. Showing local record.'
                    );
                }
            }
        } else {
            $error_message = "Transaction UUID not found in records.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status Checker - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #f0f2f5;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #6b7280;
            font-size: 14px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 24px;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            align-self: flex-end;
        }
        
        .btn:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }
        
        .alert-success {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            color: #166534;
        }
        
        .alert-info {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            color: #1e40af;
        }
        
        .result-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .result-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-complete {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .source-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .detail-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #6b7280;
        }
        
        .detail-value {
            color: #1f2937;
            word-break: break-all;
        }
        
        .button-group {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background-color: #d1d5db;
        }
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .detail-row {
                grid-template-columns: 1fr;
            }
            
            .detail-label::after {
                content: ": ";
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>💳 Payment Status Checker</h1>
        <p>Check and verify payment status for pending transactions</p>
    </div>
    
    <div class="card">
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="transaction_uuid">Transaction UUID *</label>
                    <input type="text" id="transaction_uuid" name="transaction_uuid" 
                           placeholder="e.g., txn-6793a8c3b1d9c2" required>
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount (NPR) *</label>
                    <input type="number" id="amount" name="amount" step="0.01" 
                           placeholder="e.g., 100.00" required>
                </div>
            </div>
            
            <button type="submit" class="btn">Check Status</button>
        </form>
    </div>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($check_result): ?>
        <div class="card">
            <div class="result-box">
                <div class="result-header">
                    <div>
                        <div class="result-title">Payment Status Result</div>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span class="source-badge">Source: <?php echo $check_result['source']; ?></span>
                        <span class="status-badge status-<?php echo strtolower($check_result['status']); ?>">
                            <?php echo $check_result['status']; ?>
                        </span>
                    </div>
                </div>
                
                <div style="color: #6b7280; font-size: 14px; margin-bottom: 20px;">
                    <?php echo $check_result['message']; ?>
                </div>
                
                <div>
                    <div class="detail-row">
                        <span class="detail-label">Transaction UUID</span>
                        <span class="detail-value"><?php echo htmlspecialchars($check_result['data']['transaction_uuid'] ?? 'N/A'); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <span class="status-badge status-<?php echo strtolower($check_result['data']['status'] ?? 'pending'); ?>">
                                <?php echo htmlspecialchars($check_result['data']['status'] ?? 'PENDING'); ?>
                            </span>
                        </span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Amount</span>
                        <span class="detail-value">NPR <?php echo number_format($check_result['data']['total_amount'] ?? $check_result['data']['amount'] ?? 0, 2); ?></span>
                    </div>
                    
                    <?php if (isset($check_result['data']['ref_id']) && !empty($check_result['data']['ref_id'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Reference ID</span>
                        <span class="detail-value"><?php echo htmlspecialchars($check_result['data']['ref_id']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($check_result['data']['student_name'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Student Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($check_result['data']['student_name']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($check_result['data']['student_email'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($check_result['data']['student_email']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($check_result['data']['created_at'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Initiated At</span>
                        <span class="detail-value"><?php echo htmlspecialchars($check_result['data']['created_at']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="location.reload();">Check Another</button>
                <a href="../../Admin/admin.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="card" style="background: #f0f9ff; border-left: 4px solid #3b82f6;">
        <h3 style="color: #1e40af; margin-bottom: 10px;">ℹ️ How to Use</h3>
        <ol style="color: #1e40af; margin-left: 20px; line-height: 1.8;">
            <li>Enter the <strong>Transaction UUID</strong> from the pre-registration payment attempt</li>
            <li>Enter the <strong>Amount</strong> that was to be paid</li>
            <li>Click <strong>Check Status</strong></li>
            <li>This will check your database first, then eSewa API if needed</li>
            <li>If status is PENDING, it will query eSewa for the latest status</li>
            <li>Database is automatically updated if eSewa confirms payment</li>
        </ol>
    </div>
</div>

<?php $conn->close(); ?>

</body>
</html>
