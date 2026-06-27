// ==========================================
// 1. PLAYER-LOGIK (PLAY / PAUSE)
// ==========================================
const audio = document.getElementById('radioStream');
const playBtn = document.getElementById('playBtn');

function togglePlay() {
    if (!audio) return;
    if (audio.paused) {
        audio.play().catch(err => console.log("Stream-Fehler oder URL fehlt."));
        playBtn.innerHTML = "⏸ Stoppen";
        playBtn.style.background = "rgba(255,255,255,0.15)";
        playBtn.style.border = "1px solid rgba(255,255,255,0.3)";
    } else {
        audio.pause();
        playBtn.innerHTML = "▶ Abspielen";
        playBtn.style.background = "#ff0055";
        playBtn.style.border = "none";
    }
}

// ==========================================
// 2. LIVE-SONG & COVER ANZEIGE (ÜBER PHP-PROXY)
// ==========================================
async function updateStickySong() {
    try {
        const res = await fetch("get_song.php");
        
        // Prüfen, ob die get_song.php überhaupt antwortet
        if (!res.ok) {
            throw new Error("get_song.php konnte nicht geladen werden");
        }
        
        const data = await res.json();
        console.log("Empfangene Radio-Daten:", data); // Schreibt die Daten in die Browser-Konsole (F12)

        let title = "Joy FM – Musik läuft!";
        let coverSrc = "transparent-logo.png";

        // Flexibles Auslesen, je nachdem wie euer Panel aufgebaut ist
        if (data) {
            if (data.now_playing) {
                title = data.now_playing;
            } else if (data.title) {
                title = data.title;
            } else if (data.tracks && data.tracks.length > 0) {
                title = data.tracks[0].title || data.tracks[0].name || title;
                if (data.tracks[0].cover) coverSrc = data.tracks[0].cover;
            }
            
            if (data.cover) {
                coverSrc = data.cover;
            }
        }

        document.getElementById("currentSong").textContent = title;
        document.getElementById("songCover").src = coverSrc;

    } catch (error) {
        console.error("Fehler beim Laden des Songtitels:", error);
        document.getElementById("currentSong").textContent = "Joy FM – Fühle den Sound";
        document.getElementById("songCover").src = "transparent-logo.png";
    }
}

// Alle 15 Sekunden aktualisieren
updateStickySong();
setInterval(updateStickySong, 15000);

// ==========================================
// 3. TEAM-LISTEN LOGIK
// ==========================================
const TEAM_LIST = "https://panel.joystream-fm.de/includes/team_mitglieder";
const TEAM_DETAIL = (id) => "https://panel.joystream-fm.de/includes/team_mitglieder?id=" + encodeURIComponent(id);

function escapeHtml(value){
  return String(value ?? "").replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;"
  }[char]));
}

async function loadTeam(){
  try {
      const res = await fetch(TEAM_LIST, { headers: { "Accept":"application/json" }});
      const data = await res.json();
      if(!data.ok) return document.getElementById("teamList").textContent = "Fehler beim Laden";

      document.getElementById("teamList").innerHTML = data.members.map(m =>
        `<button style="display:block;width:100%;text-align:left;margin:8px 0;padding:12px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#fff;cursor:pointer"
          onclick="loadMember(${m.id})">${escapeHtml(m.name)} (#${m.id})</button>`
      ).join("");
  } catch(e) {
      document.getElementById("teamList").textContent = "Team-API aktuell nicht erreichbar.";
  }
}

async function loadMember(id){
  try {
      const res = await fetch(TEAM_DETAIL(id), { headers: { "Accept":"application/json" }});
      const data = await res.json();
      if(!data.ok) return document.getElementById("teamDetails").textContent = "Nicht gefunden";

      const m = data.member;
      const bio = m.bio ? escapeHtml(m.bio).replace(/\n/g, "<br>") : "Keine Beschreibung vorhanden.";

      document.getElementById("teamDetails").innerHTML = `
        <div style="margin-top:12px;padding:14px;border-radius:16px;border:1px solid rgba(255,255,255,.12);background:rgba(0,0,0,.20)">
          <b>${escapeHtml(m.name)}</b> <span style="opacity:.75">(${escapeHtml(m.role)})</span><br>
          ${m.avatar ? `<img src="${escapeHtml(m.avatar)}" style="margin-top:10px;width:84px;height:84px;border-radius:18px;object-fit:cover;border:1px solid rgba(255,255,255,.12)">` : `<div style="opacity:.7;margin-top:10px">Kein Avatar</div>`}
          <div style="margin-top:12px;opacity:.85;line-height:1.45">${bio}</div>
        </div>
      `;
  } catch(e) {
      console.log("Fehler beim Laden des Mitglieds", e);
  }
}

loadTeam();
