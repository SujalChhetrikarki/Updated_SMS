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
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'created_at';
$order = isset($_GET['order']) ? trim($_GET['order']) : 'DESC';

// Validate sort and order
$allowed_sorts = ['id', 'student_name', 'student_email', 'application_status', 'payment_status', 'created_at'];
$allowed_orders = ['ASC', 'DESC'];
if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
if (!in_array($order, $allowed_orders)) $order = 'DESC';

// Build query
$sql = "SELECT * FROM pre_admission WHERE 1=1";

if ($status_filter !== 'all') {
    $status_filter = $conn->real_escape_string($status_filter);
    $sql .= " AND application_status = '$status_filter'";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (student_name LIKE '%$search%' OR student_email LIKE '%$search%')";
}

$sql .= " ORDER BY $sort $order";

$result = $conn->query($sql);
if (!$result) {
    die("Query Error: " . $conn->error . "<br>Query: " . $sql);
}

// Get statistics
$stats_sql = "SELECT 
                application_status,
                COUNT(*) as count
              FROM pre_admission
              GROUP BY application_status";
$stats_result = $conn->query($stats_sql);
$stats = array();
if ($stats_result) {
    while ($row = $stats_result->fetch_assoc()) {
        $stats[$row['application_status']] = $row['count'];
    }
}

// Get total stats
$total_sql = "SELECT COUNT(*) as total FROM pre_admission";
$total_result = $conn->query($total_sql);
$total_applications = 0;
if ($total_result) {
    $total_row = $total_result->fetch_assoc();
    $total_applications = $total_row['total'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Admission Applications - Admin</title>
    <style>
        /* ===== Reset & Body ===== */
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            display: flex;
            min-height: 100vh;
            color: #333;
        }

        /* ===== Sidebar ===== */
        .sidebar {
            width: 240px;
            background: #1f2937;
            color: #fff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 30px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 22px;
            color: #3b82f6;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            margin: 4px 12px;
            background: #374151;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .sidebar a:hover { background: #3b82f6; color: #fff; }
        .sidebar a.active { background: #3b82f6; }
        .sidebar a.logout { background: #ef4444; }
        .sidebar a.logout:hover { background: #f87171; }

        /* ===== Main Content ===== */
        .main {
            margin-left: 240px;
            padding: 20px 30px;
            flex: 1;
        }

        /* ===== Header ===== */
        .header {
            background: #fff;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #111;
        }
        .header-actions {
            display: flex;
            gap: 12px;
        }
        .header-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            background: #3b82f6;
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: 0.3s;
        }
        .header-btn:hover { background: #2563eb; }

        /* ===== Dashboard Cards ===== */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background: #fff;
            padding: 22px;
            border-radius: 16px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid #3b82f6;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
        }
        .card h3 {
            margin-bottom: 12px;
            font-size: 16px;
            color: #666;
            font-weight: 500;
        }
        .card p {
            font-size: 28px;
            font-weight: 700;
            color: #3b82f6;
            margin: 0;
        }
        .card.pending { border-left-color: #f59e0b; }
        .card.pending p { color: #f59e0b; }
        .card.approved { border-left-color: #10b981; }
        .card.approved p { color: #10b981; }
        .card.rejected { border-left-color: #ef4444; }
        .card.rejected p { color: #ef4444; }

        /* ===== Filters Section ===== */
        .filters {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filters input,
        .filters select {
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
        }
        .filters input {
            flex: 1;
            min-width: 200px;
        }
        .filters button,
        .filters a {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            background: #3b82f6;
            color: #fff;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: 0.3s;
            font-size: 14px;
        }
        .filters button:hover,
        .filters a:hover { background: #2563eb; }
        .filters .clear-btn { background: #9ca3af; }
        .filters .clear-btn:hover { background: #6b7280; }

        /* ===== Table Section ===== */
        .table-wrapper {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
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
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        tbody tr:hover {
            background: #f9fafb;
        }

        /* ===== Status Badges ===== */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
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
        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        /* ===== Action Buttons ===== */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-weight: 500;
            border: 1px solid transparent;
        }
        .btn-view {
            background: #3b82f6;
            color: #fff;
        }
        .btn-view:hover {
            background: #2563eb;
        }
        .btn-approve {
            background: #10b981;
            color: #fff;
        }
        .btn-approve:hover {
            background: #059669;
        }
        .btn-reject {
            background: #ef4444;
            color: #fff;
        }
        .btn-reject:hover {
            background: #dc2626;
        }

        /* ===== No Data ===== */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
            font-size: 16px;
        }

        /* ===== Email Link ===== */
        .email-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        .email-link:hover {
            text-decoration: underline;
        }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main {
                margin-left: 200px;
                padding: 15px;
            }
            .header {
                flex-direction: column;
                gap: 15px;
            }
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }
            .filters {
                flex-direction: column;
            }
            .filters input {
                min-width: auto;
            }
            table {
                font-size: 12px;
            }
            td, th {
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar Navigation -->
<div class="sidebar">
    <h2>📋 Pre-Admissions</h2>
    <a href="index.php">🏠 Back to Dashboard</a>
    <a href="PreAdmissions.php" class="active">📝 View Applications</a>
    <a href="view_pre_admission.php">👁 View Details</a>
    <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="main">
    <!-- Header -->
    <div class="header">
        <h1>📝 Pre-Admission Applications</h1>
        <div class="header-actions">
            <a href="index.php" class="header-btn">← Back to Admin</a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="cards">
        <div class="card">
            <h3>Total Applications</h3>
            <p><?php echo $total_applications; ?></p>
        </div>
        <div class="card pending">
            <h3>Pending</h3>
            <p><?php echo isset($stats['PENDING']) ? $stats['PENDING'] : 0; ?></p>
        </div>
        <div class="card approved">
            <h3>Approved</h3>
            <p><?php echo isset($stats['APPROVED']) ? $stats['APPROVED'] : 0; ?></p>
        </div>
        <div class="card rejected">
            <h3>Rejected</h3>
            <p><?php echo isset($stats['REJECTED']) ? $stats['REJECTED'] : 0; ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; width: 100%;">
            <input type="text" name="search" placeholder="🔍 Search by name or email..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <select name="status">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="PENDING" <?php echo $status_filter === 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                <option value="APPROVED" <?php echo $status_filter === 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                <option value="REJECTED" <?php echo $status_filter === 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
            </select>
            <button type="submit">🔎 Search</button>
            <a href="PreAdmissions.php" class="clear-btn">↺ Clear</a>
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
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Fee (NPR)</th>
                        <th>Applied Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($app = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($app['student_name']); ?></strong></td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($app['student_email']); ?>" class="email-link">
                                    <?php echo htmlspecialchars($app['student_email']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($app['phone'] ?: 'N/A'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($app['application_status']); ?>">
                                    <?php echo htmlspecialchars($app['application_status'] ?: 'PENDING'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($app['payment_status']); ?>">
                                    <?php echo htmlspecialchars($app['payment_status']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($app['admission_fee'], 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_pre_admission.php?id=<?php echo $app['id']; ?>" class="btn-small btn-view">👁 View</a>
                                    <?php if ($app['application_status'] !== 'APPROVED'): ?>
                                        <a href="approve_pre_admission.php?id=<?php echo $app['id']; ?>" class="btn-small btn-approve">✓ Approve</a>
                                    <?php endif; ?>
                                    <?php if ($app['application_status'] !== 'REJECTED'): ?>
                                        <a href="reject_pre_admission.php?id=<?php echo $app['id']; ?>" class="btn-small btn-reject">✕ Reject</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <p>📭 No pre-admission applications found.</p>
            </div>
        <?php endif; ?>
    </div>

