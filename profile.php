<?php
include "session_check.php";
include "connectDB.php";

$user_id = $_SESSION['user_id'];
$pass_msg = ""; 
$show_password_form = false; 

// --- 1. ŞİFRE DEĞİŞTİRME İŞLEMİ ---
if (isset($_POST['change_password'])) {
    $show_password_form = true; 
    
    $current_pass = $_POST['current_pass'];
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    $sql_pass = "SELECT password FROM users WHERE user_id = $user_id";
    $res_pass = $conn->query($sql_pass);
    $row_pass = $res_pass->fetch_assoc();
    $db_password_hash = $row_pass['password'];

    if (password_verify($current_pass, $db_password_hash)) {
        if ($new_pass === $confirm_pass) {
            if (!empty($new_pass)) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $new_hash, $user_id);
                
                if ($stmt->execute()) {
                    $pass_msg = "<div class='alert success'>✅ Şifreniz başarıyla güncellendi!</div>";
                } else {
                    $pass_msg = "<div class='alert error'>❌ Bir hata oluştu.</div>";
                }
            } else {
                $pass_msg = "<div class='alert error'>⚠️ Yeni şifre boş olamaz.</div>";
            }
        } else {
            $pass_msg = "<div class='alert error'>⚠️ Yeni şifreler uyuşmuyor.</div>";
        }
    } else {
        $pass_msg = "<div class='alert error'>⛔ Mevcut şifreniz hatalı.</div>";
    }
}

// --- 2. HESAP SİLME ---
if (isset($_POST['delete_account'])) {
    $check_admin_sql = "SELECT is_admin FROM users WHERE user_id = $user_id";
    $check_admin_res = $conn->query($check_admin_sql);
    $admin_row = $check_admin_res->fetch_assoc();

    if ($admin_row['is_admin'] == 1) {
        echo "<script>alert('⛔ Yöneticiler hesaplarını silemezler!'); window.location.href='profile.php';</script>";
        exit;
    } else {
        $sql_pic = "SELECT profile_pic FROM users WHERE user_id = $user_id";
        $res_pic = $conn->query($sql_pic);
        $row_pic = $res_pic->fetch_assoc();
        
        if (!empty($row_pic['profile_pic']) && file_exists($row_pic['profile_pic'])) {
            unlink($row_pic['profile_pic']);
        }
        
        $sql_del_user = "DELETE FROM users WHERE user_id = $user_id";
        if ($conn->query($sql_del_user)) {
            session_destroy();
            header("Location: index.php?msg=account_deleted");
            exit;
        }
    }
}

// --- 3. RESİM SİLME ---
if (isset($_POST['delete_photo'])) {
    $sql_check = "SELECT profile_pic FROM users WHERE user_id = $user_id";
    $res_check = $conn->query($sql_check);
    $row_check = $res_check->fetch_assoc();
    if (!empty($row_check['profile_pic']) && file_exists($row_check['profile_pic'])) {
        unlink($row_check['profile_pic']);
    }
    $conn->query("UPDATE users SET profile_pic = NULL WHERE user_id = $user_id");
    header("Location: profile.php");
    exit;
}

// --- 4. RESİM YÜKLEME ---
if (isset($_FILES['profile_image'])) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
    $file_name = basename($_FILES["profile_image"]["name"]);
    $imageFileType = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $new_file_name = "profile_" . $user_id . "_" . time() . "." . $imageFileType;
    $target_file = $target_dir . $new_file_name;
    $uploadOk = 1;
    $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
    if($check === false) $uploadOk = 0;
    if ($_FILES["profile_image"]["size"] > 5000000) $uploadOk = 0;
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") $uploadOk = 0;

    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $sql_old = "SELECT profile_pic FROM users WHERE user_id = $user_id";
            $res_old = $conn->query($sql_old);
            $old_row = $res_old->fetch_assoc();
            if(!empty($old_row['profile_pic']) && file_exists($old_row['profile_pic'])){
                unlink($old_row['profile_pic']);
            }
            $conn->query("UPDATE users SET profile_pic = '$target_file' WHERE user_id = $user_id");
            header("Location: profile.php");
            exit;
        }
    }
}

// Kullanıcı Bilgileri
$sql_user = "SELECT username, email, created_at, profile_pic, is_admin FROM users WHERE user_id = $user_id";
$res_user = $conn->query($sql_user);
$user_info = $res_user->fetch_assoc();

$profile_pic_path = "https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png";
$has_custom_pic = false;
if (!empty($user_info['profile_pic']) && file_exists($user_info['profile_pic'])) {
    $profile_pic_path = $user_info['profile_pic'];
    $has_custom_pic = true;
}

$res_my_sets = $conn->query("SELECT sets.*, categories.name AS category FROM sets LEFT JOIN categories ON sets.category_id = categories.category_id WHERE user_id = $user_id ORDER BY created_at DESC");
$res_folders = $conn->query("SELECT * FROM folders WHERE user_id = $user_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profilim - Mini Sınavım</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* SIFIRLAMA VE TEMEL AYARLAR */
        * { box-sizing: border-box; }
        html { overflow-y: scroll; }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px 40px 20px;
        }

        /* --- AYDINLIK CAM PANEL (LIGHT GLASS) --- */
        .glass-panel {
            background: rgba(255, 255, 255, 0.3); /* Daha açık beyaz */
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            color: #333; /* Koyu Yazı */
        }

        /* PROFİL HEADER */
        .profile-header {
            text-align: center;
            padding: 40px;
            margin-bottom: 40px;
            position: relative;
        }
        .profile-pic-wrapper {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px auto;
        }
        .profile-pic {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255,255,255,0.8);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            background: #fff;
        }
        .upload-label, .delete-btn {
            position: absolute;
            bottom: 5px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            transition: 0.2s;
            border: 2px solid white;
            color: white;
        }
        .upload-label { right: 5px; background: #6A5ACD; }
        .upload-label:hover { background: #584ab8; transform: scale(1.1); }
        .delete-btn { left: 5px; background: #ff7675; border: none; }
        .delete-btn:hover { background: #d63031; transform: scale(1.1); }
        #file-upload { display: none; }
        
        .profile-header h1 { 
            margin: 0 0 10px 0; 
            font-size: 32px; 
            font-weight: 800; 
            color: #000; /* Başlık Siyah */
        }
        .profile-header p { 
            margin: 5px 0; 
            font-size: 16px; 
            color: #444; /* Metin Koyu Gri */
            font-weight: 500;
        }

        /* KART STİLLERİ */
        .section-title { 
            font-size: 24px; 
            color: #333; /* Başlık Koyu */
            margin-bottom: 20px; 
            font-weight: 700; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            text-shadow: none;
        }
        .section-title a { font-size: 14px; color: #333; text-decoration: underline; font-weight: normal; }
        
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 50px; }
        
        /* Kartların içi zaten açıktı, biraz daha belirginleştirdik */
        .item-card { 
            background: rgba(255, 255, 255, 0.7); 
            border-radius: 16px; 
            padding: 20px; 
            text-decoration: none; 
            color: #333; 
            transition: all 0.3s ease; 
            border: 1px solid rgba(255, 255, 255, 0.6); 
            display: flex; flex-direction: column; min-height: 160px; 
        }
        .item-card:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.95); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        
        .category-badge { align-self: flex-start; background: #6A5ACD; color: white; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; margin-bottom: 15px; }
        .card-title { font-size: 18px; font-weight: 700; color: #222; margin: 0 0 10px 0; }
        .card-desc { font-size: 13px; color: #555; line-height: 1.4; flex-grow: 1; margin-bottom: 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .card-footer { border-top: 1px solid rgba(0,0,0,0.1); padding-top: 10px; font-size: 12px; color: #777; text-align: right; }
        .folder-icon { font-size: 40px; margin-bottom: 10px; display: block; text-align: center; }
        .folder-card-content { text-align: center; width: 100%; }
        
        .empty-msg { 
            grid-column: 1 / -1; 
            text-align: center; padding: 40px; 
            color: #555; 
            font-size: 18px; 
            background: rgba(255,255,255,0.4); 
            border-radius: 16px; 
            border: 1px dashed rgba(0,0,0,0.2); 
            font-weight: 500;
        }
        
        .create-btn { 
            display: inline-block; 
            background: #333; color: #fff; /* Siyah Buton */
            padding: 12px 30px; border-radius: 30px; 
            text-decoration: none; font-weight: bold; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: 0.3s; 
        }
        .create-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.15); background: #000; }

        /* --- GÜVENLİK PANELİ STİLLERİ --- */
        .security-panel {
            margin-top: 50px;
            margin-bottom: 30px;
            overflow: hidden; 
        }
        
        .security-header {
            padding: 20px 30px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.4);
            border-bottom: 1px solid rgba(255,255,255,0.5);
            transition: 0.2s;
        }
        .security-header:hover { background: rgba(255, 255, 255, 0.6); }
        .security-header h3 { margin: 0; color: #222; font-size: 18px; }
        .toggle-icon { font-size: 20px; font-weight: bold; color: #333; transition: transform 0.3s; }

        #password-form-container {
            padding: 30px;
            background: rgba(255, 255, 255, 0.2);
        }

        .form-group { 
            margin-bottom: 15px; 
            position: relative; 
        }

        .form-group label { display: block; margin-bottom: 5px; color: #333; font-weight: 600; }
        
        /* Inputlar (Create Set ve Login stili) */
        .form-group input { 
            width: 100%; 
            padding: 12px 40px 12px 12px; 
            border: 1px solid rgba(255,255,255,0.4); 
            border-radius: 8px; 
            background: rgba(255,255,255,0.6); /* Yarı saydam beyaz arka plan */
            color: #333; /* Koyu Yazı */
            font-weight: 500;
            outline: none;
            transition: 0.3s;
        }
        .form-group input:focus {
            background: rgba(255,255,255,0.8);
            border-color: #fff;
            box-shadow: 0 0 10px rgba(255,255,255,0.3);
        }

        .btn-update-pass {
            background: #0984e3; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s;
        }
        .btn-update-pass:hover { background: #0773c7; }

        .toggle-password-icon {
            position: absolute;
            right: 15px;
            bottom: 13px; 
            cursor: pointer;
            color: #555;
            transition: color 0.3s;
        }
        .toggle-password-icon:hover { color: #000; }

        .alert { padding: 10px; margin-bottom: 15px; border-radius: 8px; font-weight: bold; }
        .alert.success { color: #006400; background: rgba(80, 255, 120, 0.3); border: 1px solid #50ff78; }
        .alert.error { color: #8b0000; background: rgba(255, 80, 80, 0.3); border: 1px solid #fa3939; }

        /* Danger Zone - Yazıları koyulaştırdık */
        .danger-zone {
            background: rgba(255, 71, 87, 0.15); 
            border: 1px solid rgba(255, 71, 87, 0.5); 
            padding: 30px; 
            border-radius: 20px; 
            text-align: center; 
            color: #333; /* Yazı rengi koyu */
            margin-top: 30px; 
            backdrop-filter: blur(15px);
        }
        .danger-zone h3 { margin-top: 0; color: #d63031; }
        .danger-zone p { margin-bottom: 20px; font-size: 14px; color: #444; }
        
        .btn-delete-account { background: #ff4757; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 15px; box-shadow: 0 4px 10px rgba(255, 71, 87, 0.3); }
        .btn-delete-account:hover { background: #e04050; transform: scale(1.02); box-shadow: 0 6px 15px rgba(255, 71, 87, 0.5); }
        
        /* Admin Kilit Bölgesi */
        .admin-locked {
            background: rgba(52, 152, 219, 0.15); 
            border: 1px solid rgba(52, 152, 219, 0.5); 
            padding: 20px; 
            border-radius: 20px; 
            text-align: center; 
            color: #333; /* Yazı rengi koyu */
            margin-top: 30px; 
            backdrop-filter: blur(15px);
        }
        .admin-locked h3 { margin-top: 0; color: #0984e3; }
    </style>
</head>
<body>

    <?php include "menu.php"; ?>

    <div class="container">
        
        <div class="glass-panel profile-header">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="profile-pic-wrapper">
                    <img src="<?php echo $profile_pic_path; ?>" alt="Profil Resmi" class="profile-pic">
                    <?php if($has_custom_pic): ?>
                        <button type="submit" name="delete_photo" class="delete-btn" title="Fotoğrafı Kaldır" onclick="return confirm('Profil fotoğrafını silmek istediğine emin misin?');">🗑️</button>
                    <?php endif; ?>
                    <label for="file-upload" class="upload-label" title="Fotoğrafı Değiştir"><span>📷</span></label>
                    <input type="file" name="profile_image" id="file-upload" accept="image/*" onchange="this.form.submit()">
                </div>
            </form>
            <h1>Merhaba, <?php echo htmlspecialchars($user_info['username']); ?>!</h1>
            <?php if ($user_info['is_admin'] == 1): ?>
                <span style="background: #e1b12c; color: #2f3640; padding: 5px 10px; border-radius: 10px; font-weight: bold; font-size: 12px; margin-bottom: 10px; display: inline-block;">YÖNETİCİ</span>
            <?php endif; ?>
            <p>📧 <?php echo htmlspecialchars($user_info['email']); ?></p>
            <p>📅 Üyelik Tarihi: <?php echo date("d.m.Y", strtotime($user_info['created_at'])); ?></p>
        </div>

        <div class="section-title">
            <span>📝 Oluşturduğum Setler</span>
            <a href="my_sets.php">Yönet & Düzenle</a>
        </div>
        <div class="grid-container">
            <?php if ($res_my_sets->num_rows > 0): ?>
                <?php while($row = $res_my_sets->fetch_assoc()): ?>
                    <a href="view_set.php?id=<?php echo $row['set_id']; ?>" class="item-card">
                        <?php $cat = !empty($row['category']) ? htmlspecialchars($row['category']) : 'Genel'; ?>
                        <span class="category-badge"><?php echo $cat; ?></span>
                        <h3 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="card-desc"><?php echo htmlspecialchars($row['description']); ?></div>
                        <div class="card-footer">Senin Setin</div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-msg">Henüz hiç set oluşturmadın.</div>
            <?php endif; ?>
        </div>

        <div class="section-title">
            <span>📂 Klasörlerim</span>
            <a href="folders.php">Yönet & Düzenle</a>
        </div>
        <div class="grid-container">
            <?php if ($res_folders->num_rows > 0): ?>
                <?php while($row = $res_folders->fetch_assoc()): ?>
                    <?php 
                        $f_id = $row['folder_id'];
                        $sql_count = "SELECT COUNT(*) as cnt FROM folder_sets WHERE folder_id = $f_id";
                        $res_count = $conn->query($sql_count);
                        $count = $res_count->fetch_assoc()['cnt'];
                    ?>
                    <a href="view_folder.php?id=<?php echo $row['folder_id']; ?>" class="item-card" style="align-items: center; justify-content: center;">
                        <div class="folder-card-content">
                            <span class="folder-icon">📁</span>
                            <h3 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <span style="font-size:12px; color:#666;"><?php echo $count; ?> set</span>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-msg">Henüz klasörün yok.</div>
            <?php endif; ?>
        </div>
        <div style="text-align: center; margin-top: 10px;">
            <a href="folders.php" class="create-btn">+ Yeni Klasör Oluştur</a>
        </div>


        <div class="glass-panel security-panel">
            
            <div class="security-header" onclick="togglePasswordForm()">
                <h3>🔐 Şifre Değiştir</h3>
                <span id="toggleIcon" class="toggle-icon"><?php echo $show_password_form ? '▲' : '▼'; ?></span>
            </div>

            <div id="password-form-container" style="display: <?php echo $show_password_form ? 'block' : 'none'; ?>;">
                
                <?php echo $pass_msg; ?>

                <form method="POST">
                    
                    <div class="form-group">
                        <label>Mevcut Şifre</label>
                        <input type="password" name="current_pass" id="current_pass" required placeholder="Şu anki şifreniz">
                        <i class="fa-solid fa-eye toggle-password-icon" onclick="togglePass('current_pass', this)"></i>
                    </div>

                    <div class="form-group">
                        <label>Yeni Şifre</label>
                        <input type="password" name="new_pass" id="new_pass" required placeholder="Yeni şifreniz">
                        <i class="fa-solid fa-eye toggle-password-icon" onclick="togglePass('new_pass', this)"></i>
                    </div>

                    <div class="form-group">
                        <label>Yeni Şifre Tekrar</label>
                        <input type="password" name="confirm_pass" id="confirm_pass" required placeholder="Yeni şifrenizi tekrar girin">
                        <i class="fa-solid fa-eye toggle-password-icon" onclick="togglePass('confirm_pass', this)"></i>
                    </div>

                    <button type="submit" name="change_password" class="btn-update-pass">Şifreyi Güncelle</button>
                </form>
            </div>
        </div>

        <?php if ($user_info['is_admin'] != 1): ?>
            <div class="danger-zone">
                <h3>Hesap İşlemleri</h3>
                <p>Hesabını silersen tüm verilerin kalıcı olarak silinecektir.</p>
                <form method="POST">
                    <button type="submit" name="delete_account" class="btn-delete-account" onclick="return confirm('❗ DİKKAT: Hesabını tamamen silmek üzeresin. Devam etmek istiyor musun?');">⚠️ Hesabımı Kalıcı Olarak Sil</button>
                </form>
            </div>
        <?php else: ?>
            <div class="admin-locked">
                <h3>🛡️ Hesap Güvenliği</h3>
                <p>Yönetici (Admin) rolüne sahip olduğunuz için hesabınızı silemezsiniz.</p>
            </div>
        <?php endif; ?>

    </div>

    <script>
        // Formu Aç/Kapa (Accordion)
        function togglePasswordForm() {
            var formDiv = document.getElementById("password-form-container");
            var icon = document.getElementById("toggleIcon");

            if (formDiv.style.display === "none") {
                formDiv.style.display = "block";
                icon.innerHTML = "▲";
            } else {
                formDiv.style.display = "none";
                icon.innerHTML = "▼";
            }
        }

        // Şifre Göster/Gizle
        function togglePass(inputId, icon) {
            const input = document.getElementById(inputId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

</body>
</html>