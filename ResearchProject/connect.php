<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set PHP timezone to match database timezone (UTC+8)
date_default_timezone_set('Asia/Manila');

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'researchproject';

$conn = new mysqli($host, $user, $password, $dbname);
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+08:00'");
$conn->query("SET NAMES utf8mb4");

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'DB connection failed: ' . $conn->connect_error
    ]);
    exit();
}
?>