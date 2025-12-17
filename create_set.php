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
            // Sets tablosuna kaydet
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    
    * { box-sizing: border-box; }

    .create-container {
        width: 100%;
        max-width: 650px;
        padding: 20px;
        margin: 40px auto; 
    }

    /* --- LOGIN SAYFASINA BENZER CAM EFEKTİ --- */
    .glass-card { 
        backdrop-filter: blur(15px);
        background: rgba(255, 255, 255, 0.2); /* Daha açık beyaz */
        border-radius: 16px;
        padding: 35px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        border: 1px solid rgba(255,255,255,0.4);
        animation: fadeIn 0.6s ease;
        color: #333; /* Genel yazı rengi koyu */
    }

    @keyframes fadeIn {
        from {opacity:0; transform:translateY(20px);}
        to   {opacity:1; transform:translateY(0);}
    }

    h2 {
        text-align: center;
        color: #000; /* Başlık Siyah */
        margin-bottom: 10px;
        font-size: 30px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Input stilleri - Login sayfasına uyarlandı */
    textarea.auto-expand, .input-wrapper input, .input-wrapper select {
        width: 100%;
        padding: 14px 14px;
        border: 1px solid rgba(255,255,255,0.4);
        background: rgba(255,255,255,0.4); /* Yazı koyu olduğu için arka plan daha opak beyaz */
        border-radius: 8px;
        font-size: 15px;
        color: #333; /* Yazı rengi koyu gri/siyah */
        outline: none;
        resize: none;
        transition: 0.3s;
        font-weight: 500;
    }

    textarea.auto-expand:focus, .input-wrapper input:focus, .input-wrapper select:focus {
        background: rgba(255,255,255,0.6);
        border-color: #fff;
        box-shadow: 0 0 10px rgba(255,255,255,0.2);
    }

    .input-wrapper {
        position: relative;
        margin-bottom: 25px;
    }

    /* Label (Placeholder) Rengi - Login'deki gri ton */
    .input-wrapper label {
        position: absolute;
        top: 50%;
        left: 12px;
        transform: translateY(-50%);
        color: #555; /* Daha koyu gri */
        pointer-events: none;
        transition: .2s ease;
        font-weight: 500;
    }

    .input-wrapper input:focus + label,
    .input-wrapper input:not(:placeholder-shown) + label,
    .input-wrapper textarea:focus + label,
    .input-wrapper textarea:not(:placeholder-shown) + label {
        top: -8px;
        font-size: 12px;
        color: #333; /* Odaklanınca siyah */
        font-weight: bold;
        background: transparent;
    }

    .focus-border {
        position: absolute; bottom: 0; left: 0; height: 2px; width: 0; background: #333; transition: .3s;
    }
    .input-wrapper input:focus ~ .focus-border,
    .input-wrapper textarea:focus ~ .focus-border { width: 100%; }

    /* --- KART KUTUSU (Tema buraya uygulanacak) --- */
    .card-box {
        position: relative;
        /* Kartın kendisi biraz daha belirgin olsun */
        backdrop-filter: blur(25px);
        background: rgba(255,255,255,0.3); 
        border: 1px solid rgba(255,255,255,0.5);
        padding: 60px 22px 22px 22px;
        border-radius: 14px;
        margin-bottom: 18px;
        transition: background 0.5s ease, border-color 0.5s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .card-number {
        position: absolute; top: 10px; left: 10px;
        background: rgba(0, 0, 0, 0.1); /* Numara arka planı koyulaştırıldı */
        color: #333; font-weight: bold; padding: 4px 8px; border-radius: 12px; font-size: 14px;
    }

    /* --- TEMA SEÇİCİ TASARIMI --- */
    .theme-selector-wrapper {
        margin-bottom: 25px;
        background: rgba(255, 255, 255, 0.3);
        padding: 15px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        text-align: center;
    }

    .theme-label {
        color: #222; display: block; margin-bottom: 10px; font-weight: 600;
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
        border: 3px solid rgba(255, 255, 255, 0.6);
        position: relative;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .theme-circle:hover { transform: translateY(-3px); }

    .theme-options input[type="radio"]:checked + .theme-circle {
        border-color: #333; /* Seçilince çerçeve koyu olsun */
        transform: scale(1.15);
        box-shadow: 0 0 15px rgba(255, 255, 255, 0.8);
    }

    .theme-options input[type="radio"]:checked + .theme-circle::after {
        content: '✓';
        color: #fff;
        font-size: 24px;
        font-weight: bold;
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        text-shadow: 0 1px 2px rgba(0,0,0,0.5);
    }

    /* CUSTOM SELECT */
    .custom-select {
        position: relative; z-index: 1000; cursor: pointer;
        border-radius: 8px; 
        background: rgba(255,255,255,0.4); /* Login input style */
        border: 1px solid rgba(255,255,255,0.4); 
        color: #333; 
        padding: 14px 14px;
        font-weight: 500;
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

    /* BUTTONS */
    .delete-btn { background: #ff4d4d; border: none; padding: 8px 12px; border-radius: 8px; color: white; cursor: pointer; margin-top: 10px; float: bottom; font-size: 13px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    
    .add-btn { 
        width: 100%; padding: 12px; margin-top: 10px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; 
        background: #fff; color:#333; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: 0.3s;
    }
    .add-btn:hover { background: #f0f0f0; transform: scale(1.01); }

    .create-btn { 
        width: 100%; padding: 12px; margin-top: 10px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; 
        background: #333; font-weight:bold; color: #fff; /* Siyah buton, beyaz yazı */
        transition: 0.3s;
    }
    .create-btn:hover { background: #000; transform: scale(1.01); }

    .cancel-btn { 
        width: 100%; padding: 12px; margin-top: 10px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; 
        background:#DA3A3A; color:#fff; font-weight: bold;
    }

    .success-msg { background: rgba(80, 255, 120, 0.2); border: 1px solid #50ff78; color: #006400; padding: 12px; border-radius: 10px; text-align:center; font-weight: bold; }
    .error-msg { background: rgba(255, 80, 80, 0.2); border: 1px solid #fa3939; color: #8b0000; padding: 12px; border-radius: 10px; text-align:center; font-weight: bold; }
    
    h3 { color: #333 !important; } /* Kartlar başlığını zorla siyah yap */

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

        <h3 style="text-align:center;">Kartlar</h3>

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

    // --- ÖNİZLEME FONKSİYONU ---
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

    // --- KART EKLEME ---
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