<?php
session_start();
require 'includes/db.php'; 

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Find the user with this specific code who isn't verified yet
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_code = ? AND is_verified = 0");
    $stmt->execute([$code]);
    $user = $stmt->fetch();

    if ($user) {
        // Activate account by setting is_verified = 1 and clearing the code
        $update = $pdo->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE id = ?");
        if ($update->execute([$user['id']])) {
            echo "<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>";
            echo "<h2 style='color:#28a745;'>✅ Email verified successfully!</h2>";
            echo "<p>You can now <a href='login.php' style='color:#FF7A00; text-decoration:none; font-weight:bold;'>Log in here</a>.</p>";
            echo "</div>";
        } else {
            echo "<h3 style='color:red; text-align:center; font-family:sans-serif;'>Failed to verify email. Please try again.</h3>";
        }
    } else {
        echo "<h3 style='color:red; text-align:center; font-family:sans-serif;'>Invalid or already used verification link.</h3>";
    }
} else {
    echo "No verification code provided.";
}
?>