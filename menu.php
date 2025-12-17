<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Menü Konteynerı - Cam Efekti */
    nav {
        display: flex;
        align-items: center;
        justify-content: space-between; /* Sağı ve solu ayır */
        padding: 15px 30px;
        
        /* Cam Efekti (Light Glass) */
        background: rgba(255, 255, 255, 0.4); 
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px); /* Safari desteği */
        border-bottom: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        
        /* Sayfa üstüne sabitleme */
        position: sticky;
        top: 0;
        z-index: 1000;
        margin-bottom: 20px;
        border-radius: 0 0 16px 16px; /* Alt köşeleri yuvarla */
    }

    /* Sol Taraf (Logo ve Linkler) */
    .nav-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Logo Stili */
    .brand-logo {
        font-size: 20px;
        font-weight: 800;
        color: #333;
        text-decoration: none;
        margin-right: 20px;
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .brand-logo i { color: #6A5ACD; font-size: 24px; }

    /* Link Stilleri */
    nav a.nav-link {
        color: #444;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        padding: 8px 16px;
        border-radius: 12px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Link Hover Efekti */
    nav a.nav-link:hover {
        background: rgba(255, 255, 255, 0.8);
        color: #6A5ACD; /* Hover rengi mor */
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    /* Aktif Sayfa Efekti (Opsiyonel: PHP ile class="active" eklersen çalışır) */
    nav a.nav-link.active {
        background: #6A5ACD;
        color: white;
    }

    /* Sağ Taraf (Kullanıcı İşlemleri) */
    .nav-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Giriş/Kayıt Butonları için özel stil */
    .btn-auth {
        padding: 8px 20px !important;
        border: 1px solid rgba(0,0,0,0.1);
    }
    .btn-login { background: rgba(255,255,255,0.5); }
    .btn-register { background: #333; color: #fff !important; }
    .btn-register:hover { background: #000 !important; color: #fff !important; }

    /* Çıkış Butonu */
    .btn-logout {
        color: #ff6b6b !important;
        border: 1px solid rgba(255, 107, 107, 0.2);
    }
    .btn-logout:hover {
        background: #ff6b6b !important;
        color: white !important;
    }

    /* Arka Plan Kartları (Senin kodun - z-index ile arkaya atıldı) */
    .bg-cards-container {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        z-index: -1; /* En arkaya at */
        overflow: hidden;
        pointer-events: none;
    }
    /* Senin mevcut .bg-card stillerin style.css'de durmalı */

    /* Mobil Uyumluluk */
    @media (max-width: 768px) {
        nav { flex-direction: column; gap: 15px; padding: 15px; }
        .nav-left, .nav-right { flex-wrap: wrap; justify-content: center; }
        .brand-logo { margin-right: 0; margin-bottom: 5px; }
    }
</style>

<nav>
    
    <div class="nav-left">
        <a href="index.php" class="brand-logo">
            <i class="fa-solid fa-layer-group"></i> FlashCard
        </a>
        <a href="sets.php" class="nav-link"><i class="fa-solid fa-clone"></i> Tüm Setler</a>
        <a href="oneri.php" class="nav-link"><i class="fa-regular fa-paper-plane"></i> Öneri</a>
    </div>

    <div class="nav-right">
        <?php if (isset($_SESSION["user_id"])): ?>
            
            <a href="create_set.php" class="nav-link" style="color: #6A5ACD;">
                <i class="fa-solid fa-plus-circle"></i> Set Oluştur
            </a>
            
            <a href="profile.php" class="nav-link">
                <i class="fa-solid fa-user-circle"></i> Profilim
            </a>
            
            <a href="logout.php" 
               class="nav-link btn-logout"
               onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');">
               <i class="fa-solid fa-right-from-bracket"></i> Çıkış
            </a>

        <?php else: ?>
            
            <a href="login.php" class="nav-link btn-auth btn-login">Giriş Yap</a>
            <a href="register.php" class="nav-link btn-auth btn-register">Kayıt Ol</a>

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