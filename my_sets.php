<?php
session_start();
include "connectDB.php";
include "session_check.php";

$user_id = $_SESSION['user_id'];

// Kullanıcının oluşturduğu setleri çek
// Not: Tablo adının 'sets' olduğunu ve 'created_at' sütunu olduğunu varsayıyorum.
$sql_sets = "SELECT * FROM sets WHERE user_id = $user_id ORDER BY created_at DESC";
$result = $conn->query($sql_sets);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Setlerim - Mini Sınavım</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* --- DÜZELTİLEN CSS (Senin Kodun) --- */
        .container {
            width: 100%;
            max-width: 900px;
            margin: 40px auto; 
            padding: 0 20px 40px 20px;
            box-sizing: border-box;
        }

        /* Ana Cam Panel */
        .glass-card {
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.25);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeIn 0.6s ease;
            position: relative;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Başlık */
        .header-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            color: #fff;
        }

        .header-area h1 {
            margin: 0;
            font-size: 28px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Aksiyon Kutusu (Yeni Set Butonu için) */
        .action-box {
            background: rgba(255, 255, 255, 0.4);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: flex-end; /* Butonu sağa yasla */
            align-items: center;
            border: 1px solid rgba(255,255,255,0.5);
        }

        .create-btn {
            padding: 12px 25px;
            border-radius: 8px;
            border: none;
            background: #fff;
            color: #7b68ee;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            text-decoration: none; /* Link olduğu için alt çizgiyi kaldır */
            display: inline-block;
        }

        .create-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            background: #f0f0f0;
        }

        /* Grid Yapısı */
        .folder-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .folder-card {
            background: rgba(255, 255, 255, 0.6);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.5);
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 160px;
        }

        .folder-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .folder-icon {
            font-size: 50px;
            margin-bottom: 10px;
            display: block;
            text-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .folder-name {
            font-size: 18px;
            font-weight: 600;
            color: #444;
            margin-bottom: 5px;
            word-break: break-word;
            /* Çok uzun başlıkları sınırla */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .folder-count {
            font-size: 13px;
            color: #666;
            background: rgba(0,0,0,0.05);
            padding: 2px 8px;
            border-radius: 10px;
            margin-top: 5px;
        }

        .empty-state {
            text-align: center;
            color: rgba(255,255,255,0.9);
            font-size: 18px;
            grid-column: 1 / -1;
            padding: 40px;
            background: rgba(0,0,0,0.1);
            border-radius: 15px;
        }
    </style>
</head>
<body>

    <?php include "menu.php"; ?>

    <div class="container">
        <div class="glass-card">
            
            <div class="header-area">
                <h1>📚 Setlerim</h1>
            </div>
            
            <div class="action-box">
                <a href="create_set.php" class="create-btn">+ Yeni Set Oluştur</a>
            </div>

            <div class="folder-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php 
                            $s_id = $row['set_id'];
                            $sql_count = "SELECT COUNT(*) as cnt FROM sets WHERE set_id = $s_id";
                            $res_count = $conn->query($sql_count);
                            $count = ($res_count) ? $res_count->fetch_assoc()['cnt'] : 0;
                        ?>
                        
                        <a href="view_set.php?id=<?php echo $row['set_id']; ?>" class="folder-card">
                            <span class="folder-icon">📝</span>
                            <div class="folder-name"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="folder-count"><?php echo $count; ?> terim</div>
                        </a>

                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        Henüz hiç set oluşturmadın. <br><br>
                        Yukarıdaki butona tıklayarak ilk setini oluşturabilirsin! 🚀
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>