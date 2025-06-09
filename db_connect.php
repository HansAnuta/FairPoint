<?php
// db_connect.php

$host = 'localhost'; // Your database host
$db   = 'djs';      // Your database name
$user = 'root';     // Your database username
$pass = '';         // Your database password (empty for root with no password)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "Database connected successfully!"; // Optional: for testing connection
} catch (\PDOException $e) {
    // Handle connection error gracefully, log it, and show a user-friendly message
    // In a production environment, avoid showing $e->getMessage() directly to users.
    die("Database connection failed: " . $e->getMessage());
}
?>