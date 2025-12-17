<?php
include "connectDB.php";
include "menu.php"; 

// --- GÜVENLİK KONTROLLERİ ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$my_id = $_SESSION['user_id'];
$check_admin = $conn->query("SELECT is_admin FROM users WHERE user_id = $my_id");
$user_data = $check_admin->fetch_assoc();

if ($user_data['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

// --- SİLME İŞLEMLERİ ---

// 1. Kullanıcı Silme
if (isset($_GET['delete_user'])) {
    $del_uid = intval($_GET['delete_user']);
    if ($del_uid != $my_id) { 
        $conn->query("DELETE FROM users WHERE user_id = $del_uid");
        header("Location: admin.php?msg=user_deleted");
        exit;
    }
}

// 2. Set Silme
if (isset($_GET['delete_set'])) {
    $del_sid = intval($_GET['delete_set']);
    $conn->query("DELETE FROM sets WHERE set_id = $del_sid");
    header("Location: admin.php?msg=set_deleted");
    exit;
}

// 3. Kategori Silme
if (isset($_GET['delete_category'])) {
    $del_cat_id = intval($_GET['delete_category']);
    $conn->query("DELETE FROM categories WHERE category_id = $del_cat_id");
    header("Location: admin.php?msg=cat_deleted");
    exit;
}

// 4. İstek/Öneri Silme
if (isset($_GET['delete_suggestion'])) {
    $del_sug_id = intval($_GET['delete_suggestion']);
    $conn->query("DELETE FROM suggestions WHERE id = $del_sug_id");
    header("Location: admin.php?msg=suggestion_deleted");
    exit;
}

// 5. Yorum Silme (YENİ)
if (isset($_GET['delete_comment'])) {
    $del_com_id = intval($_GET['delete_comment']);
    $conn->query("DELETE FROM comments WHERE comment_id = $del_com_id");
    header("Location: admin.php?msg=comment_deleted");
    exit;
}

// 6. Puanlama Silme (YENİ)
if (isset($_GET['delete_rating'])) {
    $del_rate_id = intval($_GET['delete_rating']);
    $conn->query("DELETE FROM set_ratings WHERE rating_id = $del_rate_id");
    header("Location: admin.php?msg=rating_deleted");
    exit;
}

// --- EKLEME İŞLEMLERİ ---
if (isset($_POST['add_category'])) {
    $cat_name = trim($_POST['cat_name']);
    if (!empty($cat_name)) {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $cat_name);
        $stmt->execute();
        header("Location: admin.php?msg=cat_added");
        exit;
    }
}

// --- VERİ ÇEKME & SAYFALAMA MANTIĞI ---

// Global Arama Parametreleri
$search_term = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : "";
$search_set_term = isset($_GET['search_set']) ? $conn->real_escape_string($_GET['search_set']) : "";

// --- A. KULLANICILAR ---
$user_where_sql = "";
if (!empty($search_term)) {
    $user_where_sql = " WHERE username LIKE '%$search_term%' OR user_id = '$search_term'";
}
$search_result_count = $conn->query("SELECT COUNT(*) as c FROM users" . $user_where_sql)->fetch_assoc()['c'];
$limit = 5; 
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($search_result_count / $limit);
$users = $conn->query("SELECT * FROM users $user_where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset");

// --- B. SETLER ---
$set_where_sql = "";
if (!empty($search_set_term)) {
    $set_where_sql = " WHERE sets.title LIKE '%$search_set_term%' OR users.username LIKE '%$search_set_term%' ";
}
$limit_sets = 5; 
$set_page = isset($_GET['set_page']) ? max(1, (int)$_GET['set_page']) : 1;
$offset_sets = ($set_page - 1) * $limit_sets;
$total_sets_count = $conn->query("SELECT COUNT(*) as c FROM sets JOIN users ON sets.user_id = users.user_id" . $set_where_sql)->fetch_assoc()['c'];
$total_set_pages = ceil($total_sets_count / $limit_sets);
$sets = $conn->query("SELECT sets.*, users.username FROM sets JOIN users ON sets.user_id = users.user_id $set_where_sql ORDER BY sets.created_at DESC LIMIT $limit_sets OFFSET $offset_sets");

// --- C. İSTEK & ÖNERİLER ---
$limit_sug = 5; 
$sug_page = isset($_GET['sug_page']) ? max(1, (int)$_GET['sug_page']) : 1;
$offset_sug = ($sug_page - 1) * $limit_sug;
$total_sug_stat = $conn->query("SELECT COUNT(*) as c FROM suggestions")->fetch_assoc()['c'];
$total_sug_pages = ceil($total_sug_stat / $limit_sug);
$suggestions = $conn->query("SELECT suggestions.*, users.username FROM suggestions LEFT JOIN users ON suggestions.user_id = users.user_id ORDER BY suggestions.created_at DESC LIMIT $limit_sug OFFSET $offset_sug");

// --- D. YORUMLAR (YENİ) ---
$limit_com = 5;
$com_page = isset($_GET['com_page']) ? max(1, (int)$_GET['com_page']) : 1;
$offset_com = ($com_page - 1) * $limit_com;
$total_com_stat = $conn->query("SELECT COUNT(*) as c FROM comments")->fetch_assoc()['c'];
$total_com_pages = ceil($total_com_stat / $limit_com);
// Yorumları çekerken set başlığını ve kullanıcı adını da alıyoruz
$comments = $conn->query("
    SELECT comments.*, users.username, sets.title AS set_title 
    FROM comments 
    LEFT JOIN users ON comments.user_id = users.user_id 
    LEFT JOIN sets ON comments.set_id = sets.set_id 
    ORDER BY comments.created_at DESC 
    LIMIT $limit_com OFFSET $offset_com
");

// --- E. PUANLAMALAR (YENİ) ---
$limit_rate = 5;
$rate_page = isset($_GET['rate_page']) ? max(1, (int)$_GET['rate_page']) : 1;
$offset_rate = ($rate_page - 1) * $limit_rate;
$total_rate_stat = $conn->query("SELECT COUNT(*) as c FROM set_ratings")->fetch_assoc()['c'];
$total_rate_pages = ceil($total_rate_stat / $limit_rate);
// Puanları çekerken set başlığı ve kullanıcı adı
$ratings = $conn->query("
    SELECT set_ratings.*, users.username, sets.title AS set_title 
    FROM set_ratings 
    LEFT JOIN users ON set_ratings.user_id = users.user_id 
    LEFT JOIN sets ON set_ratings.set_id = sets.set_id 
    ORDER BY set_ratings.created_at DESC 
    LIMIT $limit_rate OFFSET $offset_rate
");

// --- İSTATİSTİKLER ---
$total_users_stat = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c']; 
$total_sets_stat = $conn->query("SELECT COUNT(*) as c FROM sets")->fetch_assoc()['c'];
$total_cards = $conn->query("SELECT COUNT(*) as c FROM cards")->fetch_assoc()['c'];
$categories = $conn->query("SELECT * FROM categories");

// Yıldız Oluşturma Fonksiyonu (Görsellik için)
function displayStars($rating) {
    $stars = "";
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= "★"; // Dolu yıldız
        } else {
            $stars .= "☆"; // Boş yıldız
        }
    }
    return "<span style='color:#f1c40f; font-size:16px;'>$stars</span>";
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Paneli - Mini Sınavım</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>


        .container {
            width: 90%;
            max-width: 1100px; /* Biraz genişlettik */
            margin: 40px auto;
        }

        /* Glassmorphism Kartları */
        .glass-card {
            background: rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(15px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        h1, h2 { color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 0; }
        h2 { color: #333; font-size: 1.5rem; border-bottom: 2px solid rgba(255,255,255,0.5); padding-bottom: 10px; margin-bottom: 20px; }

        /* İstatistik Kutuları */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: rgba(255,255,255,0.6);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-number { font-size: 2rem; font-weight: bold; color: #6c5ce7; }
        .stat-label { font-size: 0.9rem; color: #555; margin-top: 5px; }

        /* Tablolar */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: rgba(255,255,255,0.4); border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(0,0,0,0.05); }
        th { background: rgba(0,0,0,0.05); font-weight: 600; color: #444; }
        tr:hover { background: rgba(255,255,255,0.5); }

        /* Butonlar & Etiketler */
        .btn-del { background: #ff7675; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 12px; transition: 0.3s; }
        .btn-del:hover { background: #d63031; }
        .btn-view { background: #74b9ff; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 12px; }
        .admin-badge { background: #fdcb6e; color: #d35400; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; }
        .type-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; color: white; }
        .type-oneri { background: #f1c40f; } .type-istek { background: #3498db; } .type-hata { background: #e74c3c; }

        /* Formlar */
        .add-cat-form { display: flex; gap: 10px; margin-bottom: 20px; }
        .add-cat-form input, .search-form input { flex: 1; padding: 10px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.1); outline: none; }
        .add-cat-form button, .search-form button { padding: 10px 20px; background: #00b894; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .search-form { display: flex; gap: 10px; margin-bottom: 15px; max-width: 400px; }
        .search-form button { background: #6c5ce7; }
        .btn-clear { background: #b2bec3 !important; text-decoration: none; display: flex; align-items: center; padding: 0 15px; border-radius: 8px; color: white; font-size: 14px; }

        .cat-tag { background: rgba(255,255,255,0.7); padding: 5px 10px 5px 15px; border-radius: 20px; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; border: 1px solid transparent; }
        .cat-tag:hover { background: #fff; border-color: rgba(0,0,0,0.1); transform: translateY(-2px); }
        .cat-del-btn { background: #ff7675; color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; justify-content: center; align-items: center; text-decoration: none; font-size: 12px; font-weight: bold; }

        /* Pagination */
        .pagination { display: flex; justify-content: center; align-items: center; margin-top: 20px; gap: 10px; }
        .page-btn { background: rgba(255,255,255,0.5); padding: 8px 15px; border-radius: 8px; text-decoration: none; color: #333; font-weight: 600; transition: 0.2s; border: 1px solid rgba(255,255,255,0.6); }
        .page-btn:hover { background: #fff; transform: translateY(-2px); }
        .page-info { font-size: 14px; color: #555; font-weight: bold; }

    </style>
</head>
<body>

<div class="container">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1>🛡️ Admin Paneli</h1>
        <a href="index.php" style="color: white; text-decoration: none; font-weight: bold; background: rgba(0,0,0,0.2); padding: 8px 16px; border-radius: 20px;">← Siteye Dön</a>
    </div>

    <div class="stats-grid">
        <div class="stat-box glass-card" style="margin:0;">
            <div class="stat-number"><?php echo $total_users_stat; ?></div>
            <div class="stat-label">Kullanıcı</div>
        </div>
        <div class="stat-box glass-card" style="margin:0;">
            <div class="stat-number"><?php echo $total_sets_stat; ?></div>
            <div class="stat-label">Set</div>
        </div>
        <div class="stat-box glass-card" style="margin:0;">
            <div class="stat-number"><?php echo $total_cards; ?></div>
            <div class="stat-label">Kart</div>
        </div>
        <div class="stat-box glass-card" style="margin:0;">
            <div class="stat-number"><?php echo $total_sug_stat; ?></div>
            <div class="stat-label">İstek/Öneri</div>
        </div>
        <div class="stat-box glass-card" style="margin:0;">
            <div class="stat-number"><?php echo $total_com_stat; ?></div>
            <div class="stat-label">Yorum</div>
        </div>
        <div class="stat-box glass-card" style="margin:0;">
            <div class="stat-number"><?php echo $total_rate_stat; ?></div>
            <div class="stat-label">Puanlama</div>
        </div>
    </div>

    <div class="glass-card">
        <h2>📂 Kategoriler</h2>
        <form method="POST" class="add-cat-form">
            <input type="text" name="cat_name" placeholder="Yeni kategori adı..." required>
            <button type="submit" name="add_category">+ Ekle</button>
        </form>
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            <?php while($cat = $categories->fetch_assoc()): ?>
                <div class="cat-tag">
                    <?php echo htmlspecialchars($cat['name']); ?>
                    <a href="admin.php?delete_category=<?php echo $cat['category_id']; ?>" class="cat-del-btn" onclick="return confirm('Kategoriyi silmek istiyor musunuz?')">✕</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="glass-card">
        <h2>📢 İstek & Öneriler</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr><th>Türü</th><th>Kimden</th><th>Mesaj</th><th>Tarih</th><th>İşlem</th></tr>
                </thead>
                <tbody>
                    <?php if($suggestions->num_rows > 0): ?>
                        <?php while($sug = $suggestions->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php 
                                    $t = $sug['type'];
                                    $cl = ($t=='istek')?'type-istek':(($t=='hata')?'type-hata':'type-oneri');
                                    echo "<span class='type-badge $cl'>$t</span>";
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($sug['username'])) echo "👤 ".htmlspecialchars($sug['username']); 
                                else echo "👽 ".htmlspecialchars($sug['guest_name'] ?? 'Anonim');
                                ?>
                            </td>
                            <td><?php echo nl2br(htmlspecialchars($sug['message'])); ?></td>
                            <td style="font-size:12px;"><?php echo date("d.m H:i", strtotime($sug['created_at'])); ?></td>
                            <td><a href="admin.php?delete_suggestion=<?php echo $sug['id']; ?>" class="btn-del" onclick="return confirm('Silinsin mi?')">Sil</a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">Kayıt yok.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($total_sug_pages > 1): ?>
            <div class="pagination">
                <?php if($sug_page > 1) echo "<a href='admin.php?sug_page=".($sug_page-1)."' class='page-btn'>«</a>"; ?>
                <span class="page-info"><?php echo $sug_page." / ".$total_sug_pages; ?></span>
                <?php if($sug_page < $total_sug_pages) echo "<a href='admin.php?sug_page=".($sug_page+1)."' class='page-btn'>»</a>"; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card">
        <h2>💬 Son Yorumlar</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr><th>Kullanıcı</th><th>Set</th><th>Yorum</th><th>Tarih</th><th>İşlem</th></tr>
                </thead>
                <tbody>
                    <?php if($comments->num_rows > 0): ?>
                        <?php while($com = $comments->fetch_assoc()): ?>
                        <tr>
                            <td><b><?php echo htmlspecialchars($com['username'] ?? 'Silinmiş Üye'); ?></b></td>
                            <td><?php echo htmlspecialchars($com['set_title'] ?? 'Silinmiş Set'); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($com['comment_text'])); ?></td>
                            <td style="font-size:12px;"><?php echo date("d.m H:i", strtotime($com['created_at'])); ?></td>
                            <td><a href="admin.php?delete_comment=<?php echo $com['comment_id']; ?>" class="btn-del" onclick="return confirm('Yorumu silmek istediğine emin misin?')">Sil</a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">Henüz yorum yok.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($total_com_pages > 1): ?>
            <div class="pagination">
                <?php if($com_page > 1) echo "<a href='admin.php?com_page=".($com_page-1)."' class='page-btn'>«</a>"; ?>
                <span class="page-info"><?php echo $com_page." / ".$total_com_pages; ?></span>
                <?php if($com_page < $total_com_pages) echo "<a href='admin.php?com_page=".($com_page+1)."' class='page-btn'>»</a>"; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card">
        <h2>⭐ Son Puanlamalar</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr><th>Kullanıcı</th><th>Set</th><th>Puan</th><th>Tarih</th><th>İşlem</th></tr>
                </thead>
                <tbody>
                    <?php if($ratings->num_rows > 0): ?>
                        <?php while($rate = $ratings->fetch_assoc()): ?>
                        <tr>
                            <td><b><?php echo htmlspecialchars($rate['username'] ?? 'Silinmiş Üye'); ?></b></td>
                            <td><?php echo htmlspecialchars($rate['set_title'] ?? 'Silinmiş Set'); ?></td>
                            <td><?php echo displayStars($rate['rating']); ?> (<?php echo $rate['rating']; ?>)</td>
                            <td style="font-size:12px;"><?php echo date("d.m H:i", strtotime($rate['created_at'])); ?></td>
                            <td><a href="admin.php?delete_rating=<?php echo $rate['rating_id']; ?>" class="btn-del" onclick="return confirm('Puanlamayı silmek istediğine emin misin?')">Sil</a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">Henüz puanlama yok.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($total_rate_pages > 1): ?>
            <div class="pagination">
                <?php if($rate_page > 1) echo "<a href='admin.php?rate_page=".($rate_page-1)."' class='page-btn'>«</a>"; ?>
                <span class="page-info"><?php echo $rate_page." / ".$total_rate_pages; ?></span>
                <?php if($rate_page < $total_rate_pages) echo "<a href='admin.php?rate_page=".($rate_page+1)."' class='page-btn'>»</a>"; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card">
        <h2>👥 Kullanıcılar</h2>
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="ID veya Kullanıcı Adı..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit">🔍 Ara</button>
            <?php if(!empty($search_term)) echo "<a href='admin.php' class='btn-clear'>Temizle</a>"; ?>
        </form>
        <div style="overflow-x: auto;">
            <table>
                <thead><tr><th>ID</th><th>Kullanıcı Adı</th><th>Email</th><th>Yetki</th><th>Kayıt</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php if($users->num_rows > 0): ?>
                        <?php while($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $u['user_id']; ?></td>
                            <td><b><?php echo htmlspecialchars($u['username']); ?></b></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo $u['is_admin'] ? '<span class="admin-badge">ADMIN</span>' : 'Üye'; ?></td>
                            <td><?php echo date("d.m.Y", strtotime($u['created_at'])); ?></td>
                            <td>
                                <?php if($u['user_id'] != $my_id): ?>
                                    <a href="admin.php?delete_user=<?php echo $u['user_id']; ?>" class="btn-del" onclick="return confirm('Kullanıcıyı ve setlerini sil?')">Sil</a>
                                <?php else: ?><span>(Sen)</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?><tr><td colspan="6" style="text-align:center;">Sonuç yok.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php $sp = !empty($search_term) ? "&search=".urlencode($search_term) : ""; ?>
                <?php if($page > 1) echo "<a href='admin.php?page=".($page-1).$sp."' class='page-btn'>«</a>"; ?>
                <span class="page-info"><?php echo $page." / ".$total_pages; ?></span>
                <?php if($page < $total_pages) echo "<a href='admin.php?page=".($page+1).$sp."' class='page-btn'>»</a>"; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card">
        <h2>📚 Setler</h2>
        <form method="GET" class="search-form">
            <input type="text" name="search_set" placeholder="Set veya Kullanıcı Adı..." value="<?php echo htmlspecialchars($search_set_term); ?>">
            <button type="submit">🔍 Ara</button>
            <?php if(!empty($search_set_term)) echo "<a href='admin.php' class='btn-clear'>Temizle</a>"; ?>
        </form>
        <div style="overflow-x: auto;">
            <table>
                <thead><tr><th>ID</th><th>Başlık</th><th>Oluşturan</th><th>Tarih</th><th>İşlem</th></tr></thead>
                <tbody>
                    <?php if($sets->num_rows > 0): ?>
                        <?php while($s = $sets->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $s['set_id']; ?></td>
                            <td><?php echo htmlspecialchars($s['title']); ?></td>
                            <td><?php echo htmlspecialchars($s['username']); ?></td>
                            <td><?php echo date("d.m.Y", strtotime($s['created_at'])); ?></td>
                            <td>
                                <a href="view_set.php?id=<?php echo $s['set_id']; ?>" target="_blank" class="btn-view">Git</a>
                                <a href="admin.php?delete_set=<?php echo $s['set_id']; ?>" class="btn-del" onclick="return confirm('Seti sil?')">Sil</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?><tr><td colspan="5" style="text-align:center;">Sonuç yok.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($total_set_pages > 1): ?>
            <div class="pagination">
                <?php $ssp = !empty($search_set_term) ? "&search_set=".urlencode($search_set_term) : ""; ?>
                <?php if($set_page > 1) echo "<a href='admin.php?set_page=".($set_page-1).$ssp."' class='page-btn'>«</a>"; ?>
                <span class="page-info"><?php echo $set_page." / ".$total_set_pages; ?></span>
                <?php if($set_page < $total_set_pages) echo "<a href='admin.php?set_page=".($set_page+1).$ssp."' class='page-btn'>»</a>"; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>