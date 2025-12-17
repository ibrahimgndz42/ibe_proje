<?php
// Session kontrolü (Yoksa başlat)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gerekli dosyalar
if (file_exists("session_check.php")) {
    include "session_check.php";
} else {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}
include "connectDB.php";

if (!isset($_GET['set_id'])) {
    header("Location: sets.php");
    exit;
}

$set_id = intval($_GET['set_id']);
$user_id = $_SESSION['user_id'];

// Set bilgisini çek
$stmt = $conn->prepare("SELECT title FROM sets WHERE set_id = ?");
$stmt->bind_param("i", $set_id);
$stmt->execute();
$res_set = $stmt->get_result();

if ($res_set->num_rows == 0) {
    echo "Set bulunamadı.";
    exit;
}
$set = $res_set->fetch_assoc();

// ---------------------------------------------------------
// 0. BU SET HANGİ KLASÖRLERDE VAR?
// ---------------------------------------------------------
$existing_folders = []; 
$stmt_check = $conn->prepare("SELECT folder_id FROM folder_sets WHERE set_id = ?");
$stmt_check->bind_param("i", $set_id);
$stmt_check->execute();
$res_check_exists = $stmt_check->get_result();

while($row_exist = $res_check_exists->fetch_assoc()) {
    $existing_folders[] = $row_exist['folder_id'];
}

// ---------------------------------------------------------
// 1. MEVCUT KLASÖRE EKLEME İŞLEMİ (GET)
// ---------------------------------------------------------
if (isset($_GET['add_to_folder'])) {
    $folder_id = intval($_GET['add_to_folder']);
    
    // Klasörün sahibi bu kullanıcı mı?
    $check_folder = $conn->query("SELECT * FROM folders WHERE folder_id = $folder_id AND user_id = $user_id");
    
    if ($check_folder->num_rows > 0) {
        if (!in_array($folder_id, $existing_folders)) {
            $stmt_insert = $conn->prepare("INSERT INTO folder_sets (folder_id, set_id) VALUES (?, ?)");
            $stmt_insert->bind_param("ii", $folder_id, $set_id);
            
            if ($stmt_insert->execute()) {
                $success = "Set klasöre eklendi!";
            } else {
                $error = "Hata oluştu.";
            }
        } else {
            $error = "Bu set zaten o klasörde var.";
        }
    } else {
        $error = "Yetkisiz işlem.";
    }
}

// ---------------------------------------------------------
// 2. YENİ KLASÖR OLUŞTUR VE EKLE İŞLEMİ (POST)
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_folder_name'])) {
    $new_name = trim($_POST['new_folder_name']);

    if (!empty($new_name)) {
        // Önce klasörü oluştur
        $stmt_create = $conn->prepare("INSERT INTO folders (user_id, name) VALUES (?, ?)");
        $stmt_create->bind_param("is", $user_id, $new_name);
        
        if ($stmt_create->execute()) {
            $new_folder_id = $conn->insert_id;
            
            // Sonra seti içine at
            $stmt_link = $conn->prepare("INSERT INTO folder_sets (folder_id, set_id) VALUES (?, ?)");
            $stmt_link->bind_param("ii", $new_folder_id, $set_id);
            
            if ($stmt_link->execute()) {
                $success = "Klasör oluşturuldu ve set eklendi!";
            } else {
                $error = "Klasör oluştu ama set eklenemedi.";
            }
        } else {
            $error = "Klasör oluşturulurken hata oluştu.";
        }
    } else {
        $error = "Lütfen bir klasör adı girin.";
    }
}

// Kullanıcının klasörlerini çek
$stmt_folders = $conn->prepare("SELECT * FROM folders WHERE user_id = ? ORDER BY created_at DESC");
$stmt_folders->bind_param("i", $user_id);
$stmt_folders->execute();
$res_folders = $stmt_folders->get_result();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klasöre Ekle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        /* --- SAYFA DÜZENİ --- */
        body {
            margin: 0; padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
        }

        /* --- KART TASARIMI --- */
        .select-container {
            width: 100%;
            max-width: 500px;
        }

        .glass-card {
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.4);
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.5);
            animation: fadeIn 0.6s ease;
            position: relative;
            text-align: center;
            display: flex; flex-direction: column;
            min-height: 350px; /* Kart çok küçülmesin */
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Başlıklar */
        h2 { margin: 0 0 10px 0; color: #333; font-size: 24px; }
        p.sub-text { color: #666; font-size: 14px; margin-bottom: 20px; }

        /* Kapat Butonu */
        .close-btn {
            position: absolute; top: 15px; right: 15px;
            width: 30px; height: 30px;
            background: rgba(0,0,0,0.1);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; color: #333; transition: 0.2s;
        }
        .close-btn:hover { background: #ff7675; color: white; }

        /* Klasör Listesi */
        .folder-list {
            flex-grow: 1;
            max-height: 220px;
            overflow-y: auto;
            margin-bottom: 20px;
            padding-right: 5px;
            text-align: left;
        }
        /* Scrollbar Özelleştirme */
        .folder-list::-webkit-scrollbar { width: 5px; }
        .folder-list::-webkit-scrollbar-track { background: rgba(0,0,0,0.05); }
        .folder-list::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 10px; }

        .folder-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 15px; margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 10px;
            color: #333; text-decoration: none; font-weight: 600;
            transition: 0.2s;
        }
        
        a.folder-item:hover {
            background: #fff; transform: translateX(3px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .folder-item.added {
            background: rgba(46, 204, 113, 0.2);
            border-color: rgba(46, 204, 113, 0.4);
            color: #27ae60;
            cursor: default; pointer-events: none;
        }

        /* Hata Mesajı */
        .error-msg {
            padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: bold;
            background: rgba(255, 100, 100, 0.15); color: #c0392b; border: 1px solid rgba(255, 100, 100, 0.3);
        }

        /* Yeni Klasör Formu */
        .new-folder-area {
            border-top: 1px solid rgba(0,0,0,0.1);
            padding-top: 15px; margin-top: auto;
        }
        .new-folder-form { display: flex; gap: 8px; }
        
        .glass-input {
            flex-grow: 1; padding: 10px; border-radius: 8px;
            border: 1px solid #ccc; background: rgba(255,255,255,0.8);
            outline: none; font-size: 14px;
        }
        .glass-input:focus { border-color: #6c5ce7; background: #fff; }

        .action-btn {
            padding: 0 15px; background: #6c5ce7; color: white;
            border: none; border-radius: 8px; font-weight: bold;
            cursor: pointer; transition: 0.2s; white-space: nowrap;
        }
        .action-btn:hover { background: #5a4db8; }

        /* Başarı Ekranı */
        .success-view {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            height: 100%; min-height: 300px;
            animation: popIn 0.5s ease;
        }
        .checkmark-circle {
            width: 70px; height: 70px; background: #2ecc71;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin-bottom: 15px; box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }
        .checkmark { font-size: 35px; color: white; }
        .success-title { font-size: 22px; color: #333; margin-bottom: 5px; }
        .success-desc { color: #666; font-size: 15px; }

        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    </style>
</head>
<body>
    
    <?php include "menu.php"; ?>

    <div class="main-wrapper">
        <div class="select-container">
            <div class="glass-card">
                
                <?php if(isset($success)): ?>
                    <div class="success-view">
                        <div class="checkmark-circle">
                            <div class="checkmark"><i class="fa-solid fa-check"></i></div>
                        </div>
                        <h3 class="success-title">Harika!</h3>
                        <p class="success-desc"><?= $success ?></p>
                        <p style="font-size: 12px; color: #999; margin-top: 20px;">Yönlendiriliyorsunuz...</p>
                    </div>
                    
                    <script>
                        setTimeout(function(){
                            window.location.href = 'view_set.php?id=<?= $set_id ?>';
                        }, 2000); 
                    </script>

                <?php else: ?>
                    <a href="view_set.php?id=<?php echo $set_id; ?>" class="close-btn"><i class="fa-solid fa-xmark"></i></a>

                    <h2>Klasöre Ekle</h2>
                    <p class="sub-text">"<?php echo htmlspecialchars($set['title']); ?>" setini seçtiğin klasöre ekle.</p>
                    
                    <?php if(isset($error)): ?>
                        <div class="error-msg"><?= $error ?></div>
                    <?php endif; ?>

                    <div class="folder-list">
                        <?php if ($res_folders->num_rows > 0): ?>
                            <?php while($row = $res_folders->fetch_assoc()): ?>
                                
                                <?php $is_added = in_array($row['folder_id'], $existing_folders); ?>

                                <?php if ($is_added): ?>
                                    <div class="folder-item added">
                                        <span><i class="fa-regular fa-folder-open"></i> <?php echo htmlspecialchars($row['name']); ?></span>
                                        <span><i class="fa-solid fa-check"></i> Eklendi</span>
                                    </div>
                                <?php else: ?>
                                    <a href="select_folder.php?set_id=<?php echo $set_id; ?>&add_to_folder=<?php echo $row['folder_id']; ?>" class="folder-item">
                                        <span><i class="fa-regular fa-folder"></i> <?php echo htmlspecialchars($row['name']); ?></span>
                                        <span><i class="fa-solid fa-plus"></i> Ekle</span>
                                    </a>
                                <?php endif; ?>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="text-align:center; color:#777; margin-top:30px;">
                                <i class="fa-regular fa-folder-open" style="font-size:30px; margin-bottom:10px; display:block;"></i>
                                Henüz hiç klasörün yok.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="new-folder-area">
                        <p style="color:#555; font-size:13px; margin-bottom:8px; text-align:left;">Yeni klasör oluştur ve ekle:</p>
                        <form method="POST" class="new-folder-form">
                            <input type="text" name="new_folder_name" class="glass-input" placeholder="Klasör Adı..." required>
                            <button type="submit" class="action-btn">Oluştur</button>
                        </form>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>

</body>
</html>