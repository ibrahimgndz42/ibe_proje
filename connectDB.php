<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mini-sinavim-vt";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
