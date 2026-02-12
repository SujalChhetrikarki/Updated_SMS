<?php
/**
 * eSewa Payment Failure Handler
 * This page handles payment failures, cancellations, or errors
 */

include 'esewa_config.php';

$error_reason = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : 'Payment was cancelled or failed to process.';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - eSewa</title>
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
        
        .failure-container {
            background: white;
            padding: 50px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .failure-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .failure-title {
            font-size: 28px;
            font-weight: bold;
            color: #ef4444;
            margin-bottom: 15px;
        }
        
        .failure-message {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .alert-box {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .alert-title {
            font-weight: 600;
            color: #991b1b;
            margin-bottom: 8px;
        }
        
        .alert-content {
            color: #7f1d1d;
            font-size: 14px;
            word-break: break-word;
        }
        
        .suggestions {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .suggestions-title {
            font-weight: 600;
            color: #166534;
            margin-bottom: 10px;
        }
        
        .suggestions-list {
            list-style: none;
            color: #1b7234;
            font-size: 14px;
        }
        
        .suggestions-list li {
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }
        
        .suggestions-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
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
        
        .support-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>

<div class="failure-container">
    
    <div class="failure-icon">❌</div>
    <div class="failure-title">Payment Failed or Cancelled</div>
    
    <div class="failure-message">
        We were unable to process your payment. Your pre-registration is not yet complete. 
        Please review the options below and try again.
    </div>
    
    <!-- Error Information -->
    <div class="alert-box">
        <div class="alert-title">Why Your Payment Failed:</div>
        <div class="alert-content">
            <?php 
            if (!empty($error_reason)) {
                echo $error_reason;
            } else {
                echo "Either you cancelled the transaction or eSewa encountered an error. Your account has not been charged.";
            }
            ?>
        </div>
    </div>
    
    <!-- Helpful Suggestions -->
    <div class="suggestions">
        <div class="suggestions-title">What You Can Do:</div>
        <ul class="suggestions-list">
            <li>Check your eSewa account balance and ensure sufficient funds</li>
            <li>Verify your internet connection is stable</li>
            <li>Ensure your eSewa credentials are correct</li>
            <li>Try again in a few minutes if there was a connection timeout</li>
            <li>Contact support if the problem persists</li>
        </ul>
    </div>
    
    <!-- Action Buttons -->
    <div class="button-group">
        <a href="../Admin/PreRegistration/preregistration.php" class="btn btn-primary">Try Payment Again</a>
        <a href="../index.php" class="btn btn-secondary">Return to Home</a>
    </div>
    
    <!-- Support Information -->
    <div class="support-info">
        <p>If you continue to experience issues, please contact our support team.</p>
        <p>For eSewa-related issues, visit: <a href="https://developer.esewa.com.np/" target="_blank" style="color: #3b82f6;">https://developer.esewa.com.np/</a></p>
    </div>
</div>

</body>
</html>
