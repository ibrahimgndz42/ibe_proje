<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "connectDB.php";

// --- 1. EN İYİ SETLERİ ÇEK ---
$sql_top_rated = "SELECT sets.set_id, sets.title, users.username, categories.name AS category, 
                  AVG(set_ratings.rating) as avg_score, COUNT(set_ratings.rating) as total_votes
                  FROM sets
                  JOIN users ON sets.user_id = users.user_id
                  LEFT JOIN categories ON sets.category_id = categories.category_id
                  LEFT JOIN set_ratings ON sets.set_id = set_ratings.set_id
                  GROUP BY sets.set_id HAVING avg_score > 0 
                  ORDER BY avg_score DESC LIMIT 8";
$result_top = $conn->query($sql_top_rated);

$is_logged_in = isset($_SESSION['user_id']);
$is_admin = false;

// --- 2. KULLANICI VERİLERİ ---
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $sql_admin_check = "SELECT is_admin FROM users WHERE user_id = $user_id";
    $res_admin = $conn->query($sql_admin_check);
    if ($res_admin->num_rows > 0 && $res_admin->fetch_assoc()['is_admin'] == 1) {
        $is_admin = true;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini Sınavım</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- TEMEL AYARLAR --- */
        body {
            margin: 0; padding: 0;
            font-family: 'Inter', sans-serif;
            background: #f4f6f8;
            color: #333;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* --- ARKA PLAN KARTLARI --- */
        .bg-cards-container {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1; pointer-events: none; overflow: hidden;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .bg-card {
            position: absolute;
            width: 220px; height: 150px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            opacity: 0.5;
            background-image: linear-gradient(transparent 90%, rgba(255,255,255,0.3) 90%);
            background-size: 100% 20px;
            animation: floatCard 15s infinite ease-in-out alternate;
        }

        /* Renkler & Pozisyonlar */
        .card-blue { background-color: #a1c4fd; border: 2px solid rgba(255,255,255,0.6); }
        .card-purple { background-color: #c471ed; border: 2px solid rgba(255,255,255,0.6); }
        .card-green { background-color: #84fab0; border: 2px solid rgba(255,255,255,0.6); }
        .card-orange { background-color: #fccb90; border: 2px solid rgba(255,255,255,0.6); }
        .card-red { background-color: #ff9a9e; border: 2px solid rgba(255,255,255,0.6); }

        .pos-1 { top: 5%; left: 2%; transform: rotate(-8deg); animation-duration: 14s; }
        .pos-2 { top: 15%; right: 5%; transform: rotate(12deg); animation-duration: 16s; filter: blur(1px); }
        .pos-3 { bottom: 20%; left: 10%; transform: rotate(5deg); animation-duration: 18s; }
        .pos-4 { bottom: 5%; right: 2%; transform: rotate(-15deg); animation-duration: 15s; opacity: 0.3; }
        .pos-5 { top: 45%; left: 40%; transform: rotate(20deg); animation-duration: 20s; opacity: 0.2; }
        .pos-6 { top: 8%; right: 30%; transform: rotate(-5deg); animation-duration: 22s; opacity: 0.4; }
        .pos-7 { bottom: 40%; right: 25%; transform: rotate(15deg); animation-duration: 19s; opacity: 0.3; }
        .pos-8 { bottom: 10%; left: 40%; transform: rotate(-10deg); animation-duration: 21s; filter: blur(2px); }

        @keyframes floatCard {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-30px) rotate(5deg); }
        }

        /* --- ANA CONTAINER --- */
        .main-container {
            max-width: 1200px;
            width: 95%;
            margin: 30px auto;
            padding-bottom: 50px;
        }

        /* --- DASHBOARD STİLLERİ (LOGGED IN) --- */
        .welcome-section {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.5);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        .welcome-title { font-size: 2.5rem; margin: 0; color: #2d3436; }
        .welcome-subtitle { font-size: 1.1rem; color: #636e72; margin-top: 10px; }

        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px; }
        .action-card { background: #fff; padding: 20px; border-radius: 16px; text-align: center; text-decoration: none; color: #333; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; border: 1px solid rgba(0,0,0,0.05); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; }
        .action-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .action-icon { font-size: 2rem; color: #6c5ce7; }
        .action-text { font-weight: 700; font-size: 1.1rem; }

        /* --- KART STİLLERİ --- */
        .section-title { font-size: 1.8rem; margin-bottom: 25px; color: #2d3436; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid rgba(0,0,0,0.05); padding-bottom: 10px; }
        .sets-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .set-card { background: rgba(255, 255, 255, 0.85); border-radius: 16px; padding: 25px; position: relative; transition: all 0.3s ease; text-decoration: none; color: #333; display: flex; flex-direction: column; min-height: 180px; border: 1px solid rgba(255,255,255,0.6); }
        .set-card:hover { transform: translateY(-8px); background: #fff; box-shadow: 0 15px 30px rgba(0,0,0,0.15); }
        .badge-score { position: absolute; top: 15px; right: 15px; background: #fff; padding: 5px 10px; border-radius: 20px; font-weight: 800; font-size: 0.85rem; color: #f1c40f; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card-title { font-size: 1.3rem; font-weight: 700; margin: 0 0 10px 0; color: #2d3436; }
        .card-info { font-size: 0.9rem; color: #636e72; margin-bottom: 15px; }
        .card-footer { margin-top: auto; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .tag { background: #dfe6e9; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; color: #636e72; font-weight: 600; }

        /* --- GUEST HERO ALANI (DÜZENLENDİ: SİYAH YAZI) --- */
        .guest-hero-container {
            display: flex; flex-direction: column; justify-content: center; align-items: center; 
            padding: 40px 20px; min-height: 60vh; width: 100%;
        }
        .hero-box {
            padding: 50px 40px; text-align: center; max-width: 700px; width: 100%;
            background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(15px);
            border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.8s ease-out; margin-bottom: 50px;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* YAZI RENKLERİ GÜNCELLENDİ: BEYAZ YERİNE KOYU */
        .hero-title { 
            font-size: 3.5rem; 
            font-weight: 800; 
            color: #2d3436; /* Koyu Gri */
            margin: 0 0 10px 0; 
            /* text-shadow kaldırıldı veya çok hafifletildi */
        }
        .hero-subtitle { 
            font-size: 1.3rem; 
            color: #636e72; /* Orta Gri */
            margin-bottom: 40px; 
            min-height: 30px; 
            font-weight: 500; 
            transition: opacity 0.5s ease-in-out; 
        }
        
        .btn-group { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .hero-btn { padding: 12px 30px; border-radius: 50px; font-size: 1rem; font-weight: 600; text-decoration: none; transition: 0.3s; border: none; cursor: pointer; }
        
        .btn-white { background-color: #fff; color: #7b68ee; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-white:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); background-color: #f8f9fa; }
        
        /* BUTON RENGİ GÜNCELLENDİ: SİYAH ÇERÇEVE */
        .btn-outline { 
            background: transparent; 
            border: 2px solid #2d3436; 
            color: #2d3436; 
        }
        .btn-outline:hover { 
            background: #2d3436; 
            color: #fff; 
            transform: translateY(-3px); 
        }

    </style>
</head>
<body>

    <?php include "menu.php"; ?>

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

    <?php if ($is_logged_in): ?>
        
        <div class="main-container">
            
            <div class="welcome-section">
                <h1 class="welcome-title">Hoşgeldin, <?php echo htmlspecialchars($_SESSION['username']); ?> 👋</h1>
                <p class="welcome-subtitle">Bugün öğrenmek için harika bir gün!</p>

                <div class="quick-actions">
                    <a href="create_set.php" class="action-card">
                        <i class="fa-solid fa-plus-circle action-icon"></i>
                        <span class="action-text">Yeni Set Oluştur</span>
                    </a>
                    <a href="my_sets.php" class="action-card">
                        <i class="fa-solid fa-layer-group action-icon" style="color: #00b894;"></i>
                        <span class="action-text">Setlerim</span>
                    </a>
                    <a href="folders.php" class="action-card">
                        <i class="fa-solid fa-folder-open action-icon" style="color: #fdcb6e;"></i>
                        <span class="action-text">Klasörlerim</span>
                    </a>
                    <?php if ($is_admin): ?>
                    <a href="admin.php" class="action-card" style="border:1px solid #ff7675;">
                        <i class="fa-solid fa-shield-halved action-icon" style="color: #ff7675;"></i>
                        <span class="action-text">Admin Paneli</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-title">
                <i class="fa-solid fa-fire" style="color:#e17055;"></i> Top 8 En İyi Set
            </div>

            <div class="sets-grid">
                <?php if ($result_top->num_rows > 0): ?>
                    <?php 
                    $result_top->data_seek(0);
                    while($top = $result_top->fetch_assoc()): 
                    ?>
                        <a href="view_set.php?id=<?= $top['set_id'] ?>" class="set-card">
                            <div class="badge-score">
                                <i class="fa-solid fa-star"></i> <?= number_format($top['avg_score'], 1) ?>
                            </div>
                            <h3 class="card-title"><?= htmlspecialchars($top['title']) ?></h3>
                            <div class="card-info">
                                <i class="fa-regular fa-user"></i> <?= htmlspecialchars($top['username']) ?>
                            </div>
                            <div class="card-footer">
                                <span class="tag"><?= htmlspecialchars($top['category'] ?? 'Genel') ?></span>
                                <span style="font-size:12px; color:#999;"><?= $top['total_votes'] ?> oy</span>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; padding:30px; background:rgba(255,255,255,0.5); text-align:center; border-radius:12px;">
                        Henüz puanlanmış set yok.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        
        <div class="guest-hero-container">
            <div class="hero-box">
                <h1 class="hero-title">Mini Sınavım</h1>
                <p id="changingText" class="hero-subtitle">Yükleniyor...</p>

                <div class="btn-group">
                    <a href="sets.php" class="hero-btn btn-white">Setleri Keşfet</a>
                    <a href="login.php" class="hero-btn btn-outline">Giriş Yap</a>
                </div>
            </div>

            <div class="main-container" style="margin-top: 0;">
                <div class="section-title" style="justify-content: center; color: #444; border:none;">
                    <i class="fa-solid fa-star" style="color:#f1c40f;"></i> Popüler Setler
                </div>

                <div class="sets-grid">
                    <?php 
                    $result_top->data_seek(0);
                    $count = 0;
                    if ($result_top->num_rows > 0):
                        while($top = $result_top->fetch_assoc()): 
                            if($count >= 4) break; 
                            $count++;
                    ?>
                        <a href="view_set.php?id=<?= $top['set_id'] ?>" class="set-card" style="min-height:150px;">
                            <div class="badge-score"><i class="fa-solid fa-star"></i> <?= number_format($top['avg_score'], 1) ?></div>
                            <h3 class="card-title" style="font-size:1.1rem;"><?= htmlspecialchars($top['title']) ?></h3>
                            <div class="card-info" style="font-size:0.8rem;">
                                <i class="fa-regular fa-user"></i> <?= htmlspecialchars($top['username']) ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                    <?php else: ?>
                        <div style="grid-column: 1/-1; text-align:center; color:#666;">Henüz popüler set yok.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    <?php endif; ?>

    <script>
        const textElement = document.getElementById("changingText");
        
        if(textElement) {
            const texts = [
                "Kendi çalışma setlerini oluştur.",
                "Öğrenmeni hızlandır.",
                "Bilgini test et.",
                "Başarıya hazırlan."  
            ];
            let index = 0;

            function changeText() {
                textElement.style.opacity = 0;
                setTimeout(() => {
                    textElement.textContent = texts[index];
                    textElement.style.opacity = 1;
                    index = (index + 1) % texts.length;
                }, 500); 
            }

            changeText();
            setInterval(changeText, 4000);
        }
    </script>

</body>
</html>