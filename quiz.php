<?php
include "session_check.php";
include "connectDB.php";
// include "menu.php"; // Tam ekran glass tasarımında menüyü genelde kaldırırız, isterseniz açabilirsiniz.

if (!isset($_GET['id'])) {
    echo "ID gerekli.";
    exit;
}

$set_id = intval($_GET['id']);

// Set bilgisi
$sql_set = "SELECT title FROM sets WHERE set_id = $set_id";
$res_set = $conn->query($sql_set);
if ($res_set->num_rows == 0) {
    echo "Set bulunamadı.";
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

// Test için en az 4 kart kontrolü
if (count($cards) < 4) {
    // Hata mesajı için basit bir HTML çıktısı
    echo '<body style="background: linear-gradient(135deg, #8EC5FC, #E0C3FC); font-family: sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;">';
    echo '<div style="background: rgba(255,255,255,0.25); backdrop-filter: blur(15px); padding:40px; border-radius:16px; text-align:center; color:white; box-shadow: 0 8px 32px rgba(0,0,0,0.15);">';
    echo '<h3>Test modu için en az 4 kart gereklidir.</h3>';
    echo '<a href="view_set.php?id='.$set_id.'" style="color:#fff; text-decoration:underline;">Geri Dön</a>';
    echo '</div></body>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test: <?php echo htmlspecialchars($set['title']); ?></title>
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #8EC5FC, #E0C3FC);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .quiz-container {
            width: 100%;
            max-width: 500px;
        }

        .glass-card {
            backdrop-filter: blur(15px);
            background: rgba(255, 255, 255, 0.25);
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            position: relative;
            animation: fadeIn 0.6s ease;
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
            transition: 0.25s ease;
            text-decoration: none;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.55);
            transform: scale(1.1);
        }

        .quiz-header {
            text-align: center;
            margin-bottom: 25px;
            color: #fff;
        }

        .quiz-header h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }

        .progress-text {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            font-weight: 600;
        }

        .question {
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
            color: #fff;
            min-height: 60px; /* Soru kısa olsa bile alan kaplasın */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .options {
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
            transition: 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .option-btn:hover {
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .correct {
            background-color: #2ecc71 !important; /* Canlı Yeşil */
            color: white !important;
            box-shadow: 0 0 10px #2ecc71;
        }

        .wrong {
            background-color: #e74c3c !important; /* Canlı Kırmızı */
            color: white !important;
            box-shadow: 0 0 10px #e74c3c;
        }

        /* Sonuç Alanı */
        #resultArea {
            display: none;
            text-align: center;
            color: white;
        }

        .result-score {
            font-size: 48px;
            font-weight: bold;
            margin: 20px 0;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .action-btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 5px;
            transition: 0.3s;
        }

        .btn-retry {
            background: #fff;
            color: #333;
        }
        .btn-retry:hover { background: #f0f0f0; }

        .btn-back {
            background: rgba(255,255,255,0.3);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.5);
        }
        .btn-back:hover { background: rgba(255,255,255,0.5); }

    </style>
</head>
<body>

<div class="quiz-container">
    <div class="glass-card">
        
        <a href="view_set.php?id=<?php echo $set_id; ?>" class="close-btn">✕</a>

        <div id="quizBox">
            <div class="quiz-header">
                <h2><?php echo htmlspecialchars($set['title']); ?></h2>
                <div class="progress-text">
                    Soru <span id="qIndex">1</span> / <span id="qTotal"><?php echo count($cards); ?></span>
                </div>
            </div>

            <div class="question" id="questionText">Soru Yükleniyor...</div>

            <div class="options" id="optionsBox">
                </div>
        </div>

        <div id="resultArea">
            <h2>Test Tamamlandı!</h2>
            <div class="result-score">
                <span id="scoreVal">0</span> / <?php echo count($cards); ?>
            </div>
            <p>Doğru Cevap Sayısı</p>
            <br>
            <button class="action-btn btn-retry" onclick="location.reload()">Tekrar Çöz</button>
            <button class="action-btn btn-back" onclick="window.location.href='view_set.php?id=<?php echo $set_id; ?>'">Sete Dön</button>
        </div>

    </div>
</div>

<script>
    const cards = <?php echo json_encode($cards); ?>;
    let currentQuestion = 0;
    let score = 0;
    
    // Fisher-Yates Shuffle
    function shuffle(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array;
    }

    // Soruları karıştır
    let questions = [...cards];
    shuffle(questions);

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
        questionText.textContent = q.term;
        
        // Şıkları hazırla
        let options = [q.defination];
        
        // Yanlış cevapları havuzdan çek (kendi cevabı hariç)
        let allDefinitions = cards.map(c => c.defination).filter(d => d !== q.defination);
        
        shuffle(allDefinitions);
        options.push(...allDefinitions.slice(0, 3));
        
        // Şıkları karıştır
        shuffle(options);

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
        // Tüm butonları kilitle
        const buttons = document.querySelectorAll(".option-btn");
        buttons.forEach(b => b.disabled = true);

        if (selected === correct) {
            btn.classList.add("correct");
            score++;
        } else {
            btn.classList.add("wrong");
            // Doğru olanı göster
            buttons.forEach(b => {
                if (b.textContent === correct) b.classList.add("correct");
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

    // Başlat
    loadQuestion();
</script>

</body>
</html>