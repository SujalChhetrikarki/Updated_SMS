<?php
/**
 * eSewa Payment Checkout Page
 * This page displays the payment form and generates HMAC-SHA256 signature
 * User is redirected here after submitting pre-registration form
 */

include 'esewa_config.php';

// Get data from POST (from preregistration form)
$student_name = isset($_POST['student_name']) ? trim($_POST['student_name']) : '';
$student_email = isset($_POST['student_email']) ? trim($_POST['student_email']) : '';
$admission_fee = isset($_POST['admission_fee']) ? floatval($_POST['admission_fee']) : 100.00;

// If no data provided, show error
if (empty($student_name) || empty($student_email)) {
    die('<div style="text-align:center; padding:50px;"><h2 style="color:red;">Invalid Request</h2><p>Pre-registration data not found.</p><a href="../Admin/PreRegistration/preregistration.php">Back to Registration</a></div>');
}

// 1. Setup Invoice Details for eSewa
$transaction_uuid = uniqid("txn-", true); // Unique ID for this transaction
$product_code = "EPAYTEST"; // eSewa Merchant Code
$total_amount = $admission_fee;
$tax_amount = 0;
$product_service_charge = 0;
$product_delivery_charge = 0;

// 2. Generate the Signature (HMAC-SHA256)
// Formula: total_amount,transaction_uuid,product_code
$message = "total_amount=$total_amount,transaction_uuid=$transaction_uuid,product_code=$product_code";
$signature = base64_encode(hash_hmac('sha256', $message, $secret_key, true));

// 3. Save "PENDING" status to database
$student_name_db = $conn->real_escape_string($student_name);
$student_email_db = $conn->real_escape_string($student_email);

$sql = "INSERT INTO payment_logs 
        (student_name, student_email, transaction_uuid, amount, status, created_at) 
        VALUES 
        ('$student_name_db', '$student_email_db', '$transaction_uuid', '$total_amount', 'PENDING', NOW())";

if (!$conn->query($sql)) {
    die('<div style="text-align:center; padding:50px;"><h2 style="color:red;">Database Error</h2><p>' . $conn->error . '</p></div>');
}

// Update pre-admission record with transaction UUID
$update_pre_admission_sql = "UPDATE pre_admission 
                             SET transaction_uuid = '$transaction_uuid', payment_status = 'PENDING', updated_at = NOW() 
                             WHERE student_email = '$student_email_db' 
                             ORDER BY created_at DESC LIMIT 1";
$conn->query($update_pre_admission_sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Payment - eSewa</title>
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
        
        .payment-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .payment-header h2 {
            color: #1f2937;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .payment-header p {
            color: #6b7280;
            font-size: 14px;
        }
        
        .student-info {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3b82f6;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .info-row label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .info-row value {
            color: #1f2937;
            font-weight: 600;
        }
        
        .amount-section {
            background: #f0fdf4;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 2px solid #dcfce7;
        }
        
        .amount-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .amount-row.total {
            border-top: 2px solid #dcfce7;
            padding-top: 10px;
            font-size: 18px;
            font-weight: bold;
            color: #059669;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-esewa {
            background-color: #60bb46;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-esewa:hover {
            background-color: #52ad3a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(96, 187, 70, 0.3);
        }
        
        .btn-cancel {
            background-color: #e5e7eb;
            color: #374151;
        }
        
        .btn-cancel:hover {
            background-color: #d1d5db;
        }
        
        .esewa-logo {
            width: 20px;
            height: 20px;
            display: inline-block;
        }
        
        .security-note {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #9ca3af;
        }
        
        .security-note::before {
            content: "🔒 ";
            margin-right: 5px;
        }
    </style>
</head>
<body>

<div class="payment-container">
    <div class="payment-header">
        <h2>Payment Confirmation</h2>
        <p>Secure payment gateway powered by eSewa</p>
    </div>
    
    <!-- Student Information -->
    <div class="student-info">
        <div class="info-row">
            <label>Student Name:</label>
            <value><?php echo htmlspecialchars($student_name); ?></value>
        </div>
        <div class="info-row">
            <label>Email:</label>
            <value><?php echo htmlspecialchars($student_email); ?></value>
        </div>
    </div>
    
    <!-- Amount Details -->
    <div class="amount-section">
        <div class="amount-row">
            <span>Admission Fee:</span>
            <span>NPR <?php echo number_format($admission_fee, 2); ?></span>
        </div>
        <div class="amount-row">
            <span>Tax:</span>
            <span>NPR 0.00</span>
        </div>
        <div class="amount-row total">
            <span>TOTAL PAYABLE:</span>
            <span>NPR <?php echo number_format($total_amount, 2); ?></span>
        </div>
    </div>
    
    <!-- eSewa Payment Form -->
    <form action="<?php echo $payment_url; ?>" method="POST" id="esewaForm">
        <!-- Hidden form fields required by eSewa -->
        <input type="hidden" name="amount" value="<?php echo $admission_fee; ?>">
        <input type="hidden" name="tax_amount" value="<?php echo $tax_amount; ?>">
        <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
        <input type="hidden" name="transaction_uuid" value="<?php echo $transaction_uuid; ?>">
        <input type="hidden" name="product_code" value="<?php echo $product_code; ?>">
        <input type="hidden" name="product_service_charge" value="<?php echo $product_service_charge; ?>">
        <input type="hidden" name="product_delivery_charge" value="<?php echo $product_delivery_charge; ?>">
        <input type="hidden" name="success_url" value="<?php echo $success_url; ?>">
        <input type="hidden" name="failure_url" value="<?php echo $failure_url; ?>">
        <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
        <input type="hidden" name="signature" value="<?php echo $signature; ?>">
        
        <!-- Buttons -->
        <div class="button-group">
            <button type="submit" class="btn btn-esewa">Pay with eSewa</button>
            <button type="button" class="btn btn-cancel" onclick="window.history.back();">Cancel</button>
        </div>
    </form>
    
    <div class="security-note">
        You will be redirected to eSewa to complete your payment securely.
    </div>
</div>

</body>
</html>
