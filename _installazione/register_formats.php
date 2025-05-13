<?php
/**
 * Script per l'aggiunta dei formati puzzle predefiniti
 */

// Includi config
include(dirname(__FILE__).'/../../config/config.inc.php');

// Formati puzzle predefiniti
$defaultFormats = [
    // Formati quadrati
    'square_small' => [
        'name' => 'Quadrato piccolo',
        'dimensions' => '30x30 cm',
        'width' => 600,
        'height' => 600,
        'pieces' => 100,
        'orientation' => 'square',
        'ratio' => 1,
        'difficulty' => 'easy'
    ],
    'square_medium' => [
        'name' => 'Quadrato medio',
        'dimensions' => '45x45 cm',
        'width' => 900,
        'height' => 900,
        'pieces' => 500,
        'orientation' => 'square',
        'ratio' => 1,
        'difficulty' => 'medium'
    ],
    'square_large' => [
        'name' => 'Quadrato grande',
        'dimensions' => '60x60 cm',
        'width' => 1200,
        'height' => 1200,
        'pieces' => 1000,
        'orientation' => 'square',
        'ratio' => 1,
        'difficulty' => 'hard'
    ],
    
    // Formati orizzontali (landscape)
    'landscape_small' => [
        'name' => 'Orizzontale piccolo',
        'dimensions' => '40x30 cm',
        'width' => 800,
        'height' => 600,
        'pieces' => 150,
        'orientation' => 'landscape',
        'ratio' => 1.33,
        'difficulty' => 'easy'
    ],
    'landscape_medium' => [
        'name' => 'Orizzontale medio',
        'dimensions' => '60x45 cm',
        'width' => 1200,
        'height' => 900,
        'pieces' => 750,
        'orientation' => 'landscape',
        'ratio' => 1.33,
        'difficulty' => 'medium'
    ],
    'landscape_large' => [
        'name' => 'Orizzontale grande',
        'dimensions' => '80x60 cm',
        'width' => 1600,
        'height' => 1200,
        'pieces' => 1500,
        'orientation' => 'landscape',
        'ratio' => 1.33,
        'difficulty' => 'hard'
    ],
    
    // Formati verticali (portrait)
    'portrait_small' => [
        'name' => 'Verticale piccolo',
        'dimensions' => '30x40 cm',
        'width' => 600,
        'height' => 800,
        'pieces' => 150,
        'orientation' => 'portrait',
        'ratio' => 0.75,
        'difficulty' => 'easy'
    ],
    'portrait_medium' => [
        'name' => 'Verticale medio',
        'dimensions' => '45x60 cm',
        'width' => 900,
        'height' => 1200,
        'pieces' => 750,
        'orientation' => 'portrait',
        'ratio' => 0.75,
        'difficulty' => 'medium'
    ],
    'portrait_large' => [
        'name' => 'Verticale grande',
        'dimensions' => '60x80 cm',
        'width' => 1200,
        'height' => 1600,
        'pieces' => 1500,
        'orientation' => 'portrait',
        'ratio' => 0.75,
        'difficulty' => 'hard'
    ]
];

// Font predefiniti
$defaultFonts = [
    'default.ttf',
    'arial.ttf',
    'times.ttf',
    'georgia.ttf',
    'courier.ttf'
];

// Connessione al database
$link = new mysqli(_DB_SERVER_, _DB_USER_, _DB_PASSWD_, _DB_NAME_);
if ($link->connect_error) {
    die("Errore connessione al database: " . $link->connect_error);
}

// Salva i formati
$formatsJson = json_encode($defaultFormats);
$query = "INSERT INTO " . _DB_PREFIX_ . "configuration (name, value) VALUES ('ART_PUZZLE_FORMATS', '" . $link->real_escape_string($formatsJson) . "') 
          ON DUPLICATE KEY UPDATE value = '" . $link->real_escape_string($formatsJson) . "'";
$result = $link->query($query);

if ($result) {
    echo "Formati puzzle aggiunti con successo.<br>";
} else {
    echo "Errore nell'aggiunta dei formati puzzle: " . $link->error . "<br>";
}

// Salva i font
$fontsString = implode(',', $defaultFonts);
$query = "INSERT INTO " . _DB_PREFIX_ . "configuration (name, value) VALUES ('ART_PUZZLE_FONTS', '" . $link->real_escape_string($fontsString) . "') 
          ON DUPLICATE KEY UPDATE value = '" . $link->real_escape_string($fontsString) . "'";
$result = $link->query($query);

if ($result) {
    echo "Font aggiunti con successo.<br>";
} else {
    echo "Errore nell'aggiunta dei font: " . $link->error . "<br>";
}

$link->close();

echo "<p>Registrazione completata. <a href='art_puzzle_debug_simple.php'>Torna alla diagnostica</a></p>";