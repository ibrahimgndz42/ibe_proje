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
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yazma Çalışması: <?php echo htmlspecialchars($set['title']); ?></title>
    <link rel="stylesheet" href="style.css">

    <style>
        /* --- SAYFA DÜZENİ (Menü + Ortalanmış İçerik) --- */
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
            background: rgba(255, 255, 255, 0.35); /* Biraz daha opak */
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.4);
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
            background: rgba(255, 255, 255, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.6);
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            color: #333; font-size: 16px; text-decoration: none;
            transition: 0.2s ease;
        }
        .close-btn:hover { background: #ff7675; color: white; border-color: #ff7675; }

        /* Başlıklar */
        .quiz-header { text-align: center; margin-bottom: 30px; }
        .quiz-header h2 { margin: 0 0 5px 0; font-size: 24px; color: #2d3436; }
        
        .progress-text {
            color: #636e72;
            font-size: 14px;
            font-weight: 600;
            background: rgba(255,255,255,0.5);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }

        /* Soru Alanı */
        .question {
            font-size: 22px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
            color: #2d3436;
            min-height: 60px;
            display: flex; align-items: center; justify-content: center;
            /* Soru alanı vurgusu */
            padding: 20px;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }

        /* Input Alanı */
        .input-area { display: flex; flex-direction: column; gap: 15px; }

        .answer-input {
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.8);
            color: #333;
            font-size: 18px;
            text-align: center;
            outline: none;
            transition: 0.3s;
            box-sizing: border-box;
            font-family: inherit;
        }
        .answer-input:focus {
            background: #fff;
            border-color: #6c5ce7;
            box-shadow: 0 0 0 4px rgba(108, 92, 231, 0.1);
        }

        .check-btn {
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: #2d3436; /* Koyu tema butonu */
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .check-btn:hover { background: #000; transform: translateY(-2px); }

        /* Geri Bildirim */
        .feedback-msg {
            margin-top: 20px;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            font-weight: bold;
            display: none;
            animation: popIn 0.3s ease;
        }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .feedback-correct { background: #00b894; color: white; }
        .feedback-wrong { background: #ff7675; color: white; }

        .correct-answer-text { display: block; font-size: 14px; margin-top: 5px; opacity: 0.9; font-weight: normal; }

        /* Sonuç Ekranı */
        #resultArea { display: none; text-align: center; color: #333; }
        .result-score { font-size: 48px; font-weight: 800; margin: 20px 0; color: #2d3436; }
        .action-btn { padding: 12px 24px; border-radius: 8px; border: none; font-size: 16px; font-weight: bold; cursor: pointer; margin: 5px; transition: 0.3s; }
        .btn-retry { background: #6c5ce7; color: white; }
        .btn-retry:hover { background: #5a4db8; }
        .btn-back { background: rgba(0,0,0,0.1); color: #333; }
        .btn-back:hover { background: rgba(0,0,0,0.2); }

        /* Hata Mesajı (Set boşsa) */
        .empty-set-msg { text-align: center; color: #444; }
        .empty-set-msg h3 { margin-bottom: 20px; }
        .empty-set-msg a { color: #6c5ce7; font-weight: bold; text-decoration: none; }

    </style>
</head>
<body>

<?php include "menu.php"; ?>

<div class="main-wrapper">
    <div class="quiz-container">
        
        <?php if (count($cards) < 1): ?>
            <div class="glass-card empty-set-msg">
                <h3>⚠️ Bu mod için sette en az 1 kart bulunmalıdır.</h3>
                <a href="view_set.php?id=<?php echo $set_id; ?>">Sete Geri Dön</a>
            </div>
        <?php else: ?>
            <div class="glass-card">
                
                <a href="view_set.php?id=<?php echo $set_id; ?>" class="close-btn" title="Çıkış">✕</a>

                <div id="quizBox">
                    <div class="quiz-header">
                        <h2><?php echo htmlspecialchars($set['title']); ?></h2>
                        <div class="progress-text">
                            Soru <span id="qIndex">1</span> / <span id="qTotal"><?php echo count($cards); ?></span>
                        </div>
                    </div>

                    <div class="question" id="questionText">Yükleniyor...</div>

                    <div class="input-area">
                        <input type="text" id="userAnswer" class="answer-input" placeholder="Anlamını buraya yazın..." autocomplete="off">
                        <button class="check-btn" id="submitBtn" onclick="checkAnswer()">Kontrol Et</button>
                    </div>

                    <div id="feedbackBox" class="feedback-msg"></div>
                </div>

                <div id="resultArea">
                    <h2>🎉 Çalışma Tamamlandı!</h2>
                    <div class="result-score">
                        <span id="scoreVal">0</span> / <?php echo count($cards); ?>
                    </div>
                    <p style="color:#666; margin-bottom:30px;">Doğru Yazılan Kelime Sayısı</p>
                    
                    <button class="action-btn btn-retry" onclick="location.reload()">Tekrar Dene</button>
                    <button class="action-btn btn-back" onclick="window.location.href='view_set.php?id=<?php echo $set_id; ?>'">Sete Dön</button>
                </div>

            </div>
        <?php endif; ?>

    </div>
</div>

<?php if (count($cards) > 0): ?>
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

    // Soruları karıştırıyoruz
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
        
        // Veritabanında Tanım (defination) göster, kullanıcı Terimi (term) bilsin.
        // Tam tersini istersen: questionText.textContent = q.term;
        questionText.textContent = q.defination;
        
        // Inputu temizle ve odakla
        answerInput.value = "";
        answerInput.disabled = false;
        answerInput.focus();
        
        // Geri bildirimi gizle
        feedbackBox.style.display = "none";
        feedbackBox.className = "feedback-msg"; 
        submitBtn.disabled = false;
        isWaiting = false;
    }

    function checkAnswer() {
        if (isWaiting) return; 
        if (answerInput.value.trim() === "") return; // Boşsa işlem yapma

        isWaiting = true;
        answerInput.disabled = true;
        submitBtn.disabled = true;

        const q = questions[currentQuestion];
        
        // DÜZELTME: Türkçe karakter uyumlu küçük harfe çevirme
        const userVal = answerInput.value.trim().toLocaleLowerCase('tr-TR');
        const correctVal = q.term.trim().toLocaleLowerCase('tr-TR');

        feedbackBox.style.display = "block";

        if (userVal === correctVal) {
            // Doğru
            score++;
            feedbackBox.innerHTML = '<i class="fa-solid fa-check"></i> Doğru!';
            feedbackBox.classList.add("feedback-correct");
        } else {
            // Yanlış
            feedbackBox.innerHTML = `<i class="fa-solid fa-xmark"></i> Yanlış! <span class="correct-answer-text">Doğru cevap: <strong>${q.term}</strong></span>`;
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
<?php endif; ?>

</body>
</html>