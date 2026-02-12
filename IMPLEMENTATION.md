IMPLEMENTATION.md - eSewa Payment Integration
1. Project Overview
This guide integrates the eSewa Payment Gateway (ePay V2) into the Student Management System.
Scenario: A student pays an admission fee after filling out the Pre-Registration form.
2. Prerequisites
eSewa Test Credentials:
Merchant Code (SCD): EPAYTEST
Secret Key: 8gBm/:&EnhH.1/q
Test URL: https://rc-epay.esewa.com.np/api/epay/main/v2/form
3. Database Setup
Run this SQL query in your XAMPP phpMyAdmin to create a table for storing transaction logs.
code
SQL
CREATE TABLE payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100),
    transaction_uuid VARCHAR(50) UNIQUE,
    amount DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'PENDING',
    ref_id VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
4. Directory Structure
Create a new folder named Payment inside your root directory (or inside PreRegistration).
code
Text
/StudentManagementSystem
  /Images
  /PreRegistration
  /Payment           <-- Create this folder
     ├── esewa_config.php
     ├── checkout.php
     ├── success.php
     └── failure.php
  index.php
5. PHP Implementation Codes
A. Configuration File (Payment/esewa_config.php)
This file holds the settings so you don't have to repeat them.
code
PHP
<?php
// Payment/esewa_config.php

// eSewa Test Environment Credentials
$merchant_code = "EPAYTEST"; 
$secret_key = "8gBm/:&EnhH.1/q"; 
$payment_url = "https://rc-epay.esewa.com.np/api/epay/main/v2/form";

// Success and Failure URLs (Adjust 'localhost' to your folder structure)
$success_url = "http://localhost/StudentManagementSystem/Payment/success.php";
$failure_url = "http://localhost/StudentManagementSystem/Payment/failure.php";
?>
B. The Checkout Page (Payment/checkout.php)
This file generates the required Signature and shows the "Pay with eSewa" button.
Note: In a real app, you would get the student_name and amount from the previous form submission via $_POST.
code
PHP
<?php
// Payment/checkout.php
include 'esewa_config.php';

// 1. Setup Invoice Details (Dynamic data from your form)
$transaction_uuid = uniqid("txn-", true); // Unique ID for this transaction
$product_code = "EPAYTEST";
$total_amount = 100.00; // Example: Admission Fee
$tax_amount = 0;
$product_service_charge = 0;
$product_delivery_charge = 0;

// 2. Generate the Signature (HMAC-SHA256)
// Formula: total_amount,transaction_uuid,product_code
$message = "total_amount=$total_amount,transaction_uuid=$transaction_uuid,product_code=$product_code";
$signature = base64_encode(hash_hmac('sha256', $message, $secret_key, true));

// 3. (Optional) Save "PENDING" status to database here
// $conn = new mysqli("localhost", "root", "", "your_db_name");
// $sql = "INSERT INTO payment_logs (transaction_uuid, amount, status) VALUES ('$transaction_uuid', '$total_amount', 'PENDING')";
// $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Payment</title>
    <style>
        body { font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0fdf4; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center; }
        .esewa-btn { background-color: #60bb46; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .esewa-btn:hover { background-color: #4da534; }
    </style>
</head>
<body>

<div class="card">
    <h2>Admission Fee Payment</h2>
    <p>Total Amount: <span style="color:green; font-weight:bold;">NPR <?php echo $total_amount; ?></span></p>
    
    <!-- eSewa Form -->
    <form action="<?php echo $payment_url; ?>" method="POST">
        <input type="hidden" name="amount" value="<?php echo $total_amount; ?>">
        <input type="hidden" name="tax_amount" value="0">
        <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
        <input type="hidden" name="transaction_uuid" value="<?php echo $transaction_uuid; ?>">
        <input type="hidden" name="product_code" value="<?php echo $product_code; ?>">
        <input type="hidden" name="product_service_charge" value="0">
        <input type="hidden" name="product_delivery_charge" value="0">
        <input type="hidden" name="success_url" value="<?php echo $success_url; ?>">
        <input type="hidden" name="failure_url" value="<?php echo $failure_url; ?>">
        <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
        <input type="hidden" name="signature" value="<?php echo $signature; ?>">
        
        <button type="submit" class="esewa-btn">Pay with eSewa</button>
    </form>
</div>

</body>
</html>
C. The Success Handler (Payment/success.php)
This page handles the redirect from eSewa. It verifies the signature to ensure the payment is genuine.
code
PHP
<?php
// Payment/success.php
include 'esewa_config.php';

// 1. Get Response Data
$data = $_GET['data']; // eSewa sends a base64 encoded string

// 2. Decode Data
$json = base64_decode($data);
$response = json_decode($json, true);

// Extract details
$status = $response['status'];
$signature_received = $response['signature'];
$transaction_code = $response['transaction_code']; // eSewa Ref ID
$transaction_uuid = $response['transaction_uuid'];
$total_amount = $response['total_amount'];

// 3. Verify Signature (Security Check)
$message = "transaction_code=$transaction_code,status=$status,total_amount=$total_amount,transaction_uuid=$transaction_uuid,product_code=EPAYTEST,signed_field_names=transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names";
$signature_generated = base64_encode(hash_hmac('sha256', $message, $secret_key, true));

if ($status === "COMPLETE" && $signature_received === $signature_generated) {
    // SUCCESS: Update Database
    // $conn = new mysqli("localhost", "root", "", "your_db_name");
    // $sql = "UPDATE payment_logs SET status='COMPLETE', ref_id='$transaction_code' WHERE transaction_uuid='$transaction_uuid'";
    // $conn->query($sql);

    echo "<div style='text-align:center; padding:50px;'>";
    echo "<h1 style='color:green;'>Payment Successful!</h1>";
    echo "<p>Ref ID: $transaction_code</p>";
    echo "<a href='../index.php'>Return to Home</a>";
    echo "</div>";
} else {
    // FRAUD DETECTED OR ERROR
    echo "Payment verification failed.";
}
?>
D. The Failure Handler (Payment/failure.php)
code
PHP
<?php
echo "<div style='text-align:center; padding:50px;'>";
echo "<h1 style='color:red;'>Payment Failed or Cancelled</h1>";
echo "<a href='../index.php'>Return to Home</a>";
echo "</div>";
?>
6. Integrating into your workflow
Open your PreRegistration/preregistration.php file.
In the <form> tag where students submit their details, change the action attribute.
Option A: Submit to a database saving script first, then redirect to ../Payment/checkout.php.
Option B (Simple): Set <form action="../Payment/checkout.php" method="POST">.
7. Testing
Open your browser and navigate to the Pre-Admission page.
Click the Payment/Submit button.
On the eSewa Login page, use these credentials:
ID: 9806800001
Password: Nepal@123
Token: 123456
Key Notes for your XAMPP Setup:
Ensure cURL or OpenSSL is enabled in php.ini (usually enabled by default in XAMPP) because hash_hmac requires it.
If your folder name in htdocs is not StudentManagementSystem, update the URLs in esewa_config.php accordingly.