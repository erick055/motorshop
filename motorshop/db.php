<?php
$host = 'localhost';
$dbname = 'motoshop_db'; // Change to your database name
$user = 'root'; // Change to your database user
$pass = ''; // Change to your database password
$port = '3307';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>