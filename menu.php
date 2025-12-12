<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 
?>

<style>
    nav {
        background: #333;
        padding: 10px;
    }
    nav a {
        color: white;
        margin-right: 15px;
        text-decoration: none;
        font-weight: bold;
    }
    nav a:hover {
        text-decoration: underline;
    }
</style>

<nav>

    <a href="index.php">Ana Sayfa</a>

    <a href="sets.php">Tüm Setler</a>

    <?php if (isset($_SESSION["user_id"])): ?>
        <a href="profile.php">Profilim</a>
        <a href="create_set.php">Set Oluştur</a>
        <a href="logout.php">Çıkış Yap</a>
    <?php else: ?>
        <a href="login.php">Giriş Yap</a>
        <a href="register.php">Kayıt Ol</a>
    <?php endif; ?>

</nav>
