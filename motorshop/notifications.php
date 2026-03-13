<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$adminName = $_SESSION['username'] ?? 'Name';
$adminEmail = 'Email'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - ServiceHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --sidebar-bg: #101623;
            --sidebar-hover: #1f2937;
            --primary-orange: #FF7A00;
            --bg-light: #f9fafb;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
        }

        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            flex-shrink: 0;
            background-color: var(--sidebar-bg);
            color: #fff;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .sidebar-header p {
            margin: 0;
            font-size: 11px;
            color: #8b949e;
        }

        .nav-links {
            list-style: none;
            padding: 15px 0;
            margin: 0;
            flex-grow: 1;
        }

        .nav-links li {
            padding: 5px 20px;
        }

        .nav-links a {
            color: #c9d1d9;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.2s;
        }

        .nav-links a i {
            width: 20px;
            margin-right: 10px;
            font-size: 16px;
        }

        .nav-links a:hover {
            background-color: var(--sidebar-hover);
            color: #fff;
        }

        .nav-links a.active {
            background-color: var(--primary-orange);
            color: #fff;
            font-weight: bold;
        }

        /* User Profile */
        .user-profile-container {
            border-top: 1px solid #1f2937;
            padding: 15px 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .avatar {
            width: 35px;
            height: 35px;
            background-color: var(--primary-orange);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 16px;
        }

        .user-info {
            flex-grow: 1;
        }

        .user-info h4 {
            margin: 0;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-badge {
            background-color: #ff7b72;
            color: white;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 10px;
        }

        .user-info p {
            margin: 2px 0 0 0;
            font-size: 10px;
            color: #8b949e;
        }

        .logout-btn {
            color: #c9d1d9;
            text-decoration: none;
            transition: 0.2s;
        }

        .logout-btn:hover {
            color: #ff7b72;
        }

        .app-version {
            font-size: 9px;
            color: #4b5563;
            text-align: left;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            width: calc(100% - 250px);
            padding: 30px 40px;
            overflow-y: auto;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .top-header h1 {
            margin: 0 0 5px 0;
            font-size: 22px;
            color: var(--text-dark);
        }

        .top-header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        /* Stats & Controls */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .stat-card h3 {
            margin: 0;
            font-size: 14px;
            color: var(--text-dark);
            text-align: left;
        }

        .controls-row {
            margin-bottom: 20px;
        }

        .filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: #fff;
            border: 1px solid var(--border-color);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            color: var(--text-dark);
            cursor: pointer;
            transition: 0.2s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            border-color: var(--text-dark);
        }

        /* Notification Items */
        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .notification-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: #fff;
            gap: 15px;
        }

        .notification-item i {
            font-size: 18px;
        }

        .notif-alert i {
            color: #ef4444;
        }

        .notif-warning i {
            color: #f59e0b;
        }

        .notification-item div {
            font-size: 13px;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-wrench" style="color: var(--primary-orange); font-size: 20px;"></i>
            <div>
                <h2>Name</h2>
                <p>Workshop Management</p>
            </div>
        </div>

        <ul class="nav-links">
            <li><a href="admin_dashboard.php"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
            <li><a href="appointments.php"><i class="fa-regular fa-calendar-check"></i> Appointments</a></li>
            <li><a href="job_orders.php"><i class="fa-solid fa-clipboard-list"></i> Job Orders</a></li>
            <li><a href="invoices.php"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</a></li>
            <li><a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a></li>
            <li><a href="inventory.php"><i class="fa-solid fa-box"></i> Inventory</a></li>
            <li><a href="notifications.php" class="active"><i class="fa-regular fa-bell"></i> Notifications</a></li>
            <li><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        </ul>

        <div class="user-profile-container">
            <div class="user-profile">
                <div class="avatar"><?php echo strtoupper(substr($adminName, 0, 1)); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($adminName); ?> <span class="admin-badge">Admin</span></h4>
                    <p><?php echo htmlspecialchars($adminEmail); ?></p>
                </div>
                <a href="index.php" class="logout-btn" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
            </div>
            <div class="app-version">Workshop Manager v1.0</div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-header">
            <div>
                <h1>Notification</h1>
                <p>System alert and important updates</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Signal</h3>
            </div>
            <div class="stat-card">
                <h3>Unread</h3>
            </div>
            <div class="stat-card">
                <h3>Alerts</h3>
            </div>
            <div class="stat-card">
                <h3>Success</h3>
            </div>
        </div>

        <div class="controls-row">
            <div class="filters">
                <button class="filter-btn">All</button>
                <button class="filter-btn active">Unread (2)</button>
                <button class="filter-btn">Alerts</button>
                <button class="filter-btn">Warning</button>
            </div>
        </div>

        <div class="notification-list">
            <div class="notification-item notif-alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div>
                    <strong>Low Inventory Alert</strong>
                </div>
            </div>
            <div class="notification-item notif-warning">
                <i class="fa-regular fa-clock"></i>
                <div>
                    <strong>Appointment Reminder</strong>
                </div>
            </div>
        </div>
    </main>

</body>
</html>