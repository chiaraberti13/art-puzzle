<?php
// Disattiva output buffering
ini_set('output_buffering', 'off');
// Disattiva compressione
ini_set('zlib.output_compression', 'off');
// Imposta flush automatico
ini_set('implicit_flush', 'on');
ob_implicit_flush(true);

// Imposta header per prevenire caching
header('Content-Type: text/plain');
header('Cache-Control: no-store, no-cache, must-revalidate');

echo "Inizio test diagnostico...\n";
echo "Directory modulo: " . _PS_MODULE_DIR_ . "art_puzzle\n";

// Verifica presenza e permessi dei file principali
$files_to_check = [
    'views/js/front.js',
    'views/js/cropper-integration.js',
    'views/js/preview-generator.js',
    'views/css/front.css',
    'classes/PuzzleImageProcessor.php',
    'classes/PuzzleFormatManager.php',
    'classes/PuzzleBoxManager.php',
    'classes/PDFGeneratorPuzzle.php',
    'classes/ArtPuzzleLogger.php',
    'controllers/front/customizer.php',
    'controllers/front/preview.php',
    'controllers/front/ajax.php'
];

foreach ($files_to_check as $file) {
    $path = _PS_MODULE_DIR_ . 'art_puzzle/' . $file;
    echo "Verifico file: $file... ";
    if (file_exists($path)) {
        echo "PRESENTE";
        if (is_readable($path)) {
            echo " (leggibile)";
        } else {
            echo " (NON leggibile)";
        }
    } else {
        echo "MANCANTE";
    }
    echo "\n";
}

// Verifica le directory critiche
$dirs_to_check = [
    'upload',
    'logs',
    'views/fonts',
    'views/img/scatole_base'
];

echo "\nVerifica directory:\n";
foreach ($dirs_to_check as $dir) {
    $path = _PS_MODULE_DIR_ . 'art_puzzle/' . $dir;
    echo "Directory: $dir... ";
    if (is_dir($path)) {
        echo "PRESENTE";
        if (is_writable($path)) {
            echo " (scrivibile)";
        } else {
            echo " (NON scrivibile)";
        }
    } else {
        echo "MANCANTE";
    }
    echo "\n";
}

// Verifica configurazione
echo "\nVerifica configurazione:\n";
$configs = [
    'ART_PUZZLE_BOX_COLORS',
    'ART_PUZZLE_FONTS',
    'ART_PUZZLE_FORMATS',
    'ART_PUZZLE_PRODUCT_IDS',
    'ART_PUZZLE_ENABLE_CROP_TOOL'
];

foreach ($configs as $config) {
    echo "$config: " . (Configuration::get($config) ? "IMPOSTATO" : "NON IMPOSTATO") . "\n";
    if ($config == 'ART_PUZZLE_PRODUCT_IDS') {
        echo "Valore: " . Configuration::get($config) . "\n";
    }
}

echo "\nTest completato.";