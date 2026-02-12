<?php
/**
 * eSewa Payment Configuration
 * This file contains all the configuration settings for eSewa ePay integration
 * 
 * Test Environment Credentials:
 * - Merchant Code: EPAYTEST
 * - Secret Key: 8gBm/:&EnhH.1/q
 * - Test URL: https://rc-epay.esewa.com.np/api/epay/main/v2/form
 */

// eSewa Test Environment Credentials
$merchant_code = "EPAYTEST"; 
$secret_key = "8gBm/:&EnhH.1/q"; 
$payment_url = "https://rc-epay.esewa.com.np/api/epay/main/v2/form";

// Status Check API URL (for verifying transactions)
$status_check_url = "https://rc.esewa.com.np/api/epay/transaction/status/";

// Success and Failure URLs (Update 'localhost' according to your server/domain)
$site_url = "http://localhost/Student_Management_System"; // Change this in production
$success_url = $site_url . "/Payment/success.php";
$failure_url = $site_url . "/Payment/failure.php";

// Database connection
include '../Database/db_connect.php';
?>
