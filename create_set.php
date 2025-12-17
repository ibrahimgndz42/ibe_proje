<?php
include "session_check.php";
include "connectDB.php";

// Temaları veritabanından çek
$sql_themes = "SELECT * FROM themes ORDER BY theme_id ASC";
$result_themes = $conn->query($sql_themes);

// Kategorileri çek
$sql_cats = "SELECT * FROM categories ORDER BY category_id ASC";
$result_cats = $conn->query($sql_cats);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $set_title = trim($_POST["set_title"]);
    $set_desc  = trim($_POST["set_desc"]);
    $set_category = $_POST["set_category"];
    // Tema ID'sini al (Seçilmezse varsayılan 1 olsun veya kontrol et)
    $set_theme_id = isset($_POST["set_theme_id"]) ? $_POST["set_theme_id"] : 1; 
    $user_id   = $_SESSION["user_id"];

    // HATA KONTROLLERİ
    if (empty($set_title)) {
        $error = "Lütfen set başlığı giriniz.";
    } 
    elseif (empty($set_category)) {
        $error = "Lütfen bir kategori seçiniz!";
    } 
    else {
        // Kart Kontrolü
        $validCardCount = 0;
        if (isset($_POST["term"])) {
            foreach ($_POST["term"] as $key => $term) {
                $def = $_POST["defination"][$key];
                if (trim($term) !== "" && trim($def) !== "") {
                    $validCardCount++;
                }
            }
        }

        if ($validCardCount < 2) {
            $error = "En az 2 dolu kart eklemelisiniz!";
        } else {
            // Sets tablosuna kaydet (theme yerine theme_id kaydediyoruz)
            // Tablo yapında 'theme' sütunu VARCHAR ise adını, INT ise ID'sini kaydetmelisin.
            // Ben burada ID kaydettiğini varsayıyorum. 
            // Eğer ismini kaydedeceksen $_POST['theme_name'] gibi bir hidden input daha gerekebilir
            // veya ID kaydedip ilişkisel tablo kullanmak en doğrusudur.
            
            $stmt = $conn->prepare("INSERT INTO sets (user_id, title, description, category_id, theme_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issii", $user_id, $set_title, $set_desc, $set_category, $set_theme_id);
            
            if ($stmt->execute()) {
                $set_id = $conn->insert_id;

                // Kartları Ekle
                $stmt_card = $conn->prepare("INSERT INTO cards (set_id, term, defination) VALUES (?, ?, ?)");
                
                foreach ($_POST["term"] as $key => $term) {
                    $def = $_POST["defination"][$key];
                    if (trim($term) !== "" && trim($def) !== "") {
                        $stmt_card->bind_param("iss", $set_id, $term, $def);
                        $stmt_card->execute();
                    }
                }
                $success = "Set başarıyla oluşturuldu! Yönlendiriliyorsunuz...";
            } else {
                $error = "Veritabanı Hatası: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Set Oluştur</title>

<link rel="stylesheet" href="style.css">

<style>
    /* style.css içinde body arka planı tanımlı olsa bile, 
       bu sayfada SABİT kalmasını istediğimiz için override ediyoruz.
    */
    body {
        margin: 0;
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #8EC5FC, #E0C3FC) !important; /* Sabit Mavi */
        min-height: 100vh;
    }
    
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

    /* Input stilleri */
    textarea.auto-expand, .input-wrapper input, .input-wrapper select {
        width: 100%;
        padding: 14px 14px;
        border: 1px solid rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.15); /* Biraz daha şeffaf yaptım ki tema rengi belli olsun */
        border-radius: 8px;
        font-size: 15px;
        color: #fff;
        outline: none;
        resize: none;
    }

    .input-wrapper {
        position: relative;
        margin-bottom: 25px;
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

    .input-wrapper input:focus + label,
    .input-wrapper input:not(:placeholder-shown) + label,
    .input-wrapper textarea:focus + label,
    .input-wrapper textarea:not(:placeholder-shown) + label {
        top: -6px;
        font-size: 12px;
        color: #fff;
    }

    .focus-border {
        position: absolute; bottom: 0; left: 0; height: 2px; width: 0; background: #fff; transition: .3s;
    }
    .input-wrapper input:focus ~ .focus-border,
    .input-wrapper textarea:focus ~ .focus-border { width: 100%; }

    /* --- KART KUTUSU (Tema buraya uygulanacak) --- */
    .card-box {
        position: relative;
        /* Varsayılan stil (backdrop-filter korunuyor) */
        backdrop-filter: blur(25px);
        background: rgba(255,255,255,0.05); /* Default */
        border: 1px solid rgba(255,255,255,0.35);
        padding: 60px 22px 22px 22px;
        border-radius: 14px;
        margin-bottom: 18px;
        transition: background 0.5s ease, border-color 0.5s ease; /* Renk geçişi yumuşak olsun */
    }

    .card-number {
        position: absolute; top: 10px; left: 10px;
        background: rgba(255, 255, 255, 0.2);
        color: #fff; font-weight: bold; padding: 4px 8px; border-radius: 12px; font-size: 14px;
    }

    /* --- TEMA SEÇİCİ TASARIMI (CSS GÜNCELLEMESİ) --- */
    .theme-selector-wrapper {
        margin-bottom: 25px;
        background: rgba(255, 255, 255, 0.15);
        padding: 15px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        text-align: center;
    }

    .theme-label {
        color: #fff; display: block; margin-bottom: 10px; font-weight: 600;
    }

    .theme-options {
        display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;
    }
    
    .theme-options input[type="radio"] { display: none; }

    /* Yuvarlak Butonlar */
    .theme-circle {
        width: 45px; height: 45px;
        border-radius: 50%;
        cursor: pointer;
        border: 3px solid rgba(255, 255, 255, 0.4); /* Hafif beyaz çerçeve */
        position: relative;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .theme-circle:hover { transform: translateY(-3px); }

    /* SEÇİLİ OLDUĞUNDA (Tik İşareti Geliyor) */
    .theme-options input[type="radio"]:checked + .theme-circle {
        border-color: #fff;
        transform: scale(1.15);
        box-shadow: 0 0 15px rgba(255, 255, 255, 0.5); /* Parlama */
    }

    /* Tik İşareti (Pseudo-element) */
    .theme-options input[type="radio"]:checked + .theme-circle::after {
        content: '✓';
        color: #fff;
        font-size: 24px;
        font-weight: bold;
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }

    /* CUSTOM SELECT */
    .custom-select {
        position: relative; z-index: 1000; cursor: pointer;
        border-radius: 12px; background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.3); color: #fff; padding: 10px 12px;
    }
    .custom-select ul.options {
        position: absolute; top: 100%; left: 0; right: 0;
        background: rgba(255,255,255,0.95); border-radius: 12px;
        list-style: none; padding: 0; margin: 5px 0 0 0; display: none;
        max-height: 200px; overflow-y: auto; color: #333;
    }
    .custom-select ul.options li { padding: 10px; border-bottom: 1px solid #eee; }
    .custom-select ul.options li:hover { background: #dceefc; }

    /* BUTTONS */
    .delete-btn { background: #ff4d4d; border: none; padding: 8px 12px; border-radius: 8px; color: white; cursor: pointer; margin-top: 10px; float: right; font-size: 13px;}
    .add-btn { width: 100%; padding: 12px; margin-top: 10px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; background: #fff; color:#333; }
    .create-btn { width: 100%; padding: 12px; margin-top: 10px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; background:#fff; font-weight:bold; color: #7b68ee; }
    .cancel-btn { width: 100%; padding: 12px; margin-top: 10px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; background:#ff4040; color:#fff; }

    .success-msg { background: rgba(0,255,150,0.25); border: 1px solid rgba(0,255,150,0.4); color:#003d18; padding: 12px; border-radius: 10px; text-align:center; }
    .error-msg { background: rgba(255,0,0,0.25); border: 1px solid rgba(255,0,0,0.4); color: #ffcccc; padding: 12px; border-radius: 10px; text-align:center; }
</style>
</head>

<body>

<?php include "menu.php"; ?>

<div class="create-container">
    <div class="glass-card">

    <?php if(!isset($success)): ?>
        <h2>Yeni Set Oluştur</h2>
    <?php endif; ?>

    <?php if(isset($success)): ?>
        <p class="success-msg"><?= $success ?></p>
        <script>setTimeout(()=>{ window.location.href="my_sets.php"; }, 2000);</script>
    <?php elseif(isset($error)): ?>
        <p class="error-msg"><?= $error ?></p>
    <?php endif; ?>

    <?php if(!isset($success)): ?>
    <form method="POST">

        <div class="input-wrapper">
            <textarea class="auto-expand" rows="1" name="set_title" required placeholder=" "><?php echo isset($_POST['set_title']) ? htmlspecialchars($_POST['set_title']) : ''; ?></textarea>
            <label>Set Başlığı</label>
            <span class="focus-border"></span>
        </div>

        <div class="input-wrapper">
            <textarea class="auto-expand" rows="1" name="set_desc" rows="1" placeholder=" "><?php echo isset($_POST['set_desc']) ? htmlspecialchars($_POST['set_desc']) : ''; ?></textarea>
            <label>Açıklama (İsteğe bağlı)</label>
            <span class="focus-border"></span>
        </div>

        <div class="input-wrapper">
            <div class="custom-select" id="categorySelect">
                <div class="selected">Kategori Seçiniz</div>
                <ul class="options">
                    <?php if ($result_cats->num_rows > 0): ?>
                        <?php while($cat = $result_cats->fetch_assoc()): ?>
                            <li data-value="<?php echo $cat['category_id']; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li data-value="">Veritabanında kategori bulunamadı!</li>
                    <?php endif; ?>
                </ul>
            </div>
            <input type="hidden" name="set_category" id="hiddenCategory" required>
        </div>

        <div class="theme-selector-wrapper">
            <span class="theme-label">Kart Teması Seçin:</span>
            <div class="theme-options">
                <?php 
                if ($result_themes->num_rows > 0):
                    $first = true;
                    while($theme = $result_themes->fetch_assoc()): 
                ?>
                    <label>
                        <input type="radio" 
                            name="set_theme_id" 
                            value="<?php echo $theme['theme_id']; ?>" 
                            data-css="<?php echo $theme['css_class']; ?>"
                            onclick="changePreview(this)"
                            <?php if($first) { echo "checked"; $first = false; } ?>
                        >
                        <div class="theme-circle" 
                            title="<?php echo $theme['theme_name']; ?>"
                            style="background: <?php echo $theme['preview_color']; ?>;">
                        </div>
                    </label>
                <?php endwhile; endif; ?>
            </div>
        </div>

        <h3 style="color:white; text-align:center;">Kartlar</h3>

        <div id="cardsContainer">
            <div class="card-box">
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
                <button type="button" class="delete-btn" style="display:none">Sil</button>
            </div>

            <div class="card-box">
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
                <button type="button" class="delete-btn" style="display:none">Sil</button>
            </div>
        </div>

        <button type="button" class="add-btn" onclick="addCard()">+ Kart Ekle</button>
        <button type="submit" class="create-btn">Seti Oluştur</button>
        <button type="button" class="cancel-btn" onclick="window.location.href='index.php'">İptal</button>

    </form>
    <?php endif; ?>
    </div>
</div>

<script>
    // Kategori Seçimi (Değişmedi)
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

    // --- ÖNİZLEME FONKSİYONU (GÜNCELLENDİ) ---
    function changePreview(element) {
        // Seçilen temanın CSS class'ını al (örn: bg-sunset)
        const cssClass = element.getAttribute('data-css');
        
        // Tüm kart kutularını seç
        const cards = document.querySelectorAll('.card-box');
        
        cards.forEach(card => {
            // Mevcut "bg-" ile başlayan classları temizle
            card.classList.forEach(cls => {
                if (cls.startsWith('bg-')) {
                    card.classList.remove(cls);
                }
            });
            // Yeni temayı ekle (Bu classlar style.css içinde var)
            card.classList.add(cssClass);
        });
    }

    // Sayfa Yüklendiğinde
    window.onload = function() {
        const checkedInput = document.querySelector('input[name="set_theme_id"]:checked');
        if(checkedInput) {
            changePreview(checkedInput);
        }
        updateCardNumbers();
    };

    // Kart Numaraları ve Textarea Genişletme
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

    function autoExpandTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }
    document.querySelectorAll('textarea.auto-expand').forEach(textarea => {
        textarea.addEventListener('input', () => autoExpandTextarea(textarea));
    });

    // --- KART EKLEME (GÜNCELLENDİ) ---
    function addCard() {
        const container = document.getElementById("cardsContainer");
        const box = document.createElement("div");
        box.className = "card-box";

        // Yeni eklenen kartın rengi, o an seçili olan tema olmalı
        const checkedInput = document.querySelector('input[name="set_theme_id"]:checked');
        if(checkedInput) {
             const currentTheme = checkedInput.getAttribute('data-css');
             box.classList.add(currentTheme);
        }

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
            <button type="button" class="delete-btn">Sil</button>
        `;

        container.appendChild(box);
        
        // Silme butonu mantığı
        const delBtn = box.querySelector(".delete-btn");
        delBtn.addEventListener("click", function() {
            box.remove();
            checkDeletes();
            updateCardNumbers();
        });

        checkDeletes();
        updateCardNumbers();
    }

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
    updateCardNumbers();
    checkDeletes();
</script>

</body>
</html>