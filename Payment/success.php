<?php
/**
 * eSewa Payment Success Handler
 * This page receives payment confirmation from eSewa
 * It verifies the signature and updates the database
 */

include 'esewa_config.php';

$status_message = '';
$payment_status = 'FAILED';
$transaction_code = '';
$transaction_uuid = '';
$total_amount = 0;
$student_info = '';

try {
    // 1. Get Response Data from eSewa (Base64 encoded)
    if (!isset($_GET['data'])) {
        throw new Exception("No payment data received from eSewa");
    }
    
    $data_encoded = $_GET['data'];
    
    // 2. Decode the Base64 encoded response
    $json = base64_decode($data_encoded);
    $response = json_decode($json, true);
    
    if (!$response) {
        throw new Exception("Invalid payment response format");
    }
    
    // Extract payment details from eSewa response
    $status = isset($response['status']) ? $response['status'] : '';
    $signature_received = isset($response['signature']) ? $response['signature'] : '';
    $transaction_code = isset($response['transaction_code']) ? $response['transaction_code'] : '';
    $transaction_uuid = isset($response['transaction_uuid']) ? $response['transaction_uuid'] : '';
    $total_amount = isset($response['total_amount']) ? $response['total_amount'] : 0;
    $product_code = isset($response['product_code']) ? $response['product_code'] : '';
    $signed_field_names = isset($response['signed_field_names']) ? $response['signed_field_names'] : 'transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names';
    
    // 3. Reconstruct the signature verification message
    // CRITICAL: Build message using the exact order specified in signed_field_names
    // This ensures we match eSewa's signature generation exactly
    $fields_to_sign = explode(',', $signed_field_names);
    $message_parts = array();
    
    foreach ($fields_to_sign as $field) {
        $field = trim($field);
        if ($field === 'transaction_code') {
            $message_parts[] = "transaction_code=$transaction_code";
        } elseif ($field === 'status') {
            $message_parts[] = "status=$status";
        } elseif ($field === 'total_amount') {
            $message_parts[] = "total_amount=$total_amount";
        } elseif ($field === 'transaction_uuid') {
            $message_parts[] = "transaction_uuid=$transaction_uuid";
        } elseif ($field === 'product_code') {
            $message_parts[] = "product_code=$product_code";
        } elseif ($field === 'signed_field_names') {
            $message_parts[] = "signed_field_names=$signed_field_names";
        }
    }
    
    $message = implode(',', $message_parts);
    
    // 4. Generate signature on our end for verification
    $signature_generated = base64_encode(hash_hmac('sha256', $message, $secret_key, true));
    
    // 5. Verify the signature (Security Check)
    if ($status === "COMPLETE" && $signature_received === $signature_generated) {
        // ✓ SIGNATURE VERIFIED - Payment is genuine
        
        // Update database with successful payment
        $payment_status = 'COMPLETE';
        
        $sql = "UPDATE payment_logs 
                SET status='COMPLETE', ref_id='$transaction_code' 
                WHERE transaction_uuid='$transaction_uuid'";
        
        if (!$conn->query($sql)) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        // Update pre-admission payment status
        $sql_pre_admission = "UPDATE pre_admission 
                              SET payment_status='COMPLETE', application_status='PAID', updated_at=NOW() 
                              WHERE transaction_uuid='$transaction_uuid'";
        $conn->query($sql_pre_admission); // Non-critical update
        
        // Fetch student details for confirmation
        $sql = "SELECT student_name, student_email FROM payment_logs WHERE transaction_uuid='$transaction_uuid' LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $student_info = $row['student_name'] . ' (' . $row['student_email'] . ')';
        }
        
        $status_message = "Payment verified successfully. Your admission fee has been received.";
        
    } else if ($status === "COMPLETE" && $signature_received !== $signature_generated) {
        // Signature mismatch - log for debugging
        $debug_info = "\n" . str_repeat("=", 50) . "\n";
        $debug_info .= "SIGNATURE VERIFICATION FAILED\n";
        $debug_info .= str_repeat("=", 50) . "\n";
        $debug_info .= "eSewa Response Data:\n";
        $debug_info .= "  Transaction Code: $transaction_code\n";
        $debug_info .= "  Transaction UUID: $transaction_uuid\n";
        $debug_info .= "  Status: $status\n";
        $debug_info .= "  Amount: $total_amount\n";
        $debug_info .= "  Product Code: $product_code\n";
        $debug_info .= "  Signed Fields: $signed_field_names\n\n";
        $debug_info .= "Signature Verification:\n";
        $debug_info .= "  Message built: $message\n";
        $debug_info .= "  Signature from eSewa: $signature_received\n";
        $debug_info .= "  Signature we generated: $signature_generated\n";
        $debug_info .= "  Secret Key: " . (hash_equals($secret_key, '8gBm/:&EnhH.1/q') ? 'EPAYTEST (Test Environment)' : 'CUSTOM (Production?)') . "\n";
        $debug_info .= "  Secret Key Used: $secret_key\n";
        $debug_info .= "  Raw Response: " . json_encode($response) . "\n";
        $debug_info .= str_repeat("=", 50) . "\n";
        error_log($debug_info);
        throw new Exception("Signature verification failed. Payment could not be verified.");

    } else if ($status === "PENDING") {
        // Transaction is in pending state
        $payment_status = 'PENDING';
        $status_message = "Your payment is pending. Please wait for confirmation.";
        
        // Update database
        $sql = "UPDATE payment_logs 
                SET status='PENDING', ref_id='$transaction_code' 
                WHERE transaction_uuid='$transaction_uuid'";
        $conn->query($sql);
        
    } else {
        // Signature verification failed - potential fraud
        throw new Exception("Signature verification failed. Payment could not be verified.");
    }
    
} catch (Exception $e) {
    $status_message = $e->getMessage();
    $payment_status = 'FAILED';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment <?php echo ucfirst($payment_status); ?> - eSewa</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .result-container {
            background: white;
            padding: 50px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .result-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .result-status {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .result-status.success {
            color: #10b981;
        }
        
        .result-status.pending {
            color: #f59e0b;
        }
        
        .result-status.failed {
            color: #ef4444;
        }
        
        .result-message {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .result-details {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-row label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .detail-row value {
            color: #1f2937;
            font-weight: 600;
            word-break: break-all;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background-color: #d1d5db;
        }
        
        .alert-box {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: left;
            color: #991b1b;
        }
    </style>
</head>
<body>

<div class="result-container">
    
    <?php if ($payment_status === 'COMPLETE'): ?>
        <div class="result-status success">Payment Successful!</div>
        <div class="result-message">
            Your admission fee payment has been received and verified successfully. 
            Your registration is now complete.
        </div>
        
        <div class="result-details">
            <div class="detail-row">
                <label>Student:</label>
                <value><?php echo htmlspecialchars($student_info ?: 'N/A'); ?></value>
            </div>
            <div class="detail-row">
                <label>Amount Paid:</label>
                <value>NPR <?php echo number_format($total_amount, 2); ?></value>
            </div>
            <div class="detail-row">
                <label>Transaction UUID:</label>
                <value><?php echo htmlspecialchars($transaction_uuid); ?></value>
            </div>
            <div class="detail-row">
                <label>Reference ID:</label>
                <value><?php echo htmlspecialchars($transaction_code); ?></value>
            </div>
            <div class="detail-row">
                <label>Date & Time:</label>
                <value><?php echo date('Y-m-d H:i:s'); ?></value>
            </div>
        </div>
        
        <div class="button-group">
            <a href="../index.php" class="btn btn-primary">Return to Home</a>
            <a href="../Students/Student.php" class="btn btn-secondary">Student Portal</a>
        </div>
        
    <?php elseif ($payment_status === 'PENDING'): ?>
        <div class="result-icon">⏳</div>
        <div class="result-status pending">Payment Pending</div>
        <div class="result-message">
            Your payment is still processing. Please wait for confirmation from eSewa.
            You will receive a confirmation email shortly.
        </div>
        
        <div class="result-details">
            <div class="detail-row">
                <label>Transaction UUID:</label>
                <value><?php echo htmlspecialchars($transaction_uuid); ?></value>
            </div>
            <div class="detail-row">
                <label>Amount:</label>
                <value>NPR <?php echo number_format($total_amount, 2); ?></value>
            </div>
        </div>
        
        <div class="button-group">
            <a href="../index.php" class="btn btn-primary">Return to Home</a>
        </div>
        
    <?php else: ?>
        <div class="result-icon">❌</div>
        <div class="result-status failed">Payment Failed</div>
        
        <div class="alert-box">
            <strong>Error:</strong> <?php echo htmlspecialchars($status_message); ?>
        </div>
        
        <div class="result-message">
            Unfortunately, your payment could not be processed. 
            Please try again or contact our support team if the problem persists.
        </div>
        
        <div class="result-details">
            <div class="detail-row">
                <label>Transaction UUID:</label>
                <value><?php echo htmlspecialchars($transaction_uuid ?: 'N/A'); ?></value>
            </div>
            <div class="detail-row">
                <label>Error Message:</label>
                <value><?php echo htmlspecialchars($status_message); ?></value>
            </div>
        </div>
        
        <div class="button-group">
            <a href="../Admin/PreRegistration/preregistration.php" class="btn btn-primary">Try Again</a>
            <a href="../index.php" class="btn btn-secondary">Return to Home</a>
        </div>
    
    <?php endif; ?>
    
</div>

<?php $conn->close(); ?>

</body>
</html>
