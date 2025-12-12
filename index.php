<?php
// Session başlatılmamışsa başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "connectDB.php";
include "menu.php";

$is_logged_in = isset($_SESSION['user_id']);
$my_sets = [];
$my_folders = [];

// EĞER KULLANICI GİRİŞ YAPMIŞSA VERİLERİ ÇEK
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];

    // 1. Kullanıcının Setlerini Çek (Son 5 tanesi)
    $sql_sets = "SELECT set_id, title FROM sets WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5";
    $result_sets = $conn->query($sql_sets);
    
    // 2. Kullanıcının Klasörlerini Çek (Son 5 tanesi)
    $sql_folders = "SELECT folder_id, name FROM folders WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5";
    $result_folders = $conn->query($sql_folders);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini Sınavım - Ana Sayfa</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* TEMEL AYARLAR */
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #8EC5FC, #E0C3FC);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        html { overflow-y: scroll; }
        * { box-sizing: border-box; }

        /* ORTAK CAM EFEKTİ SINIFI */
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        /* --- SENARYO 1: GİRİŞ YAPILMAMIŞ (HERO ORTADA) --- */
        .hero-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            width: 100%;
        }

        .hero-card {
            padding: 50px 40px;
            text-align: center;
            max-width: 600px;
            width: 100%;
            animation: floatIn 1s ease-out;
        }

        /* --- SENARYO 2: GİRİŞ YAPILMIŞ (DASHBOARD) --- */
        .dashboard-container {
            display: flex;
            flex-wrap: wrap; /* Mobilde alt alta geçsin */
            gap: 20px;
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            align-items: flex-start; /* Yukarı hizala */
        }

        /* SOL MENÜ (Sidebar) */
        .sidebar {
            flex: 1;
            min-width: 280px;
            padding: 25px;
            color: #fff;
        }

        .sidebar h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 1px solid rgba(255,255,255,0.3);
            padding-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar h3 a {
            font-size: 12px;
            color: #fff;
            text-decoration: none;
            background: rgba(255,255,255,0.2);
            padding: 3px 8px;
            border-radius: 10px;
        }

        .list-group {
            list-style: none;
            padding: 0;
            margin: 0 0 30px 0; /* Alt boşluk */
        }

        .list-item {
            margin-bottom: 8px;
        }

        .list-link {
            display: block;
            padding: 10px 15px;
            background: rgba(255,255,255,0.15);
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .list-link:hover {
            background: rgba(255,255,255,0.4);
            transform: translateX(5px);
            color: #333;
        }

        .empty-text {
            font-size: 13px;
            color: rgba(255,255,255,0.7);
            font-style: italic;
        }

        /* SAĞ İÇERİK (Welcome Area) */
        .main-content {
            flex: 2;
            min-width: 300px;
            padding: 40px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 400px; /* Sidebar ile dengeli dursun */
        }

        /* ORTAK TİPOGRAFİ */
        .title {
            font-size: 3rem;
            font-weight: 700;
            color: #fff;
            margin: 0 0 10px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .subtitle {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 40px;
            min-height: 30px;
            font-weight: 400;
            transition: opacity 0.5s ease-in-out;
        }

        /* BUTONLAR */
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-white {
            background-color: #fff;
            color: #7b68ee;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn-white:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            background-color: #f8f9fa;
        }

        .btn-outline {
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.6);
            color: #fff;
        }
        .btn-outline:hover {
            background: rgba(255,255,255,0.3);
            border-color: #fff;
            transform: translateY(-3px);
        }

        @keyframes floatIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .dashboard-container { flex-direction: column-reverse; } /* Mobilde menü altta kalsın, ana içerik üstte */
            .sidebar { width: 100%; }
            .main-content { width: 100%; min-height: auto; }
        }
    </style>
</head>
<body>

    <?php if ($is_logged_in): ?>
        
        <div class="dashboard-container">
            
            <div class="sidebar glass-effect">
                
                <h3>
                    <span><i class="fa-solid fa-layer-group"></i> Setlerim</span>
                    <a href="my_sets.php">Tümü</a>
                </h3>
                <ul class="list-group">
                    <?php if ($result_sets->num_rows > 0): ?>
                        <?php while($set = $result_sets->fetch_assoc()): ?>
                            <li class="list-item">
                                <a href="view_set.php?id=<?= $set['set_id'] ?>" class="list-link">
                                    📄 <?= htmlspecialchars($set['title']) ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="empty-text">Henüz set oluşturmadın.</li>
                    <?php endif; ?>
                </ul>

                <h3>
                    <span><i class="fa-solid fa-folder"></i> Klasörlerim</span>
                    <a href="folders.php">Tümü</a>
                </h3>
                <ul class="list-group">
                    <?php if ($result_folders->num_rows > 0): ?>
                        <?php while($folder = $result_folders->fetch_assoc()): ?>
                            <li class="list-item">
                                <a href="view_folder.php?id=<?= $folder['folder_id'] ?>" class="list-link">
                                    📁 <?= htmlspecialchars($folder['name']) ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="empty-text">Henüz klasörün yok.</li>
                    <?php endif; ?>
                </ul>

                <div style="text-align:center; margin-top:30px; display: flex; flex-direction: column; gap: 10px;">
                    <a href="create_set.php" class="btn btn-white" style="font-size:14px; padding:10px 20px;">+ Yeni Set Oluştur</a>
                    <a href="folders.php" class="btn btn-outline" style="font-size:14px; padding:10px 20px;">+ Yeni Klasör Oluştur</a>
                </div>

            </div>

            <div class="main-content glass-effect">
                <h1 class="title">Merhaba, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
                <p id="changingText" class="subtitle">Hoşgeldin...</p>

                <div class="btn-group">
                    <a href="sets.php" class="btn btn-white">Tüm Setleri Keşfet</a>
                    </div>
            </div>

        </div>

    <?php else: ?>

        <div class="hero-container">
            <div class="hero-card glass-effect">
                <h1 class="title">Mini Sınavım</h1>
                <p id="changingText" class="subtitle">Yükleniyor...</p>

                <div class="btn-group">
                    <a href="sets.php" class="btn btn-white">Setleri Keşfet</a>
                    <a href="login.php" class="btn btn-outline">Giriş Yap / Set Oluştur</a>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <script>
        const texts = [
            "Kendi çalışma setlerini oluştur.",
            "Öğrenmeni hızlandır.",
            "Bilgini test et.",
            "Başarıya hazırlan."  
        ];

        let index = 0;
        const textElement = document.getElementById("changingText");

        function changeText() {
            if(!textElement) return; // Element yoksa dur
            
            // Önce yazıyı görünmez yap (fade out)
            textElement.style.opacity = 0;

            setTimeout(() => {
                // Yazıyı değiştir
                textElement.textContent = texts[index];
                // Tekrar görünür yap (fade in)
                textElement.style.opacity = 1;
                // Sıradaki index'e geç
                index = (index + 1) % texts.length;
            }, 500); 
        }

        // İlk açılışta hemen çalıştır
        changeText();
        // Her 4 saniyede bir değiştir
        setInterval(changeText, 4000);
    </script>

</body>
</html>