<?php
session_start();
include "connectDB.php";
include "session_check.php";

if (!isset($_GET['set_id'])) {
    header("Location: sets.php");
    exit;
}

$set_id = intval($_GET['set_id']);
$user_id = $_SESSION['user_id'];

// Set bilgisini çek
$sql_set = "SELECT title FROM sets WHERE set_id = $set_id";
$res_set = $conn->query($sql_set);
if ($res_set->num_rows == 0) {
    echo "Set bulunamadı.";
    exit;
}
$set = $res_set->fetch_assoc();

// ---------------------------------------------------------
// 0. BU SET HANGİ KLASÖRLERDE VAR?
// ---------------------------------------------------------
$existing_folders = []; 
$sql_check_exists = "SELECT folder_id FROM folder_sets WHERE set_id = $set_id";
$res_check_exists = $conn->query($sql_check_exists);

while($row_exist = $res_check_exists->fetch_assoc()) {
    $existing_folders[] = $row_exist['folder_id'];
}

// ---------------------------------------------------------
// 1. MEVCUT KLASÖRE EKLEME İŞLEMİ (GET)
// ---------------------------------------------------------
if (isset($_GET['add_to_folder'])) {
    $folder_id = intval($_GET['add_to_folder']);
    
    $check_folder = $conn->query("SELECT * FROM folders WHERE folder_id = $folder_id AND user_id = $user_id");
    
    if ($check_folder->num_rows > 0) {
        if (!in_array($folder_id, $existing_folders)) {
            $sql_insert = "INSERT INTO folder_sets (folder_id, set_id) VALUES ($folder_id, $set_id)";
            if ($conn->query($sql_insert)) {
                $success = "Set klasöre başarıyla eklendi!";
            } else {
                $error = "Hata: " . $conn->error;
            }
        } else {
            $error = "Bu set zaten o klasörde var.";
        }
    } else {
        $error = "Bu işlem için yetkiniz yok.";
    }
}

// ---------------------------------------------------------
// 2. YENİ KLASÖR OLUŞTUR VE EKLE İŞLEMİ (POST)
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_folder_name'])) {
    $new_name = trim($_POST['new_folder_name']);

    if (!empty($new_name)) {
        $new_name_clean = $conn->real_escape_string($new_name);
        
        $sql_create = "INSERT INTO folders (user_id, name) VALUES ($user_id, '$new_name_clean')";
        
        if ($conn->query($sql_create)) {
            $new_folder_id = $conn->insert_id;
            $sql_link = "INSERT INTO folder_sets (folder_id, set_id) VALUES ($new_folder_id, $set_id)";
            
            if ($conn->query($sql_link)) {
                $success = "Yeni klasör oluşturuldu ve set eklendi!";
            } else {
                $error = "Klasör oluştu ama set eklenemedi: " . $conn->error;
            }
        } else {
            $error = "Klasör oluşturulurken hata: " . $conn->error;
        }
    } else {
        $error = "Lütfen bir klasör adı girin.";
    }
}

// Kullanıcının klasörlerini çek
$sql_folders = "SELECT * FROM folders WHERE user_id = $user_id ORDER BY created_at DESC";
$res_folders = $conn->query($sql_folders);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Klasöre Ekle</title>
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #8EC5FC, #E0C3FC);
            min-height: 100vh;
            display: block; 
        }

        .container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
            margin: 80px auto; 
        }

        .glass-card {
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.25);
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeIn 0.6s ease;
            position: relative;
            text-align: center;
            min-height: 300px; /* Kartın boyutu çok küçülmesin diye */
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(5px);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 18px;
            color: #fff;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            transition: 0.25s ease;
        }

        .close-btn:hover { background: rgba(255, 255, 255, 0.55); transform: scale(1.1); }

        h2 { margin-top: 0; margin-bottom: 10px; color: #fff; font-size: 24px; text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        p.sub-text { color: #f0f0f0; font-size: 14px; margin-bottom: 25px; }

        .folder-list {
            max-height: 250px;
            overflow-y: auto;
            margin-top: 20px;
            padding-right: 5px;
            margin-bottom: 20px;
            text-align: left; /* Listeyi sola hizala */
        }
        .folder-list::-webkit-scrollbar { width: 6px; }
        .folder-list::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); border-radius: 10px; }
        .folder-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.4); border-radius: 10px; }

        .folder-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        a.folder-item:hover { background: rgba(255, 255, 255, 0.5); transform: translateX(5px); color: #333; }
        
        .folder-item.added {
            background: rgba(46, 204, 113, 0.3);
            border-color: rgba(46, 204, 113, 0.5);
            cursor: default;
            pointer-events: none;
        }
        .folder-item.added .status-icon { font-weight: bold; color: #e0ffe0; }

        .error-msg {
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
            font-weight: 600;
            background: rgba(255, 0, 0, 0.25); border: 1px solid rgba(255, 0, 0, 0.4); color: #ff4d4d;
        }

        .new-folder-area { border-top: 1px solid rgba(255,255,255,0.3); padding-top: 20px; margin-top: 10px; }
        .new-folder-form { display: flex; gap: 10px; }
        .glass-input {
            flex-grow: 1; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.4); background: rgba(255,255,255,0.15); color: #fff; outline: none; font-size: 14px;
        }
        .glass-input::placeholder { color: rgba(255,255,255,0.7); }
        .glass-input:focus { background: rgba(255,255,255,0.25); border-color: #fff; }
        .action-btn {
            padding: 0 20px; background: #fff; color: #6A5ACD; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s;
        }
        .action-btn:hover { background: #f0f0f0; transform: translateY(-2px); }

        /* --- BAŞARI EKRANI STİLİ --- */
        .success-view {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .checkmark-circle {
            width: 80px;
            height: 80px;
            background: rgba(46, 204, 113, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            border: 2px solid rgba(46, 204, 113, 0.5);
        }

        .checkmark {
            font-size: 40px;
            color: #2ecc71;
        }

        .success-title {
            color: #fff;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .success-desc {
            color: rgba(255,255,255,0.8);
            font-size: 16px;
        }

        @keyframes popIn {
            0% { transform: scale(0.5); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

    </style>
</head>
<body>
    
    <?php include "menu.php"; ?>

    <div class="container">
        <div class="glass-card">
            
            <?php if(isset($success)): ?>
                <div class="success-view">
                    <div class="checkmark-circle">
                        <div class="checkmark">✔</div>
                    </div>
                    <h3 class="success-title">Harika!</h3>
                    <p class="success-desc"><?= $success ?></p>
                    <p style="font-size: 12px; color: rgba(255,255,255,0.6); margin-top: 20px;">Yönlendiriliyorsunuz...</p>
                </div>
                
                <script>
                    setTimeout(function(){
                        window.location.href = 'view_set.php?id=<?= $set_id ?>';
                    }, 3000); // 3 saniye bekle
                </script>

            <?php else: ?>
                <a href="view_set.php?id=<?php echo $set_id; ?>" class="close-btn">✕</a>

                <h2>Klasöre Ekle</h2>
                <p class="sub-text">"<?php echo htmlspecialchars($set['title']); ?>" setini seçtiğin klasöre ekle.</p>
                
                <?php if(isset($error)): ?>
                    <div class="error-msg"><?= $error ?></div>
                <?php endif; ?>

                <div class="folder-list">
                    <?php if ($res_folders->num_rows > 0): ?>
                        <?php while($row = $res_folders->fetch_assoc()): ?>
                            
                            <?php 
                            $is_added = in_array($row['folder_id'], $existing_folders); 
                            ?>

                            <?php if ($is_added): ?>
                                <div class="folder-item added">
                                    <span>📁 <?php echo htmlspecialchars($row['name']); ?></span>
                                    <span class="status-icon">✔ Eklendi</span>
                                </div>
                            <?php else: ?>
                                <a href="select_folder.php?set_id=<?php echo $set_id; ?>&add_to_folder=<?php echo $row['folder_id']; ?>" class="folder-item">
                                    <span>📁 <?php echo htmlspecialchars($row['name']); ?></span>
                                    <span>+ Ekle</span>
                                </a>
                            <?php endif; ?>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #fff; font-style: italic; margin-bottom: 0;">Henüz klasörün yok.</p>
                    <?php endif; ?>
                </div>

                <div class="new-folder-area">
                    <p style="color:#fff; font-size:14px; margin-bottom:10px; text-align:left;">Veya yeni oluştur ve ekle:</p>
                    <form method="POST" class="new-folder-form">
                        <input type="text" name="new_folder_name" class="glass-input" placeholder="Yeni Klasör Adı" required>
                        <button type="submit" class="action-btn">Oluştur & Ekle</button>
                    </form>
                </div>

            <?php endif; ?>

        </div>
    </div>

</body>
</html>