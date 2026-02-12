<?php
/**
 * Admin Pre-Admission Applications Dashboard
 * View and manage all student pre-admission form submissions
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login_process.php');
    exit();
}

include '../Database/db_connect.php';

// Get filter parameters
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT * FROM pre_admission WHERE 1=1";

if ($status_filter !== 'all') {
    $status_filter = $conn->real_escape_string($status_filter);
    $sql .= " AND application_status = '$status_filter'";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (student_name LIKE '%$search%' OR student_email LIKE '%$search%' OR phone LIKE '%$search%')";
}

$sql .= " ORDER BY created_at DESC";

$result = $conn->query($sql);

// Get statistics
$stats_sql = "SELECT 
                application_status,
                COUNT(*) as count
              FROM pre_admission
              GROUP BY application_status";
$stats_result = $conn->query($stats_sql);
$stats = array();
while ($row = $stats_result->fetch_assoc()) {
    $stats[$row['application_status']] = $row['count'];
}

// Get total stats
$total_sql = "SELECT COUNT(*) as total FROM pre_admission";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_applications = $total_row['total'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Admission Applications - Admin</title>
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            font-size: 28px;
            color: #1f2937;
        }
        
        .header a {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .header a:hover {
            background: #2563eb;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid #3b82f6;
        }
        
        .stat-card.pending {
            border-left-color: #f59e0b;
        }
        
        .stat-card.approved {
            border-left-color: #10b981;
        }
        
        .stat-card.rejected {
            border-left-color: #ef4444;
        }
        
        .stat-card.paid {
            border-left-color: #8b5cf6;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filters input {
            flex: 1;
            min-width: 200px;
            padding: 10px 15px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .filters select {
            padding: 10px 15px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .filters button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .filters button:hover {
            background: #2563eb;
        }
        
        .table-wrapper {
            background: white;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        tbody tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-paid {
            background: #ede9fe;
            color: #6d28d9;
        }
        
        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .btn-view {
            background: #3b82f6;
            color: white;
        }
        
        .btn-view:hover {
            background: #2563eb;
        }
        
        .btn-approve {
            background: #10b981;
            color: white;
        }
        
        .btn-approve:hover {
            background: #059669;
        }
        
        .btn-reject {
            background: #ef4444;
            color: white;
        }
        
        .btn-reject:hover {
            background: #dc2626;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }
        
        .email-link {
            color: #3b82f6;
            text-decoration: none;
        }
        
        .email-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    
    <!-- Header -->
    <div class="header">
        <h1>Pre-Admission Applications</h1>
        <a href="admin.php">← Back to Admin Panel</a>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_applications; ?></div>
            <div class="stat-label">Total Applications</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-number"><?php echo isset($stats['PENDING']) ? $stats['PENDING'] : 0; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card processing">
            <div class="stat-number"><?php echo isset($stats['PROCESSING']) ? $stats['PROCESSING'] : 0; ?></div>
            <div class="stat-label">Processing</div>
        </div>
        <div class="stat-card approved">
            <div class="stat-number"><?php echo isset($stats['APPROVED']) ? $stats['APPROVED'] : 0; ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card paid">
            <div class="stat-number"><?php echo isset($stats['PAID']) ? $stats['PAID'] : 0; ?></div>
            <div class="stat-label">Payment Complete</div>
        </div>
        <div class="stat-card rejected">
            <div class="stat-number"><?php echo isset($stats['REJECTED']) ? $stats['REJECTED'] : 0; ?></div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters">
        <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center; width: 100%;">
            <input type="text" name="search" placeholder="Search by name, email, or phone..." 
                   value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 200px;">
            <select name="status">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="PENDING" <?php echo $status_filter === 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                <option value="PROCESSING" <?php echo $status_filter === 'PROCESSING' ? 'selected' : ''; ?>>Processing</option>
                <option value="APPROVED" <?php echo $status_filter === 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                <option value="PAID" <?php echo $status_filter === 'PAID' ? 'selected' : ''; ?>>Payment Complete</option>
                <option value="REJECTED" <?php echo $status_filter === 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
            </select>
            <button type="submit">Search</button>
            <a href="PreAdmissions.php" style="background: #9ca3af; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; border: none; cursor: pointer;">
                Clear Filters
            </a>
        </form>
    </div>
    
    <!-- Applications Table -->
    <div class="table-wrapper">
        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Father's Name</th>
                        <th>DOB</th>
                        <th>Gender</th>
                        <th>Application Status</th>
                        <th>Payment Status</th>
                        <th>Applied Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($app = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($app['student_name']); ?></td>
                            <td><a href="mailto:<?php echo htmlspecialchars($app['student_email']); ?>" class="email-link">
                                <?php echo htmlspecialchars($app['student_email']); ?>
                            </a></td>
                            <td><?php echo htmlspecialchars($app['phone'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($app['father_name']); ?></td>
                            <td><?php echo $app['date_of_birth'] ? date('M d, Y', strtotime($app['date_of_birth'])) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($app['gender'] ?: 'N/A'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($app['application_status']); ?>">
                                    <?php echo $app['application_status']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($app['payment_status']); ?>">
                                    <?php echo $app['payment_status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($app['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_pre_admission.php?id=<?php echo $app['id']; ?>" class="btn-small btn-view">View</a>
                                    <?php if ($app['application_status'] === 'PENDING'): ?>
                                        <a href="approve_pre_admission.php?id=<?php echo $app['id']; ?>" class="btn-small btn-approve" onclick="return confirm('Approve this application?');">Approve</a>
                                        <a href="reject_pre_admission.php?id=<?php echo $app['id']; ?>" class="btn-small btn-reject" onclick="return confirm('Reject this application?');">Reject</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <p>No pre-admission applications found.</p>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<?php $conn->close(); ?>

</body>
</html>
