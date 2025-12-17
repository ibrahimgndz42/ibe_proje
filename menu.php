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
            <a href="logout.php" 
                onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');" 
                style="color: #ff6b6b;">
                Çıkış Yap
            </a>
        <?php else: ?>
            <a href="login.php">Giriş Yap</a>
            <a href="register.php">Kayıt Ol</a>
        <?php endif; ?>
    </div>

</nav>

<div class="bg-cards-container">
    <div class="bg-card card-blue pos-1"></div>
    <div class="bg-card card-purple pos-2"></div>
    <div class="bg-card card-green pos-3"></div>
    <div class="bg-card card-orange pos-4"></div>
    <div class="bg-card card-red pos-5"></div>
    <div class="bg-card card-blue pos-6"></div>
    <div class="bg-card card-green pos-7"></div>
    <div class="bg-card card-purple pos-8"></div>
</div>