<?php
/**
 * Signature Verification Diagnostic Tool
 * Use this to test and debug signature verification issues
 */

include 'esewa_config.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eSewa Signature Verification Diagnostic</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #1e293b;
            color: #e2e8f0;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #0f172a;
            border: 2px solid #64748b;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        h1 {
            color: #60a5fa;
            margin-bottom: 20px;
            border-bottom: 2px solid #64748b;
            padding-bottom: 15px;
        }
        
        .section {
            margin-bottom: 30px;
            background: #1e293b;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #10b981;
        }
        
        .section.error {
            border-left-color: #ef4444;
            background: #7f1d1d;
        }
        
        .section.warning {
            border-left-color: #f59e0b;
            background: #78350f;
        }
        
        .section h2 {
            color: #60a5fa;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .info-box {
            background: #0f172a;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 10px;
            border: 1px solid #475569;
            word-break: break-all;
        }
        
        .label {
            color: #94a3b8;
            font-weight: bold;
            display: inline-block;
            width: 200px;
        }
        
        .value {
            color: #86efac;
        }
        
        .status-ok {
            color: #10b981;
            font-weight: bold;
        }
        
        .status-error {
            color: #ef4444;
            font-weight: bold;
        }
        
        button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            margin-top: 15px;
        }
        
        button:hover {
            background: #2563eb;
        }
        
        .test-form {
            background: #1e293b;
            padding: 20px;
            border-radius: 6px;
            margin-top: 15px;
        }
        
        textarea {
            width: 100%;
            height: 100px;
            background: #0f172a;
            color: #e2e8f0;
            border: 1px solid #475569;
            padding: 10px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .result {
            background: #0f172a;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
            border: 1px solid #475569;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🔐 eSewa Signature Verification Diagnostic Tool</h1>
    
    <!-- Configuration Check -->
    <div class="section">
        <h2>✓ Current Configuration</h2>
        <div class="info-box">
            <div><span class="label">Merchant Code:</span> <span class="value"><?php echo $merchant_code; ?></span></div>
            <div><span class="label">Secret Key:</span> <span class="value"><?php echo str_repeat('*', strlen($secret_key)-4) . substr($secret_key, -4); ?></span></div>
            <div><span class="label">Payment URL:</span> <span class="value"><?php echo $payment_url; ?></span></div>
            <div><span class="label">Status Check URL:</span> <span class="value"><?php echo $status_check_url; ?></span></div>
            <div><span class="label">Success URL:</span> <span class="value"><?php echo $success_url; ?></span></div>
        </div>
    </div>
    
    <!-- Status Check -->
    <div class="section">
        <h2>🧪 Test Environment Status</h2>
        <div class="info-box">
            <?php 
            // Check if we're using test environment
            if (strpos($merchant_code, 'TEST') !== false):
            ?>
                <div><span class="status-ok">✓ Test Environment ACTIVE</span></div>
                <div style="margin-top: 10px; color: #cbd5e1;">
                    Using test credentials. Use these for testing:
                    <div style="margin-top: 8px; background: #0f172a; padding: 10px; border-radius: 4px;">
                        eSewa ID: 9806800001-9806800005<br>
                        Password: Nepal@123<br>
                        OTP/Token: 123456
                    </div>
                </div>
            <?php else: ?>
                <div><span class="status-error">✗ Production Mode</span></div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Signature Test Form -->
    <div class="section">
        <h2>🔗 Test Signature Generation & Verification</h2>
        <p style="margin-bottom: 15px; color: #cbd5e1;">
            Use this to test if signatures are being generated and verified correctly:
        </p>
        
        <form method="POST" action="">
            <div>
                <label style="color: #cbd5e1;">Test Message (for signature generation):</label>
                <textarea name="test_message" placeholder="Example: total_amount=100,transaction_uuid=txn-123,product_code=EPAYTEST">total_amount=100,transaction_uuid=txn-123,product_code=EPAYTEST</textarea>
            </div>
            
            <button type="submit" name="test_signature">Generate Test Signature</button>
        </form>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_signature'])): ?>
            <?php
            $test_message = $_POST['test_message'];
            $test_signature = base64_encode(hash_hmac('sha256', $test_message, $secret_key, true));
            ?>
            <div class="result">
                <h3 style="color: #60a5fa; margin-bottom: 10px;">Signature Generation Result:</h3>
                <div><span class="label">Message:</span></div>
                <div class="info-box" style="margin-bottom: 10px;"><?php echo htmlspecialchars($test_message); ?></div>
                
                <div><span class="label">Generated Signature:</span></div>
                <div class="info-box"><?php echo $test_signature; ?></div>
                
                <p style="margin-top: 15px; color: #cbd5e1;">
                    This signature should match the one from eSewa if the message format is correct.
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Payment Logs Debug -->
    <div class="section">
        <h2>📊 Recent Payment Logs</h2>
        <?php
        $sql = "SELECT id, student_name, transaction_uuid, amount, status, created_at FROM payment_logs ORDER BY created_at DESC LIMIT 5";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
        ?>
                <div class="info-box" style="margin-bottom: 10px;">
                    <div><span class="label">ID:</span> <span class="value"><?php echo $row['id']; ?></span></div>
                    <div><span class="label">Student:</span> <span class="value"><?php echo htmlspecialchars($row['student_name']); ?></span></div>
                    <div><span class="label">UUID:</span> <span class="value"><?php echo $row['transaction_uuid']; ?></span></div>
                    <div><span class="label">Amount:</span> <span class="value">NPR <?php echo $row['amount']; ?></span></div>
                    <div><span class="label">Status:</span> <span class="value"><?php 
                        if ($row['status'] === 'COMPLETE') echo '<span class="status-ok">✓ COMPLETE</span>';
                        elseif ($row['status'] === 'PENDING') echo '<span style="color: #f59e0b;">⏳ PENDING</span>';
                        else echo '<span class="status-error">✗ ' . $row['status'] . '</span>';
                    ?></span></div>
                    <div><span class="label">Date:</span> <span class="value"><?php echo $row['created_at']; ?></span></div>
                </div>
        <?php 
            endwhile;
        else:
        ?>
            <div class="info-box">No payment logs found yet.</div>
        <?php endif; ?>
    </div>
    
    <!-- Troubleshooting Guide -->
    <div class="section warning">
        <h2>⚠️ Common Issues & Solutions</h2>
        <div style="color: #fbbf24;">
            <p><strong>Issue 1: Signature Verification Failed</strong></p>
            <p style="margin-left: 20px; margin-top: 8px; color: #cbd5e1;">
                ✓ Verify secret key matches eSewa settings<br>
                ✓ Ensure signed_field_names is included in message<br>
                ✓ Check message format (exact order: transaction_code, status, total_amount, transaction_uuid, product_code, signed_field_names)<br>
                ✓ Verify base64 encoding/decoding
            </p>
            
            <p style="margin-top: 15px;"><strong>Issue 2: Payment Not Showing as COMPLETE</strong></p>
            <p style="margin-left: 20px; margin-top: 8px; color: #cbd5e1;">
                ✓ Check if success page is being reached (success_url correct)<br>
                ✓ Verify database connection in success.php<br>
                ✓ Check PHP error logs for exceptions
            </p>
            
            <p style="margin-top: 15px;"><strong>Issue 3: eSewa Status Check API Fails</strong></p>
            <p style="margin-left: 20px; margin-top: 8px; color: #cbd5e1;">
                ✓ Verify curl/openssl is enabled in PHP<br>
                ✓ Check internet connectivity<br>
                ✓ Verify API endpoint URLs are correct
            </p>
        </div>
    </div>
    
    <!-- Manual Verification -->
    <div class="section">
        <h2>🔍 Manual Verification Steps</h2>
        <p style="color: #cbd5e1; margin-bottom: 15px;">
            If you're still getting signature errors, follow these steps:
        </p>
        <ol style="color: #cbd5e1; margin-left: 20px; line-height: 1.8;">
            <li>Go to phpMyAdmin → payment_logs table</li>
            <li>Find the payment with status PENDING (your failed payment)</li>
            <li>Copy the transaction_uuid</li>
            <li>Use the check_payment_status.php tool to verify with eSewa API</li>
            <li>If eSewa confirms COMPLETE, manually update database:
                <div style="background: #0f172a; padding: 10px; margin-top: 5px; border-radius: 4px;">
                    UPDATE payment_logs SET status='COMPLETE', ref_id='REF_ID' WHERE transaction_uuid='txn-xxx';
                </div>
            </li>
        </ol>
    </div>
</div>

<?php $conn->close(); ?>

</body>
</html>
