<?php
$logPath = __DIR__ . '/log_validate_debug.txt'; // cartella corrente del modulo

if (!file_exists($logPath)) {
    echo "❌ Il file di log non esiste.";
    exit;
}

echo "<h2>📄 Contenuto di log_validate_debug.txt</h2>";
echo "<pre style='background:#f0f0f0;padding:1em;border:1px solid #ccc'>";
echo htmlspecialchars(file_get_contents($logPath));
echo "</pre>";

if (isset($_GET['delete'])) {
    unlink($logPath);
    echo "<p>🗑️ Log eliminato.</p>";
}
?>

<p><a href="?delete=1">🧹 Elimina log</a></p>
