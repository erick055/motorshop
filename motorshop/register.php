<?php
session_start();
require 'includes/db.php';

// Include PHPMailer. Adjust path if you didn't use Composer.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $message = "Username or Email already taken!";
        } else {
            // Hash password and generate random verification code
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $verification_code = bin2hex(random_bytes(16)); 
            
            // Insert user as unverified (is_verified = 0)
            $insert = $pdo->prepare("INSERT INTO users (role, full_name, username, email, password, is_verified, verification_code) VALUES (?, ?, ?, ?, ?, 0, ?)");
            
            if ($insert->execute([$role, $full_name, $username, $email, $hashed_password, $verification_code])) {
                
                // --- SMTP CONFIGURATION ---
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.sendgrid.net'; // Or your SMTP provider
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'apikey'; // Replace with your email
                      // Replace with your App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('erickpallorina@gmail.com', 'ServiceHub');
                    $mail->addAddress($email, $full_name);

                    // Email Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Verify Your Email Address - ServiceHub';
                    
                    // Ensure the domain points to your app
                    $verify_link = "http://localhost/motoshop/verify.php?code=" . $verification_code;
                    
                    $mail->Body = "Hi $full_name,<br><br>Thank you for registering at ServiceHub! Please click the link below to verify your email address:<br><br><a href='$verify_link'>$verify_link</a>";

                    $mail->send();
                    $message = "Account created! A verification email has been sent. Please check your inbox before logging in.";
                } catch (Exception $e) {
                    $message = "Account created, but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $message = "Error creating account.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ServiceHub</title>
    <style>
        body {
            background-color: #0d1117;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            background-color: #161b22;
            padding: 40px;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .header h2 { margin: 10px 0 5px; font-size: 24px; }
        .header p { color: #8b949e; font-size: 14px; margin: 0; }
        
        .role-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .role-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            background-color: #21262d;
            color: #8b949e;
            transition: 0.3s;
        }
        .role-btn.active {
            background-color: #ec2929;
            color: #fff;
        }

        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block;
            font-size: 12px;
            margin-bottom: 5px;
            color: #c9d1d9;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #30363d;
            background-color: #0d1117;
            color: #fff;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #FF7A00;
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #E63946 0%, #FF7A00 100%);
            border: none;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
        }
        .footer-text a { 
            color: #FF7A00; 
            text-decoration: none; 
        }
        .msg { 
            text-align: center; 
            color: #ff7b72; 
            font-size: 14px; 
            margin-bottom: 15px; 
        }
    </style>
</head>
<body>

<div class="card">
    <div class="header">
        <span style="color: #FF7A00; font-size: 24px;">🔧</span>
        <h2>Create your account</h2>
        <p>Get started with ServiceHub workshop management</p>
    </div>

    <?php if($message): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="role-toggle">
        <button class="role-btn active" onclick="setRole('Customer', this)">Customer</button>
        <button class="role-btn" onclick="setRole('Admin', this)">Admin</button>
    </div>

    <form method="POST" action="">
        <input type="hidden" name="role" id="roleInput" value="Customer">
        
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required>
        </div>
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>
        </div>

        <button type="submit" class="submit-btn">Create Account</button>
    </form>

    <div class="footer-text">
        Already have an account? <a href="login.php">Log in</a>
    </div>
</div>

<script>
    function setRole(role, btnElement) {
        // Update hidden input
        document.getElementById('roleInput').value = role;
        
        // Update button styles
        let buttons = document.querySelectorAll('.role-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');
    }
</script>

</body>
</html>
