<?php
session_start();
include "connectDB.php";

// Eğer kullanıcı giriş yapmışsa token silinsin
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];

    // Token ve süresini sıfırla
    $conn->query("
        UPDATE users 
        SET remember_token = NULL,
            remember_expire = NULL
        WHERE user_id = $uid
    ");
}

// Session içeriğini temizle
session_unset();

// Session'ı tamamen yok et
session_destroy();

// Cookie’yi tamamen sil
setcookie("remember_token", "", time() - 3600, "/", "", false, true);

// Ana sayfaya yönlendir
header("Location: index.php");
exit;
?>
