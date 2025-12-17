<?php
include "session_check.php";
include "connectDB.php";

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

// Kart kontrolü (Yazma modu için 1 kart bile yeterlidir)
if (count($cards) < 1) {
    echo '<body style="background: linear-gradient(135deg, #8EC5FC, #E0C3FC); font-family: sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;">';
    echo '<div style="background: rgba(255,255,255,0.25); backdrop-filter: blur(15px); padding:40px; border-radius:16px; text-align:center; color:white; box-shadow: 0 8px 32px rgba(0,0,0,0.15);">';
    echo '<h3>Bu mod için sette kart bulunmalıdır.</h3>';
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
    <title>Yazma Çalışması: <?php echo htmlspecialchars($set['title']); ?></title>
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
            font-size: 20px;
            font-weight: 500;
            text-align: center;
            margin-bottom: 25px;
            color: #fff;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.1);
            padding: 15px;
            border-radius: 12px;
        }

        /* Input Alanı Stilleri */
        .input-area {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .answer-input {
            width: 100%;
            padding: 15px;
            border-radius: 10px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            font-size: 18px;
            text-align: center;
            outline: none;
            box-sizing: border-box;
            transition: 0.3s;
        }

        .answer-input:focus {
            background: #fff;
            box-shadow: 0 0 15px rgba(255,255,255,0.5);
            transform: scale(1.02);
        }

        .check-btn {
            padding: 15px;
            border: none;
            border-radius: 10px;
            background: #6c5ce7;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .check-btn:hover {
            background: #5b4cc4;
            transform: translateY(-2px);
        }

        /* Geri Bildirim Alanı */
        .feedback-msg {
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            display: none; /* Başlangıçta gizli */
            animation: popIn 0.3s ease;
        }

        @keyframes popIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .feedback-correct {
            background: #2ecc71;
            color: white;
            box-shadow: 0 0 15px rgba(46, 204, 113, 0.6);
        }

        .feedback-wrong {
            background: #e74c3c;
            color: white;
            box-shadow: 0 0 15px rgba(231, 76, 60, 0.6);
        }

        .correct-answer-text {
            display: block;
            font-size: 14px;
            margin-top: 5px;
            opacity: 0.9;
            font-weight: normal;
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

        .btn-retry { background: #fff; color: #333; }
        .btn-retry:hover { background: #f0f0f0; }

        .btn-back { background: rgba(255,255,255,0.3); color: #fff; border: 1px solid rgba(255,255,255,0.5); }
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

            <div class="question" id="questionText">Yükleniyor...</div>

            <div class="input-area">
                <input type="text" id="userAnswer" class="answer-input" placeholder="Cevabı buraya yazın..." autocomplete="off">
                <button class="check-btn" id="submitBtn" onclick="checkAnswer()">Kontrol Et</button>
            </div>

            <div id="feedbackBox" class="feedback-msg"></div>
        </div>

        <div id="resultArea">
            <h2>Çalışma Tamamlandı!</h2>
            <div class="result-score">
                <span id="scoreVal">0</span> / <?php echo count($cards); ?>
            </div>
            <p>Doğru Yazılan Kelime Sayısı</p>
            <br>
            <button class="action-btn btn-retry" onclick="location.reload()">Tekrar Dene</button>
            <button class="action-btn btn-back" onclick="window.location.href='view_set.php?id=<?php echo $set_id; ?>'">Sete Dön</button>
        </div>

    </div>
</div>

<script>
    const cards = <?php echo json_encode($cards); ?>;
    let currentQuestion = 0;
    let score = 0;
    let isWaiting = false; // Cevap gösterilirken butonu kilitlemek için

    // Fisher-Yates Shuffle (Kartları karıştır)
    function shuffle(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array;
    }

    let questions = [...cards];
    shuffle(questions);

    const questionText = document.getElementById("questionText");
    const qIndexSpan = document.getElementById("qIndex");
    const quizBox = document.getElementById("quizBox");
    const resultArea = document.getElementById("resultArea");
    const scoreVal = document.getElementById("scoreVal");
    const answerInput = document.getElementById("userAnswer");
    const feedbackBox = document.getElementById("feedbackBox");
    const submitBtn = document.getElementById("submitBtn");

    // Enter tuşuna basınca kontrol et
    answerInput.addEventListener("keypress", function(event) {
        if (event.key === "Enter") {
            checkAnswer();
        }
    });

    function loadQuestion() {
        if (currentQuestion >= questions.length) {
            showResult();
            return;
        }

        const q = questions[currentQuestion];
        qIndexSpan.textContent = currentQuestion + 1;
        
        // Ekrana Tanımı (Definition) yazdırıyoruz, kullanıcı Terimi (Term) tahmin edecek.
        // Eğer tam tersini isterseniz burayı q.term yapın.
        questionText.textContent = q.defination;
        
        // Inputu temizle ve odakla
        answerInput.value = "";
        answerInput.disabled = false;
        answerInput.focus();
        
        // Geri bildirimi gizle
        feedbackBox.style.display = "none";
        feedbackBox.className = "feedback-msg"; // sınıfları sıfırla
        submitBtn.disabled = false;
        isWaiting = false;
    }

    function checkAnswer() {
        if (isWaiting) return; // Zaten cevap gösteriliyorsa bekle
        if (answerInput.value.trim() === "") return; // Boşsa işlem yapma

        isWaiting = true;
        answerInput.disabled = true;
        submitBtn.disabled = true;

        const q = questions[currentQuestion];
        const userVal = answerInput.value.trim().toLowerCase();
        const correctVal = q.term.trim().toLowerCase();

        feedbackBox.style.display = "block";

        if (userVal === correctVal) {
            // Doğru
            score++;
            feedbackBox.innerHTML = "Doğru! 🎉";
            feedbackBox.classList.add("feedback-correct");
        } else {
            // Yanlış
            feedbackBox.innerHTML = `Yanlış! <span class="correct-answer-text">Doğru cevap: <strong>${q.term}</strong></span>`;
            feedbackBox.classList.add("feedback-wrong");
        }

        // 2 saniye sonra sonraki soruya geç
        setTimeout(() => {
            currentQuestion++;
            loadQuestion();
        }, 2000);
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