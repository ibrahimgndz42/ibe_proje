<?php
include "connectDB.php";
include "menu.php"; 

$status_msg = "";

// Form Gönderildiğinde Çalışacak Kod
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = isset($_POST['name']) ? trim(htmlspecialchars($_POST['name'])) : "";
    $subject = isset($_POST['subject']) ? trim(htmlspecialchars($_POST['subject'])) : "";
    $message = isset($_POST['message']) ? trim(htmlspecialchars($_POST['message'])) : "";
    
    // Giriş yapmışsa ID'yi al, yapmamışsa NULL
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

    if (!empty($name) && !empty($message)) {
        // Veritabanına Ekleme
        $stmt = $conn->prepare("INSERT INTO suggestions (user_id, name, subject, message) VALUES (?, ?, ?, ?)");
        // user_id integer olabilir veya null olabilir, bu yüzden bind_param'ı dikkatli yapıyoruz
        // Ancak basitlik adına query içine gömerek veya bind_param ile şöyle yapabiliriz:
        
        // ID varsa 'i', yoksa null geçmek biraz karışıktır, basit query kullanalım:
        if($user_id){
             $sql = "INSERT INTO suggestions (user_id, name, subject, message) VALUES ('$user_id', '$name', '$subject', '$message')";
        } else {
             $sql = "INSERT INTO suggestions (user_id, name, subject, message) VALUES (NULL, '$name', '$subject', '$message')";
        }

        if($conn->query($sql)){
            $status_msg = '<div class="alert-success">Mesajınız başarıyla iletildi! Teşekkürler.</div>';
        } else {
            $status_msg = '<div class="alert-error">Bir hata oluştu.</div>';
        }

    } else {
        $status_msg = '<div class="alert-error">Lütfen isim ve mesaj alanlarını doldurun.</div>';
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
            max-width: 800px; /* Form çok geniş olmasın diye biraz kıstım */
            margin: 40px auto;
        }

        /* Glassmorphism Kartları (Admin ile aynı) */
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

        /* Form Elemanları (Admin stili) */
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
            outline: none;
            background: rgba(255, 255, 255, 0.8);
            font-family: 'Inter', sans-serif;
            box-sizing: border-box; /* Padding taşmasın diye */
        }

        input:focus, textarea:focus {
            background: #fff;
            border-color: #6c5ce7;
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

        /* Mesaj Kutuları */
        .alert-success {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
            color: #27ae60;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #c0392b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
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
            
            <label>İsim / Rumuz:</label>
            <input type="text" name="name" required placeholder="Adınız..." 
                   value="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>">

            <label>Konu (Opsiyonel):</label>
            <input type="text" name="subject" placeholder="Örn: Set eklerken hata alıyorum">

            <label>Mesajınız:</label>
            <textarea name="message" rows="6" required placeholder="Düşüncelerinizi buraya yazın..."></textarea>

            <button type="submit" class="btn-submit">Gönder 🚀</button>
        </form>
    </div>

</div>

</body>
</html>