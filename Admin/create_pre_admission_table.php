<?php
/**
 * Create Pre-Admission Table
 * Run this once to setup the pre_admission table from payment_logs data
 */

include '../Database/db_connect.php';

// Create pre_admission table
$create_table_sql = "CREATE TABLE IF NOT EXISTS `pre_admission` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique identifier for pre-admission entry',
    `student_name` VARCHAR(150) NOT NULL COMMENT 'Full name of the student',
    `student_email` VARCHAR(100) NOT NULL COMMENT 'Email address of the student',
    `father_name` VARCHAR(150) COMMENT 'Father\'s full name',
    `mother_name` VARCHAR(150) COMMENT 'Mother\'s full name',
    `phone` VARCHAR(20) COMMENT 'Contact phone number',
    `gender` VARCHAR(20) COMMENT 'Student gender (Male, Female, Other)',
    `date_of_birth` DATE COMMENT 'Student date of birth',
    `address` TEXT COMMENT 'Residential address (City, District, Province)',
    `admission_fee` DECIMAL(10, 2) DEFAULT 100.00 COMMENT 'Admission fee amount',
    `application_status` VARCHAR(30) DEFAULT 'PENDING' COMMENT 'Status: PENDING, PROCESSING, APPROVED, REJECTED, PAID',
    `payment_status` VARCHAR(20) DEFAULT 'PENDING' COMMENT 'Payment status: PENDING, COMPLETE, FAILED',
    `transaction_uuid` VARCHAR(100) COMMENT 'Reference to payment_logs transaction',
    `notes` TEXT COMMENT 'Admin notes about the application',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When application was submitted',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time',
    KEY `idx_student_email` (`student_email`),
    KEY `idx_application_status` (`application_status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_transaction_uuid` (`transaction_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores student pre-admission applications'";

if ($conn->query($create_table_sql)) {
    echo "<p style='color: green;'><strong>✓ Table created successfully!</strong></p>";
} else {
    echo "<p style='color: red;'><strong>✗ Error creating table:</strong> " . $conn->error . "</p>";
}

// Now migrate data from payment_logs to pre_admission for those with COMPLETE status
echo "<hr>";
echo "<h2>Migrating Payment Data to Pre-Admission</h2>";

$migrate_sql = "INSERT INTO pre_admission 
                (student_name, student_email, admission_fee, payment_status, transaction_uuid, created_at, updated_at)
                SELECT student_name, student_email, amount, 'COMPLETE', transaction_uuid, created_at, updated_at
                FROM payment_logs
                WHERE status = 'COMPLETE' AND student_email NOT IN (SELECT student_email FROM pre_admission)";

if ($conn->query($migrate_sql)) {
    $affected = $conn->affected_rows;
    echo "<p style='color: green;'><strong>✓ Migrated $affected records from payment_logs to pre_admission!</strong></p>";
} else {
    echo "<p style='color: orange;'><strong>ℹ No new records to migrate or already migrated:</strong> " . $conn->error . "</p>";
}

// Show all data from pre_admission
echo "<hr>";
echo "<h2>Pre-Admission Applications</h2>";
$result = $conn->query("SELECT * FROM pre_admission ORDER BY created_at DESC");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Father</th><th>Payment Status</th><th>Amount</th><th>Created</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['student_email']) . "</td>";
        echo "<td>" . ($row['phone'] ?: 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['father_name'] ?: 'N/A') . "</td>";
        echo "<td><strong>" . $row['payment_status'] . "</strong></td>";
        echo "<td>" . $row['admission_fee'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No pre-admission applications found.</p>";
}

// Show payment_logs data
echo "<hr>";
echo "<h2>Payment Logs (Reference)</h2>";
$payment_result = $conn->query("SELECT * FROM payment_logs ORDER BY created_at DESC");

if ($payment_result && $payment_result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Name</th><th>Email</th><th>Amount</th><th>Status</th><th>Transaction UUID</th><th>Created</th>";
    echo "</tr>";
    
    while ($row = $payment_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['student_email']) . "</td>";
        echo "<td>" . $row['amount'] . "</td>";
        echo "<td><strong>" . $row['status'] . "</strong></td>";
        echo "<td>" . $row['transaction_uuid'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No payment logs found.</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pre-Admission Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }
        table {
            background: white;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        td, th {
            text-align: left;
        }
    </style>
</head>
<body>
</body>
</html>
