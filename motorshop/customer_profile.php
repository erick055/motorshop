<?php
session_start();
require 'includes/db.php'; // Includes your PDO database connection

// Security check: Redirect if not logged in or not a Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// --- FETCH ACTUAL USER DATA ---
try {
    $stmt = $pdo->prepare("SELECT full_name, email, phone_number, profile_image, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Fallback for variables not set during login
    $customerName = $_SESSION['username'] ?? ($user['full_name'] ?? 'Customer Name');
    $customerEmail = $_SESSION['email'] ?? ($user['email'] ?? 'customer@email.com');

} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}


// ==========================================
// HANDLE FORM SUBMISSIONS
// ==========================================

// --- 1. HANDLE PROFILE IMAGE UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_image') {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        
        $file = $_FILES['profile_photo'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmpName = $file['tmp_name'];
        
        // Allowed file types
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowed)) {
            $error_message = "Error: Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        } elseif ($fileSize > 2000000) { // 2MB limit
            $error_message = "Error: File size is too large (max 2MB).";
        } else {
            // Create uploads directory if it doesn't exist
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }

            // Generate a unique file name
            $newFileName = "customer_" . $user_id . "_" . uniqid() . "." . $fileExt;
            $fileDestination = 'uploads/' . $newFileName;

            // Move the file
            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                // Delete old image if it's not the default
                if ($user['profile_image'] && $user['profile_image'] !== 'default_avatar.png' && file_exists($user['profile_image'])) {
                    unlink($user['profile_image']);
                }

                // Update database
                $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->execute([$fileDestination, $user_id]);
                $success_message = "Profile photo updated successfully!";
                
                // Refresh data
                header("Location: customer_profile.php?success=1");
                exit();
            } else {
                $error_message = "Error: There was an issue uploading your file.";
            }
        }
    } else {
        $error_message = "Error: No file selected or upload error occurred.";
    }
}


// --- 2. HANDLE PERSONAL INFORMATION UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);

    // Basic validation
    if (empty($full_name)) {
        $error_message = "Full Name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone_number = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone_number, $user_id]);
            $success_message = "Personal information updated successfully!";
            
            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}


// --- 3. HANDLE CHANGE PASSWORD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_new_password'];

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters.";
    } else {
        // Fetch current hashed password from db
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $stored_user = $stmt->fetch();

        if ($stored_user && password_verify($current_password, $stored_user['password'])) {
            // Current password matches, hash the new one and update
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_new_password, $user_id]);
                $success_message = "Password changed successfully!";
            } catch (PDOException $e) {
                $error_message = "Error changing password: " . $e->getMessage();
            }
        } else {
            $error_message = "Incorrect current password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ServiceHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        :root {
            --sidebar-bg: #101623; --sidebar-hover: #1f2937;
            --primary-orange: #FF7A00; --bg-light: #f9fafb;
            --text-dark: #1f2937; --text-muted: #6b7280;
            --border-color: #e5e7eb;
        }
        body, html {
            margin: 0; padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light); display: flex;
            height: 100vh; width: 100vw; overflow: hidden;
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

        /* Sidebar user profile at the bottom */
        .user-profile-container { border-top: 1px solid #1f2937; padding: 15px 20px; }
        .user-profile { display: flex; align-items: center; gap: 10px; }
        .sidebar-avatar { width: 35px; height: 35px; object-fit: cover; border-radius: 50%; }
        .sidebar-avatar.initial { background-color: var(--primary-orange); color: white; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 16px; }
        .user-info { flex-grow: 1; }
        .user-info h4 { margin: 0; font-size: 13px; display: flex; align-items: center; gap: 8px; }
        .customer-badge { background-color: #3b82f6; color: white; font-size: 9px; padding: 2px 6px; border-radius: 10px; }
        .user-info p { margin: 2px 0 0 0; font-size: 10px; color: #8b949e; overflow: hidden; text-overflow: ellipsis; max-width: 120px; }
        .logout-btn { color: #c9d1d9; text-decoration: none; transition: 0.2s; }
        .logout-btn:hover { color: #ff7b72; }

        /* Main Content */
        .main-content { flex: 1; width: calc(100% - 250px); padding: 30px 40px; overflow-y: auto; }
        .top-header { margin-bottom: 25px; }
        .top-header h1 { margin: 0 0 5px 0; font-size: 22px; color: var(--text-dark); }
        .top-header p { margin: 0; color: var(--text-muted); font-size: 13px; }

        /* Card styles matching the screenshot */
        .card { background: #fff; border-radius: 8px; border: 1px solid var(--border-color); padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }

        /* Top Profile Card (Photo Area) */
        .profile-photo-area { display: flex; align-items: center; gap: 25px; }
        .profile-avatar-large { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; font-size: 40px; }
        .profile-avatar-large.initial { background-color: var(--primary-orange); color: white; display: flex; justify-content: center; align-items: center; font-weight: bold; }
        
        .profile-meta-info { flex-grow: 1; display: grid; grid-template-columns: auto 1fr; gap: 15px; align-items: center; color: var(--text-dark); }
        .profile-meta-info i { color: #9ca3af; width: 20px; text-align: center; font-size: 16px; }
        .meta-text-muted { font-size: 13px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 5px; background: #f9fafb; color: var(--text-muted); width: 100%; max-width: 250px;}

        /* Photo Upload Controls */
        .photo-controls { display: flex; flex-direction: column; gap: 10px; align-items: flex-start; }
        .custom-file-upload { border: 1px solid var(--border-color); display: inline-block; padding: 6px 12px; cursor: pointer; border-radius: 5px; background: #f3f4f6; color: var(--text-dark); font-size: 12px; font-weight: 500;}
        .photo-controls input[type="file"] { display: none; }
        .btn-photo-action { background: var(--blue-btn, #3b82f6); color: white; border: none; padding: 6px 12px; border-radius: 5px; font-size: 12px; font-weight: bold; cursor: pointer;}

        /* Personal Information Card */
        .personal-info-area form { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-full-row { grid-column: span 2; }
        .card-title-row { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; color: var(--text-dark); }
        .card-title-row h3 { margin: 0; font-size: 16px; font-weight: 600; }
        .card-desc { font-size: 12px; color: var(--text-muted); margin: -15px 0 20px 30px; }

        /* General form styles */
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-dark); margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 10px 15px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 13px; color: var(--text-dark); }
        .form-group input:focus { border-color: var(--primary-orange); outline: none;}

        /* Purple button */
        .btn-purple { background-color: #C084FC; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; font-size: 13px; cursor: pointer;  gap: 8px; transition: 0.2s; }
        .btn-purple:hover { background-color: #a855f7; }

        /* Orange button */
        .btn-orange { background-color: var(--primary-orange); color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; font-size: 13px; cursor: pointer;  gap: 8px; transition: 0.2s; margin-top: 10px;}
        .btn-orange:hover { background-color: #e66a00; }

        /* Password section styles */
        .change-password-area form { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .password-input-wrapper { position: relative; width: 100%; }
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted); cursor: pointer; font-size: 14px;}

        /* Alert Messages */
        .alert-container { margin-bottom: 20px; }
        .alert { padding: 15px; border-radius: 6px; font-size: 14px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-wrench" style="color: var(--primary-orange); font-size: 20px;"></i>
            <div>
                <h2>ServiceHub</h2>
                <p>Customer Portal</p>
            </div>
        </div>

        <ul class="nav-links">
            <li><a href="customer_dashboard.php"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
            <li><a href="my_vehicles.php"><i class="fa-solid fa-car"></i> My Vehicles</a></li>
            <li><a href="book_appointment.php"><i class="fa-regular fa-calendar-plus"></i> Book Appointment</a></li>
            <li><a href="service_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Service History</a></li>
            <li><a href="my_invoices.php"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</a></li>
            <li><a href="customer_profile.php"><i class="fa-regular fa-user"></i> Profile</a></li>
        </ul>

        <div class="user-profile-container">
            <div class="user-profile">
                <!-- THE CORRECTED AVATAR LOGIC -->
                <?php if ($user['profile_image'] && $user['profile_image'] !== 'default_avatar.png' && file_exists($user['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Avatar" class="sidebar-avatar">
                <?php else: ?>
                    <div class="sidebar-avatar initial"><?php echo strtoupper(substr($customerName, 0, 1)); ?></div>
                <?php endif; ?>

                <div class="user-info">
                    <h4><?php echo htmlspecialchars($customerName); ?> <span class="customer-badge">Customer</span></h4>
                    <p title="<?php echo htmlspecialchars($customerEmail); ?>"><?php echo htmlspecialchars($customerEmail); ?></p>
                </div>
                <a href="logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-header">
            <h1>Profile</h1>
            <p>Manage your personal information and security settings</p>
        </div>

        <!-- Alert messages from backend -->
        <div class="alert-container">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Profile updated successfully!</div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
        </div>

        <!-- Top Profile Card (Photo Area) -->
        <div class="card">
            <div class="profile-photo-area">
                <?php if ($user['profile_image'] && $user['profile_image'] !== 'default_avatar.png' && file_exists($user['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Large Avatar" class="profile-avatar-large">
                <?php else: ?>
                    <div class="profile-avatar-large initial"><?php echo strtoupper(substr($customerName, 0, 1)); ?></div>
                <?php endif; ?>

                <div class="profile-meta-info">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span class="customer-badge" style="justify-self: start;"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                    
                    <i class="fa-regular fa-envelope"></i>
                    <div class="meta-text-muted"><?php echo htmlspecialchars($user['email']); ?></div>
                    
                    <i class="fa-regular fa-calendar-days"></i>
                    <div class="meta-text-muted">
                        Member since: 
                        <?php 
                        $joinDate = ($user['created_at']) ? date("F d, Y", strtotime($user['created_at'])) : "N/A";
                        echo $joinDate;
                        ?>
                    </div>
                </div>

                <!-- Photo Upload Form -->
                <form action="" method="POST" enctype="multipart/form-data" class="photo-controls">
                    <input type="hidden" name="action" value="update_image">
                    <label for="profile_photo" class="custom-file-upload">
                        <i class="fa-solid fa-upload"></i> Choose Photo
                    </label>
                    <input type="file" name="profile_photo" id="profile_photo" accept="image/*" onchange="enableUploadButton()" />
                    <button type="submit" id="photo_upload_btn" class="btn-photo-action" disabled>Update Photo</button>
                    <p style="font-size: 10px; color: var(--text-muted); margin: 0">Max 2MB (JPG, PNG)</p>
                </form>

            </div>
        </div>

        <!-- Personal Information Card -->
        <div class="card personal-info-area">
            <div class="card-title-row">
                <i class="fa-solid fa-user-pen"></i>
                <h3>Personal Information</h3>
            </div>
            <p class="card-desc">Update your name and phone number</p>
            
            <form action="" method="POST">
                <div class="form-group form-full-row">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" placeholder="Full Name" required>
                </div>
                <div class="form-group form-full-row">
                    <label>Phone Number</label>
                    <!-- Pre-filled with database value -->
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" placeholder="e.g. (09XX) XXX-XXXX">
                </div>
                <button type="submit" name="update_profile" class="btn-purple">
                    <i class="fa-solid fa-save"></i> Save changes
                </button>
            </form>
        </div>

        <!-- Change Password Card -->
        <div class="card change-password-area">
            <div class="card-title-row">
                <i class="fa-solid fa-lock"></i>
                <h3>Change Password</h3>
            </div>
            <p class="card-desc">Update your password to keep your account secure</p>
            
            <form action="" method="POST">
                <div class="form-group form-full-row">
                    <label>Current Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="current_password" required placeholder="Enter current password" id="current_pwd_input">
                        <i class="fa-regular fa-eye-slash toggle-password" onclick="togglePassword('current_pwd_input', this)"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="new_password" required placeholder="At least 6 characters" id="new_pwd_input" minlength="6">
                        <i class="fa-regular fa-eye-slash toggle-password" onclick="togglePassword('new_pwd_input', this)"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_new_password" required placeholder="Re-enter new password" minlength="6">
                </div>
                
                <button type="submit" name="change_password" class="btn-orange">
                    <i class="fa-solid fa-key"></i> Update Password
                </button>
            </form>
        </div>
    </main>

    <script>
        // Toggle password visibility function
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }

        // Enable the upload button only when a file is selected
        function enableUploadButton() {
            const fileInput = document.getElementById('profile_photo');
            const uploadBtn = document.getElementById('photo_upload_btn');
            if (fileInput.files.length > 0) {
                uploadBtn.disabled = false;
                uploadBtn.style.opacity = '1';
                uploadBtn.textContent = "Click to Save Photo";
            } else {
                uploadBtn.disabled = true;
                uploadBtn.textContent = "Update Photo";
            }
        }
    </script>
</body>
</html>