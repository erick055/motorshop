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
    <title>Settings - ServiceHub</title>
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

        /* Forms & Settings Specifics */
        .outline-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .outline-card h3 {
            margin: 0 0 5px 0;
            font-size: 15px;
            color: var(--text-dark);
        }

        .outline-card .sub-text {
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 15px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .form-group input {
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 12px;
            outline: none;
        }

        .form-group input:focus {
            border-color: var(--primary-orange);
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-item-text span {
            font-size: 13px;
            font-weight: 500;
            display: block;
        }

        .setting-item-text small {
            color: var(--text-muted);
            font-weight: normal;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 20px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #10b981;
        }

        input:checked + .slider:before {
            transform: translateX(20px);
        }

        /* Action Buttons */
        .data-btn {
            padding: 10px;
            border: 1px solid var(--border-color);
            background: #f3f4f6;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            text-align: center;
            margin-bottom: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .data-btn:hover {
            background: #e5e7eb;
        }

        .data-btn.danger {
            border-color: #fca5a5;
            background: #fee2e2;
            color: #ef4444;
        }

        .data-btn.danger:hover {
            background: #fecaca;
        }

        .save-btn {
            background-color: #ef4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .save-btn:hover {
            background-color: #dc2626;
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
            <li><a href="notifications.php"><i class="fa-regular fa-bell"></i> Notifications</a></li>
            <li><a href="settings.php" class="active"><i class="fa-solid fa-gear"></i> Settings</a></li>
        </ul>

        <div class="user-profile-container">
            <div class="user-profile">
                <div class="avatar"><?php echo strtoupper(substr($adminName, 0, 1)); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($adminName); ?> <span class="admin-badge">Admin</span></h4>
                    <p><?php echo htmlspecialchars($adminEmail); ?></p>
                </div>
                <a href="logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
            </div>
            <div class="app-version">Workshop Manager v1.0</div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-header">
            <div>
                <h1>Settings</h1>
                <p>Manage your workshop configuration and preference</p>
            </div>
        </div>

        <form action="" method="POST">
            <div class="outline-card">
                <h3>Workshop Information</h3>
                <p class="sub-text">Basic details about your workshop</p>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Workshop Name</label>
                        <input type="text" value="Service Hub Workshop">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" placeholder="Email/Contact">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" placeholder="Contact">
                    </div>
                    <div class="form-group">
                        <label>Business Hours</label>
                        <input type="text" placeholder="Open/Closing hours">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" placeholder="Address">
                    </div>
                </div>
            </div>

            <div class="outline-card">
                <h3>Notification Reference</h3>
                <p class="sub-text">Control how you receive alerts and notifications</p>
                
                <div class="setting-item">
                    <div class="setting-item-text">
                        <span>Enable Notification</span>
                        <small>Receive all system notifications</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="setting-item">
                    <div class="setting-item-text">
                        <span>Email alerts</span>
                        <small>Get alerts via Email</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="setting-item">
                    <div class="setting-item-text">
                        <span>Inventory alerts</span>
                        <small>Get notified for low stock</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="setting-item">
                    <div class="setting-item-text">
                        <span>Appointment Reminders</span>
                        <small>Receive appointment reminders</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <div class="outline-card">
                <h3>System Settings</h3>
                <div class="setting-item">
                    <div class="setting-item-text">
                        <span>Auto Backup</span>
                        <small>Automatically backup data daily</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="setting-item">
                    <div class="setting-item-text">
                        <span>Maintenance Mode</span>
                        <small>Disable access for maintenance</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <div class="outline-card">
                <h3>Data Management</h3>
                <p class="sub-text">Export and manage your working data</p>
                <button type="button" class="data-btn"><i class="fa-solid fa-download"></i> Export Data as CSV</button>
                <button type="button" class="data-btn"><i class="fa-solid fa-file-invoice"></i> Generate System Support</button>
                <button type="button" class="data-btn danger"><i class="fa-solid fa-trash"></i> Clear Cache and Reset</button>
            </div>

            <button type="submit" class="save-btn">Save Changes</button>
        </form>
    </main>

</body>
</html>