<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 
?>

<style>
    nav {
        background: #333;
        padding: 10px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }
    nav a {
        color: white;
        margin-right: 15px;
        text-decoration: none;
        font-weight: bold;
    }
    nav a:hover {
        text-decoration: underline;
        color: #ddd;
    }
    /* Sağ tarafa yaslamak istersen opsiyonel */
    .nav-right {
        margin-left: auto;
    }
</style>

<nav>

    <a href="index.php">Ana Sayfa</a>

    <a href="sets.php">Tüm Setler</a>
    
    <a href="oneri.php">İstek ve Öneri</a>

    <div class="nav-right">
        <?php if (isset($_SESSION["user_id"])): ?>
            <a href="profile.php">Profilim</a>
            <a href="create_set.php">Set Oluştur</a>
            <a href="logout.php" style="color: #ff6b6b;">Çıkış Yap</a>
        <?php else: ?>
            <a href="login.php">Giriş Yap</a>
            <a href="register.php">Kayıt Ol</a>
        <?php endif; ?>
    </div>

</nav>