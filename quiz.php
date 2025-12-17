<?php
// Session kontrolü
if (file_exists("session_check.php")) {
    include "session_check.php";
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Basit bir kontrol (Session yoksa login'e at)
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

include "connectDB.php";

if (!isset($_GET['set_id'])) {
    echo "<center><h1>ID gerekli.</h1></center>";
    exit;
}

$set_id = intval($_GET['set_id']);

// Set başlığını çek
$sql_set = "SELECT title FROM sets WHERE set_id = $set_id";
$res_set = $conn->query($sql_set);
if ($res_set->num_rows == 0) {
    echo "<center><h1>Set bulunamadı.</h1></center>";
    exit;
}
$set = $res_set->fetch_assoc();

// Kartları çek
$sql_cards = "SELECT term, defination FROM cards WHERE set_id = $set_id";
$res_cards = $conn->query($sql_cards);

$cards = [];
while($row = $res_cards->fetch_assoc()) {
    $cards[] = $row;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Modu: <?php echo htmlspecialchars($set['title']); ?></title>
    <link rel="stylesheet" href="style.css">

    <style>
        /* --- DÜZEN (LAYOUT) --- */
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
        .quiz-container {
            width: 100%;
            max-width: 600px;
        }

        .glass-card {
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.4);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.5);
            position: relative;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Kapat Butonu */
        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 30px; height: 30px;
            background: rgba(0,0,0,0.1);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; color: #333; font-weight: bold;
            transition: 0.2s;
        }
        .close-btn:hover { background: #ff7675; color: white; }

        /* Başlıklar */
        .quiz-header { text-align: center; margin-bottom: 25px; }
        .quiz-header h2 { margin: 0; color: #333; font-size: 24px; }
        .progress-badge { 
            display: inline-block; margin-top: 5px; 
            font-size: 12px; font-weight: bold; color: #666; 
            background: rgba(255,255,255,0.6); padding: 4px 12px; border-radius: 10px;
        }

        /* Soru Kutusu */
        .question-box {
            background: rgba(255,255,255,0.6);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            color: #2d3436;
            margin-bottom: 25px;
            border: 1px solid rgba(255,255,255,0.6);
            min-height: 60px;
            display: flex; align-items: center; justify-content: center;
        }

        /* Şıklar */
        .options-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .option-btn {
            padding: 15px;
            border: none;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.85);
            color: #333;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: 0.2s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            text-align: left;
            padding-left: 20px;
        }

        .option-btn:hover {
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        /* Doğru/Yanlış Renkleri */
        .correct {
            background-color: #00b894 !important; /* Yeşil */
            color: white !important;
        }
        .wrong {
            background-color: #ff7675 !important; /* Kırmızı */
            color: white !important;
        }

        /* Sonuç Alanı */
        #resultArea { display: none; text-align: center; }
        .score-big { font-size: 48px; font-weight: 800; color: #2d3436; display: block; margin: 15px 0; }
        
        .btn-group { display: flex; gap: 10px; justify-content: center; margin-top: 25px; }
        .btn-act { padding: 12px 24px; border-radius: 8px; border: none; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; font-size: 15px; }
        .btn-retry { background: #6c5ce7; color: white; }
        .btn-retry:hover { background: #5a4db8; }
        .btn-exit { background: #dfe6e9; color: #333; }
        .btn-exit:hover { background: #b2bec3; }

        /* Hata Mesajı */
        .error-msg { text-align: center; color: #444; }
        .error-msg h3 { margin-bottom: 20px; }
        .error-msg a { color: #6c5ce7; font-weight: bold; text-decoration: none; }

    </style>
</head>
<body>

    <?php include "menu.php"; ?>

    <div class="main-wrapper">
        <div class="quiz-container">
            
            <?php if (count($cards) < 4): ?>
                <div class="glass-card error-msg">
                    <h3>⚠️ Test modu için bu sette en az 4 kart bulunmalıdır.</h3>
                    <p>Şu anki kart sayısı: <strong><?php echo count($cards); ?></strong></p>
                    <br>
                    <a href="view_set.php?set_id=<?php echo $set_id; ?>" class="btn-act btn-exit">Geri Dön</a>
                </div>
            <?php else: ?>
                <div class="glass-card">
                    
                    <a href="view_set.php?set_id=<?php echo $set_id; ?>" class="close-btn" title="Çıkış">✕</a>

                    <div id="quizBox">
                        <div class="quiz-header">
                            <h2><?php echo htmlspecialchars($set['title']); ?></h2>
                            <span class="progress-badge">Soru <span id="qIndex">1</span> / <span id="qTotal"><?php echo count($cards); ?></span></span>
                        </div>

                        <div class="question-box" id="questionText">Yükleniyor...</div>

                        <div class="options-grid" id="optionsBox">
                            </div>
                    </div>

                    <div id="resultArea">
                        <h2>🎉 Test Tamamlandı!</h2>
                        <span class="score-big"><span id="scoreVal">0</span> / <?php echo count($cards); ?></span>
                        <p style="color:#666;">Doğru Cevap Sayısı</p>
                        
                        <div class="btn-group">
                            <button class="btn-act btn-retry" onclick="location.reload()">Tekrar Çöz</button>
                            <a href="view_set.php?set_id=<?php echo $set_id; ?>" class="btn-act btn-exit">Sete Dön</a>
                        </div>
                    </div>

                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php if (count($cards) >= 4): ?>
    <script>
        const cards = <?php echo json_encode($cards); ?>;
        let currentQuestion = 0;
        let score = 0;
        
        // Karıştırma Fonksiyonu
        function shuffle(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
            return array;
        }

        // Ana soru listesini karıştır
        let questions = [...cards];
        shuffle(questions);

        // HTML Elemanları
        const questionText = document.getElementById("questionText");
        const optionsBox = document.getElementById("optionsBox");
        const qIndexSpan = document.getElementById("qIndex");
        const quizBox = document.getElementById("quizBox");
        const resultArea = document.getElementById("resultArea");
        const scoreVal = document.getElementById("scoreVal");

        function loadQuestion() {
            if (currentQuestion >= questions.length) {
                showResult();
                return;
            }

            const q = questions[currentQuestion];
            qIndexSpan.textContent = currentQuestion + 1;
            
            // Soru: Term (Terim), Cevap: Definition (Tanım)
            questionText.textContent = q.term; 
            
            // Şık havuzu oluştur
            // 1. Doğru cevabı ekle
            let options = [q.defination];
            
            // 2. Yanlış cevapları havuzdan seç (kendi cevabı hariç)
            let allDefinitions = cards.map(c => c.defination).filter(d => d !== q.defination);
            
            // Yanlış cevapları karıştır ve ilk 3 tanesini al
            shuffle(allDefinitions);
            options.push(...allDefinitions.slice(0, 3));
            
            // 3. Son olarak şıkları kendi içinde karıştır (A,B,C,D yerleri değişsin)
            shuffle(options);

            // Butonları oluştur
            optionsBox.innerHTML = "";
            options.forEach(opt => {
                const btn = document.createElement("button");
                btn.className = "option-btn";
                btn.textContent = opt;
                btn.onclick = () => checkAnswer(btn, opt, q.defination);
                optionsBox.appendChild(btn);
            });
        }

        function checkAnswer(btn, selected, correct) {
            // Tüm butonları kilitle (tekrar tıklamayı önle)
            const buttons = document.querySelectorAll(".option-btn");
            buttons.forEach(b => b.disabled = true);

            if (selected === correct) {
                // Doğru bildi
                btn.classList.add("correct");
                // Opsiyonel: İkon ekle
                btn.innerHTML += ' <i class="fa-solid fa-check" style="float:right;"></i>';
                score++;
            } else {
                // Yanlış bildi
                btn.classList.add("wrong");
                btn.innerHTML += ' <i class="fa-solid fa-xmark" style="float:right;"></i>';
                
                // Doğru olanı yeşil yakarak göster
                buttons.forEach(b => {
                    if (b.textContent === correct) {
                        b.classList.add("correct");
                        b.innerHTML += ' <i class="fa-solid fa-check" style="float:right;"></i>';
                    }
                });
            }

            // 1.5 saniye bekle ve sonraki soruya geç
            setTimeout(() => {
                currentQuestion++;
                loadQuestion();
            }, 1500);
        }

        function showResult() {
            quizBox.style.display = "none";
            resultArea.style.display = "block";
            scoreVal.textContent = score;
        }

        // Oyunu Başlat
        loadQuestion();
    </script>
    <?php endif; ?>

</body>
</html>