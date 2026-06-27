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
// 2. LIVE-SONG AUS ICECAST & ITUNES-COVER
// ==========================================
async function updateStickySong() {
    try {
        const res = await fetch("get_song.php");
        if (!res.ok) throw new Error("Fehler beim Laden der Songdaten");
        
        const data = await res.json();
        let rawTitle = "";

        // 1. Icecast JSON-Struktur nach dem Text durchsuchen
        if (data && data.icestats && data.icestats.source) {
            const source = data.icestats.source;
            if (Array.isArray(source)) {
                for (let s of source) {
                    if (s.title) { rawTitle = s.title; break; }
                }
            } else if (source.title) {
                rawTitle = source.title;
            }
        }

        // Standard-Werte setzen, falls nichts läuft
        let displayArtist = "Joy FM";
        let displayTitle = "Dein Live Radio";
        let coverSrc = "transparent-logo.png"; // Fallback-Bild

        if (rawTitle && rawTitle.trim() !== "") {
            // 2. Text am Bindestrich aufteilen (Künstler - Titel)
            if (rawTitle.includes(" - ")) {
                const parts = rawTitle.split(" - ");
                displayArtist = parts[0].trim();
                displayTitle = parts[1].trim();
            } else {
                displayTitle = rawTitle.trim();
            }

            // 3. Cover-Bild live über die iTunes API suchen
            try {
                const iTunesRes = await fetch(`https://itunes.apple.com/search?term=${encodeURIComponent(rawTitle)}&media=music&limit=1`);
                const iTunesData = await iTunesRes.json();
                if (iTunesData.results && iTunesData.results.length > 0) {
                    // Holt das Cover-Bild und macht es hochauflösender (600x600)
                    coverSrc = iTunesData.results[0].artworkUrl100.replace("100x100bb", "600x600bb");
                }
            } catch (coverError) {
                console.log("Kein Cover bei iTunes gefunden, nutze Logo.");
            }
        }

        // 4. HTML in der Leiste aktualisieren
        document.getElementById("currentSong").innerHTML = `<span style="color: #ff0055; font-weight: bold;">${displayArtist}</span> - ${displayTitle}`;
        document.getElementById("songCover").src = coverSrc;

    } catch (error) {
        console.log("Fehler beim Icecast- oder Cover-Auslesen:", error);
        document.getElementById("currentSong").textContent = "Joy FM – Fühle den Sound";
        document.getElementById("songCover").src = "transparent-logo.png";
    }
}

// Song alle 15 Sekunden automatisch aktualisieren
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

// Team direkt beim Laden initialisieren
loadTeam();
