<?php
session_start();
require 'includes/db.php'; // Connect to database

// Ensure only Admins can access the settings
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$adminName = $_SESSION['username'] ?? 'Admin';
$adminEmail = 'admin@email.com'; 

// Fetch Global Shop Name for Sidebar
$globalShopName = 'ServiceHub';
try {
    $shopStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'shop_name'");
    if ($row = $shopStmt->fetch(PDO::FETCH_ASSOC)) {
        $globalShopName = $row['setting_value'];
    }
} catch (PDOException $e) {}

// ==========================================
// 1. DATA EXPORT & BACKUP LOGIC
// ==========================================

// --- CSV Export Logic ---
if (isset($_GET['export_csv'])) {
    $table = $_GET['export_csv'];
    // White-list allowed tables to prevent SQL injection or exposing system_settings
    $allowed_tables = ['appointments', 'job_orders', 'invoices', 'inventory', 'users'];

    if (in_array($table, $allowed_tables)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $table . '_export_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');

        // Prevent exporting user passwords
        if ($table === 'users') {
            $stmt = $pdo->query("SELECT id, role, full_name, username, email, created_at FROM users");
        } else {
            $stmt = $pdo->query("SELECT * FROM $table");
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            fputcsv($output, array_keys($row)); // Write column headers
            fputcsv($output, $row); // Write first row
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row); // Write remaining rows
            }
        }
        fclose($output);
        exit(); // Stop HTML rendering
    }
}

// --- Full SQL Backup Logic ---
if (isset($_GET['export_sql'])) {
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename=database_backup_' . date('Y-m-d_H-i-s') . '.sql');

    $sql_output = "-- Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $sql_output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\nSTART TRANSACTION;\nSET time_zone = \"+00:00\";\n\n";

    // Get all tables
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        // Drop table if exists & Create table statement
        $sql_output .= "DROP TABLE IF EXISTS `$table`;\n";
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $sql_output .= $row[1] . ";\n\n";

        // Insert data
        $stmt = $pdo->query("SELECT * FROM `$table`");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $keys = array_keys($row);
            $values = array_map(function($value) use ($pdo) {
                if ($value === null) return 'NULL';
                return $pdo->quote($value); // Safely escape strings
            }, array_values($row));

            $sql_output .= "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $values) . ");\n";
        }
        $sql_output .= "\n\n";
    }

    $sql_output .= "COMMIT;\n";
    echo $sql_output;
    exit(); // Stop HTML rendering
}

// ==========================================
// 2. HANDLE POST REQUESTS (SAVING DATA)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Profile Update ---
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $username, $email, $user_id]);
            $_SESSION['username'] = $username; 
            $adminName = $username; 
            $success_message = "Profile updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }

    // --- Password Update ---
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } else {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashed_password, $user_id]);
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "Incorrect current password.";
            }
        }
    }

    // --- Shop Settings Update ---
    if (isset($_POST['update_shop'])) {
        $keys = ['shop_name', 'shop_email', 'shop_phone', 'shop_address', 'currency'];
        try {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            foreach ($keys as $key) {
                $val = $_POST[$key] ?? '';
                $stmt->execute([$val, $key]);
            }
            $success_message = "Shop settings updated successfully!";
            $globalShopName = $_POST['shop_name']; 
        } catch (PDOException $e) {
            $error_message = "Error updating shop settings: " . $e->getMessage();
        }
    }
}

// ==========================================
// 3. FETCH CURRENT DATA (PRE-FILL FORMS)
// ==========================================
$profileStmt = $pdo->prepare("SELECT full_name, username, email FROM users WHERE id = ?");
$profileStmt->execute([$user_id]);
$adminData = $profileStmt->fetch(PDO::FETCH_ASSOC);
if ($adminData) {
    $adminEmail = $adminData['email'];
}

$settings = [];
try {
    $shopStmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while ($row = $shopStmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo htmlspecialchars($globalShopName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        :root {
            --sidebar-bg: #101623;
            --sidebar-hover: #1f2937;
            --primary-orange: #FF7A00;
            --bg-light: #f9fafb;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --blue-btn: #3b82f6;
        }

        body, html {
            margin: 0; padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            display: flex; height: 100vh; width: 100vw; overflow: hidden;
        }

        /* Sidebar Styles */
        .sidebar { width: 250px; flex-shrink: 0; background-color: var(--sidebar-bg); color: #fff; display: flex; flex-direction: column; height: 100%; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid #1f2937; display: flex; align-items: center; gap: 10px; }
        .sidebar-header h2 { margin: 0; font-size: 18px; font-weight: 600; }
        .sidebar-header p { margin: 0; font-size: 11px; color: #8b949e; }
        
        .nav-links { list-style: none; padding: 15px 0; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 5px 20px; }
        .nav-links a { color: #c9d1d9; text-decoration: none; display: flex; align-items: center; padding: 10px 15px; border-radius: 8px; font-size: 14px; transition: 0.2s; }
        .nav-links a i { width: 20px; margin-right: 10px; font-size: 16px; }
        .nav-links a:hover { background-color: var(--sidebar-hover); color: #fff; }
        .nav-links a.active { background-color: var(--primary-orange); color: #fff; font-weight: bold; }

        .user-profile-container { border-top: 1px solid #1f2937; padding: 15px 20px; }
        .user-profile { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 35px; height: 35px; background-color: var(--primary-orange); color: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 16px; }
        .user-info { flex-grow: 1; }
        .user-info h4 { margin: 0; font-size: 13px; display: flex; align-items: center; gap: 8px; }
        .admin-badge { background-color: #ef4444; color: white; font-size: 9px; padding: 2px 6px; border-radius: 10px; }
        .user-info p { margin: 2px 0 0 0; font-size: 10px; color: #8b949e; overflow: hidden; text-overflow: ellipsis; max-width: 120px; }
        .logout-btn { color: #c9d1d9; text-decoration: none; transition: 0.2s; }
        .logout-btn:hover { color: #ff7b72; }

        .main-content { flex: 1; padding: 30px 40px; overflow-y: auto; }
        .top-header { margin-bottom: 25px; }
        .top-header h1 { margin: 0 0 5px 0; font-size: 22px; color: var(--text-dark); }
        .top-header p { margin: 0; color: var(--text-muted); font-size: 13px; }

        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
        .settings-column { display: flex; flex-direction: column; gap: 20px; }

        .card { background: #fff; border-radius: 8px; border: 1px solid var(--border-color); padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card-header { margin-bottom: 20px; }
        .card-header h2 { margin: 0 0 5px 0; font-size: 15px; display: flex; align-items: center; gap: 8px; color: var(--text-dark); }
        .card-header h2 i { color: var(--primary-orange); }
        .card-header p { margin: 0; font-size: 12px; color: var(--text-muted); }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-dark); margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 13px; outline: none; font-family: inherit; color: var(--text-dark);
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary-orange); }
        textarea { resize: vertical; }

        .btn-submit { background-color: var(--primary-orange); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; transition: 0.2s; margin-top: 5px; }
        .btn-submit:hover { background-color: #e66a00; }
        
        .btn-full-blue { background-color: var(--blue-btn); color: white; text-decoration: none; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; width: 100%; display: flex; justify-content: center; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-full-blue:hover { background-color: #2563eb; }

        .data-links { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        .data-links a { color: var(--blue-btn); text-decoration: none; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .data-links a:hover { text-decoration: underline; color: #1d4ed8; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-wrench" style="color: var(--primary-orange); font-size: 20px;"></i>
            <div>
                <h2><?php echo htmlspecialchars($globalShopName); ?></h2>
                <p>Workshop Management</p>
            </div>
        </div>

        <ul class="nav-links">
            <li><a href="admin_dashboard.php"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
            <li><a href="appointments.php"><i class="fa-regular fa-calendar-check"></i> Appointments</a></li>
            <li><a href="job_orders.php"><i class="fa-solid fa-clipboard-list"></i> Job Orders</a></li>
            <li><a href="invoices.php"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</a></li>
            <li><a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a></li>
            <li><a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a></li>
            <li><a href="notifications.php"><i class="fa-regular fa-bell"></i> Notifications</a></li>
            <li><a href="settings.php" class="active"><i class="fa-solid fa-gear"></i> Settings</a></li>
        </ul>

        <div class="user-profile-container">
            <div class="user-profile">
                <div class="avatar"><?php echo strtoupper(substr($adminName, 0, 1)); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($adminName); ?> <span class="admin-badge">Admin</span></h4>
                    <p title="<?php echo htmlspecialchars($adminEmail); ?>"><?php echo htmlspecialchars($adminEmail); ?></p>
                </div>
                <a href="logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-header">
            <h1>Settings</h1>
            <p>Manage your account and system preferences</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="settings-grid">
            
            <!-- LEFT COLUMN -->
            <div class="settings-column">
                
                <!-- Profile Settings -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fa-regular fa-user"></i> Profile Settings</h2>
                        <p>Update your personal account information.</p>
                    </div>
                    <form action="" method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($adminData['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($adminData['username'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($adminData['email'] ?? ''); ?>" required>
                        </div>
                        <button type="submit" class="btn-submit">Save Profile</button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-lock"></i> Change Password</h2>
                        <p>Ensure your account uses a long, random password.</p>
                    </div>
                    <form action="" method="POST">
                        <input type="hidden" name="update_password" value="1">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required minlength="6">
                        </div>
                        <button type="submit" class="btn-submit">Update Password</button>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="settings-column">
                
                <!-- Shop Settings -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-store"></i> Shop Settings</h2>
                        <p>Manage public contact info and localization.</p>
                    </div>
                    <form action="" method="POST">
                        <input type="hidden" name="update_shop" value="1">
                        <div class="form-group">
                            <label>Shop Name</label>
                            <input type="text" name="shop_name" value="<?php echo htmlspecialchars($settings['shop_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" name="shop_email" value="<?php echo htmlspecialchars($settings['shop_email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Phone</label>
                            <input type="text" name="shop_phone" value="<?php echo htmlspecialchars($settings['shop_phone'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Shop Address</label>
                            <textarea name="shop_address" rows="3" required><?php echo htmlspecialchars($settings['shop_address'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Default Currency</label>
                            <select name="currency">
                                <option value="PHP" <?php echo ($settings['currency'] ?? '') === 'PHP' ? 'selected' : ''; ?>>PHP - Philippine Peso (₱)</option>
                                <option value="USD" <?php echo ($settings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD - US Dollar ($)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-submit">Save Shop Settings</button>
                    </form>
                </div>

                <!-- Data Management / Backup -->
                <div class="card">
                    <div class="card-header" style="margin-bottom: 10px; border-bottom: none; padding-bottom: 0;">
                        <h2><i class="fa-solid fa-file-export"></i> Data Management</h2>
                        <p>Export system records to CSV format for Excel reporting.</p>
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <label style="font-size: 12px; font-weight: 600; color: var(--text-dark);">Select Table to Export</label>
                        <div class="data-links">
                            <!-- Linked with the GET parameters we defined at the top of the file -->
                            <a href="settings.php?export_csv=appointments"><i class="fa-regular fa-calendar"></i> Appointments</a>
                            <a href="settings.php?export_csv=job_orders"><i class="fa-solid fa-wrench"></i> Job Orders</a>
                            <a href="settings.php?export_csv=invoices"><i class="fa-solid fa-file-invoice"></i> Invoices</a>
                            <a href="settings.php?export_csv=inventory"><i class="fa-solid fa-box"></i> Inventory</a>
                            <a href="settings.php?export_csv=users"><i class="fa-solid fa-users"></i> Clients/Users</a>
                        </div>
                    </div>

                    <div class="card-header" style="margin-bottom: 15px; border-bottom: none; padding-bottom: 0;">
                        <h2><i class="fa-solid fa-database"></i> Database Backup</h2>
                        <p>Backup all current records into a system archive before clearing data.</p>
                    </div>
                    
                    <!-- Linked with the GET parameter for SQL Backup -->
                    <a href="settings.php?export_sql=1" class="btn-full-blue"><i class="fa-solid fa-download"></i> Download Full SQL Backup</a>
                </div>

            </div>
        </div>
    </main>

</body>
</html>