<?php
include "session_check.php";
include "connectDB.php";

// 1. URL'den Set ID'yi al ve Güvenlik Kontrolü Yap
if (!isset($_GET['set_id']) || empty($_GET['set_id'])) {
    die("Geçersiz Set ID.");
}

$set_id = intval($_GET['set_id']);
$user_id = $_SESSION['user_id'];

// 2. Setin bu kullanıcıya ait olup olmadığını kontrol et ve bilgileri çek
$sql_set = "SELECT * FROM sets WHERE set_id = ? AND user_id = ?";
$stmt_set = $conn->prepare($sql_set);
$stmt_set->bind_param("ii", $set_id, $user_id);
$stmt_set->execute();
$result_set = $stmt_set->get_result();

if ($result_set->num_rows == 0) {
    die("Set bulunamadı veya bu seti düzenleme yetkiniz yok.");
}

$current_set = $result_set->fetch_assoc();

// 3. Set'e ait kartları çek
$sql_cards = "SELECT * FROM cards WHERE set_id = ? ORDER BY card_id ASC";
$stmt_cards = $conn->prepare($sql_cards);
$stmt_cards->bind_param("i", $set_id);
$stmt_cards->execute();
$result_cards = $stmt_cards->get_result();

$cards = [];
while ($row = $result_cards->fetch_assoc()) {
    $cards[] = $row;
}

// Temaları ve Kategorileri Çek
$sql_themes = "SELECT * FROM themes ORDER BY theme_id ASC";
$result_themes = $conn->query($sql_themes);

$sql_cats = "SELECT * FROM categories ORDER BY category_id ASC";
$result_cats = $conn->query($sql_cats);


// --- FORM GÖNDERİLDİĞİNDE (GÜNCELLEME İŞLEMİ) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $set_title = trim($_POST["set_title"]);
    $set_desc  = trim($_POST["set_desc"]);
    $set_category = $_POST["set_category"];
    $set_theme_id = isset($_POST["set_theme_id"]) ? $_POST["set_theme_id"] : 1;

    // Hata Kontrolleri
    if (empty($set_title)) {
        $error = "Lütfen set başlığı giriniz.";
    } elseif (empty($set_category)) {
        $error = "Lütfen bir kategori seçiniz!";
    } else {
        // En az 2 kart kontrolü
        $validCardCount = 0;
        if (isset($_POST["term"])) {
            foreach ($_POST["term"] as $key => $term) {
                if (trim($term) !== "" && trim($_POST["defination"][$key]) !== "") {
                    $validCardCount++;
                }
            }
        }

        if ($validCardCount < 2) {
            $error = "En az 2 dolu kart olmalıdır!";
        } else {
            // A) SET BİLGİLERİNİ GÜNCELLE
            $stmt_update = $conn->prepare("UPDATE sets SET title=?, description=?, category_id=?, theme_id=? WHERE set_id=? AND user_id=?");
            $stmt_update->bind_param("ssiiii", $set_title, $set_desc, $set_category, $set_theme_id, $set_id, $user_id);
            
            if ($stmt_update->execute()) {
                
                // B) KART İŞLEMLERİ
                $submitted_card_ids = [];
                
                if (isset($_POST["term"])) {
                    $stmt_insert_card = $conn->prepare("INSERT INTO cards (set_id, term, defination) VALUES (?, ?, ?)");
                    $stmt_update_card = $conn->prepare("UPDATE cards SET term=?, defination=? WHERE card_id=? AND set_id=?");

                    foreach ($_POST["term"] as $key => $term) {
                        $def = $_POST["defination"][$key];
                        $card_id = isset($_POST["card_id"][$key]) ? $_POST["card_id"][$key] : null;

                        if (trim($term) !== "" && trim($def) !== "") {
                            if (!empty($card_id)) {
                                // GÜNCELLE
                                $stmt_update_card->bind_param("ssii", $term, $def, $card_id, $set_id);
                                $stmt_update_card->execute();
                                $submitted_card_ids[] = $card_id; 
                            } else {
                                // EKLE
                                $stmt_insert_card->bind_param("iss", $set_id, $term, $def);
                                $stmt_insert_card->execute();
                            }
                        }
                    }
                }

                // C) SİLİNEN KARTLARI KALDIR
                $db_card_ids = [];
                foreach ($cards as $c) { $db_card_ids[] = $c['card_id']; }
                $ids_to_delete = array_diff($db_card_ids, $submitted_card_ids);

                if (!empty($ids_to_delete)) {
                    $ids_string = implode(",", $ids_to_delete);
                    $conn->query("DELETE FROM cards WHERE card_id IN ($ids_string) AND set_id = $set_id");
                }

                $success = "Set başarıyla güncellendi! Yönlendiriliyorsunuz...";
            } else {
                $error = "Veritabanı güncelleme hatası: " . $conn->error;
            }
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* --- CSS: AYDINLIK CAM EFEKTİ (Light Glass) --- */
    
    * { box-sizing: border-box; }

    .create-container {
        width: 100%;
        max-width: 650px;
        padding: 20px;
        margin: 40px auto; 
    }

    /* Kart Arka Planı */
    .glass-card { 
        backdrop-filter: blur(15px);
        background: rgba(255, 255, 255, 0.2); /* Açık Beyaz */
        border-radius: 16px;
        padding: 35px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        border: 1px solid rgba(255,255,255,0.4);
        animation: fadeIn 0.6s ease;
        color: #333; /* Koyu Yazı */
    }

    @keyframes fadeIn {
        from {opacity:0; transform:translateY(20px);}
        to   {opacity:1; transform:translateY(0);}
    }

    h2 {
        text-align: center;
        color: #000;
        margin-bottom: 20px;
        font-size: 30px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Inputlar */
    textarea.auto-expand, .input-wrapper input, .input-wrapper select {
        width: 100%;
        padding: 14px 14px;
        border: 1px solid rgba(255,255,255,0.4);
        background: rgba(255,255,255,0.4); /* Yarı saydam beyaz */
        border-radius: 8px;
        font-size: 15px;
        color: #333; /* Koyu yazı */
        outline: none;
        resize: none;
        font-weight: 500;
        transition: 0.3s;
    }

    textarea.auto-expand:focus, .input-wrapper input:focus, .input-wrapper select:focus {
        background: rgba(255,255,255,0.6);
        border-color: #fff;
        box-shadow: 0 0 10px rgba(255,255,255,0.2);
    }

    .input-wrapper { position: relative; margin-bottom: 25px; }

    .input-wrapper label {
        position: absolute; top: 50%; left: 12px; transform: translateY(-50%);
        color: #555; pointer-events: none; transition: .2s ease; font-weight: 500;
    }

    .input-wrapper input:focus + label, .input-wrapper input:not(:placeholder-shown) + label,
    .input-wrapper textarea:focus + label, .input-wrapper textarea:not(:placeholder-shown) + label {
        top: -8px; font-size: 12px; color: #333; font-weight: bold; background: transparent;
    }

    .focus-border { position: absolute; bottom: 0; left: 0; height: 2px; width: 0; background: #333; transition: .3s; }
    .input-wrapper input:focus ~ .focus-border, .input-wrapper textarea:focus ~ .focus-border { width: 100%; }
    
    /* Kart Kutuları (Flashcards) */
    .card-box {
        position: relative; 
        backdrop-filter: blur(25px); 
        background: rgba(255,255,255,0.3); /* Biraz daha belirgin */
        border: 1px solid rgba(255,255,255,0.5); 
        padding: 60px 22px 22px 22px;
        border-radius: 14px; 
        margin-bottom: 18px; 
        transition: background 0.5s ease, border-color 0.5s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .card-number {
        position: absolute; top: 10px; left: 10px; 
        background: rgba(0, 0, 0, 0.1); /* Koyu numara arka planı */
        color: #333; font-weight: bold; padding: 4px 8px; border-radius: 12px; font-size: 14px;
    }

    /* Tema Seçici */
    .theme-selector-wrapper { 
        margin-bottom: 25px; 
        background: rgba(255, 255, 255, 0.3); 
        padding: 15px; border-radius: 12px; 
        border: 1px solid rgba(255, 255, 255, 0.4); 
        text-align: center; 
    }
    .theme-label { color: #222; display: block; margin-bottom: 10px; font-weight: 600; }
    .theme-options { display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; }
    .theme-options input[type="radio"] { display: none; }
    
    .theme-circle { 
        width: 45px; height: 45px; border-radius: 50%; cursor: pointer; 
        border: 3px solid rgba(255, 255, 255, 0.6); 
        position: relative; transition: transform 0.2s, box-shadow 0.2s; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
    }
    .theme-circle:hover { transform: translateY(-3px); }
    
    .theme-options input[type="radio"]:checked + .theme-circle { 
        border-color: #333; transform: scale(1.15); box-shadow: 0 0 15px rgba(255, 255, 255, 0.8); 
    }
    .theme-options input[type="radio"]:checked + .theme-circle::after { 
        content: '✓'; color: #fff; font-size: 24px; font-weight: bold; 
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
        text-shadow: 0 1px 2px rgba(0,0,0,0.5); 
    }
    
    /* Kategori Dropdown */
    .custom-select { 
        position: relative; z-index: 1000; cursor: pointer; border-radius: 8px; 
        background: rgba(255,255,255,0.4); border: 1px solid rgba(255,255,255,0.4); 
        color: #333; padding: 14px 14px; font-weight: 500;
    }
    .custom-select ul.options { 
        position: absolute; top: 100%; left: 0; right: 0; 
        background: rgba(255,255,255,0.95); border-radius: 8px; 
        list-style: none; padding: 0; margin: 5px 0 0 0; display: none; 
        max-height: 200px; overflow-y: auto; color: #333; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border: 1px solid rgba(0,0,0,0.1);
    }
    .custom-select ul.options li { padding: 10px; border-bottom: 1px solid #eee; }
    .custom-select ul.options li:hover { background: #f0f0f0; }

    /* Butonlar */
    .delete-btn { background: #ff4d4d; border: none; padding: 8px 12px; border-radius: 8px; color: white; cursor: pointer; margin-top: 10px; float: bottom; font-size: 13px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    
    .add-btn { 
        width: 100%; padding: 12px; margin-top: 10px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; 
        background: #fff; color:#333; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: 0.3s;
    }
    .add-btn:hover { background: #f0f0f0; transform: scale(1.01); }

    .create-btn { 
        width: 100%; padding: 12px; margin-top: 10px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; 
        background:#333; font-weight:bold; color: #fff; transition: 0.3s;
    }
    .create-btn:hover { background: #000; transform: scale(1.01); }
    
    .cancel-btn { 
        width: 100%; padding: 12px; margin-top: 10px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; 
        background:#DA3A3A; color:#fff; font-weight: bold; 
    }

    .success-msg { background: rgba(80, 255, 120, 0.2); border: 1px solid #50ff78; color: #006400; padding: 12px; border-radius: 10px; text-align:center; font-weight: bold; }
    .error-msg { background: rgba(255, 80, 80, 0.2); border: 1px solid #fa3939; color: #8b0000; padding: 12px; border-radius: 10px; text-align:center; font-weight: bold; }

    h3 { color: #333 !important; text-align: center; } 
</style>
</head>
<body>

<?php include "menu.php"; ?>

<div class="create-container">
    <div class="glass-card">

    <?php if(!isset($success)): ?>
        <h2>Seti Düzenle</h2>
    <?php endif; ?>

    <?php if(isset($success)): ?>
        <p class="success-msg"><?= $success ?></p>
        <script>
            setTimeout(() => { 
                // set_id değişkenini PHP'den JS'ye aktarıyoruz
                window.location.href = "view_set.php?id=<?= $set_id ?>"; 
            }, 2000);
        </script>

    <?php elseif(isset($error)): ?>
        <p class="error-msg"><?= $error ?></p>
    <?php endif; ?>

    <?php if(!isset($success)): ?>
    <form method="POST">

        <div class="input-wrapper">
            <textarea class="auto-expand" rows="1" name="set_title" required placeholder=" "><?= htmlspecialchars($current_set['title']) ?></textarea>
            <label>Set Başlığı</label>
            <span class="focus-border"></span>
        </div>

        <div class="input-wrapper">
            <textarea class="auto-expand" rows="1" name="set_desc" placeholder=" "><?= htmlspecialchars($current_set['description']) ?></textarea>
            <label>Açıklama (İsteğe bağlı)</label>
            <span class="focus-border"></span>
        </div>

        <?php 
            // Mevcut kategori ismini bulmak için
            $current_cat_name = "Kategori Seçiniz";
            // İmleci başa sar
            $result_cats->data_seek(0); 
            while($c = $result_cats->fetch_assoc()) {
                if($c['category_id'] == $current_set['category_id']){
                    $current_cat_name = $c['name'];
                    break;
                }
            }
            // Tekrar döngü için başa sar
            $result_cats->data_seek(0);
        ?>

        <div class="input-wrapper">
            <div class="custom-select" id="categorySelect">
                <div class="selected"><?= htmlspecialchars($current_cat_name) ?></div>
                <ul class="options">
                    <?php if ($result_cats->num_rows > 0): ?>
                        <?php while($cat = $result_cats->fetch_assoc()): ?>
                            <li data-value="<?php echo $cat['category_id']; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </li>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <input type="hidden" name="set_category" id="hiddenCategory" value="<?= $current_set['category_id'] ?>" required>
        </div>

        <div class="theme-selector-wrapper">
            <span class="theme-label">Kart Teması Seçin:</span>
            <div class="theme-options">
                <?php 
                if ($result_themes->num_rows > 0):
                    while($theme = $result_themes->fetch_assoc()): 
                        $isChecked = ($theme['theme_id'] == $current_set['theme_id']) ? "checked" : "";
                ?>
                    <label>
                        <input type="radio" 
                            name="set_theme_id" 
                            value="<?php echo $theme['theme_id']; ?>" 
                            data-css="<?php echo $theme['css_class']; ?>"
                            onclick="changePreview(this)"
                            <?php echo $isChecked; ?>
                        >
                        <div class="theme-circle" 
                            title="<?php echo $theme['theme_name']; ?>"
                            style="background: <?php echo $theme['preview_color']; ?>;">
                        </div>
                    </label>
                <?php endwhile; endif; ?>
            </div>
        </div>

        <h3>Kartlar</h3>

        <div id="cardsContainer">
            <?php foreach($cards as $index => $card): ?>
            <div class="card-box">
                <input type="hidden" name="card_id[]" value="<?= $card['card_id'] ?>">

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
                <button type="button" class="delete-btn">Sil</button>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="add-btn" onclick="addCard()">+ Kart Ekle</button>
        <button type="submit" class="create-btn">Değişiklikleri Kaydet</button>
        <button type="button" class="cancel-btn" onclick="window.history.back()">İptal</button>
    </form>
    <?php endif; ?>
    </div>
</div>

<script>
    // --- JS KISMI ---

    // Kategori Seçimi
    const categorySelect = document.getElementById("categorySelect");
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

    // Tema Önizleme
    function changePreview(element) {
        const cssClass = element.getAttribute('data-css');
        const cards = document.querySelectorAll('.card-box');
        
        cards.forEach(card => {
            card.classList.forEach(cls => {
                if (cls.startsWith('bg-')) {
                    card.classList.remove(cls);
                }
            });
            card.classList.add(cssClass);
        });
    }

    // Sayfa Yüklendiğinde Tema ve Kart Numaraları
    window.onload = function() {
        const checkedInput = document.querySelector('input[name="set_theme_id"]:checked');
        if(checkedInput) {
            changePreview(checkedInput);
        }
        updateCardNumbers();
        checkDeletes();
    };

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

    // Kart Ekleme
    function addCard() {
        const container = document.getElementById("cardsContainer");
        const box = document.createElement("div");
        box.className = "card-box";

        const checkedInput = document.querySelector('input[name="set_theme_id"]:checked');
        if(checkedInput) {
             const currentTheme = checkedInput.getAttribute('data-css');
             box.classList.add(currentTheme);
        }

        box.innerHTML = `
            <input type="hidden" name="card_id[]" value="">

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
            <button type="button" class="delete-btn">Sil</button>
        `;

        container.appendChild(box);
        
        const delBtn = box.querySelector(".delete-btn");
        delBtn.addEventListener("click", function() {
            box.remove();
            checkDeletes();
            updateCardNumbers();
        });

        checkDeletes();
        updateCardNumbers();
    }

    // Mevcut "Sil" butonlarına event listener ekle
    function attachDeleteListeners() {
        const boxes = document.querySelectorAll("#cardsContainer .card-box");
        boxes.forEach(box => {
            const btn = box.querySelector(".delete-btn");
            if (!btn.dataset.listener) {
                btn.addEventListener("click", function() {
                    box.remove();
                    checkDeletes();
                    updateCardNumbers();
                });
                btn.dataset.listener = true;
            }
        });
    }

    function checkDeletes() {
        const boxes = document.querySelectorAll(".card-box");
        const delBtns = document.querySelectorAll(".delete-btn");
        delBtns.forEach(btn => btn.style.display = boxes.length > 2 ? "block" : "none");
    }

    attachDeleteListeners();
</script>

</body>
</html>