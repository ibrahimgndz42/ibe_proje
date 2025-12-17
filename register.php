<?php
// Oturumu başlatıyoruz
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'connectDB.php';

// HTML çıktısı başlamadan önce PHP mantığını hallediyoruz
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO users (username, email, password) 
                VALUES ('$username', '$email', '$password')";
        
        if ($conn->query($sql)) {
            // Kayıt başarılı ise ID al ve oturum aç
            $new_user_id = $conn->insert_id;

            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['username'] = $username;

            $success = "Kayıt başarılı! Giriş yapılıyor...";
        }

    } catch (mysqli_sql_exception $e) {
        // Hata mesajlarını yakala (Duplicate entry)
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            if (strpos($e->getMessage(), "'$email'") !== false) {
                $error = "Bu e‑posta adresi zaten kullanılıyor!";
            } elseif (strpos($e->getMessage(), "'$username'") !== false) {
                $error = "Bu kullanıcı adı zaten kullanılıyor!";
            } else {
                $error = "Bu bilgilerle zaten bir kayıt mevcut.";
            }
        } else {
            $error = "Bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* --- SAYFA YAPISI --- */
        body { margin: 0; padding: 0; min-height: 100vh; display: flex; flex-direction: column; }
        .main-wrapper { flex: 1; display: flex; justify-content: center; align-items: center; padding: 20px; width: 100%; box-sizing: border-box; }

        /* --- KART TASARIMI --- */
        .login-card {
            width: 100%; max-width: 400px; backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.2); border-radius: 16px; padding: 40px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.3);
            animation: fadeIn 0.8s ease; position: relative;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header h2 { margin: 0; color: #000; font-size: 26px; text-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .login-header p { margin-top: 5px; color: #222; font-size: 14px; }

        .close-btn { position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.3); border: none; color: black; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 16px; transition: 0.3s; }
        .close-btn:hover { background: rgba(255,255,255,0.5); }

        /* --- INPUT ALANLARI --- */
        .input-group { position: relative; margin-bottom: 25px; }
        .input-group input { width: 100%; padding: 12px 15px; padding-right: 40px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.4); border-radius: 8px; color: #333; font-size: 16px; outline: none; transition: 0.3s; box-sizing: border-box; }
        .input-group input:focus { background: rgba(255,255,255,0.2); border-color: white; box-shadow: 0 0 10px rgba(255,255,255,0.1); }
        .input-group label { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; pointer-events: none; transition: 0.3s ease; font-size: 15px; }
        .input-group input:focus + label, .input-group input:not(:placeholder-shown) + label { top: -10px; left: 5px; font-size: 12px; color: #aaa; font-weight: bold; background: transparent; padding: 0 5px; }

        /* --- BUTONLAR VE MESAJLAR --- */
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #aaa; cursor: pointer; }
        .login-btn { width: 100%; padding: 12px; background: white; color: #333; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .login-btn:hover { background: #f0f0f0; transform: scale(1.02); }
        
        /* Link Stili */
        .register-link { display: block; text-align: center; margin-top: 15px; color: #DA3A3A; text-decoration: none; font-size: 14px; opacity: 0.8; transition: 0.3s; }
        .register-link:hover { opacity: 1; text-decoration: underline; }
        
        .msg-box { padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-size: 14px; font-weight: bold; }
        .error { background: rgba(255, 80, 80, 0.2); border: 1px solid #fa3939ff; color: #7e0c0cff; }
        .success { background: rgba(80, 255, 120, 0.2); border: 1px solid #50ff78; color: #71f571ff; }
    </style>
</head>
<body>

    <?php include "menu.php"; ?>

    <div class="main-wrapper">
        <div class="login-card">

            <?php if(!isset($success)): ?>
                <button class="close-btn" onclick="window.location.href='index.php'">✕</button>
                <div class="login-header">
                    <h2>Hesap Oluştur</h2>
                    <p>Hemen aramıza katılın</p>
                </div>
            <?php endif; ?>

            <?php if(isset($error)): ?>
                <div class="msg-box error"><?= $error ?></div>
            <?php endif; ?>

            <?php if(isset($success)): ?>
                <div class="msg-box success"><?= $success ?></div>
                <script>
                    setTimeout(() => { window.location.href = "index.php"; }, 1500);
                </script>
            <?php else: ?>

                <form action="register.php" method="POST">
                    
                    <div class="input-group">
                        <input type="text" id="username" name="username" placeholder=" " required>
                        <label for="username">Kullanıcı Adı</label>
                    </div>

                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder=" " required>
                        <label for="email">E-posta</label>
                    </div>

                    <div class="input-group">
                        <input type="password" id="password" name="password" placeholder=" " required>
                        <label for="password">Şifre</label>
                        <i class="fa-solid fa-eye toggle-password" onclick="togglePassword()"></i>
                    </div>

                    <button type="submit" class="login-btn">Kaydol</button>
                    
                    <a href="login.php" class="register-link">Zaten hesabınız var mı? Giriş Yap</a>
                </form>

            <?php endif; ?>

        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.toggle-password');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

</body>
</html>