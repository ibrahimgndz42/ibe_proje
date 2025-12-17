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

// --- İŞLEMLER ---

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

// 3. Kategori Ekleme
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

// 4. Kategori Silme
if (isset($_GET['delete_category'])) {
    $del_cat_id = intval($_GET['delete_category']);
    $conn->query("DELETE FROM categories WHERE category_id = $del_cat_id");
    header("Location: admin.php?msg=cat_deleted");
    exit;
}

// --- VERİ ÇEKME MANTIĞI ---

// GLOBAL PARAMETRELERİ AL (Linklerin bozulmaması için)
// Kullanıcı arama terimi
$search_term = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : "";
// Set arama terimi (YENİ)
$search_set_term = isset($_GET['search_set']) ? $conn->real_escape_string($_GET['search_set']) : "";


// --- A. KULLANICI LİSTESİ AYARLARI ---

$user_where_sql = "";
if (!empty($search_term)) {
    $user_where_sql = " WHERE username LIKE '%$search_term%' OR user_id = '$search_term'";
}

$sql_count_users = "SELECT COUNT(*) as c FROM users" . $user_where_sql;
$search_result_count = $conn->query($sql_count_users)->fetch_assoc()['c'];

$limit = 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($search_result_count / $limit);

$sql_users = "SELECT * FROM users $user_where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$users = $conn->query($sql_users);


// --- B. SET LİSTESİ AYARLARI (GÜNCELLENDİ) ---

$set_where_sql = "";
// Eğer set araması yapılmışsa SQL'i hazırla
if (!empty($search_set_term)) {
    // Hem Set Başlığında hem de Kullanıcı Adında arar
    $set_where_sql = " WHERE sets.title LIKE '%$search_set_term%' OR users.username LIKE '%$search_set_term%' ";
}

$limit_sets = 5; 
$set_page = isset($_GET['set_page']) ? (int)$_GET['set_page'] : 1;
if ($set_page < 1) $set_page = 1;
$offset_sets = ($set_page - 1) * $limit_sets;

// Toplam Set Sayısı (Arama filtresine göre saymalı, bu yüzden JOIN şart)
// Eğer arama yoksa normal sayar, varsa filtreli sayar.
$count_sets_query = "SELECT COUNT(*) as c FROM sets JOIN users ON sets.user_id = users.user_id" . $set_where_sql;
$total_sets_count = $conn->query($count_sets_query)->fetch_assoc()['c'];

$total_set_pages = ceil($total_sets_count / $limit_sets);

// Setleri Çek
$sets = $conn->query("SELECT sets.*, users.username FROM sets JOIN users ON sets.user_id = users.user_id $set_where_sql ORDER BY sets.created_at DESC LIMIT $limit_sets OFFSET $offset_sets");


// --- C. DİĞER İSTATİSTİKLER VE KATEGORİLER ---
$total_users_stat = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c']; 
$total_sets_stat = $conn->query("SELECT COUNT(*) as c FROM sets")->fetch_assoc()['c'];
$total_cards = $conn->query("SELECT COUNT(*) as c FROM cards")->fetch_assoc()['c'];
$categories = $conn->query("SELECT * FROM categories");

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Paneli - Mini Sınavım</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #8EC5FC, #E0C3FC);
            min-height: 100vh;
            color: #333;
            padding-bottom: 50px;
        }

        .container {
            width: 90%;
            max-width: 1000px;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: rgba(255,255,255,0.6);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-number { font-size: 2.5rem; font-weight: bold; color: #6c5ce7; }
        .stat-label { font-size: 1rem; color: #555; }

        /* Tablolar */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: rgba(255,255,255,0.4);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        th { background: rgba(0,0,0,0.05); font-weight: 600; color: #444; }
        tr:hover { background: rgba(255,255,255,0.5); }

        /* Butonlar */
        .btn-del {
            background: #ff7675;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            transition: 0.3s;
        }
        .btn-del:hover { background: #d63031; }

        .btn-view {
            background: #74b9ff;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
        }

        .admin-badge {
            background: #fdcb6e;
            color: #d35400;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
        }

        /* Formlar */
        .add-cat-form { display: flex; gap: 10px; margin-bottom: 20px; }
        .add-cat-form input, .search-form input {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
            outline: none;
        }
        .add-cat-form button, .search-form button {
            padding: 10px 20px;
            background: #00b894;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        .add-cat-form button:hover { background: #00a884; }

        /* Arama Formu Stili */
        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            max-width: 400px;
        }
        .search-form button { background: #6c5ce7; }
        .search-form button:hover { background: #5b4cc4; }
        .btn-clear {
            background: #b2bec3 !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0 15px;
            border-radius: 8px;
            color: white;
            font-size: 14px;
        }

        .cat-tag {
            background: rgba(255,255,255,0.7);
            padding: 5px 10px 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            border: 1px solid transparent;
        }
        .cat-tag:hover {
            background: #fff;
            border-color: rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .cat-del-btn {
            background: #ff7675;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 10px;
        }
        .page-btn {
            background: rgba(255,255,255,0.5);
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            font-weight: 600;
            transition: 0.2s;
            border: 1px solid rgba(255,255,255,0.6);
        }
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
            <div class="stat-label">Toplam Kullanıcı</div>
        </div>
        <div class="stat-box glass-card" style="margin:0;">
            <div class="stat-number"><?php echo $total_sets_stat; ?></div>
            <div class="stat-label">Oluşturulan Set</div>
        </div>
        <div class="stat-box glass-card" style="margin:0;">
            <div class="stat-number"><?php echo $total_cards; ?></div>
            <div class="stat-label">Toplam Kart</div>
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
                    <a href="admin.php?delete_category=<?php echo $cat['category_id']; ?>" class="cat-del-btn" onclick="return confirm('Kategoriyi silmek istiyor musunuz? (Setler silinmez)')">✕</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="glass-card">
        <h2>👥 Kullanıcılar</h2>
        
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="ID veya Kullanıcı Adı ara..." value="<?php echo htmlspecialchars($search_term); ?>">
            
            <?php if(!empty($search_set_term)): ?>
                <input type="hidden" name="search_set" value="<?php echo htmlspecialchars($search_set_term); ?>">
            <?php endif; ?>
            <?php if($set_page > 1): ?>
                <input type="hidden" name="set_page" value="<?php echo $set_page; ?>">
            <?php endif; ?>

            <button type="submit">🔍 Ara</button>
            <?php if(!empty($search_term)): ?>
                <a href="admin.php?search_set=<?php echo urlencode($search_set_term); ?>&set_page=<?php echo $set_page; ?>" class="btn-clear">Temizle</a>
            <?php endif; ?>
        </form>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kullanıcı Adı</th>
                        <th>Email</th>
                        <th>Yetki</th>
                        <th>Kayıt Tarihi</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($users->num_rows > 0): ?>
                        <?php while($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $u['user_id']; ?></td>
                            <td><b><?php echo htmlspecialchars($u['username']); ?></b></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <?php if($u['is_admin']): ?>
                                    <span class="admin-badge">ADMIN</span>
                                <?php else: ?>
                                    Üye
                                <?php endif; ?>
                            </td>
                            <td><?php echo date("d.m.Y", strtotime($u['created_at'])); ?></td>
                            <td>
                                <?php if($u['user_id'] != $my_id): ?>
                                    <a href="admin.php?delete_user=<?php echo $u['user_id']; ?>" class="btn-del" onclick="return confirm('Bu kullanıcıyı ve tüm setlerini silmek istediğine emin misin?')">Sil</a>
                                <?php else: ?>
                                    <span style="font-size:11px; color:#777;">(Sen)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 20px; color:#555;">Sonuç bulunamadı.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($total_pages > 1): ?>
        <div class="pagination">
            <?php 
                $search_param = !empty($search_term) ? "&search=" . urlencode($search_term) : "";
                
                // Set parametrelerini linke ekle (Kaybolmasın)
                $set_params = "";
                if(!empty($search_set_term)) $set_params .= "&search_set=" . urlencode($search_set_term);
                if($set_page > 1) $set_params .= "&set_page=" . $set_page;
            ?>

            <?php if($page > 1): ?>
                <a href="admin.php?page=<?php echo $page - 1 . $search_param . $set_params; ?>" class="page-btn">« Önceki</a>
            <?php endif; ?>

            <span class="page-info">Sayfa <?php echo $page; ?> / <?php echo $total_pages; ?></span>

            <?php if($page < $total_pages): ?>
                <a href="admin.php?page=<?php echo $page + 1 . $search_param . $set_params; ?>" class="page-btn">Sonraki »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <div class="glass-card">
        <h2>📚 Tüm Setler</h2>

        <form method="GET" class="search-form">
            <input type="text" name="search_set" placeholder="Set Adı veya Kullanıcı Adı ara..." value="<?php echo htmlspecialchars($search_set_term); ?>">
            
            <?php if(!empty($search_term)): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
            <?php endif; ?>
            <?php if($page > 1): ?>
                <input type="hidden" name="page" value="<?php echo $page; ?>">
            <?php endif; ?>

            <button type="submit">🔍 Ara</button>
            <?php if(!empty($search_set_term)): ?>
                <a href="admin.php?search=<?php echo urlencode($search_term); ?>&page=<?php echo $page; ?>" class="btn-clear">Temizle</a>
            <?php endif; ?>
        </form>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Set Başlığı</th>
                        <th>Oluşturan</th>
                        <th>Tarih</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($sets->num_rows > 0): ?>
                        <?php while($s = $sets->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $s['set_id']; ?></td>
                            <td><?php echo htmlspecialchars($s['title']); ?></td>
                            <td><?php echo htmlspecialchars($s['username']); ?></td>
                            <td><?php echo date("d.m.Y", strtotime($s['created_at'])); ?></td>
                            <td>
                                <a href="view_set.php?id=<?php echo $s['set_id']; ?>" target="_blank" class="btn-view">İncele</a>
                                <a href="admin.php?delete_set=<?php echo $s['set_id']; ?>" class="btn-del" onclick="return confirm('Bu seti silmek istediğine emin misin?')">Sil</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 20px; color:#555;">Sonuç bulunamadı.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($total_set_pages > 1): ?>
        <div class="pagination">
            <?php 
                // Diğer değişkenleri (Search ve User Page) korumak için linke ekliyoruz
                $other_params = "";
                if(!empty($search_term)) $other_params .= "&search=" . urlencode($search_term);
                if(isset($_GET['page'])) $other_params .= "&page=" . $_GET['page'];

                // Mevcut set aramasını ekle
                $set_search_param = !empty($search_set_term) ? "&search_set=" . urlencode($search_set_term) : "";
            ?>

            <?php if($set_page > 1): ?>
                <a href="admin.php?set_page=<?php echo $set_page - 1 . $other_params . $set_search_param; ?>" class="page-btn">« Önceki</a>
            <?php endif; ?>

            <span class="page-info">Sayfa <?php echo $set_page; ?> / <?php echo $total_set_pages; ?></span>

            <?php if($set_page < $total_set_pages): ?>
                <a href="admin.php?set_page=<?php echo $set_page + 1 . $other_params . $set_search_param; ?>" class="page-btn">Sonraki »</a>
            <?php endif; ?>

        </div>
        <?php endif; ?>

    </div>

</div>

</body>
</html>