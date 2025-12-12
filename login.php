<?php
include 'connectDB.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username='$username'");

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];

            $success = "Giriş başarılı! Yönlendiriliyorsunuz...";
        } else {
            $error = "Şifre hatalı!";
        }
    } else {
        $error = "Kullanıcı adı bulunamadı!";
    }

}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @keyframes onAutoFillStart {}

        input:-webkit-autofill {
            animation-name: onAutoFillStart;
        }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #8EC5FC, #E0C3FC);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Container */
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        /* Glass Card */
        .login-card {
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.25);
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .close-btn {
            position: absolute;
            top: 12px;
            right: 12px;
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
            transition: 0.25s ease;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.55);
            transform: scale(1.1);
        }

        .login-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .login-header h2 {
            margin: 0;
            font-size: 28px;
            color: #fff;
        }

        .login-header p {
            margin-top: 6px;
            color: #f0f0f0;
            font-size: 14px;
        }

        /* Floating Input */
        .input-wrapper {
            position: relative;
            display: flex;
            margin-bottom: 25px;
            align-items: center;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 12px;
            /* İkon için sağdan boşluk bırakıyoruz */
            padding-right: 40px; 
            border: 1px solid rgba(255,255,255,0.4);
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            font-size: 15px;
            color: #fff;
            outline: none;
        }

        /* Sadece .filled ve focus için label yukarı çıksın */
        .input-wrapper label {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            color: #eaeaea;
            pointer-events: none;
            transition: 0.2s ease;
        }

        .input-wrapper input:focus + label,
        .input-wrapper input:not(:placeholder-shown) + label {
            top: -6px;
            font-size: 12px;
            color: #fff;
        }

        /* Focus Border Animation */
        .focus-border {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 2px;
            width: 0;
            background: #fff;
            transition: 0.3s;
        }

        .input-wrapper input:focus ~ .focus-border {
            width: 100%;
        }

        /* Göz İkonu Stili */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255, 255, 255, 0.7);
            transition: color 0.3s;
            z-index: 10;
        }

        .toggle-password:hover {
            color: #fff;
        }

        .error-msg {
            background: rgba(255, 0, 0, 0.25);
            border: 1px solid rgba(255, 0, 0, 0.4);
            padding: 10px;
            border-radius: 8px;
            color: #ff4d4d;
            text-align: center;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .success-msg {
            background: rgba(0, 255, 150, 0.25);
            border: 1px solid rgba(0, 255, 150, 0.4);
            padding: 10px;
            border-radius: 8px;
            color: #004228ff;
            text-align: center;
            margin-bottom: 15px;
            font-weight: 600;
        }

        /* Login Button */
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #ffffff;
            color: #333;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }

        .login-btn:hover {
            background: #eaeaea;
        }
    </style>
</head>

<body>

<div class="login-container">
    <div class="login-card">

        <?php if(!isset($success)): ?>
        <div class="login-header">
            <button class="close-btn" onclick="history.back()">✕</button>
            <h2>Giriş Yap</h2>
            <p>Hesabınıza giriş yapın</p>
        </div>
        <?php endif; ?>

        <?php if(isset($success)): ?>
            <p class="success-msg"><?= $success ?></p>

            <script>
                setTimeout(() => {
                    window.location.href = "index.php";
                }, 3000);
            </script>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <p class="error-msg"><?= $error ?></p>
        <?php endif; ?>

        <?php if(!isset($success)): ?>
        <form action="login.php" method="POST">

            <div class="input-wrapper">
                <input type="text" id="username" name="username" required placeholder=" ">
                <label for="username">Kullanıcı Adı</label>
                <span class="focus-border"></span>
            </div>

            <div class="input-wrapper">
                <input type="password" id="password" name="password" required placeholder=" ">
                <label for="password">Şifre</label>
                <span class="focus-border"></span>
                <i class="fa-solid fa-eye toggle-password" onclick="togglePassword()"></i>
            </div>

            <button type="submit" class="login-btn">Giriş Yap</button>
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
        // İkonu 'göz kapalı' (slash) yap
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        // İkonu tekrar 'göz açık' yap
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Auto-fill yakalama + elle yazma kontrolü
function checkFilled(input) {
    if (input.value.trim() !== "") {
        input.classList.add("filled");
    } else {
        input.classList.remove("filled");
    }
}

window.addEventListener("DOMContentLoaded", () => {
    const inputs = document.querySelectorAll(".input-wrapper input");

    inputs.forEach(input => {
        checkFilled(input);

        input.addEventListener("input", () => {
            checkFilled(input);
        });
    });
});

// Auto-fill animasyonunu yakala (Chrome için)
document.addEventListener("animationstart", (e) => {
    if (e.animationName === "onAutoFillStart") {
        e.target.classList.add("filled");
    }
});
</script>

</body>
</html>