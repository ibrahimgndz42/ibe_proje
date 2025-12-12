<?php
session_start();

// Tüm session değerlerini sil
session_unset();

// Session'ı tamamen yok et
session_destroy();

// Ana sayfaya yönlendir
header("Location: index.php");
exit;
?>
