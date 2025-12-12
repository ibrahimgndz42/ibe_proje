<?php
include "connectDB.php";
include "menu.php";

if (!isset($_GET['id'])) {
    echo "<center><h1>Geçersiz Set ID</h1><a href='sets.php'>Geri Dön</a></center>";
    exit;
}

$set_id = intval($_GET['id']);

// 1. Set bilgilerini çek
$sql_set = "SELECT sets.*, users.username, users.user_id as owner_id, categories.name AS category
            FROM sets 
            JOIN users ON sets.user_id = users.user_id
            LEFT JOIN categories ON sets.category_id = categories.category_id
            WHERE sets.set_id = $set_id";

$result_set = $conn->query($sql_set);

if ($result_set->num_rows == 0) {
    echo "<center><h1>Set bulunamadı</h1><a href='sets.php'>Geri Dön</a></center>";
    exit;
}

$set = $result_set->fetch_assoc();

// Yorum Gönderme İşlemi
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_comment']) && $current_user_id > 0) {
    $comment_text = trim($_POST['comment']);
    if (!empty($comment_text)) {
        $stmt_com = $conn->prepare("INSERT INTO comments (set_id, user_id, comment_text) VALUES (?, ?, ?)");
        $stmt_com->bind_param("iis", $set_id, $current_user_id, $comment_text);
        if ($stmt_com->execute()) {
            header("Location: view_set.php?id=$set_id");
            exit;
        } else {
            echo "<script>alert('Yorum gönderilirken hata oluştu.');</script>";
        }
    }
}

// Yorum Silme İşlemi
if (isset($_GET['delete_comment']) && $current_user_id > 0) {
    $del_id = intval($_GET['delete_comment']);
    $check_owner = $conn->query("SELECT * FROM comments WHERE comment_id = $del_id AND user_id = $current_user_id");
    if ($check_owner->num_rows > 0) {
        $conn->query("DELETE FROM comments WHERE comment_id = $del_id");
        header("Location: view_set.php?id=$set_id");
        exit;
    }
}

// Yorum Güncelleme İşlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_comment_submit']) && $current_user_id > 0) {
    $upd_id = intval($_POST['edit_comment_id']);
    $upd_text = trim($_POST['edit_comment_text']);
    
    $check_owner = $conn->query("SELECT * FROM comments WHERE comment_id = $upd_id AND user_id = $current_user_id");
    if ($check_owner->num_rows > 0 && !empty($upd_text)) {
        $stmt_upd = $conn->prepare("UPDATE comments SET comment_text = ? WHERE comment_id = ?");
        $stmt_upd->bind_param("si", $upd_text, $upd_id);
        $stmt_upd->execute();
        header("Location: view_set.php?id=$set_id");
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
    <link rel="stylesheet" href="style.css">
    <style>
        /* Genel Konteyner ve Glassmorphism */
        .view-wrapper {
            width: 90%;
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.4);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.5);
            animation: fadeIn 0.6s ease;
            box-sizing: border-box; 
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .view-wrapper h1 {
            margin-bottom: 10px;
            text-align: center;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        
        .view-wrapper p {
            color: #555;
            text-align: center;
            margin-bottom: 20px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        /* --- FLASHCARD ALANI --- */
        .flashcard-container {
            display: flex;
            justify-content: center;
            align-items: center;
            perspective: 1000px;
            margin: 40px 0;
        }

        .flashcard {
            width: 100%;
            max-width: 600px;
            height: 350px;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.6s cubic-bezier(0.4, 0.2, 0.2, 1);
            cursor: pointer;
        }

        .flashcard.flipped {
            transform: rotateY(180deg);
        }

        .flashcard-face {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            padding: 30px;
            box-sizing: border-box;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            overflow-y: auto;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .flashcard-front {
            background-color: #ffffff;
            color: #333;
            z-index: 2;
        }

        .flashcard-back {
            background-color: #2c3e50;
            color: #fff;
            transform: rotateY(180deg);
        }

        .controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 40px;
        }

        .controls button {
            padding: 12px 24px;
            font-size: 16px;
            cursor: pointer;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .controls button:hover {
            background-color: #555;
        }

        #cardCounter {
            font-size: 18px;
            font-weight: bold;
            font-family: monospace;
            background: rgba(255,255,255,0.6);
            padding: 5px 10px;
            border-radius: 5px;
        }

        /* --- YORUM ALANI (GLASS KART TASARIMI) --- */
        .comments-area {
            /* Şeffaf yapıldı, kartlar üzerinde duracak */
            background: transparent; 
            padding: 20px 0;
        }

        .comments-list {
            display: flex;
            flex-direction: column;
            gap: 15px; /* Kartlar arası boşluk */
        }
        
        /* Her bir yorum kartı */
        .comment-card {
            background: rgba(255, 255, 255, 0.45); /* Hafif beyaz cam */
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            
            /* Metin taşmasını önle */
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .comment-card:hover {
            transform: translateY(-2px); /* Hafif yukarı kalkma */
            background: rgba(255, 255, 255, 0.6);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .comment-author {
            font-weight: bold;
            color: #333;
            font-size: 15px;
        }

        .comment-date {
            color: #777;
            font-size: 12px;
        }

        .comment-content {
            color: #444;
            line-height: 1.6;
            font-size: 14px;
        }

        .comment-actions a {
            text-decoration: none;
            font-size: 12px;
            margin-left: 10px;
            transition: 0.2s;
        }
        
        .comment-actions a:hover {
            text-decoration: underline;
        }

        /* Yorum Yazma Alanı */
        .new-comment-box textarea {
            width: 100%;
            height: 80px;
            padding: 15px;
            border: 1px solid rgba(255,255,255,0.6);
            background: rgba(255,255,255,0.5);
            border-radius: 12px;
            resize: none; /* Boyutlandırma kapalı */
            outline: none;
            backdrop-filter: blur(5px);
            font-family: inherit;
        }

        .new-comment-box textarea:focus {
            background: rgba(255,255,255,0.8);
            border-color: #fff;
            box-shadow: 0 0 0 3px rgba(255,255,255,0.3);
        }

    </style>
</head>
<body>

<div class="view-wrapper">

    <div>
        <h1><?php echo htmlspecialchars($set['title']); ?></h1>
        <p><?php echo nl2br(htmlspecialchars($set['description'])); ?></p>
        
        <div style="text-align: center; margin-bottom: 20px;">
            <small>Kategori: <?php echo htmlspecialchars($set['category']); ?> | Oluşturan: <b><?php echo htmlspecialchars($set['username']); ?></b></small>
            
            <div style="margin-top: 15px;">
                <?php if ($current_user_id > 0): ?>
                    <a href="select_folder.php?set_id=<?php echo $set_id; ?>" style="text-decoration:none; margin-right: 10px; background: #ffc107; color: #333; padding: 5px 10px; border-radius: 5px;">
                        📁 Klasöre Ekle
                    </a>

                    <?php if ($set['owner_id'] == $current_user_id): ?>
                        | <a href="edit_set.php?id=<?php echo $set_id; ?>" style="margin: 0 5px;">✏️ Düzenle</a>
                        | <a href="delete_set.php?id=<?php echo $set_id; ?>" onclick="return confirm('Bu seti silmek istediğine emin misin?');" style="color: red; margin: 0 5px;">🗑️ Sil</a>
                    <?php endif; ?>
                <?php endif; ?>
                | <a href="quiz.php?id=<?php echo $set_id; ?>" style="text-decoration:none; margin-left: 10px; background: #333; color: white; padding: 5px 10px; border-radius: 5px;">🧠 Test Çöz</a>
            </div>
        </div>
    </div>

    <?php if (count($cards) > 0): ?>
        <div class="flashcard-container" onclick="flipCard()">
            <div class="flashcard" id="flashcard">
                <div class="flashcard-face flashcard-front" id="cardFront">
                    </div>
                <div class="flashcard-face flashcard-back" id="cardBack">
                    </div>
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
                $sql_comments = "SELECT comments.*, users.username FROM comments 
                                JOIN users ON comments.user_id = users.user_id 
                                WHERE set_id = $set_id ORDER BY created_at DESC";
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
                                    <a href="view_set.php?id=<?php echo $set_id; ?>&edit_comment=<?php echo $com['comment_id']; ?>" style="color: #4a90e2;">✏️ Düzenle</a>
                                    <a href="view_set.php?id=<?php echo $set_id; ?>&delete_comment=<?php echo $com['comment_id']; ?>" onclick="return confirm('Silmek istediğine emin misin?');" style="color: #e74c3c;">🗑️ Sil</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php 
                        // Yorum Düzenleme Formu
                        if (isset($_GET['edit_comment']) && $_GET['edit_comment'] == $com['comment_id'] && $current_user_id == $com['user_id']): 
                        ?>
                            <form method="POST">
                                <input type="hidden" name="edit_comment_id" value="<?php echo $com['comment_id']; ?>">
                                <textarea name="edit_comment_text" style="width: 100%; height: 60px; padding: 10px; border-radius: 8px; border: 1px solid #ccc; resize: none;"><?php echo htmlspecialchars($com['comment_text']); ?></textarea>
                                <div style="margin-top: 8px;">
                                    <button type="submit" name="update_comment_submit" style="padding: 5px 15px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">Kaydet</button>
                                    <a href="view_set.php?id=<?php echo $set_id; ?>" style="color: #666; margin-left: 10px; font-size: 13px; text-decoration: none;">İptal</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($com['comment_text'])); ?>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                    <?php endwhile; ?>
            <?php else: ?>
                <div class="comment-card" style="text-align: center; color: #777; font-style: italic;">
                    Henüz hiç yorum yapılmamış. İlk yorumu sen yap!
                </div>
            <?php endif; ?>
        </div>
    </div> 
</div> 

<script>
    const cards = <?php echo json_encode($cards); ?>;
    
    let currentIndex = 0;
    const flashcard = document.getElementById("flashcard");
    const front = document.getElementById("cardFront");
    const back = document.getElementById("cardBack");
    const counter = document.getElementById("cardCounter");

    function updateCard() {
        if (cards.length === 0) return;
        
        flashcard.classList.remove("flipped");
        
        setTimeout(() => {
            front.textContent = cards[currentIndex].term;
            back.textContent = cards[currentIndex].defination;
            counter.textContent = (currentIndex + 1) + " / " + cards.length;
        }, 150);
    }

    function flipCard() {
        flashcard.classList.toggle("flipped");
    }

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

    updateCard();
</script>

</body>
</html>