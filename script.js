const texts = [
    "Kendi çalışma setlerini oluştur.",
    "Öğrenmeni hızlandır.",
    "Bilgini test et.",
    "Başarıya hazırlan."
];

let index = 0;
const textElement = document.getElementById("changingText");

function changeText() {
    textElement.style.opacity = 0;

    setTimeout(() => {
        textElement.textContent = texts[index];
        textElement.style.opacity = 1;
        index = (index + 1) % texts.length;
    }, 300);
}

changeText();
setInterval(changeText, 3000);
