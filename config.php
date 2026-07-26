<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'azmoon';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("خطا در اتصال به دیتابیس: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Tehran');
?>