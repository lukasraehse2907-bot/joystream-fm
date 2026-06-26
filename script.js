document.addEventListener("DOMContentLoaded", () => {
    const radio = document.getElementById("radio-stream");
    const playBtn = document.getElementById("play-btn");
    const volumeSlider = document.getElementById("volume-slider");
    const trackTitle = document.getElementById("track-title");

    // Standard-Lautstärke einstellen
    radio.volume = volumeSlider.value;

    playBtn.addEventListener("click", () => {
        if (radio.paused) {
            // Sorgt dafür, dass der Stream absolut live abgespielt wird
            radio.load(); 
            radio.play()
                .then(() => {
                    playBtn.textContent = "⏸";
                    trackTitle.textContent = "Joy FM Live-Stream läuft!";
                })
                .catch(err => {
                    console.error("Fehler beim Abspielen:", err);
                    trackTitle.textContent = "Stream-Fehler. Erneut versuchen.";
                });
        } else {
            radio.pause();
            playBtn.textContent = "▶";
            trackTitle.textContent = "Wiedergabe pausiert.";
        }
    });

    // Lautstärkeregler-Event
    volumeSlider.addEventListener("input", (e) => {
        radio.volume = e.target.value;
    });
});
