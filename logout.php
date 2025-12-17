<?php
session_start();
include "connectDB.php";

// Eğer kullanıcı giriş yapmışsa veritabanındaki token bilgilerini temizle
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']); // Güvenlik için intval ekledik

    // Token ve süresini sıfırla
    $conn->query("
        UPDATE users 
        SET remember_token = NULL,
            remember_expire = NULL
        WHERE user_id = $uid
    ");
}

// Session içeriğini boşalt
$_SESSION = array();

// Session'ı tamamen yok et
session_destroy();

// "Beni Hatırla" Cookie'sini tarayıcıdan sil
if (isset($_COOKIE['remember_token'])) {
    setcookie("remember_token", "", time() - 3600, "/", "", false, true);
}

// Ana sayfaya yönlendir
header("Location: index.php");
exit;
?>