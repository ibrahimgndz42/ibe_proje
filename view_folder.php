<?php
include "connectDB.php";
include "session_check.php";

if (!isset($_GET['id'])) {
    header("Location: folders.php");
    exit;
}

$folder_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Klasör bilgisini çek
$sql_folder = "SELECT * FROM folders WHERE folder_id = $folder_id AND user_id = $user_id";
$res_folder = $conn->query($sql_folder);

if ($res_folder->num_rows == 0) {
    echo "<script>alert('Klasör bulunamadı!'); window.location.href='folders.php';</script>";
    exit;
}

$folder = $res_folder->fetch_assoc();

// Klasörü Silme işlemi
if (isset($_GET['delete']) && $_GET['delete'] == 'true') {
    $del_sql = "DELETE FROM folders WHERE folder_id = $folder_id";
    if ($conn->query($del_sql)) {
        echo "<script>alert('Klasör silindi!'); window.location.href='folders.php';</script>";
        exit;
    }
}

// Seti Klasörden Çıkarma İşlemi
if (isset($_GET['remove_set'])) {
    $remove_set_id = intval($_GET['remove_set']);
    $sql_remove = "DELETE FROM folder_sets WHERE folder_id = $folder_id AND set_id = $remove_set_id";
    $conn->query($sql_remove);
    header("Location: view_folder.php?id=$folder_id");
    exit;
}

// Klasördeki setleri çek
$sql_sets = "SELECT 
                sets.*, 
                users.username, 
                folder_sets.added_at,
                categories.name AS category
            FROM folder_sets 
            JOIN sets ON folder_sets.set_id = sets.set_id 
            JOIN users ON sets.user_id = users.user_id 
            LEFT JOIN categories ON sets.category_id = categories.category_id
            WHERE folder_sets.folder_id = $folder_id 
            ORDER BY folder_sets.added_at DESC";

$res_sets = $conn->query($sql_sets);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($folder['name']); ?> - Klasör</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* --- DÜZELTİLEN CSS --- */


        /* Menü için özel ayar (Gerekirse) */
        .menu-wrapper {
            width: 100%;
            margin-bottom: 20px;
        }

        .container {
            width: 100%;
            max-width: 750px;
            /* Kartı ortalamak için margin auto kullanıyoruz */
            margin: 40px auto; 
            padding: 0 20px 40px 20px; /* Mobilde kenarlara yapışmasın */
            box-sizing: border-box;
        }

        .glass-card {
            backdrop-filter: blur(16px);
            background: rgba(255, 255, 255, 0.35);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.4);
            position: relative;
            min-height: 400px;
        }

        .close-page-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 20px;
            transition: 0.3s;
            z-index: 100; /* Buton önde dursun */
        }
        .close-page-btn:hover {
            background: white;
            color: #333;
        }

        .header-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 40px;
        }

        .folder-title {
            font-size: 32px;
            font-weight: 700;
            color: #fff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .folder-icon {
            font-size: 40px;
        }

        .delete-folder-btn {
            margin-top: 15px;
            background: rgba(220, 53, 69, 0.2);
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.3);
            padding: 8px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .delete-folder-btn:hover {
            background: rgba(220, 53, 69, 0.8);
            color: white;
        }

        .sets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }

        .set-card {
            /* Mevcut Stiller */
            background: rgba(255, 255, 255, 0.65);
            border-radius: 16px;
            padding: 20px;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: #333;
            border: 1px solid rgba(255,255,255,0.6);
            /* DÜZELTME 1: Kartın içeriğini dikey esnek düzene sokar */
            display: flex;
            flex-direction: column;
            height: 100%; /* İçerik ızgarasında düzgün yerleşmesi için */
        }

        .set-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }

        .category-tag {
            background: #8EC5FC;
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 10px;
        }

        .set-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 5px 0;
            color: #2c3e50;
            /* DÜZELTME 2: Başlığı max 2 satırla sınırlar */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .set-desc {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
            line-height: 1.4;
            /* DÜZELTME 3: Açıklamayı max 3 satırla sınırlar ve taşmayı gizler */
            display: -webkit-box;
            -webkit-line-clamp: 3; /* Maksimum 3 satır */
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            flex-grow: 1; /* Kart içinde kalan dikey alanı doldurur */
        }

        .set-author {
            font-size: 12px;
            color: #888;
            font-style: italic;
        }

        .remove-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(4px);
            color: #ff4d4d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.8);
            transition: all 0.3s ease;
            z-index: 10;
        }

        .remove-icon:hover {
            background: #ff4d4d;
            color: white;
            transform: rotate(90deg);
            box-shadow: 0 4px 10px rgba(255, 77, 77, 0.3);
            border-color: #ff4d4d;
        }

        .empty-state {
            text-align: center;
            color: rgba(255,255,255,0.7);
            font-size: 16px;
            grid-column: 1 / -1;
            padding: 20px;
        }

    </style>
</head>
<body>
    
    <?php include "menu.php"; ?>

    <div class="container">
        <div class="glass-card">
            
            <a href="folders.php" class="close-page-btn">✕</a>

            <div class="header-section">
                <div class="folder-title">
                    <span class="folder-icon">📁</span> 
                    <?php echo htmlspecialchars($folder['name']); ?>
                </div>

                <a href="view_folder.php?id=<?php echo $folder_id; ?>&delete=true" 
                   class="delete-folder-btn"
                   onclick="return confirm('Bu klasörü silmek istediğine emin misin? (Setler silinmez)');">
                   🗑 Klasörü Sil
                </a>
            </div>

            <div class="sets-grid">
                <?php if ($res_sets->num_rows > 0): ?>
                    <?php while($row = $res_sets->fetch_assoc()): ?>
                        
                        <div style="position: relative;">
                            
                            <a href="view_set.php?id=<?php echo $row['set_id']; ?>" class="set-card">
                                <?php if(!empty($row['category'])): ?>
                                    <span class="category-tag"><?php echo htmlspecialchars($row['category']); ?></span>
                                <?php else: ?>
                                    <span class="category-tag" style="background:#ccc;">Genel</span>
                                <?php endif; ?>

                                <h3 class="set-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                                
                                <div class="set-desc">
                                    <?php 
                                        $desc = $row['description'];
                                        echo htmlspecialchars(mb_strlen($desc) > 60 ? mb_substr($desc, 0, 60) . "..." : $desc); 
                                    ?>
                                </div>
                                
                                <div class="set-author">
                                    Oluşturan: <?php echo htmlspecialchars($row['username']); ?>
                                </div>
                            </a>

                            <a href="view_folder.php?id=<?php echo $folder_id; ?>&remove_set=<?php echo $row['set_id']; ?>" 
                               class="remove-icon"
                               onclick="return confirm('Bu seti klasörden çıkarmak istiyor musun?');"
                               title="Listeden Çıkar">
                               &times;
                            </a>
                        </div>

                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        Bu klasör henüz boş.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>