<?php
session_start();
require 'includes/db.php'; // Connect to database

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fetch User Data for Sidebar
try {
    $stmt = $pdo->prepare("SELECT full_name, email, profile_image FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $customerName = $_SESSION['username'] ?? ($user['full_name'] ?? 'Customer Name');
    $customerEmail = $_SESSION['email'] ?? ($user['email'] ?? 'customer@email.com');
} catch (PDOException $e) {}

// Fetch Global Shop Name
$globalShopName = 'ServiceHub';
try {
    $shopStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'shop_name'");
    if ($row = $shopStmt->fetch(PDO::FETCH_ASSOC)) {
        $globalShopName = $row['setting_value'];
    }
} catch (PDOException $e) {}

// ==========================================
// 1. HANDLE POST REQUESTS (ADD & EDIT)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Add New Vehicle ---
    if (isset($_POST['add_vehicle'])) {
        $make_model = trim($_POST['make_model']);
        $year = trim($_POST['year']);
        $plate_number = trim($_POST['plate_number']);
        $engine_type = trim($_POST['engine_type']);
        $vin = trim($_POST['vin']) ?: null;
        $notes = trim($_POST['notes']) ?: null;
        $vehicle_image = 'default_bike.png'; // Fallback just in case

        // Handle Image Upload (Required)
        if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK) {
            $fileExt = strtolower(pathinfo($_FILES['vehicle_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($fileExt, $allowed)) {
                if (!is_dir('uploads')) mkdir('uploads', 0755, true);
                $newFileName = "vehicle_" . $user_id . "_" . uniqid() . "." . $fileExt;
                $dest = 'uploads/' . $newFileName;
                
                if (move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $dest)) {
                    $vehicle_image = $dest;
                }
            } else {
                $error_message = "Invalid image type. Please upload a JPG or PNG.";
            }
        } else {
            $error_message = "A photo of your vehicle is required!";
        }

        if (empty($error_message)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO vehicles (user_id, make_model, year, plate_number, engine_type, vin, notes, vehicle_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $make_model, $year, $plate_number, $engine_type, $vin, $notes, $vehicle_image]);
                $success_message = "Vehicle added successfully!";
            } catch (PDOException $e) {
                $error_message = "Error adding vehicle: " . $e->getMessage();
            }
        }
    }

    // --- Edit Existing Vehicle ---
    if (isset($_POST['edit_vehicle'])) {
        $vehicle_id = $_POST['vehicle_id'];
        $make_model = trim($_POST['make_model']);
        $year = trim($_POST['year']);
        $plate_number = trim($_POST['plate_number']);
        $engine_type = trim($_POST['engine_type']);
        $vin = trim($_POST['vin']) ?: null;
        $notes = trim($_POST['notes']) ?: null;

        try {
            // First, get the current image to see if we need to replace it
            $stmt = $pdo->prepare("SELECT vehicle_image FROM vehicles WHERE id = ? AND user_id = ?");
            $stmt->execute([$vehicle_id, $user_id]);
            $current_vehicle = $stmt->fetch();
            $vehicle_image = $current_vehicle['vehicle_image'];

            // Handle New Image Upload (Optional on Edit)
            if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK) {
                $fileExt = strtolower(pathinfo($_FILES['vehicle_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($fileExt, $allowed)) {
                    if (!is_dir('uploads')) mkdir('uploads', 0755, true);
                    $newFileName = "vehicle_" . $user_id . "_" . uniqid() . "." . $fileExt;
                    $dest = 'uploads/' . $newFileName;
                    
                    if (move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $dest)) {
                        // Delete old image if it wasn't the default
                        if ($vehicle_image && $vehicle_image !== 'default_bike.png' && file_exists($vehicle_image)) {
                            unlink($vehicle_image);
                        }
                        $vehicle_image = $dest; // Update to new path
                    }
                } else {
                    $error_message = "Invalid image type. Please upload a JPG or PNG.";
                }
            }

            if (empty($error_message)) {
                $stmt = $pdo->prepare("UPDATE vehicles SET make_model = ?, year = ?, plate_number = ?, engine_type = ?, vin = ?, notes = ?, vehicle_image = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$make_model, $year, $plate_number, $engine_type, $vin, $notes, $vehicle_image, $vehicle_id, $user_id]);
                $success_message = "Vehicle updated successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Error updating vehicle: " . $e->getMessage();
        }
    }
}

// ==========================================
// 2. FETCH CUSTOMER'S VEHICLES
// ==========================================
$vehicles = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching vehicles: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vehicles - <?php echo htmlspecialchars($globalShopName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        :root {
            --sidebar-bg: #101623; --sidebar-hover: #1f2937;
            --primary-orange: #FF7A00; --bg-light: #f9fafb;
            --text-dark: #1f2937; --text-muted: #6b7280;
            --border-color: #e5e7eb; --blue-btn: #3b82f6;
        }

        body, html {
            margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light); display: flex; height: 100vh; width: 100vw; overflow: hidden;
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
        .sidebar-avatar { width: 35px; height: 35px; object-fit: cover; border-radius: 50%; }
        .sidebar-avatar.initial { background-color: var(--primary-orange); color: white; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 16px; }
        .user-info { flex-grow: 1; }
        .user-info h4 { margin: 0; font-size: 13px; display: flex; align-items: center; gap: 8px; }
        .customer-badge { background-color: #3b82f6; color: white; font-size: 9px; padding: 2px 6px; border-radius: 10px; }
        .user-info p { margin: 2px 0 0 0; font-size: 10px; color: #8b949e; overflow: hidden; text-overflow: ellipsis; max-width: 120px; }
        .logout-btn { color: #c9d1d9; text-decoration: none; transition: 0.2s; }
        .logout-btn:hover { color: #ff7b72; }

        /* Main Content */
        .main-content { flex: 1; padding: 30px 40px; overflow-y: auto; }
        .top-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
        .top-header h1 { margin: 0 0 5px 0; font-size: 22px; color: var(--text-dark); }
        .top-header p { margin: 0; color: var(--text-muted); font-size: 13px; }

        .btn-add { background-color: var(--primary-orange); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-add:hover { background-color: #e66a00; }
        
        .alert { padding: 15px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Vehicle Grid with Photo Banners */
        .vehicle-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .vehicle-card { background: #fff; border-radius: 8px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative; overflow: hidden; display: flex; flex-direction: column;}
        
        .vehicle-img-banner { width: 100%; height: 160px; object-fit: cover; border-bottom: 1px solid var(--border-color); background-color: #f3f4f6;}
        .vehicle-card-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column;}
        
        .vehicle-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .vehicle-title h3 { margin: 0 0 5px 0; font-size: 18px; color: var(--text-dark); }
        .vehicle-title p { margin: 0; font-size: 12px; font-weight: bold; background-color: #e5e7eb; display: inline-block; padding: 3px 8px; border-radius: 4px; color: var(--text-dark); }
        
        .vehicle-details { list-style: none; padding: 0; margin: 0 0 20px 0; font-size: 13px; color: var(--text-dark); flex-grow: 1;}
        .vehicle-details li { margin-bottom: 8px; display: flex; justify-content: space-between; border-bottom: 1px dashed #e5e7eb; padding-bottom: 4px;}
        .vehicle-details li span { color: var(--text-muted); font-weight: 500; }
        
        .vehicle-actions { display: flex; gap: 10px; margin-top: auto;}
        .btn-edit { background-color: var(--blue-btn); color: white; border: none; padding: 10px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; flex: 1; transition: 0.2s;}
        .btn-edit:hover { background-color: #2563eb; }

        /* Modals */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal-box { background: #fff; width: 500px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden; }
        .modal-header { padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { margin: 0; font-size: 18px; color: var(--text-dark); }
        .modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--text-muted); }
        .modal-body { padding: 20px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group.full { grid-column: span 2; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-dark); margin-bottom: 5px; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 13px; font-family: inherit; }
        
        /* Custom File Input Styling */
        .file-upload-wrapper { border: 1px dashed var(--primary-orange); padding: 15px; text-align: center; border-radius: 4px; background: #fff7ed; cursor: pointer; transition: 0.2s; }
        .file-upload-wrapper:hover { background: #ffedd5; }
        .file-upload-wrapper input[type="file"] { display: none; }
        .file-upload-wrapper i { font-size: 24px; color: var(--primary-orange); margin-bottom: 10px; display: block; }
        .file-upload-text { font-size: 12px; color: var(--text-dark); font-weight: 600; }
        .file-name-display { display: block; margin-top: 5px; font-size: 11px; color: var(--text-muted); }

        .modal-footer { padding: 15px 20px; border-top: 1px solid var(--border-color); background: #f9fafb; text-align: right; }
        .btn-cancel { background: #fff; border: 1px solid var(--border-color); padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-right: 10px; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-wrench" style="color: var(--primary-orange); font-size: 20px;"></i>
            <div>
                <h2><?php echo htmlspecialchars($globalShopName); ?></h2>
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
                <?php if (isset($user['profile_image']) && $user['profile_image'] !== 'default_avatar.png' && file_exists($user['profile_image'])): ?>
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
            <div>
                <h1>My Vehicles</h1>
                <p>Manage your registered vehicles for quick appointment booking</p>
            </div>
            <button class="btn-add" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Add New Vehicle</button>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Vehicle Grid -->
        <div class="vehicle-grid">
            <?php if (count($vehicles) > 0): ?>
                <?php foreach ($vehicles as $v): ?>
                    <div class="vehicle-card">
                        <!-- Vehicle Image Banner -->
                        <img src="<?php echo htmlspecialchars($v['vehicle_image'] ?: 'default_bike.png'); ?>" alt="Vehicle Photo" class="vehicle-img-banner" onerror="this.src='https://via.placeholder.com/400x200?text=No+Image'">
                        
                        <div class="vehicle-card-body">
                            <div class="vehicle-header">
                                <div class="vehicle-title">
                                    <h3><?php echo htmlspecialchars($v['make_model']); ?></h3>
                                    <p><?php echo htmlspecialchars($v['plate_number']); ?></p>
                                </div>
                                <i class="fa-solid <?php echo $v['engine_type'] == 'Motorcycle' ? 'fa-motorcycle' : 'fa-car-side'; ?>" style="font-size: 24px; color: #d1d5db;"></i>
                            </div>
                            
                            <ul class="vehicle-details">
                                <li><span>Year:</span> <?php echo htmlspecialchars($v['year']); ?></li>
                                <li><span>Engine Type:</span> <?php echo htmlspecialchars($v['engine_type']); ?></li>
                                <li><span>VIN/Chassis:</span> <?php echo htmlspecialchars($v['vin'] ?: 'N/A'); ?></li>
                                <?php if ($v['notes']): ?>
                                    <li style="flex-direction: column; border: none;">
                                        <span>Notes:</span>
                                        <div style="background: #f9fafb; padding: 8px; border-radius: 4px; margin-top: 5px; font-size: 12px;">
                                            <?php echo htmlspecialchars($v['notes']); ?>
                                        </div>
                                    </li>
                                <?php endif; ?>
                            </ul>

                            <div class="vehicle-actions">
                                <button class="btn-edit" onclick="openEditModal(
                                    <?php echo $v['id']; ?>,
                                    '<?php echo addslashes(htmlspecialchars($v['make_model'])); ?>',
                                    '<?php echo addslashes(htmlspecialchars($v['year'])); ?>',
                                    '<?php echo addslashes(htmlspecialchars($v['plate_number'])); ?>',
                                    '<?php echo addslashes(htmlspecialchars($v['engine_type'])); ?>',
                                    '<?php echo addslashes(htmlspecialchars($v['vin'] ?? '')); ?>',
                                    '<?php echo addslashes(htmlspecialchars($v['notes'] ?? '')); ?>'
                                )"><i class="fa-solid fa-pen"></i> Edit Details or Photo</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 50px; background: #fff; border: 1px dashed var(--border-color); border-radius: 8px; color: var(--text-muted);">
                    <i class="fa-solid fa-camera" style="font-size: 40px; margin-bottom: 15px; color: #d1d5db;"></i>
                    <h3 style="margin: 0 0 5px 0; color: var(--text-dark);">No vehicles found</h3>
                    <p style="margin: 0;">Upload a photo and register your first vehicle to get started.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- ADD VEHICLE MODAL -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Add New Vehicle</h2>
                <button class="modal-close" onclick="closeAddModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <!-- Added enctype for file uploads -->
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_vehicle" value="1">
                <div class="modal-body form-grid">
                    
                    <!-- REQUIRED IMAGE UPLOAD -->
                    <div class="form-group full">
                        <label>Vehicle Photo <span style="color: red;">*</span></label>
                        <label class="file-upload-wrapper">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span class="file-upload-text">Click to upload vehicle photo</span>
                            <span class="file-name-display" id="add_file_name">Required (JPG, PNG)</span>
                            <input type="file" name="vehicle_image" accept="image/*" required onchange="document.getElementById('add_file_name').innerText = this.files[0].name">
                        </label>
                    </div>

                    <div class="form-group full">
                        <label>Make & Model</label>
                        <input type="text" name="make_model" required placeholder="e.g. Honda Click 125i">
                    </div>
                    <div class="form-group">
                        <label>Year</label>
                        <input type="number" name="year" required placeholder="e.g. 2021" min="1900" max="2030">
                    </div>
                    <div class="form-group">
                        <label>Plate Number</label>
                        <input type="text" name="plate_number" required placeholder="e.g. ABC 1234">
                    </div>
                    <div class="form-group">
                        <label>Engine Type</label>
                        <select name="engine_type" required>
                            <option value="Motorcycle">Motorcycle</option>
                            <option value="Gasoline">Gasoline Car</option>
                            <option value="Diesel">Diesel Car</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Electric">Electric</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>VIN / Chassis (Optional)</label>
                        <input type="text" name="vin">
                    </div>
                    <div class="form-group full">
                        <label>Special Notes (Optional)</label>
                        <textarea name="notes" rows="2" placeholder="Any specific details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn-add">Save Vehicle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT VEHICLE MODAL -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Edit Vehicle</h2>
                <button class="modal-close" onclick="closeEditModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <!-- Added enctype for file uploads -->
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_vehicle" value="1">
                <input type="hidden" name="vehicle_id" id="edit_vehicle_id">
                <div class="modal-body form-grid">
                    
                    <!-- OPTIONAL IMAGE UPLOAD ON EDIT -->
                    <div class="form-group full">
                        <label>Update Vehicle Photo (Optional)</label>
                        <label class="file-upload-wrapper" style="border-color: #d1d5db; background: #f9fafb;">
                            <i class="fa-solid fa-camera" style="color: #6b7280;"></i>
                            <span class="file-upload-text">Click to replace photo</span>
                            <span class="file-name-display" id="edit_file_name">Leave blank to keep current photo</span>
                            <input type="file" name="vehicle_image" accept="image/*" onchange="document.getElementById('edit_file_name').innerText = this.files[0].name">
                        </label>
                    </div>

                    <div class="form-group full">
                        <label>Make & Model</label>
                        <input type="text" name="make_model" id="edit_make_model" required>
                    </div>
                    <div class="form-group">
                        <label>Year</label>
                        <input type="number" name="year" id="edit_year" required min="1900" max="2030">
                    </div>
                    <div class="form-group">
                        <label>Plate Number</label>
                        <input type="text" name="plate_number" id="edit_plate_number" required>
                    </div>
                    <div class="form-group">
                        <label>Engine Type</label>
                        <select name="engine_type" id="edit_engine_type" required>
                            <option value="Motorcycle">Motorcycle</option>
                            <option value="Gasoline">Gasoline Car</option>
                            <option value="Diesel">Diesel Car</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Electric">Electric</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>VIN / Chassis (Optional)</label>
                        <input type="text" name="vin" id="edit_vin">
                    </div>
                    <div class="form-group full">
                        <label>Special Notes (Optional)</label>
                        <textarea name="notes" id="edit_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-edit" style="width: auto;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const addModal = document.getElementById('addModal');
        const editModal = document.getElementById('editModal');

        function openAddModal() {
            addModal.style.display = 'flex';
        }
        function closeAddModal() {
            addModal.style.display = 'none';
        }

        function openEditModal(id, makeModel, year, plate, engine, vin, notes) {
            document.getElementById('edit_vehicle_id').value = id;
            document.getElementById('edit_make_model').value = makeModel;
            document.getElementById('edit_year').value = year;
            document.getElementById('edit_plate_number').value = plate;
            document.getElementById('edit_engine_type').value = engine;
            document.getElementById('edit_vin').value = vin;
            document.getElementById('edit_notes').value = notes;
            
            // Reset the file input text so it doesn't show an old filename from a previous click
            document.getElementById('edit_file_name').innerText = "Leave blank to keep current photo";
            
            editModal.style.display = 'flex';
        }
        
        function closeEditModal() {
            editModal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == addModal) closeAddModal();
            if (event.target == editModal) closeEditModal();
        }
    </script>
</body>
</html>