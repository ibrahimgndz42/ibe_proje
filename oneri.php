<?php
include "connectDB.php";
include "menu.php"; 

$status_msg = "";
$is_logged_in = isset($_SESSION['user_id']);

// Form Gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $type = isset($_POST['type']) ? $_POST['type'] : 'oneri';
    $message = isset($_POST['message']) ? trim(htmlspecialchars($_POST['message'])) : "";
    
    // Değişkenleri hazırla
    $user_id = null;
    $guest_name = null;

    if ($is_logged_in) {
        // Üye ise ID'sini al, ismi boş kalsın (zaten ID'den buluruz)
        $user_id = $_SESSION['user_id'];
    } else {
        // Misafir ise ID yok, girilen ismi al
        $guest_name = isset($_POST['guest_name']) ? trim(htmlspecialchars($_POST['guest_name'])) : "Misafir";
    }

    // Güvenlik: Tür kontrolü
    $allowed_types = ['oneri', 'istek', 'hata'];
    if (!in_array($type, $allowed_types)) $type = 'oneri';

    if (!empty($message)) {
        
        // SQL Sorgusu (user_id NULL olabilir)
        // Hazırlanan ifadeyi dinamik oluşturuyoruz
        $stmt = $conn->prepare("INSERT INTO suggestions (user_id, guest_name, type, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $guest_name, $type, $message);

        if ($stmt->execute()) {
            $status_msg = '<div class="alert-success">✅ Mesajınız başarıyla iletildi! Teşekkürler.</div>';
        } else {
            $status_msg = '<div class="alert-error">❌ Veritabanı hatası: ' . $conn->error . '</div>';
        }
        $stmt->close();

    } else {
        $status_msg = '<div class="alert-error">⚠️ Lütfen mesaj alanını doldurun.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İstek ve Öneri - Mini Sınavım</title>
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
            max-width: 800px;
            margin: 40px auto;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(15px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        h1 { color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 0; margin-bottom: 20px; }
        h2 { color: #333; font-size: 1.5rem; border-bottom: 2px solid rgba(255,255,255,0.5); padding-bottom: 10px; margin-bottom: 20px; }

        label { display: block; margin-bottom: 8px; font-weight: 600; color: #444; }

        input[type="text"], select, textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
            outline: none;
            background: rgba(255, 255, 255, 0.8);
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            box-sizing: border-box;
        }

        select:focus, textarea:focus, input:focus {
            background: #fff;
            border-color: #6c5ce7;
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.2);
        }

        button.btn-submit {
            padding: 12px 25px;
            background: #6c5ce7;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: 0.2s;
            width: 100%;
        }

        button.btn-submit:hover {
            background: #5b4cc4;
            transform: translateY(-2px);
        }

        .alert-success { background: rgba(46, 204, 113, 0.2); border: 1px solid #2ecc71; color: #27ae60; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .alert-error { background: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; color: #c0392b; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        
        .user-info-box {
            background: rgba(255,255,255,0.5);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #555;
            font-size: 14px;
            border-left: 4px solid #6c5ce7;
        }
    </style>
</head>
<body>

<div class="container">
    
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1>📬 İstek ve Öneri</h1>
    </div>

    <div class="glass-card">
        <h2>Bize Yazın</h2>
        <p style="color:#555; margin-bottom:20px;">
            Sitemizle ilgili geliştirmemizi istediğiniz özellikleri, bulduğunuz hataları veya düşüncelerinizi aşağıdan iletebilirsiniz.
        </p>

        <?php echo $status_msg; ?>

        <form method="POST">
            
            <?php if ($is_logged_in): ?>
                <div class="user-info-box">
                    👤 <b><?php echo htmlspecialchars($_SESSION['username']); ?></b> olarak gönderiyorsunuz.
                </div>
            <?php else: ?>
                <label>İsim / Rumuz (Zorunlu Değil):</label>
                <input type="text" name="guest_name" placeholder="Adınız...">
            <?php endif; ?>

            <label>Bildirim Türü:</label>
            <select name="type" required>
                <option value="oneri">💡 Öneri</option>
                <option value="istek">🚀 Özellik İsteği</option>
                <option value="hata">⚠️ Hata Bildirimi</option>
            </select>

            <label>Mesajınız:</label>
            <textarea name="message" rows="6" required placeholder="Düşüncelerinizi buraya yazın..."></textarea>

            <button type="submit" class="btn-submit">Gönder 🚀</button>
        </form>
    </div>

</div>

</body>
</html>