<?php
/**
 * Art Puzzle - Script di diagnostica semplificato
 * Posiziona questo file nella cartella principale del modulo art_puzzle
 */

// Includi solo il config.inc.php per evitare problemi di inizializzazione
include(dirname(__FILE__).'/../../config/config.inc.php');

// Imposta output plain text per evitare problemi di visualizzazione
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

echo "=======================================================\n";
echo "  DIAGNOSTICA SEMPLIFICATA MODULO ART PUZZLE\n";
echo "  Data: " . date('Y-m-d H:i:s') . "\n";
echo "=======================================================\n\n";

// 1. Verifica che il modulo sia presente
echo "VERIFICA MODULO:\n";
echo "-----------------------\n";
$module_dir = _PS_MODULE_DIR_ . 'art_puzzle/';
if (is_dir($module_dir)) {
    echo "- Directory del modulo: TROVATA\n";
    
    // Verifica il file principale
    if (file_exists($module_dir . 'art_puzzle.php')) {
        echo "- File principale art_puzzle.php: TROVATO\n";
    } else {
        echo "- File principale art_puzzle.php: MANCANTE\n";
    }
} else {
    echo "- Directory del modulo: NON TROVATA\n";
}
echo "\n";

// 2. Verifica i file principali
echo "VERIFICA FILE PRINCIPALI:\n";
echo "-----------------------\n";
$files_to_check = [
    'art_puzzle.php',
    'ajax.php',
    'controllers/front/customizer.php',
    'controllers/front/preview.php',
    'controllers/front/ajax.php',
    'views/js/front.js',
    'views/js/cropper-integration.js',
    'views/js/preview-generator.js',
    'views/css/front.css',
    'classes/PuzzleImageProcessor.php',
    'classes/PuzzleFormatManager.php',
    'classes/PuzzleBoxManager.php',
    'classes/PDFGeneratorPuzzle.php',
    'classes/ArtPuzzleLogger.php'
];

$missing_files = [];
foreach ($files_to_check as $file) {
    $path = $module_dir . $file;
    $status = file_exists($path) ? "OK" : "MANCANTE";
    echo "- $file: $status\n";
    
    if ($status === "MANCANTE") {
        $missing_files[] = $file;
    }
}

if (count($missing_files) > 0) {
    echo "\nATTENZIONE: " . count($missing_files) . " file mancanti\n";
} else {
    echo "\nTutti i file principali sono presenti\n";
}
echo "\n";

// 3. Verifica le configurazioni senza usare la classe Configuration
echo "CONFIGURAZIONI (accesso diretto DB):\n";
echo "-----------------------\n";
$configs = [
    'ART_PUZZLE_PRODUCT_IDS' => 'ID Prodotti configurati',
    'ART_PUZZLE_FORMATS' => 'Formati puzzle',
    'ART_PUZZLE_BOX_COLORS' => 'Colori scatola',
    'ART_PUZZLE_FONTS' => 'Font disponibili',
    'ART_PUZZLE_MAX_UPLOAD_SIZE' => 'Dimensione massima upload',
    'ART_PUZZLE_ALLOWED_FILE_TYPES' => 'Tipi di file permessi',
    'ART_PUZZLE_ENABLE_CROP_TOOL' => 'Strumento di ritaglio',
    'ART_PUZZLE_ENABLE_ORIENTATION' => 'Rotazione immagine',
    'ART_PUZZLE_DEFAULT_BOX_TEXT' => 'Testo predefinito scatola',
    'ART_PUZZLE_MAX_BOX_TEXT_LENGTH' => 'Lunghezza massima testo scatola'
];

// Connessione diretta al database
$port = defined('_DB_PORT_') ? (int)_DB_PORT_ : 3306;
$link = new mysqli(_DB_SERVER_, _DB_USER_, _DB_PASSWD_, _DB_NAME_, $port);
if ($link->connect_error) {
    echo "Errore connessione al database: " . $link->connect_error . "\n";
} else {
    foreach ($configs as $key => $description) {
        $query = "SELECT value FROM " . _DB_PREFIX_ . "configuration WHERE name = '" . $link->real_escape_string($key) . "'";
        $result = $link->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $value = $row['value'];
            echo "- " . $description . ": " . ($value ? (strlen($value) > 100 ? substr($value, 0, 97) . '...' : $value) : 'VUOTO') . "\n";
        } else {
            echo "- " . $description . ": NON TROVATO\n";
        }
    }
    $link->close();
}
echo "\n";

// 4. Verifica i permessi delle directory critiche
echo "PERMESSI DIRECTORY:\n";
echo "-----------------------\n";
$dirs = [
    'upload' => $module_dir . 'upload/',
    'logs' => $module_dir . 'logs/',
    'views/fonts' => $module_dir . 'views/fonts/',
    'views/img/scatole_base' => $module_dir . 'views/img/scatole_base/'
];

foreach ($dirs as $name => $path) {
    if (is_dir($path)) {
        $is_writable = is_writable($path) ? "SCRIVIBILE" : "NON SCRIVIBILE";
        echo "- $name: ESISTE ($is_writable)\n";
    } else {
        echo "- $name: NON ESISTE\n";
    }
}
echo "\n";

// 5. Verifica dell'ambiente JavaScript
echo "ANALISI FILE JAVASCRIPT:\n";
echo "-----------------------\n";
$js_files = [
    'views/js/front.js' => $module_dir . 'views/js/front.js',
    'views/js/cropper-integration.js' => $module_dir . 'views/js/cropper-integration.js',
    'views/js/preview-generator.js' => $module_dir . 'views/js/preview-generator.js'
];

foreach ($js_files as $js_name => $js_path) {
    if (file_exists($js_path)) {
        $content = file_get_contents($js_path);
        $size = filesize($js_path);
        $lines = count(file($js_path));
        echo "- $js_name: OK (dimensione: " . round($size/1024, 2) . " KB, $lines righe)\n";
        
        // Verifica errori di sintassi comuni
        $syntax_errors = [];
        if (strpos($content, '{') !== false && substr_count($content, '{') !== substr_count($content, '}')) {
            $syntax_errors[] = "Numero di parentesi graffe non bilanciato";
        }
        if (strpos($content, '(') !== false && substr_count($content, '(') !== substr_count($content, ')')) {
            $syntax_errors[] = "Numero di parentesi tonde non bilanciato";
        }
        
        if (count($syntax_errors) > 0) {
            echo "  ATTENZIONE: Possibili errori di sintassi in $js_name:\n";
            foreach ($syntax_errors as $error) {
                echo "  - $error\n";
            }
        }
    } else {
        echo "- $js_name: MANCANTE\n";
    }
}
echo "\n";

// 6. Verifica file di template
echo "VERIFICA FILE TEMPLATE:\n";
echo "-----------------------\n";
$template_files = [
    'views/templates/front/customizer.tpl',
    'views/templates/front/summary.tpl',
    'views/templates/hook/displayProductButtons.tpl',
    'views/templates/hook/displayProductExtraContent.tpl',
    'views/templates/hook/displayShoppingCartFooter.tpl',
    'views/templates/hook/fonts_css.tpl'
];

foreach ($template_files as $template) {
    $path = $module_dir . $template;
    $status = file_exists($path) ? "OK" : "MANCANTE";
    echo "- $template: $status\n";
}
echo "\n";

// 7. Verifica file CSS
echo "VERIFICA FILE CSS:\n";
echo "-----------------------\n";
$css_file = $module_dir . 'views/css/front.css';
if (file_exists($css_file)) {
    $size = filesize($css_file);
    $lines = count(file($css_file));
    echo "- front.css: OK (dimensione: " . round($size/1024, 2) . " KB, $lines righe)\n";
} else {
    echo "- front.css: MANCANTE\n";
}
echo "\n";

// 8. Verifica degli hook nel database
echo "VERIFICA HOOK (accesso diretto DB):\n";
echo "-----------------------\n";
$required_hooks = [
    'displayProductButtons',
    'displayProductExtraContent',
    'displayShoppingCartFooter',
    'actionProductCancel',
    'displayAdminProductsExtra'
];

// Ottieni l'ID modulo dal database
$port = defined('_DB_PORT_') ? (int)_DB_PORT_ : 3306;
$link = new mysqli(_DB_SERVER_, _DB_USER_, _DB_PASSWD_, _DB_NAME_, $port);
if ($link->connect_error) {
    echo "Errore connessione al database: " . $link->connect_error . "\n";
} else {
    $query = "SELECT id_module FROM " . _DB_PREFIX_ . "module WHERE name = 'art_puzzle'";
    $result = $link->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id_module = $row['id_module'];
        
        echo "ID Modulo trovato: " . $id_module . "\n\n";
        
        // Ora ottieni gli hook registrati
        $registered_hooks = [];
        $query = "SELECT h.name FROM " . _DB_PREFIX_ . "hook_module hm 
                  JOIN " . _DB_PREFIX_ . "hook h ON hm.id_hook = h.id_hook 
                  WHERE hm.id_module = " . (int)$id_module;
        $result = $link->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $registered_hooks[] = $row['name'];
            }
        }
        
        foreach ($required_hooks as $hook) {
            $registered = in_array($hook, $registered_hooks) ? "REGISTRATO" : "NON REGISTRATO";
            echo "- $hook: $registered\n";
        }
    } else {
        echo "Modulo non trovato nel database\n";
    }
    $link->close();
}
echo "\n";


echo "TEST SCRITTURA FILE NELLA CARTELLA 'upload':\n";
echo "-----------------------\n";
$test_file = $module_dir . 'upload/test_write.txt';
if (file_put_contents($test_file, "test OK - " . date('Y-m-d H:i:s'))) {
    echo "- Scrittura riuscita: test_write.txt creato in /upload\n";
    unlink($test_file); // cancella dopo verifica
} else {
    echo "- Errore: impossibile scrivere nella cartella /upload\n";
}
echo "\n";

echo "DEBUG AVANZATO: TEST VALORI BASE64 E MIME\n";
echo "-----------------------\n";

// Caricamento simulato dell'immagine da file base64.txt (se presente)
$base64_test_file = $module_dir . 'upload/base64.txt';
if (file_exists($base64_test_file)) {
    $image_data = trim(file_get_contents($base64_test_file));

    $image_parts = explode(";base64,", $image_data);
    $mime_info = explode(":", $image_parts[0]);
    $mime_type = isset($mime_info[1]) ? explode(";", $mime_info[1])[0] : 'N/D';
    $image_base64 = isset($image_parts[1]) ? $image_parts[1] : null;

    echo "- MIME rilevato: " . $mime_type . "\n";
    echo "- Lunghezza base64: " . strlen($image_base64) . " caratteri\n";

    $decoded = base64_decode($image_base64);
    if ($decoded) {
        echo "- Base64 decodificato con successo\n";

        if (function_exists('getimagesizefromstring')) {
            $info = @getimagesizefromstring($decoded);
            if ($info) {
                echo "- getimagesizefromstring OK: " . $info[0] . "x" . $info[1] . "\n";
            } else {
                echo "- getimagesizefromstring FALLITA\n";
            }
        }

        $test_filename = $module_dir . 'upload/test_debug_image.jpg';
        if (file_put_contents($test_filename, $decoded)) {
            echo "- Immagine scritta con successo in: test_debug_image.jpg\n";
            unlink($test_filename);
        } else {
            echo "- ERRORE: Impossibile scrivere test_debug_image.jpg\n";
        }
    } else {
        echo "- ERRORE: base64_decode fallita\n";
    }
} else {
    echo "- File base64.txt non trovato nella cartella /upload\n";
}
echo "\n";

echo "TEST CREAZIONE BASE64 DA IMMAGINE DI TEST (upload/test.jpg):\n";
echo "-----------------------\n";
$test_image_path = $module_dir . 'upload/test.jpg';
if (file_exists($test_image_path)) {
    $img_data = file_get_contents($test_image_path);
    $mime_type = mime_content_type($test_image_path);
    $base64_encoded = base64_encode($img_data);
    $base64_string = 'data:' . $mime_type . ';base64,' . $base64_encoded;
    echo "- MIME: " . $mime_type . "\n";
    echo "- Lunghezza stringa base64: " . strlen($base64_string) . " caratteri\n";
    echo "- Inizio stringa base64:\n" . substr($base64_string, 0, 250) . "...[TRONCATA]\n";
    file_put_contents($module_dir . 'upload/base64_GENERATA.txt', $base64_string);
    echo "- Stringa completa salvata in: upload/base64_GENERATA.txt\n";
} else {
    echo "- Immagine upload/test.jpg non trovata\n";
}
echo "\n";
// 9. Suggerimenti per la risoluzione dei problemi
echo "SUGGERIMENTI PER RISOLVERE LA PAGINA BIANCA:\n";
echo "-----------------------\n";
echo "1. Installa i file mancanti (se ce ne sono)\n";
echo "2. Abilita la modalità debug in PrestaShop (modifica /config/defines.inc.php e imposta _PS_MODE_DEV_ a true)\n";
echo "3. Controlla gli errori nella console JavaScript del browser (F12 > Console)\n";
echo "4. Verifica che le directory 'upload' e 'logs' esistano e abbiano i permessi di scrittura corretti\n";
echo "5. Verifica che tutti gli hooks necessari siano registrati\n";
echo "6. Prova a svuotare la cache di PrestaShop\n";
echo "7. Consiglio: Crea un file test.php nella cartella root del sito con questo contenuto:\n";
echo "   <?php phpinfo(); ?>\n";
echo "   Questo ti mostrerà se PHP sta funzionando correttamente e quali estensioni sono abilitate\n";
echo "\n";

echo "=======================================================\n";
echo "  FINE DIAGNOSTICA\n";
echo "=======================================================\n";