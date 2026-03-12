<?php
session_start();

// Security check: Ensure the user is logged in and has the 'Admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Get the logged-in user's name (fallback to 'Admin' if not set)
$adminName = $_SESSION['username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ServiceHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-bg: #101623;
            --sidebar-hover: #1f2937;
            --primary-orange: #FF7A00;
            --bg-light: #f3f4f6;
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
            overflow: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
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

        /* User Profile in Sidebar */
        .user-profile {
            padding: 20px;
            border-top: 1px solid #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
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
        }

        .user-info { flex-grow: 1; }
        .user-info h4 { margin: 0; font-size: 14px; }
        .user-info p { margin: 0; font-size: 11px; color: #8b949e; }
        
        .logout-btn {
            color: #c9d1d9;
            text-decoration: none;
            transition: 0.2s;
        }
        .logout-btn:hover { color: #ff7b72; }

        /* Main Content Area */
        .main-content {
            flex-grow: 1;
            padding: 30px 40px;
            overflow-y: auto;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .top-header h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
            color: var(--text-dark);
        }

        .top-header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .btn-primary {
            background-color: var(--primary-orange);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 28px;
            color: var(--primary-orange);
        }

        .stat-card p {
            margin: 5px 0 0 0;
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Charts/Lower Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .chart-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            min-height: 250px;
        }

        .chart-card h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: var(--text-dark);
        }

        .chart-card p {
            margin: 0 0 20px 0;
            font-size: 12px;
            color: var(--text-muted);
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
            <li><a href="admin_dashboard.php" class="active"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
            <li><a href="#"><i class="fa-regular fa-calendar-check"></i> Appointments</a></li>
            <li><a href="#"><i class="fa-solid fa-clipboard-list"></i> Job Orders</a></li>
            <li><a href="#"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</a></li>
            <li><a href="#"><i class="fa-solid fa-users"></i> Clients</a></li>
            <li><a href="#"><i class="fa-solid fa-box"></i> Inventory</a></li>
            <li><a href="#"><i class="fa-regular fa-bell"></i> Notifications</a></li>
            <li><a href="#"><i class="fa-solid fa-gear"></i> Settings</a></li>
        </ul>

        <div class="user-profile">
            <div class="avatar"><?php echo strtoupper(substr($adminName, 0, 1)); ?></div>
            <div class="user-info">
                <h4><?php echo htmlspecialchars($adminName); ?></h4>
                <p>Admin</p>
            </div>
            <a href="logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-header">
            <div>
                <h1>Dashboard</h1>
                <p>Welcome back! Here is your workshop overview.</p>
            </div>
            <button class="btn-primary"><i class="fa-solid fa-plus"></i> Add Item</button>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>0</h3>
                <p>Total Appointment</p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p>Active Jobs</p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p>Total Client</p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p>Monthly Revenue</p>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h4>Appointment Trends</h4>
                <p>Monthly appointment vs complete jobs</p>
                <div style="height: 150px; display:flex; align-items:center; justify-content:center; color:#e5e7eb;">
                    <i class="fa-solid fa-chart-line fa-3x"></i>
                </div>
            </div>
            
            <div class="chart-card">
                <h4>Weekly Revenue</h4>
                <p>Revenue trends for the current month</p>
                <div style="height: 150px; display:flex; align-items:center; justify-content:center; color:#e5e7eb;">
                    <i class="fa-solid fa-chart-bar fa-3x"></i>
                </div>
            </div>
        </div>
    </main>

</body>
</html>