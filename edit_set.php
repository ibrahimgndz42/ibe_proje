<?php
include "session_check.php";
include "connectDB.php";

// Hata raporlamayı geliştirme aşamasında açık tutalım
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['id'])) {
    echo "ID gerekli.";
    exit;
}

$set_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// ---------------------------------------------------------
// 1. SET BİLGİLERİNİ ÇEKME
// ---------------------------------------------------------
$sql = "SELECT sets.*, categories.name AS category_name 
        FROM sets 
        LEFT JOIN categories ON sets.category_id = categories.category_id 
        WHERE sets.set_id = $set_id";

$result = $conn->query($sql);

if (!$result) {
    die("Sorgu Hatası: " . $conn->error);
}

if ($result->num_rows == 0) {
    echo "Set bulunamadı.";
    exit;
}

$set = $result->fetch_assoc();

// Yetki Kontrolü
if ($set['user_id'] != $user_id) {
    echo "Bu seti düzenleme yetkiniz yok.";
    exit;
}

// ---------------------------------------------------------
// 2. KARTLARI ÇEKME
// ---------------------------------------------------------
$sql_cards = "SELECT * FROM cards WHERE set_id = $set_id";
$result_cards = $conn->query($sql_cards);
$cards = [];
while($row = $result_cards->fetch_assoc()) {
    $cards[] = $row;
}

// ---------------------------------------------------------
// 3. KATEGORİLERİ ÇEKME
// ---------------------------------------------------------
$sql_cats = "SELECT * FROM categories ORDER BY name ASC"; 
$result_cats = $conn->query($sql_cats);
$categories = [];
while($row = $result_cats->fetch_assoc()) {
    $categories[] = $row;
}

// ---------------------------------------------------------
// 4. GÜNCELLEME İŞLEMİ (POST)
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $set_title = $conn->real_escape_string($_POST["set_title"]);
    $set_desc  = $conn->real_escape_string($_POST["set_desc"]);
    $category_id = intval($_POST["category_id"]); 

    // Kart Sayısı Kontrolü
    $validCardCount = 0;
    if (isset($_POST["term"])) {
        foreach ($_POST["term"] as $key => $term_text) {
            $defination_text = $_POST["defination"][$key];
            if (trim($term_text) !== "" && trim($defination_text) !== "") {
                $validCardCount++;
            }
        }
    }

    if ($validCardCount < 2) {
        $error = "En az 2 kart eklemelisiniz!";
    } else {
        // A) Set bilgilerini güncelle
        $sql_update = "UPDATE sets SET title='$set_title', description='$set_desc', category_id='$category_id' WHERE set_id=$set_id";
        
        if ($conn->query($sql_update) === TRUE) {
            
            // B) Kartları güncelle (Önce hepsini sil, sonra temiz olanları ekle)
            $conn->query("DELETE FROM cards WHERE set_id=$set_id");

            foreach ($_POST["term"] as $key => $term_text) {
                $term_clean = $conn->real_escape_string($term_text);
                $def_clean  = $conn->real_escape_string($_POST["defination"][$key]);

                if (trim($term_clean) == "" && trim($def_clean) == "") continue;

                $sql_insert = "INSERT INTO cards (set_id, term, defination) VALUES ('$set_id', '$term_clean', '$def_clean')";
                $conn->query($sql_insert);
            }

            $success = "Set başarıyla güncellendi! Yönlendiriliyorsunuz...";
            
        } else {
            $error = "Güncelleme hatası: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Seti Düzenle</title>
<link rel="stylesheet" href="style.css">
<style>
    * { box-sizing: border-box; }

    .create-container {
        width: 100%;
        max-width: 650px;
        padding: 20px;
        margin: 40px auto;
    }

    .glass-card {
        backdrop-filter: blur(100px);
        background: rgba(255, 255, 255, 0.10);
        border-radius: 16px;
        padding: 35px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        border: 1px solid rgba(255,255,255,0.3);
        animation: fadeIn 0.6s ease;
    }

    @keyframes fadeIn {
        from {opacity:0; transform:translateY(20px);}
        to   {opacity:1; transform:translateY(0);}
    }

    h2 {
        text-align: center;
        color: #fff;
        margin-bottom: 20px;
        font-size: 30px;
    }

    textarea.auto-expand {
        overflow: hidden;
        min-height: 40px;
        resize: none;
        background: rgba(255,255,255,0.1);
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.3);
        color: #fff;
        padding: 14px;
        width: 100%;
        font-size: 15px;
        outline: none;
    }

    .input-wrapper {
        position: relative;
        margin-bottom: 25px;
    }

    .input-wrapper input,
    .input-wrapper textarea,
    .input-wrapper select {
        width: 100%;
        padding: 14px 14px;
        border: 1px solid rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.10);
        border-radius: 8px;
        font-size: 15px;
        color: #fff;
        outline: none;
        resize: none;
        overflow: hidden;
    }

    .input-wrapper label {
        position: absolute;
        top: 50%;
        left: 12px;
        transform: translateY(-50%);
        color: rgba(255,255,255,0.9);   
        pointer-events: none;
        transition: .2s ease;
    }

    /* Etiket animasyonları */
    .input-wrapper input:focus + label,
    .input-wrapper input:not(:placeholder-shown) + label,
    .input-wrapper textarea:focus + label,
    .input-wrapper textarea:not(:placeholder-shown) + label {
        top: -6px;
        font-size: 12px;
        color: #fff;
        background: transparent; 
    }

    .focus-border {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 2px;
        width: 0;
        background: #fff;
        transition: .3s;
    }

    .input-wrapper input:focus ~ .focus-border,
    .input-wrapper textarea:focus ~ .focus-border {
        width: 100%;
    }

    .card-number {
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        font-weight: bold;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 14px;
        pointer-events: none;
    }

    .card-box {
        position: relative;
        backdrop-filter: blur(25px);
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.35);
        padding: 60px 22px 22px 22px;
        border-radius: 14px;
        margin-bottom: 18px;
    }

    .custom-select {
        position: relative;
        z-index: 1000;
        cursor: pointer;
        border-radius: 12px;
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.3);
        color: #fff;
        padding: 10px 12px;
        user-select: none;
    }
    .custom-select .selected::after {
        content: "▾";
        float: right;
    }
    .custom-select ul.options {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: rgba(255,255,255,0.9); 
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255,255,255,0.4);
        color: #333; 
        border-radius: 12px;
        list-style: none;
        padding: 0;
        margin: 5px 0 0 0;
        display: none;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1001;
    }
    .custom-select ul.options li {
        padding: 10px 12px;
        transition: background 0.2s;
    }
    .custom-select ul.options li:hover {
        background: rgba(0,0,0,0.1);
    }

    /* DÜZELTME: width: 100% kaldırıldı */
    .delete-btn {
        background: #ff4d4d;
        border: none;
        padding: 10px;
        border-radius: 8px;
        color: white;
        cursor: pointer;
        margin-top: 12px;
        /* width: 100%;  <-- BU SATIR SİLİNDİ */
    }

    .add-btn, .create-btn, .cancel-btn {
        width: 100%;
        padding: 12px;
        margin-top: 10px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-size: 16px;
    }

    .add-btn { background: #fff; color:#333; }
    .create-btn { background:#fff; font-weight:bold; color: #333; margin-top: 20px;}
    .cancel-btn { background:#ff4040; color:#fff; }

    .success-msg, .error-msg {
        padding: 12px;
        border-radius: 10px;
        text-align: center;
        font-weight: 600;
        margin-bottom: 15px;
    }
    .success-msg {
        background: rgba(0,255,150,0.25);
        border: 1px solid rgba(0,255,150,0.4);
        color:#003d18;
    }
    .error-msg {
        background: rgba(255,0,0,0.25);
        border: 1px solid rgba(255,0,0,0.4);
        color:#ff1a1a;
    }
</style>
</head>

<body>
<?php include "menu.php"; ?>

<div class="create-container">
<div class="glass-card">

    <?php if(isset($success)): ?>
        <p class="success-msg"><?= $success ?></p>
        <script>
            setTimeout(()=>{ window.location.href="view_set.php?id=<?= $set_id ?>"; }, 2000);
        </script>
    <?php else: ?>

    <h2>Seti Düzenle</h2>

    <?php if(isset($error)): ?>
        <p class="error-msg"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">

        <div class="input-wrapper">
            <textarea class="auto-expand" rows="1" name="set_title" required placeholder=" "><?= htmlspecialchars($set['title']) ?></textarea>
            <label>Set Başlığı</label>
            <span class="focus-border"></span>
        </div>

        <div class="input-wrapper">
            <textarea class="auto-expand" rows="1" name="set_desc" placeholder=" "><?= htmlspecialchars($set['description']) ?></textarea>
            <label>Açıklama</label>
            <span class="focus-border"></span>
        </div>

        <div class="input-wrapper">
            <div class="custom-select" id="categorySelect">
                <div class="selected"><?= htmlspecialchars($set['category_name'] ?? 'Kategori Seçiniz') ?></div>
                <ul class="options">
                    <?php foreach($categories as $cat): ?>
                        <li data-value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <input type="hidden" name="category_id" id="hiddenCategory" value="<?= htmlspecialchars($set['category_id']) ?>">
        </div>

        <h3 style="color:white; text-align:center;">Kartlar</h3>

        <div id="cardsContainer">
            <?php foreach($cards as $index => $card): ?>
            <div class="card-box">
                <div class="input-wrapper">
                    <input type="text" name="term[]" value="<?= htmlspecialchars($card['term']) ?>" placeholder=" " required>
                    <label>Ön Yüz</label>
                    <span class="focus-border"></span>
                </div>

                <div class="input-wrapper">
                    <input type="text" name="defination[]" value="<?= htmlspecialchars($card['defination']) ?>" placeholder=" " required>
                    <label>Arka Yüz</label>
                    <span class="focus-border"></span>
                </div>

                <button type="button" class="delete-btn">Kartı Sil</button>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="add-btn" onclick="addCard()">+ Kart Ekle</button>
        <button type="submit" class="create-btn">Güncelle</button>
        <button type="button" class="cancel-btn" onclick="window.location.href='view_set.php?id=<?= $set_id ?>'">İptal</button>

    </form>
    <?php endif; ?>

</div></div>

<script>
// --- Dropdown Mantığı ---
const categorySelect = document.getElementById("categorySelect");
if (categorySelect) {
    const selected = categorySelect.querySelector(".selected");
    const optionsContainer = categorySelect.querySelector(".options");
    const hiddenInput = document.getElementById("hiddenCategory");

    selected.addEventListener("click", () => {
        optionsContainer.style.display = optionsContainer.style.display === "block" ? "none" : "block";
    });

    optionsContainer.querySelectorAll("li").forEach(option => {
        option.addEventListener("click", () => {
            selected.textContent = option.textContent; 
            hiddenInput.value = option.dataset.value;  
            optionsContainer.style.display = "none";
        });
    });

    document.addEventListener("click", (e) => {
        if (!categorySelect.contains(e.target)) {
            optionsContainer.style.display = "none";
        }
    });
}

// --- Textarea Otomatik Genişleme ---
function autoExpandTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}
document.querySelectorAll('textarea.auto-expand').forEach(textarea => {
    autoExpandTextarea(textarea);
    textarea.addEventListener('input', () => autoExpandTextarea(textarea));
});

// --- Kart Numaralandırma ve Ekleme ---
function updateCardNumbers() {
    const cards = document.querySelectorAll("#cardsContainer .card-box");
    cards.forEach((card, index) => {
        let numberLabel = card.querySelector(".card-number");
        if (!numberLabel) {
            numberLabel = document.createElement("div");
            numberLabel.className = "card-number";
            card.appendChild(numberLabel);
        }
        numberLabel.textContent = (index + 1) + ". Kart";
    });
}

function addCard() {
    const container = document.getElementById("cardsContainer");
    const box = document.createElement("div");
    box.className = "card-box";
    box.innerHTML = `
        <div class="input-wrapper">
            <input type="text" name="term[]" placeholder=" " required>
            <label>Ön Yüz</label>
            <span class="focus-border"></span>
        </div>
        <div class="input-wrapper">
            <input type="text" name="defination[]" placeholder=" " required>
            <label>Arka Yüz</label>
            <span class="focus-border"></span>
        </div>
        <button type="button" class="delete-btn">Kartı Sil</button>
    `;
    container.appendChild(box);
    
    // Yeni eklenen silme butonuna olay dinleyicisi ekle
    box.querySelector(".delete-btn").addEventListener("click", function() {
        box.remove();
        checkDeletes();
        updateCardNumbers();
    });

    checkDeletes();
    updateCardNumbers();
}

// Mevcut (veritabanından gelen) kartların silme butonları için dinleyici
function attachDeleteListeners() {
    const boxes = document.querySelectorAll("#cardsContainer .card-box");
    boxes.forEach(box => {
        const btn = box.querySelector(".delete-btn");
        if(btn){
            btn.onclick = function() {
                 box.remove();
                 checkDeletes();
                 updateCardNumbers();
            };
        }
    });
}

// En az 2 kart kalması kuralı (Sil butonlarını gizler)
function checkDeletes() {
    const boxes = document.querySelectorAll(".card-box");
    const delBtns = document.querySelectorAll(".delete-btn");
    if(boxes.length <= 2) {
        delBtns.forEach(btn => btn.style.display = "none");
    } else {
        delBtns.forEach(btn => btn.style.display = "block"); // Burada block olması, inline gibi davranmasını engellemez, genişlik kalktığı için sorun yok.
    }
}

// Sayfa Yüklendiğinde Başlat
attachDeleteListeners();
updateCardNumbers();
checkDeletes();

</script>

</body>
</html>