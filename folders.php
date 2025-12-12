<?php
session_start();
include "connectDB.php";
include "session_check.php";

$user_id = $_SESSION['user_id'];

// Yeni klasör oluşturma işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_folder'])) {
    $folder_name = trim($_POST['folder_name']);
    if (!empty($folder_name)) {
        $stmt = $conn->prepare("INSERT INTO folders (user_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $folder_name);
        if ($stmt->execute()) {
            echo "<script>alert('Klasör başarıyla oluşturuldu!'); window.location.href='folders.php';</script>";
        } else {
            echo "<script>alert('Hata oluştu!');</script>";
        }
    }
}

// Klasörleri çek
$sql_folders = "SELECT * FROM folders WHERE user_id = $user_id ORDER BY created_at DESC";
$result = $conn->query($sql_folders);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Klasörlerim - Mini Sınavım</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* --- DÜZELTİLEN CSS --- */


        .container {
            width: 100%;
            max-width: 900px;
            /* Kartı ortalamak için margin auto kullanıyoruz */
            margin: 40px auto; 
            padding: 0 20px 40px 20px; /* Mobilde kenarlara yapışmasın */
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

        /* Klasör Oluşturma Formu */
        .create-folder-box {
            background: rgba(255, 255, 255, 0.4);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(255,255,255,0.5);
        }

        .create-folder-box input {
            flex: 1;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.6);
            background: rgba(255,255,255,0.5);
            outline: none;
            font-size: 15px;
            color: #333;
            transition: 0.3s;
        }

        .create-folder-box input:focus {
            background: #fff;
            box-shadow: 0 0 0 3px rgba(142, 197, 252, 0.3);
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
        }

        .create-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            background: #f0f0f0;
        }

        /* Klasör Grid Yapısı */
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
        }

        .folder-count {
            font-size: 13px;
            color: #666;
            background: rgba(0,0,0,0.05);
            padding: 2px 8px;
            border-radius: 10px;
        }

        .empty-state {
            text-align: center;
            color: rgba(255,255,255,0.8);
            font-size: 16px;
            grid-column: 1 / -1;
            padding: 30px;
        }

    </style>
</head>
<body>

    <?php include "menu.php"; ?>

    <div class="container">
        <div class="glass-card">
            
            <div class="header-area">
                <h1>📂 Klasörlerim</h1>
            </div>
            
            <form method="POST" class="create-folder-box">
                <input type="text" name="folder_name" placeholder="Yeni Klasör Adı..." required autocomplete="off">
                <button type="submit" name="create_folder" class="create-btn">+ Oluştur</button>
            </form>

            <div class="folder-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php 
                            // Klasördeki set sayısını bul
                            $f_id = $row['folder_id'];
                            $sql_count = "SELECT COUNT(*) as cnt FROM folder_sets WHERE folder_id = $f_id";
                            $res_count = $conn->query($sql_count);
                            $count = $res_count->fetch_assoc()['cnt'];
                        ?>
                        
                        <a href="view_folder.php?id=<?php echo $row['folder_id']; ?>" class="folder-card">
                            <span class="folder-icon">📁</span>
                            <div class="folder-name"><?php echo htmlspecialchars($row['name']); ?></div>
                            <div class="folder-count"><?php echo $count; ?> set</div>
                        </a>

                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        Henüz hiç klasörün yok. <br>
                        Yukarıdan yeni bir tane oluşturabilirsin!
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>