<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";

    if (!isset($_FILES['image'])) {
        echo "❌ Nessun file ricevuto tramite POST.";
        exit;
    }

    $file = $_FILES['image'];
    echo "📝 File caricato:\n";
    print_r($file);

    if (!is_uploaded_file($file['tmp_name'])) {
        echo "\n⚠️ Il file non è stato caricato correttamente (tmp_name non valido).";
        exit;
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    echo "\n🔍 Risultato getimagesize:\n";
    var_dump($imageInfo);

    echo "</pre>";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Test Upload Immagini</title>
</head>
<body>
    <h2>🔼 Carica un'immagine di test</h2>
    <form action="img.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="image" required>
        <button type="submit">Invia immagine</button>
    </form>
</body>
</html>
