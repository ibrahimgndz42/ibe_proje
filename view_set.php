<?php
// Session başlatma kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "connectDB.php";

// Set ID Kontrolü
if (!isset($_GET['set_id'])) {
    echo "<div style='text-align:center; margin-top:50px;'><h1>Geçersiz Set ID</h1><a href='sets.php'>Geri Dön</a></div>";
    exit;
}

$set_id = intval($_GET['set_id']);
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// --- 0. SET SİLME İŞLEMİ (YENİ EKLENDİ) ---
// Linkten ?action=delete parametresi gelirse çalışır
if (isset($_GET['action']) && $_GET['action'] == 'delete' && $current_user_id > 0) {
    // 1. Önce bu setin sahibi gerçekten bu kullanıcı mı kontrol et
    $check_sql = "SELECT user_id FROM sets WHERE set_id = $set_id";
    $check_res = $conn->query($check_sql);

    if ($check_res->num_rows > 0) {
        $row = $check_res->fetch_assoc();
        if ($row['user_id'] == $current_user_id) {
            
            // 2. Bağlı Kartları Sil
            $conn->query("DELETE FROM cards WHERE set_id = $set_id");
            
            // 3. Bağlı Yorumları Sil (Veritabanında yetim veri kalmasın)
            $conn->query("DELETE FROM comments WHERE set_id = $set_id");

            // 4. Bağlı Puanları Sil
            $conn->query("DELETE FROM set_ratings WHERE set_id = $set_id");

            // 5. En son Seti Sil
            $conn->query("DELETE FROM sets WHERE set_id = $set_id");

            // 6. Listeleme sayfasına yönlendir
            header("Location: sets.php?msg=deleted");
            exit;
        } else {
            echo "<script>alert('Hata: Bu seti silme yetkiniz yok!'); window.location.href='view_set.php?set_id=$set_id';</script>";
            exit;
        }
    }
}

// --- 1. PUANLAMA İŞLEMİ ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_rating']) && $current_user_id > 0) {
    $puan = intval($_POST['rating']);
    if ($puan >= 1 && $puan <= 5) {
        $stmt_rate = $conn->prepare("INSERT INTO set_ratings (set_id, user_id, rating, created_at) VALUES (?, ?, ?, NOW()) 
                                     ON DUPLICATE KEY UPDATE rating = VALUES(rating), created_at = NOW()");
        $stmt_rate->bind_param("iii", $set_id, $current_user_id, $puan);
        $stmt_rate->execute();
        header("Location: view_set.php?set_id=$set_id"); 
        exit;
    }
}

// 2. Set bilgilerini çek
$sql_set = "SELECT sets.*, users.username, users.user_id as owner_id, categories.name AS category, 
            themes.css_class
            FROM sets 
            JOIN users ON sets.user_id = users.user_id
            LEFT JOIN categories ON sets.category_id = categories.category_id
            LEFT JOIN themes ON sets.theme_id = themes.theme_id
            WHERE sets.set_id = $set_id";

$result_set = $conn->query($sql_set);

if ($result_set->num_rows == 0) {
    echo "<div style='text-align:center; margin-top:50px;'><h1>Set bulunamadı</h1><a href='sets.php'>Geri Dön</a></div>";
    exit;
}

$set = $result_set->fetch_assoc();
$theme_class = !empty($set['css_class']) ? $set['css_class'] : 'bg-default';

// --- 3. PUAN BİLGİLERİNİ ÇEK ---
$sql_avg = "SELECT AVG(rating) as avg_score, COUNT(*) as total_votes FROM set_ratings WHERE set_id = $set_id";
$rating_data = $conn->query($sql_avg)->fetch_assoc();
$avg_score = round($rating_data['avg_score'], 1); 
$total_votes = $rating_data['total_votes'];

// Kullanıcının kendi puanı
$my_rating = 0;
if ($current_user_id > 0) {
    $sql_mine = "SELECT rating FROM set_ratings WHERE set_id = $set_id AND user_id = $current_user_id";
    $res_mine = $conn->query($sql_mine);
    if ($res_mine->num_rows > 0) {
        $my_rating = $res_mine->fetch_assoc()['rating'];
    }
}

// --- YORUM İŞLEMLERİ ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_comment']) && $current_user_id > 0) {
    $comment_text = trim($_POST['comment']);
    if (!empty($comment_text)) {
        $stmt_com = $conn->prepare("INSERT INTO comments (set_id, user_id, comment_text) VALUES (?, ?, ?)");
        $stmt_com->bind_param("iis", $set_id, $current_user_id, $comment_text);
        $stmt_com->execute();
        header("Location: view_set.php?set_id=$set_id");
        exit;
    }
}

// Yorum Silme
if (isset($_GET['delete_comment']) && $current_user_id > 0) {
    $del_id = intval($_GET['delete_comment']);
    $check_owner = $conn->query("SELECT * FROM comments WHERE comment_id = $del_id AND user_id = $current_user_id");
    if ($check_owner->num_rows > 0) {
        $conn->query("DELETE FROM comments WHERE comment_id = $del_id");
        header("Location: view_set.php?set_id=$set_id");
        exit;
    }
}

// Yorum Güncelleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_comment_submit']) && $current_user_id > 0) {
    $upd_id = intval($_POST['edit_comment_id']);
    $upd_text = trim($_POST['edit_comment_text']);
    
    $check_owner = $conn->query("SELECT * FROM comments WHERE comment_id = $upd_id AND user_id = $current_user_id");
    if ($check_owner->num_rows > 0 && !empty($upd_text)) {
        $stmt_upd = $conn->prepare("UPDATE comments SET comment_text = ? WHERE comment_id = ?");
        $stmt_upd->bind_param("si", $upd_text, $upd_id);
        $stmt_upd->execute();
        header("Location: view_set.php?set_id=$set_id");
        exit;
    }
}

// 2. Kartları çek
$sql_cards = "SELECT term , defination FROM cards WHERE set_id = $set_id";
$result_cards = $conn->query($sql_cards);
$cards = [];
while($row = $result_cards->fetch_assoc()) {
    $cards[] = $row;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($set['title']); ?> - Mini Sınavım</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }
        .view-wrapper {
            width: 90%; max-width: 950px; margin: 40px auto; padding: 40px;
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px);
            border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.6); animation: fadeIn 0.6s ease;
            box-sizing: border-box; 
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .set-header { text-align: center; margin-bottom: 40px; position: relative; }
        .set-title {
            font-size: 2.8rem; font-weight: 700; margin: 0 0 10px 0;
            background: linear-gradient(135deg, #2d3436 0%, #000 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            letter-spacing: -1px; line-height: 1.2;
        }
        .set-description { font-size: 1.05rem; color: #636e72; max-width: 700px; margin: 0 auto 20px auto; line-height: 1.6; }
        
        .meta-badges { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; margin-bottom: 25px; }
        .badge {
            display: inline-flex; align-items: center; padding: 6px 14px;
            border-radius: 20px; font-size: 0.85rem; font-weight: 500;
            background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.05); color: #555; transition: transform 0.2s;
        }
        .badge:hover { transform: translateY(-2px); }
        .badge i { margin-right: 6px; color: #6c5ce7; }
        .badge.category { background: #eef2ff; color: #4338ca; border-color: #e0e7ff; }
        .badge.user { background: #fff1f2; color: #be123c; border-color: #ffe4e6; }
        .badge.count { background: #ecfdf5; color: #047857; border-color: #d1fae5; }

        .stats-bar {
            display: flex; align-items: center; justify-content: space-between;
            background: #fff; padding: 15px 30px; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.03);
            margin-bottom: 30px; flex-wrap: wrap; gap: 20px;
        }
        .rating-display { display: flex; align-items: center; gap: 15px; }
        .rating-number { font-size: 2rem; font-weight: 800; color: #2d3436; }
        .rating-meta { display: flex; flex-direction: column; font-size: 0.8rem; color: #b2bec3; }

        .star-rating { direction: rtl; display: inline-flex; font-size: 20px; }
        .star-rating input { display: none; }
        .star-rating label { color: #e0e0e0; cursor: pointer; transition: color 0.2s; padding: 0 1px; }
        .star-rating:not(.disabled) input:checked ~ label,
        .star-rating:not(.disabled) label:hover,
        .star-rating:not(.disabled) label:hover ~ label { color: #f1c40f; }
        .star-rating.disabled input:checked ~ label { color: #f1c40f; }
        .star-rating.disabled label { cursor: default; }

        .btn-rate-save {
            background: #2d3436; color: #fff; border: none; padding: 6px 12px;
            border-radius: 8px; font-size: 0.75rem; cursor: pointer; margin-left: 10px; transition: 0.2s;
        }
        .btn-rate-save:hover { background: #000; }

        .action-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 0; }
        @media (min-width: 768px) { .stats-bar { justify-content: space-between; } }

        .btn-hero {
            padding: 18px 20px; border-radius: 14px; font-size: 1rem; font-weight: 600;
            text-decoration: none; color: #fff; display: flex; align-items: center;
            justify-content: center; gap: 12px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn-hero::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.1); transform: translateX(-100%); transition: transform 0.3s;
        }
        .btn-hero:hover::before { transform: translateX(0); }
        .btn-hero:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }

        .hero-write { background: linear-gradient(135deg, #6c5ce7, #8e44ad); }
        .hero-quiz { background: linear-gradient(135deg, #00b894, #00cec9); }

        .admin-controls {
            display: flex; justify-content: center; gap: 12px; margin-top: 25px;
            padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.05);
        }
        .btn-sub {
            padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; text-decoration: none;
            color: #555; background: #f8f9fa; border: 1px solid #dfe6e9; transition: 0.2s;
            display: flex; align-items: center; gap: 6px;
        }
        .btn-sub:hover { background: #e2e6ea; color: #333; }
        .btn-sub.del:hover { background: #ffebee; color: #c62828; border-color: #ffcdd2; }

        .flashcard-container { display: flex; justify-content: center; align-items: center; perspective: 1000px; margin: 50px 0; }
        .flashcard { 
            width: 100%; max-width: 650px; height: 380px; position: relative;
            transform-style: preserve-3d; transition: transform 0.6s cubic-bezier(0.4, 0.2, 0.2, 1); cursor: pointer; 
        }
        .flashcard.flipped { transform: rotateY(180deg); }
        .flashcard-face { 
            position: absolute; width: 100%; height: 100%; backface-visibility: hidden;
            -webkit-backface-visibility: hidden; display: flex; align-items: center;
            justify-content: center; text-align: center; font-size: 26px; font-weight: 500;
            padding: 40px; box-sizing: border-box; border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
        }
        .flashcard-front { background: #fff; color: #2d3436; border: 1px solid rgba(0,0,0,0.05); }
        .flashcard-back { background: #2d3436; color: #fff; transform: rotateY(180deg); }
        
        .bg-default { background: #2d3436; }
        .bg-blue { background: linear-gradient(135deg, #0984e3, #74b9ff); }
        .bg-red { background: linear-gradient(135deg, #d63031, #ff7675); }
        .bg-green { background: linear-gradient(135deg, #00b894, #55efc4); }

        .controls { display: flex; justify-content: center; align-items: center; gap: 20px; margin-bottom: 50px; }
        .nav-btn {
            background: #fff; border: 1px solid #dfe6e9; color: #2d3436; width: 50px; height: 50px;
            border-radius: 50%; cursor: pointer; transition: 0.2s; font-size: 18px;
            display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .nav-btn:hover { background: #2d3436; color: white; transform: scale(1.1); }
        #cardCounter { font-weight: 600; color: #b2bec3; letter-spacing: 1px; }

        .comments-section { margin-top: 40px; padding-top: 30px; border-top: 1px solid rgba(0,0,0,0.06); }
        .comment-box { 
            background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); border: 1px solid #f1f2f6;
        }
        .comment-header { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9rem; }
        .comment-user { font-weight: 700; color: #2d3436; }
        .comment-time { color: #b2bec3; font-size: 0.8rem; }
        .comment-text { color: #636e72; line-height: 1.5; }
        
        textarea.modern-input {
            width: 100%; border: 1px solid #dfe6e9; border-radius: 12px; padding: 15px;
            font-family: inherit; resize: none; outline: none; background: #fdfdfd; transition: 0.2s;
        }
        textarea.modern-input:focus { border-color: #6c5ce7; background: #fff; }

        @media (max-width: 600px) {
            .view-wrapper { width: 95%; padding: 20px; }
            .set-title { font-size: 2rem; }
            .stats-bar { flex-direction: column; text-align: center; }
            .action-grid { grid-template-columns: 1fr; }
            .flashcard { height: 300px; }
        }
    </style>
</head>
<body>

<?php include "menu.php"; ?>

<div class="view-wrapper">
    
    <header class="set-header">
        <h1 class="set-title"><?php echo htmlspecialchars($set['title']); ?></h1>
        
        <div class="meta-badges">
            <span class="badge category"><i class="fa fa-tag"></i> <?php echo htmlspecialchars($set['category']); ?></span>
            <span class="badge user"><i class="fa fa-user"></i> <?php echo htmlspecialchars($set['username']); ?></span>
            <span class="badge count"><i class="fa fa-layer-group"></i> <?php echo count($cards); ?> Kart</span>
        </div>

        <?php if (!empty($set['description'])): ?>
            <p class="set-description"><?php echo nl2br(htmlspecialchars($set['description'])); ?></p>
        <?php endif; ?>
    </header>

    <div class="stats-bar">
        <div class="rating-display">
            <div class="rating-number"><?php echo $avg_score > 0 ? $avg_score : "0.0"; ?></div>
            <div class="rating-meta">
                <form method="POST" style="display:flex; align-items:center;">
                    <div class="star-rating <?php echo $current_user_id == 0 ? 'disabled' : ''; ?>">
                        <input type="radio" name="rating" id="star5" value="5" <?php echo ($my_rating == 5) ? 'checked' : ''; ?> <?php echo ($current_user_id == 0) ? 'disabled' : ''; ?>>
                        <label for="star5" class="fa fa-star"></label>

                        <input type="radio" name="rating" id="star4" value="4" <?php echo ($my_rating == 4) ? 'checked' : ''; ?> <?php echo ($current_user_id == 0) ? 'disabled' : ''; ?>>
                        <label for="star4" class="fa fa-star"></label>

                        <input type="radio" name="rating" id="star3" value="3" <?php echo ($my_rating == 3) ? 'checked' : ''; ?> <?php echo ($current_user_id == 0) ? 'disabled' : ''; ?>>
                        <label for="star3" class="fa fa-star"></label>

                        <input type="radio" name="rating" id="star2" value="2" <?php echo ($my_rating == 2) ? 'checked' : ''; ?> <?php echo ($current_user_id == 0) ? 'disabled' : ''; ?>>
                        <label for="star2" class="fa fa-star"></label>

                        <input type="radio" name="rating" id="star1" value="1" <?php echo ($my_rating == 1) ? 'checked' : ''; ?> <?php echo ($current_user_id == 0) ? 'disabled' : ''; ?>>
                        <label for="star1" class="fa fa-star"></label>
                    </div>
                    <?php if($current_user_id > 0): ?>
                        <button type="submit" name="submit_rating" class="btn-rate-save">Oyla</button>
                    <?php endif; ?>
                </form>
                <span><?php echo $total_votes; ?> kişi oyladı</span>
            </div>
        </div>

        <div class="action-grid" style="flex:1; max-width: 500px;">
            <a href="write_mode.php?set_id=<?php echo $set_id; ?>" class="btn-hero hero-write">
                <i class="fa fa-pen-fancy"></i> Yazma Modu
            </a>
            <a href="quiz.php?set_id=<?php echo $set_id; ?>" class="btn-hero hero-quiz">
                <i class="fa fa-brain"></i> Test Çöz
            </a>
        </div>
    </div>

    <?php if ($current_user_id > 0): ?>
        <div class="admin-controls">
            <a href="select_folder.php?set_id=<?php echo $set_id; ?>" class="btn-sub">
                <i class="fa fa-folder-plus"></i> Klasöre Ekle
            </a>

            <?php if ($set['owner_id'] == $current_user_id): ?>
                <a href="edit_set.php?set_id=<?php echo $set_id; ?>" class="btn-sub">
                    <i class="fa fa-edit"></i> Düzenle
                </a>
                <a href="view_set.php?set_id=<?php echo $set_id; ?>&action=delete" onclick="return confirm('Bu seti tamamen silmek istediğine emin misin? Bu işlem geri alınamaz!');" class="btn-sub del">
                    <i class="fa fa-trash"></i> Sil
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (count($cards) > 0): ?>
        <div class="flashcard-container" onclick="flipCard()">
            <div class="flashcard" id="flashcard">
                <div class="flashcard-face flashcard-front">
                    <span id="cardFrontText"></span>
                    <div style="position: absolute; bottom: 20px; font-size: 12px; color: #b2bec3;">
                        <i class="fa fa-sync-alt"></i> Çevirmek için tıkla
                    </div>
                </div>
                <div class="flashcard-face flashcard-back <?php echo $theme_class; ?>" id="cardBack"></div>
            </div>
        </div>

        <div class="controls">
            <button class="nav-btn" onclick="prevCard()"><i class="fa fa-chevron-left"></i></button>
            <span id="cardCounter">1 / <?php echo count($cards); ?></span>
            <button class="nav-btn" onclick="nextCard()"><i class="fa fa-chevron-right"></i></button>
        </div>
    <?php else: ?>
        <div style="text-align:center; padding: 40px; color:#999;">
            <i class="fa fa-box-open" style="font-size: 40px; margin-bottom: 10px;"></i>
            <p>Bu sette henüz kart yok.</p>
        </div>
    <?php endif; ?>

    <div class="comments-section">
        <h3 style="color: #2d3436; margin-bottom: 20px;">Yorumlar (<?php echo $res_comments->num_rows ?? 0; ?>)</h3>
        
        <?php if ($current_user_id > 0): ?>
            <form method="POST" style="margin-bottom: 30px;">
                <textarea name="comment" class="modern-input" rows="3" placeholder="Bu set hakkında düşüncelerini paylaş..." required></textarea>
                <div style="text-align: right; margin-top: 10px;">
                    <button type="submit" name="submit_comment" style="padding: 10px 25px; background: #6c5ce7; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Gönder</button>
                </div>
            </form>
        <?php else: ?>
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; color: #856404; margin-bottom:20px;">
                Yorum yapmak için <a href="login.php" style="color:#856404; font-weight:bold;">giriş yapmalısın</a>.
            </div>
        <?php endif; ?>

        <div class="comments-list">
            <?php 
                // Yorum sorgusunu burada tekrar çalıştırıyoruz
                $sql_comments = "SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.user_id WHERE set_id = $set_id ORDER BY created_at DESC";
                $res_comments = $conn->query($sql_comments);
            ?>
            <?php if ($res_comments->num_rows > 0): ?>
                <?php while($com = $res_comments->fetch_assoc()): ?>
                    <div class="comment-box">
                        <div class="comment-header">
                            <span class="comment-user"><?php echo htmlspecialchars($com['username']); ?></span>
                            <span class="comment-time"><?php echo date("d.m.Y H:i", strtotime($com['created_at'])); ?></span>
                        </div>
                        
                        <?php if (isset($_GET['edit_comment']) && $_GET['edit_comment'] == $com['comment_id'] && $current_user_id == $com['user_id']): ?>
                            <form method="POST">
                                <input type="hidden" name="edit_comment_id" value="<?php echo $com['comment_id']; ?>">
                                <textarea name="edit_comment_text" class="modern-input" style="height: 60px;"><?php echo htmlspecialchars($com['comment_text']); ?></textarea>
                                <div style="margin-top: 8px;">
                                    <button type="submit" name="update_comment_submit" class="btn-sub" style="display:inline-block; border-color:#2ecc71; color:#2ecc71;">Kaydet</button>
                                    <a href="view_set.php?set_id=<?php echo $set_id; ?>" style="color: #999; margin-left: 10px; font-size: 13px; text-decoration: none;">İptal</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="comment-text"><?php echo nl2br(htmlspecialchars($com['comment_text'])); ?></div>
                            <?php if ($current_user_id == $com['user_id']): ?>
                                <div style="margin-top: 10px; font-size: 12px;">
                                    <a href="view_set.php?set_id=<?php echo $set_id; ?>&edit_comment=<?php echo $com['comment_id']; ?>" style="color: #0984e3; margin-right: 10px; text-decoration: none;">Düzenle</a>
                                    <a href="view_set.php?set_id=<?php echo $set_id; ?>&delete_comment=<?php echo $com['comment_id']; ?>" onclick="return confirm('Silmek istediğine emin misin?');" style="color: #d63031; text-decoration: none;">Sil</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #b2bec3; text-align: center; margin-top: 20px;">İlk yorumu sen yap!</p>
            <?php endif; ?>
        </div>
    </div> 
</div> 

<script>
    const cards = <?php echo json_encode($cards); ?>;
    const frontText = document.getElementById("cardFrontText");
    const flashcard = document.getElementById("flashcard");
    const back = document.getElementById("cardBack");
    const counter = document.getElementById("cardCounter");
    let currentIndex = 0;

    function updateCard() {
        if (cards.length === 0) return;
        flashcard.classList.remove("flipped");
        
        setTimeout(() => {
            frontText.textContent = cards[currentIndex].term;
            back.textContent = cards[currentIndex].defination;
            counter.textContent = (currentIndex + 1) + " / " + cards.length;
        }, 300);
    }

    function flipCard() { flashcard.classList.toggle("flipped"); }
    
    function nextCard() { 
        if (currentIndex < cards.length - 1) { 
            currentIndex++; 
            updateCard(); 
        } 
    }
    
    function prevCard() { 
        if (currentIndex > 0) { 
            currentIndex--; 
            updateCard(); 
        } 
    }
    
    document.addEventListener('keydown', function(event) {
        if(event.key === "ArrowRight") nextCard();
        if(event.key === "ArrowLeft") prevCard();
        if(event.key === " " || event.key === "Enter") flipCard();
    });

    updateCard();
</script>

</body>
</html>