<?php
header("Content-Type: application/json");
// Holt die Daten direkt vom Radio-Panel im Hintergrund
echo file_get_contents("https://panel.joystream-fm.de/api/tracklist.php");
?>
