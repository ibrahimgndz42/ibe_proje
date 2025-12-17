<?php
// Session başlatma kontrolü (En başta olmalı)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "connectDB.php";
// DİKKAT: include "menu.php"; BURADAN SİLİNDİ. Aşağıya, body içine taşındı.

if (!isset($_GET['set_id'])) {
    echo "<center><h1>Geçersiz Set ID</h1><a href='sets.php'>Geri Dön</a></center>";
    exit;
}

$set_id = intval($_GET['set_id']);
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

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
    echo "<center><h1>Set bulunamadı</h1><a href='sets.php'>Geri Dön</a></center>";
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
    <link rel="stylesheet" href="style.css">
    <style>
        .view-wrapper {
            width: 90%; max-width: 900px; margin: 40px auto; padding: 30px;
            backdrop-filter: blur(15px); background: rgba(255, 255, 255, 0.4);
            border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.5); animation: fadeIn 0.6s ease;
            box-sizing: border-box; 
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .view-wrapper h1 { margin-bottom: 10px; text-align: center; color: #333; }
        .view-wrapper p { color: #555; text-align: center; margin-bottom: 20px; }

        /* --- YENİ PUANLAMA KUTUSU --- */
        .rating-box {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            padding: 10px 25px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin: 0 auto 25px auto;
            width: fit-content;
            gap: 15px;
        }

        .rating-score {
            font-size: 28px;
            font-weight: 800;
            color: #2d3436;
            line-height: 1;
        }
        
        .rating-score small {
            font-size: 12px;
            color: #b2bec3;
            font-weight: normal;
            display: block;
            text-align: center;
            margin-top: 2px;
        }

        .divider {
            width: 1px;
            height: 30px;
            background: #dfe6e9;
        }

        /* Yıldız Sistemi */
        .star-rating {
            direction: rtl;
            display: inline-flex;
            font-size: 22px;
            position: relative;
        }
        .star-rating input { display: none; }
        .star-rating label { color: #dcdcdc; cursor: pointer; transition: color 0.2s; padding: 0 2px; }

        /* Giriş Yapmış Kullanıcı İçin Hover Efekti */
        .star-rating:not(.disabled) input:checked ~ label,
        .star-rating:not(.disabled) label:hover,
        .star-rating:not(.disabled) label:hover ~ label {
            color: #f1c40f;
        }

        /* Sadece görüntüleme (Seçili olanı sarı yap ama hover çalışmaşın) */
        .star-rating.disabled input:checked ~ label {
            color: #f1c40f; 
        }
        .star-rating.disabled label {
            cursor: default; /* Tıklanamaz imleci */
        }

        /* Tooltip (Giriş Yapmayanlar İçin) */
        .tooltip-wrapper {
            position: relative;
            display: inline-block;
        }
        .tooltip-wrapper:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
            bottom: 110%;
        }
        .tooltip-text {
            visibility: hidden;
            width: 160px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px 0;
            position: absolute;
            z-index: 1;
            bottom: 120%;
            left: 50%;
            margin-left: -80px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
        }
        .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }

        .btn-rate-submit {
            background: #2d3436; color: #fff; border: none; 
            padding: 5px 12px; border-radius: 12px; cursor: pointer; font-size: 12px;
            margin-left: 10px; transition: 0.2s;
        }
        .btn-rate-submit:hover { background: #000; }

        /* Aksiyon ve Kart Alanı */
        .action-container { display: flex; flex-direction: column; gap: 20px; margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.3); }
        .study-actions { display: flex; justify-content: center; gap: 20px; width: 100%; }
        .btn-hero { flex: 1; max-width: 250px; padding: 15px 20px; border-radius: 16px; font-size: 16px; font-weight: 700; text-decoration: none; color: #fff; display: flex; align-items: center; justify-content: center; gap: 10px; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.2); }
        .btn-hero:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.2); }
        .hero-write { background: linear-gradient(135deg, #6c5ce7, #a29bfe); }
        .hero-quiz { background: linear-gradient(135deg, #00b894, #55efc4); }
        .admin-actions { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }
        .btn-small { padding: 8px 14px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: 0.2s; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.4); color: #444; }
        .btn-small:hover { background: rgba(255, 255, 255, 0.6); transform: translateY(-2px); }
        .btn-small.delete:hover { background: #ff7675; color: white; border-color: #ff7675; }
        .btn-small.edit:hover { background: #74b9ff; color: white; border-color: #74b9ff; }
        .btn-small.folder:hover { background: #ffeaa7; color: #333; border-color: #ffeaa7; }

        /* Flashcard */
        .flashcard-container { display: flex; justify-content: center; align-items: center; perspective: 1000px; margin: 40px 0; }
        .flashcard { width: 100%; max-width: 600px; height: 350px; position: relative; transform-style: preserve-3d; transition: transform 0.6s cubic-bezier(0.4, 0.2, 0.2, 1); cursor: pointer; }
        .flashcard.flipped { transform: rotateY(180deg); }
        .flashcard-face { position: absolute; width: 100%; height: 100%; backface-visibility: hidden; -webkit-backface-visibility: hidden; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 24px; font-weight: 600; padding: 30px; box-sizing: border-box; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); overflow-y: auto; word-wrap: break-word; }
        .flashcard-front { background: rgba(255, 255, 255, 0.95); color: #2c3e50; z-index: 2; border: 2px solid rgba(255, 255, 255, 0.5); display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .flip-hint { position: absolute; bottom: 15px; right: 20px; font-size: 13px; color: #95a5a6; font-weight: normal; opacity: 0.8; pointer-events: none; }
        .flashcard:hover .flashcard-front { background: #fff; border-color: #fff; }
        .flashcard-back { background-color: #2c3e50; color: #fff; transform: rotateY(180deg); text-shadow: 0 1px 2px rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.2); }
        .controls { display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 40px; }
        .controls button { padding: 12px 24px; font-size: 16px; cursor: pointer; background-color: #333; color: white; border: none; border-radius: 8px; transition: background 0.2s; }
        .controls button:hover { background-color: #555; }
        #cardCounter { font-size: 18px; font-weight: bold; font-family: monospace; background: rgba(255,255,255,0.6); padding: 5px 10px; border-radius: 5px; }

        /* Yorum Alanı */
        .comments-area { background: transparent; padding: 20px 0; }
        .comments-list { display: flex; flex-direction: column; gap: 15px; }
        .comment-card { background: rgba(255, 255, 255, 0.45); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.6); border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: all 0.2s ease; word-wrap: break-word; }
        .comment-card:hover { transform: translateY(-2px); background: rgba(255, 255, 255, 0.6); }
        .comment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .comment-author { font-weight: bold; color: #333; font-size: 15px; }
        .comment-date { color: #777; font-size: 12px; }
        .comment-content { color: #444; line-height: 1.6; font-size: 14px; }
        .comment-actions a { text-decoration: none; font-size: 12px; margin-left: 10px; transition: 0.2s; }
        .comment-actions a:hover { text-decoration: underline; }
        .new-comment-box textarea { width: 100%; height: 80px; padding: 15px; border: 1px solid rgba(255,255,255,0.6); background: rgba(255,255,255,0.5); border-radius: 12px; resize: none; outline: none; backdrop-filter: blur(5px); font-family: inherit; }
        .new-comment-box textarea:focus { background: rgba(255,255,255,0.8); border-color: #fff; }
    </style>
</head>
<body>

<?php include "menu.php"; ?>

<div class="view-wrapper">
    <div>
        <h1><?php echo htmlspecialchars($set['title']); ?></h1>
        <p><?php echo nl2br(htmlspecialchars($set['description'])); ?></p>
        
        <div style="text-align: center; margin-bottom: 20px;">
            <small>Kategori: <?php echo htmlspecialchars($set['category']); ?> | Oluşturan: <b><?php echo htmlspecialchars($set['username']); ?></b></small>
            
            <br><br>

            <div class="rating-box">
                <div class="rating-score">
                    <?php echo $avg_score > 0 ? $avg_score : "0.0"; ?>
                    <small><?php echo $total_votes; ?> oy</small>
                </div>

                <div class="divider"></div>

                <div class="tooltip-wrapper">
                    <?php if ($current_user_id == 0): ?>
                        <span class="tooltip-text">Puanlamak için giriş yapın</span>
                    <?php endif; ?>

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
                            <button type="submit" name="submit_rating" class="btn-rate-submit">Kaydet</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="action-container">
                <div class="study-actions">
                    <a href="write_mode.php?set_id=<?php echo $set_id; ?>" class="btn-hero hero-write">
                        ✍️ Yazma Modu
                    </a>
                    
                    <a href="quiz.php?set_id=<?php echo $set_id; ?>" class="btn-hero hero-quiz">
                        🧠 Test Çöz
                    </a>
                </div>

                <?php if ($current_user_id > 0): ?>
                    <div class="admin-actions">
                        <a href="select_folder.php?set_id=<?php echo $set_id; ?>" class="btn-small folder">
                            📁 Klasöre Ekle
                        </a>

                        <?php if ($set['owner_id'] == $current_user_id): ?>
                            <a href="edit_set.php?set_id=<?php echo $set_id; ?>" class="btn-small edit">
                                ✏️ Düzenle
                            </a>
                            <a href="delete_set.php?set_id=<?php echo $set_id; ?>" onclick="return confirm('Bu seti silmek istediğine emin misin?');" class="btn-small delete">
                                🗑️ Sil
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>            
        </div>
    </div>

    <?php if (count($cards) > 0): ?>
        <div class="flashcard-container" onclick="flipCard()">
            <div class="flashcard" id="flashcard">
                <div class="flashcard-face flashcard-front">
                    <span id="cardFrontText"></span>
                    <div class="flip-hint">⟳ Çevir</div>
                </div>
                <div class="flashcard-face flashcard-back <?php echo $theme_class; ?>" id="cardBack"></div>
            </div>
        </div>

        <div class="controls">
            <button onclick="prevCard()">&#8592; Önceki</button>
            <span id="cardCounter">1 / <?php echo count($cards); ?></span>
            <button onclick="nextCard()">Sonraki &#8594;</button>
        </div>
    <?php else: ?>
        <p style="text-align:center; margin-top:50px;">Bu sette henüz kart yok.</p>
    <?php endif; ?>

    <div class="comments-area">
        <h3 style="margin-bottom: 15px; color: #444;">Yorumlar</h3>
        <?php if ($current_user_id > 0): ?>
            <div class="new-comment-box" style="margin-bottom: 30px;">
                <form method="POST">
                    <textarea name="comment" placeholder="Bu set hakkında bir şeyler yaz..." required></textarea>
                    <div style="text-align: right; margin-top: 8px;">
                        <button type="submit" name="submit_comment" style="padding: 10px 25px; background: #6A5ACD; color: white; border: none; border-radius: 20px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 10px rgba(106, 90, 205, 0.3);">Yorum Yap</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <p style="margin-bottom: 20px;"><i>Yorum yapmak için <a href="login.php">giriş yapmalısın</a>.</i></p>
        <?php endif; ?>

        <div class="comments-list">
            <?php 
                $sql_comments = "SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.user_id WHERE set_id = $set_id ORDER BY created_at DESC";
                $res_comments = $conn->query($sql_comments);
            ?>
            <?php if ($res_comments->num_rows > 0): ?>
                <?php while($com = $res_comments->fetch_assoc()): ?>
                    <div class="comment-card">
                        <div class="comment-header">
                            <div>
                                <span class="comment-author"><?php echo htmlspecialchars($com['username']); ?></span>
                                <span class="comment-date">• <?php echo date("d.m.Y H:i", strtotime($com['created_at'])); ?></span>
                            </div>
                            <?php if ($current_user_id == $com['user_id']): ?>
                                <div class="comment-actions">
                                    <a href="view_set.php?set_id=<?php echo $set_id; ?>&edit_comment=<?php echo $com['comment_id']; ?>" style="color: #4a90e2;">✏️ Düzenle</a>
                                    <a href="view_set.php?set_id=<?php echo $set_id; ?>&delete_comment=<?php echo $com['comment_id']; ?>" onclick="return confirm('Silmek istediğine emin misin?');" style="color: #e74c3c;">🗑️ Sil</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($_GET['edit_comment']) && $_GET['edit_comment'] == $com['comment_id'] && $current_user_id == $com['user_id']): ?>
                            <form method="POST">
                                <input type="hidden" name="edit_comment_id" value="<?php echo $com['comment_id']; ?>">
                                <textarea name="edit_comment_text" style="width: 100%; height: 60px; padding: 10px; border-radius: 8px; border: 1px solid #ccc; resize: none;"><?php echo htmlspecialchars($com['comment_text']); ?></textarea>
                                <div style="margin-top: 8px;">
                                    <button type="submit" name="update_comment_submit" style="padding: 5px 15px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">Kaydet</button>
                                    <a href="view_set.php?set_id=<?php echo $set_id; ?>" style="color: #666; margin-left: 10px; font-size: 13px; text-decoration: none;">İptal</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="comment-content"><?php echo nl2br(htmlspecialchars($com['comment_text'])); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="comment-card" style="text-align: center; color: #777; font-style: italic;">Henüz hiç yorum yapılmamış. İlk yorumu sen yap!</div>
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
        }, 150);
    }
    function flipCard() { flashcard.classList.toggle("flipped"); }
    function nextCard() { if (currentIndex < cards.length - 1) { currentIndex++; updateCard(); } }
    function prevCard() { if (currentIndex > 0) { currentIndex--; updateCard(); } }
    updateCard();
</script>

</body>
</html>