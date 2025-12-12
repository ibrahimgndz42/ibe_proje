<?php
include "connectDB.php";
include "menu.php";

// Setleri ve oluşturan kullanıcı adlarını çekiyoruz
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT 
            sets.set_id, 
            sets.title, 
            sets.description, 
            categories.name AS category,
            sets.created_at, 
            users.username,
            (SELECT COUNT(*) FROM cards WHERE cards.set_id = sets.set_id) AS card_count
        FROM sets
        JOIN users ON sets.user_id = users.user_id
        JOIN categories ON sets.category_id = categories.category_id";

$conditions = [];

if ($category_filter) {
    $safe_category = $conn->real_escape_string($category_filter);
    $conditions[] = "categories.name = '$safe_category'";
}

if ($search_query) {
    $safe_search = $conn->real_escape_string($search_query);
    $conditions[] = "(sets.title LIKE '%$safe_search%' OR sets.description LIKE '%$safe_search%' OR users.username LIKE '%$safe_search%')";
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$sql .= " ORDER BY sets.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Tüm Setler - Mini Sınavım</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* TEMEL AYARLAR */
        * {
            box-sizing: border-box;
        }

        html {
            overflow-y: scroll; /* Kaydırma çubuğu alanını rezerve et */
        }


        /* İÇERİK KONTEYNERİ */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 20px auto; /* DÜZELTME: auto ile ortaladık */
            padding: 0 20px 40px 20px; /* Kenar boşlukları */
        }

        /* CAM PANEL (HERO ALANI) */
        .glass-hero {
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.25);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            text-align: center;
            margin-bottom: 40px;
        }


        .glass-hero h1 {
            font-size: 36px;
            font-weight: 800;
            color: #fff;
            margin: 0 0 10px 0;
            text-shadow: 0 2px 5px rgba(0,0,0,0.15);
        }

        .glass-hero p {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
            margin: 0 0 30px 0;
        }

        /* ARAMA FORMU */
        .search-form {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .search-input {
            padding: 12px 20px;
            width: 100%;
            max-width: 400px;
            border-radius: 30px;
            border: 1px solid rgba(255,255,255,0.5);
            background: rgba(255,255,255,0.6);
            font-size: 16px;
            outline: none;
            color: #333;
            transition: 0.3s;
        }

        .search-input:focus {
            background: #fff;
            box-shadow: 0 0 0 4px rgba(255,255,255,0.3);
        }

        .search-btn {
            padding: 12px 25px;
            border-radius: 30px;
            border: none;
            background: #6A5ACD;
            color: white;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 4px 10px rgba(106, 90, 205, 0.3);
            transition: 0.3s;
        }

        .search-btn:hover {
            background: #5a4db8;
            transform: translateY(-2px);
        }

        .clear-btn {
            padding: 12px 20px;
            border-radius: 30px;
            background: rgba(255,255,255,0.5);
            text-decoration: none;
            color: #333;
            font-weight: bold;
            display: flex;
            align-items: center;
            transition: 0.3s;
        }
        .clear-btn:hover {
            background: rgba(255,255,255,0.8);
        }

        /* KATEGORİ MENÜSÜ */
        .category-menu {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }

        .category-btn {
            padding: 8px 18px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 20px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.4);
            transition: all 0.2s;
        }

        .category-btn:hover {
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .category-active {
            background: #6A5ACD !important;
            color: #fff !important;
            border-color: #6A5ACD !important;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(106, 90, 205, 0.4);
        }

        /* SET LİSTESİ (GRID) */
        .sets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        /* SET KARTI */
        .set-card {
            background: rgba(255, 255, 255, 0.65);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.5);
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            min-height: 160px;
            position: relative;
        }

        .set-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin: 0 0 10px 0;
            /* Uzun başlıkları kırp */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .term-badge {
            align-self: flex-start;
            background: #6A5ACD;
            padding: 4px 10px;
            color: white;
            font-size: 12px;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .meta-row {
            margin-top: auto; /* En alta it */
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #666;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding-top: 10px;
        }

        .creator { font-weight: 600; color: #444; }
        .date { opacity: 0.8; }

        .empty-msg {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: rgba(255,255,255,0.8);
            font-size: 18px;
            background: rgba(255,255,255,0.1);
            border-radius: 16px;
            border: 1px dashed rgba(255,255,255,0.3);
        }

    </style>
</head>
<body>

    <div class="container">
        
        <div class="glass-hero">
            <h1>Çalışma Setleri</h1>
            <p>Tüm kullanıcıların oluşturduğu setleri keşfet</p>
            
            <form method="GET" action="sets.php" class="search-form">
                <input type="text" name="q" class="search-input" placeholder="Set adı, açıklama veya kullanıcı ara..." value="<?php echo htmlspecialchars($search_query); ?>">
                
                <?php if ($category_filter): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                <?php endif; ?>
                
                <button type="submit" class="search-btn">Ara</button>
                
                <?php if ($search_query): ?>
                    <a href="sets.php<?php echo $category_filter ? '?category=' . urlencode($category_filter) : ''; ?>" class="clear-btn">✕</a>
                <?php endif; ?>
            </form>
            
            <?php
            $catQuery = $conn->query("SELECT name FROM categories ORDER BY category_id ASC");
            ?>
            <div class="category-menu">
                <a href="sets.php" class="category-btn <?php echo $category_filter == '' ? 'category-active' : ''; ?>">Tümü</a>

                <?php while($cat = $catQuery->fetch_assoc()): ?>
                    <a href="sets.php?category=<?php echo urlencode($cat['name']); ?><?php echo $search_query ? '&q=' . urlencode($search_query) : ''; ?>"
                       class="category-btn <?php echo ($category_filter == $cat['name']) ? 'category-active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="sets-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    
                    <a href="view_set.php?id=<?php echo $row['set_id']; ?>" class="set-card">
                        
                        <h3 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h3>

                        <div class="term-badge">
                            <?php echo $row['card_count']; ?> terim
                        </div>

                        <div class="meta-row">
                            <span class="creator">👤 <?php echo htmlspecialchars($row['username']); ?></span>
                            <span class="date"><?php echo date("d.m.Y", strtotime($row['created_at'])); ?></span>
                        </div>

                    </a>

                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-msg">
                    Aradığınız kriterlere uygun set bulunamadı. <br>
                    İlk seti siz oluşturmak ister misiniz?
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>