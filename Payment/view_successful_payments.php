<?php
/**
 * Admin Panel - View Successful Payments
 * Shows all payments with status = COMPLETE (successful)
 */

include '../Database/db_connect.php';

// Get all successful payments
$successful_payments = [];
$total_successful = 0;
$total_revenue = 0;

$sql = "SELECT 
    id,
    student_name,
    student_email,
    transaction_uuid,
    amount,
    status,
    ref_id,
    created_at,
    updated_at
FROM payment_logs 
WHERE status = 'COMPLETE'
ORDER BY created_at DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $successful_payments[] = $row;
        $total_successful++;
        $total_revenue += $row['amount'];
    }
}

// Get payment statistics
$stats_sql = "SELECT 
    COUNT(*) as total_payments,
    SUM(IF(status='COMPLETE', 1, 0)) as successful_count,
    SUM(IF(status='PENDING', 1, 0)) as pending_count,
    SUM(IF(status='FAILED', 1, 0)) as failed_count,
    SUM(IF(status='COMPLETE', amount, 0)) as successful_amount
FROM payment_logs";

$stats = $conn->query($stats_sql)->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Successful Payments - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #10b981;
        }
        
        .stat-card.pending {
            border-left-color: #f59e0b;
        }
        
        .stat-card.failed {
            border-left-color: #ef4444;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .stat-value {
            color: #1f2937;
            font-size: 32px;
            font-weight: 700;
        }
        
        .stat-subtext {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 8px;
        }
        
        .table-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #f9fafb;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table-header h2 {
            color: #1f2937;
            font-size: 18px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f9fafb;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #6b7280;
            font-size: 13px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        td {
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            color: #1f2937;
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #d1fae5;
            color: #065f46;
        }
        
        .amount {
            font-weight: 600;
            color: #10b981;
        }
        
        .ref-id {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }
        
        .button-group {
            padding: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-export {
            background: #3b82f6;
            color: white;
        }
        
        .btn-export:hover {
            background: #2563eb;
        }
        
        .btn-back {
            background: #e5e7eb;
            color: #374151;
        }
        
        .btn-back:hover {
            background: #d1d5db;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>✅ Successful Payments Dashboard</h1>
        <p>Real-time view of all confirmed payments in the system</p>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Payments</div>
            <div class="stat-value"><?php echo $stats['total_payments']; ?></div>
            <div class="stat-subtext">All transactions in system</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Successful (✅ COMPLETE)</div>
            <div class="stat-value"><?php echo $stats['successful_count']; ?></div>
            <div class="stat-subtext">Verified & confirmed payments</div>
        </div>
        
        <div class="stat-card pending">
            <div class="stat-label">Pending (⏳ PENDING)</div>
            <div class="stat-value"><?php echo $stats['pending_count']; ?></div>
            <div class="stat-subtext">Awaiting verification</div>
        </div>
        
        <div class="stat-card failed">
            <div class="stat-label">Failed (❌ FAILED)</div>
            <div class="stat-value"><?php echo $stats['failed_count']; ?></div>
            <div class="stat-subtext">Cancelled or rejected</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Total Successful Revenue</div>
            <div class="stat-value">NPR <?php echo number_format($stats['successful_amount'], 2); ?></div>
            <div class="stat-subtext">From completed payments</div>
        </div>
    </div>
    
    <!-- Successful Payments Table -->
    <div class="table-section">
        <div class="table-header">
            <h2>📊 All Successful Payments (Status: COMPLETE)</h2>
        </div>
        
        <?php if (count($successful_payments) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Amount</th>
                        <th>Transaction UUID</th>
                        <th>eSewa Ref ID</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($successful_payments as $payment): ?>
                        <tr>
                            <td>#<?php echo $payment['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($payment['student_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($payment['student_email']); ?></td>
                            <td class="amount">NPR <?php echo number_format($payment['amount'], 2); ?></td>
                            <td><code><?php echo substr($payment['transaction_uuid'], 0, 15) . '...'; ?></code></td>
                            <td><span class="ref-id"><?php echo htmlspecialchars($payment['ref_id']); ?></span></td>
                            <td><span class="status-badge">✅ <?php echo $payment['status']; ?></span></td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($payment['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <p>No successful payments yet. Payments will appear here once they are confirmed.</p>
            </div>
        <?php endif; ?>
        
        <div class="button-group">
            <a href="../../Admin/admin.php" class="btn btn-back">← Back to Admin</a>
        </div>
    </div>
</div>

<?php $conn->close(); ?>

</body>
</html>
