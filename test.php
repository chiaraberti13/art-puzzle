<?php
/**
 * Art Puzzle - Diagnostic Test Tool
 * Script di diagnostica completo per individuare problemi nel modulo art_puzzle
 */

// Includi i file di configurazione PrestaShop
$dir = dirname(__FILE__);
include($dir.'/../../config/config.inc.php');
include($dir.'/../../init.php');

// Funzione per visualizzare messaggi di errore e avviso
function displayMessage($message, $type = 'info') {
    $colors = [
        'success' => '#4CAF50',
        'info' => '#2196F3',
        'warning' => '#FF9800',
        'error' => '#F44336'
    ];
    
    echo '<div style="margin: 5px 0; padding: 10px; background-color: '.($colors[$type] ?? '#2196F3').'; color: white; border-radius: 5px;">';
    echo '<strong>'.strtoupper($type).':</strong> '.$message;
    echo '</div>';
}

// Funzione per controllare l'esistenza e i permessi di una directory
function checkDirectory($path, $label, $createIfMissing = false) {
    echo '<h3>'.$label.' ('.$path.')</h3>';
    
    if (!file_exists($path)) {
        if ($createIfMissing) {
            if (@mkdir($path, 0755, true)) {
                displayMessage("Directory creata con successo", "success");
            } else {
                displayMessage("Impossibile creare la directory", "error");
                return false;
            }
        } else {
            displayMessage("Directory non esistente", "error");
            return false;
        }
    }
    
    if (!is_dir($path)) {
        displayMessage("Il percorso esiste ma non è una directory", "error");
        return false;
    }
    
    if (!is_readable($path)) {
        displayMessage("Directory non leggibile", "error");
    } else {
        displayMessage("Directory leggibile", "success");
    }
    
    if (!is_writable($path)) {
        displayMessage("Directory non scrivibile", "error");
        return false;
    } else {
        displayMessage("Directory scrivibile", "success");
    }
    
    // Test di scrittura
    $testFile = $path.'/test_'.time().'.txt';
    if (@file_put_contents($testFile, 'Test di scrittura')) {
        displayMessage("Test di scrittura superato", "success");
        @unlink($testFile);
    } else {
        displayMessage("Test di scrittura fallito", "error");
        return false;
    }
    
    return true;
}

// Funzione per controllare i file principali del modulo
function checkFile($path, $label, $required = true) {
    echo '<div style="margin: 5px 0;">';
    if (file_exists($path)) {
        if (is_readable($path)) {
            echo '<span style="color: #4CAF50;">✓</span> ';
        } else {
            echo '<span style="color: #F44336;">✗</span> ';
        }
    } else {
        if ($required) {
            echo '<span style="color: #F44336;">✗</span> ';
        } else {
            echo '<span style="color: #FF9800;">?</span> ';
        }
    }
    
    echo $label;
    
    if (!file_exists($path)) {
        if ($required) {
            echo ' <span style="color: #F44336;">(File mancante!)</span>';
        } else {
            echo ' <span style="color: #FF9800;">(File opzionale mancante)</span>';
        }
    } elseif (!is_readable($path)) {
        echo ' <span style="color: #F44336;">(File non leggibile!)</span>';
    }
    
    echo '</div>';
}

// Controlla che un hook sia registrato
function checkHook($moduleName, $hookName) {
    $modules = Hook::getHookModuleExecList($hookName);
    
    if (!$modules) {
        displayMessage("Hook $hookName non registrato per alcun modulo", "info");
        return false;
    }
    
    foreach ($modules as $module) {
        if (isset($module['module']) && $module['module'] == $moduleName) {
            displayMessage("Hook $hookName correttamente registrato per il modulo $moduleName", "success");
            return true;
        }
    }
    
    displayMessage("Hook $hookName non registrato per il modulo $moduleName", "warning");
    return false;
}

// Funzione per controllare la configurazione
function checkConfiguration($key, $label, $default = null) {
    $value = Configuration::get($key);
    
    echo '<div style="margin: 5px 0;">';
    if ($value !== false && $value !== '') {
        echo '<span style="color: #4CAF50;">✓</span> ';
    } else {
        echo '<span style="color: #FF9800;">?</span> ';
    }
    
    echo $label . ': ' . ($value !== false ? '<code>' . htmlspecialchars($value) . '</code>' : '<em>Non impostato</em>');
    
    if ($value === false && $default !== null) {
        echo ' <span style="color: #FF9800;">(Valore consigliato: ' . htmlspecialchars($default) . ')</span>';
    }
    
    echo '</div>';
    
    return ($value !== false);
}

// Funzione per verificare la tabella database
function checkTable($tableName, $fullTableName = null) {
    if ($fullTableName === null) {
        $fullTableName = _DB_PREFIX_ . $tableName;
    }
    
    $exists = Db::getInstance()->executeS("SHOW TABLES LIKE '" . pSQL($fullTableName) . "'");
    
    if (!empty($exists)) {
        displayMessage("Tabella $tableName ($fullTableName) esiste", "success");
        
        // Controlla la struttura
        $columns = Db::getInstance()->executeS("SHOW COLUMNS FROM `" . pSQL($fullTableName) . "`");
        if (!empty($columns)) {
            echo '<div style="margin-left: 20px; font-size: 0.9em;">';
            echo '<strong>Colonne della tabella:</strong><br>';
            foreach ($columns as $column) {
                echo '- ' . $column['Field'] . ' (' . $column['Type'] . ')' . ($column['Key'] == 'PRI' ? ' <strong>PRIMARY KEY</strong>' : '') . '<br>';
            }
            echo '</div>';
        }
        
        return true;
    } else {
        displayMessage("Tabella $tableName ($fullTableName) non esiste", "error");
        return false;
    }
}

// Funzione per verificare le estensioni PHP
function checkPhpExtension($extension, $label = null) {
    if ($label === null) {
        $label = $extension;
    }
    
    echo '<div style="margin: 5px 0;">';
    if (extension_loaded($extension)) {
        echo '<span style="color: #4CAF50;">✓</span> ';
    } else {
        echo '<span style="color: #F44336;">✗</span> ';
    }
    
    echo $label;
    
    if (!extension_loaded($extension)) {
        echo ' <span style="color: #F44336;">(Estensione non caricata!)</span>';
    }
    
    echo '</div>';
    
    return extension_loaded($extension);
}

// Inizio output HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Art Puzzle - Tool di Diagnostica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2, h3, h4 {
            margin-top: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 15px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
        }
        .success { color: #4CAF50; }
        .warning { color: #FF9800; }
        .error { color: #F44336; }
        .info { color: #2196F3; }
    </style>
</head>
<body>
    <h1>Art Puzzle - Tool di Diagnostica</h1>
    
    <div>
        <p>
            <strong>Data e ora:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>Versione PrestaShop:</strong> <?php echo _PS_VERSION_; ?><br>
            <strong>Versione PHP:</strong> <?php echo phpversion(); ?><br>
            <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Sconosciuto'; ?><br>
        </p>
    </div>
    
    <h2>1. Verifica File e Directory del Modulo</h2>
    
    <?php
    $moduleDir = _PS_MODULE_DIR_ . 'art_puzzle/';
    
    displayMessage("Directory principale del modulo: " . $moduleDir);
    
    // Controlla directory principali
    $directories = [
        $moduleDir . 'upload/' => 'Directory Upload',
        $moduleDir . 'logs/' => 'Directory Logs',
        $moduleDir . 'views/templates/front/' => 'Directory Templates Front',
        $moduleDir . 'views/templates/hook/' => 'Directory Templates Hook',
        $moduleDir . 'views/js/' => 'Directory JavaScript',
        $moduleDir . 'views/css/' => 'Directory CSS',
        $moduleDir . 'classes/' => 'Directory Classi',
        $moduleDir . 'controllers/front/' => 'Directory Controller Front'
    ];
    
    foreach ($directories as $path => $label) {
        checkDirectory($path, $label, true);
    }
    
    // Controlla file principali
    echo '<h3>File Principali</h3>';
    
    $files = [
        $moduleDir . 'art_puzzle.php' => 'File Principale del Modulo',
        $moduleDir . 'views/js/front.js' => 'JavaScript Frontend',
        $moduleDir . 'views/css/front.css' => 'CSS Frontend',
        $moduleDir . 'views/templates/front/customizer.tpl' => 'Template Customizer',
        $moduleDir . 'views/templates/front/summary.tpl' => 'Template Summary',
        $moduleDir . 'views/templates/hook/displayProductButtons.tpl' => 'Template Hook Product Buttons',
        $moduleDir . 'classes/ArtPuzzleLogger.php' => 'Classe Logger',
        $moduleDir . 'classes/PuzzleImageProcessor.php' => 'Classe Image Processor',
        $moduleDir . 'classes/PuzzleFormatManager.php' => 'Classe Format Manager',
        $moduleDir . 'classes/PuzzleBoxManager.php' => 'Classe Box Manager',
        $moduleDir . 'controllers/front/ajax.php' => 'Controller AJAX',
        $moduleDir . 'controllers/front/customizer.php' => 'Controller Customizer',
        $moduleDir . 'controllers/front/preview.php' => 'Controller Preview'
    ];
    
    foreach ($files as $path => $label) {
        checkFile($path, $label);
    }
    
    ?>
    
    <h2>2. Verifica Hook</h2>
    
    <?php
    // Lista degli hook che dovrebbero essere registrati
    $hooks = [
        'displayProductButtons',
        'displayProductExtraContent',
        'displayShoppingCartFooter',
        'displayAdminProductsExtra',
        'actionProductCancel',
        'displayBackOfficeHeader',
        'displayHeader',
        'actionFrontControllerSetMedia'
    ];
    
    foreach ($hooks as $hook) {
        checkHook('art_puzzle', $hook);
    }
    ?>
    
    <h2>3. Verifica Configurazione</h2>
    
    <?php
    // Controlla le configurazioni del modulo
    echo '<h3>Configurazioni del Modulo</h3>';
    
    $configs = [
        'ART_PUZZLE_PRODUCT_IDS' => 'ID Prodotti Personalizzabili',
        'ART_PUZZLE_MAX_UPLOAD_SIZE' => 'Dimensione Massima Upload (MB)',
        'ART_PUZZLE_ALLOWED_FILE_TYPES' => 'Tipi di File Consentiti',
        'ART_PUZZLE_DEBUG_MODE' => 'Modalità Debug',
        'ART_PUZZLE_ENABLE_CROP_TOOL' => 'Abilita Strumento di Ritaglio',
        'ART_PUZZLE_ENABLE_ORIENTATION' => 'Abilita Orientamento',
        'ART_PUZZLE_DEFAULT_BOX_TEXT' => 'Testo Predefinito Scatola',
        'ART_PUZZLE_MAX_BOX_TEXT_LENGTH' => 'Lunghezza Massima Testo Scatola',
        'ART_PUZZLE_BOX_COLORS' => 'Colori Scatola',
        'ART_PUZZLE_FONTS' => 'Font Disponibili',
        'ART_PUZZLE_ADMIN_EMAIL' => 'Email Amministratore',
        'ART_PUZZLE_SEND_PREVIEW_USER_EMAIL' => 'Invia Email Anteprima Utente',
        'ART_PUZZLE_SEND_PREVIEW_ADMIN_EMAIL' => 'Invia Email Anteprima Admin',
        'ART_PUZZLE_ENABLE_PDF_USER' => 'Abilita PDF Utente',
        'ART_PUZZLE_ENABLE_PDF_ADMIN' => 'Abilita PDF Admin'
    ];
    
    foreach ($configs as $key => $label) {
        checkConfiguration($key, $label);
    }
    
    // Verifica le impostazioni PHP rilevanti
    echo '<h3>Configurazione PHP</h3>';
    
    $phpSettings = [
        'file_uploads' => 'File Uploads',
        'upload_max_filesize' => 'Dimensione Massima Upload',
        'post_max_size' => 'Dimensione Massima POST',
        'memory_limit' => 'Limite Memoria',
        'max_execution_time' => 'Tempo Massimo Esecuzione',
        'max_input_time' => 'Tempo Massimo Input',
        'max_file_uploads' => 'Numero Massimo File Upload'
    ];
    
    foreach ($phpSettings as $setting => $label) {
        echo '<div style="margin: 5px 0;">';
        echo '<strong>' . $label . ':</strong> ' . ini_get($setting);
        echo '</div>';
    }
    
    // Verifica le estensioni PHP necessarie
    echo '<h3>Estensioni PHP</h3>';
    
    $requiredExtensions = [
        'gd' => 'GD (Elaborazione Immagini)',
        'json' => 'JSON',
        'fileinfo' => 'Fileinfo (Rilevamento MIME)',
        'curl' => 'cURL (Richieste HTTP)',
        'zip' => 'ZIP',
        'mbstring' => 'Multibyte String'
    ];
    
    foreach ($requiredExtensions as $ext => $label) {
        checkPhpExtension($ext, $label);
    }
    ?>
    
    <h2>4. Test di Upload Immagine</h2>
    
    <p>Questo è un semplice form per testare l'upload delle immagini.</p>
    
    <form action="" method="post" enctype="multipart/form-data">
        <div>
            <label for="test_image">Seleziona un'immagine:</label>
            <input type="file" name="test_image" id="test_image">
        </div>
        <div style="margin-top: 10px;">
            <input type="submit" name="submit_test_upload" value="Carica Immagine">
        </div>
    </form>
    
    <?php
    // Gestisci il test di upload
    if (isset($_POST['submit_test_upload']) && isset($_FILES['test_image'])) {
        echo '<h3>Risultato Test Upload</h3>';
        
        echo '<pre>';
        echo 'Dati FILE: ' . print_r($_FILES['test_image'], true);
        echo '</pre>';
        
        // Verifica errori di upload
        if ($_FILES['test_image']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'Il file caricato supera la direttiva upload_max_filesize in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'Il file caricato supera la direttiva MAX_FILE_SIZE specificata nel form HTML',
                UPLOAD_ERR_PARTIAL => 'Il file è stato caricato solo parzialmente',
                UPLOAD_ERR_NO_FILE => 'Nessun file è stato caricato',
                UPLOAD_ERR_NO_TMP_DIR => 'Manca la directory temporanea',
                UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere il file su disco',
                UPLOAD_ERR_EXTENSION => 'Un\'estensione PHP ha fermato il caricamento del file'
            ];
            
            $errorMessage = $errorMessages[$_FILES['test_image']['error']] ?? 'Errore sconosciuto';
            displayMessage("Errore di upload: $errorMessage", "error");
        } else {
            // Controlla il tipo MIME
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['test_image']['tmp_name']);
            
            echo '<div><strong>Tipo MIME rilevato:</strong> ' . $mime . '</div>';
            
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
            
            if (!in_array($mime, $allowed_mimes)) {
                displayMessage("Tipo MIME non valido. Sono consentiti solo: " . implode(', ', $allowed_mimes), "error");
            } else {
                displayMessage("Tipo MIME valido", "success");
            }
            
            // Controlla l'estensione
            $extension = strtolower(pathinfo($_FILES['test_image']['name'], PATHINFO_EXTENSION));
            echo '<div><strong>Estensione rilevata:</strong> ' . $extension . '</div>';
            
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($extension, $allowed_extensions)) {
                displayMessage("Estensione non valida. Sono consentite solo: " . implode(', ', $allowed_extensions), "error");
            } else {
                displayMessage("Estensione valida", "success");
            }
            
            // Verifica che sia un'immagine valida
            if (getimagesize($_FILES['test_image']['tmp_name']) === false) {
                displayMessage("Il file non è un'immagine valida", "error");
            } else {
                displayMessage("Il file è un'immagine valida", "success");
                
                // Prova a salvare l'immagine
                $uploadDir = $moduleDir . 'upload/';
                $targetFile = $uploadDir . 'test_' . time() . '_' . basename($_FILES['test_image']['name']);
                
                if (move_uploaded_file($_FILES['test_image']['tmp_name'], $targetFile)) {
                    displayMessage("File caricato con successo: $targetFile", "success");
                    
                    // Mostra l'immagine
                    $shopUrl = Context::getContext()->shop->getBaseURL(true);
                    $relativePath = str_replace(_PS_ROOT_DIR_, '', $targetFile);
                    $imageUrl = $shopUrl . $relativePath;
                    
                    echo '<div style="margin: 10px 0;">';
                    echo '<img src="' . $imageUrl . '" style="max-width: 300px; max-height: 300px;">';
                    echo '</div>';
                } else {
                    displayMessage("Errore durante il salvataggio dell'immagine", "error");
                }
            }
        }
    }
    ?>
    
    <h2>5. Test Logger</h2>
    
    <?php
    // Test della classe logger
    if (file_exists($moduleDir . 'classes/ArtPuzzleLogger.php')) {
        require_once($moduleDir . 'classes/ArtPuzzleLogger.php');
        
        try {
            // Abilita temporaneamente la modalità debug
            Configuration::updateValue('ART_PUZZLE_DEBUG_MODE', 1);
            
            // Registra alcuni messaggi di prova
            ArtPuzzleLogger::info('Test messaggio INFO dal tool diagnostico');
            ArtPuzzleLogger::warning('Test messaggio WARNING dal tool diagnostico');
            ArtPuzzleLogger::error('Test messaggio ERROR dal tool diagnostico');
            ArtPuzzleLogger::debug('Test messaggio DEBUG dal tool diagnostico', ['test' => true]);
            
            displayMessage("Messaggi di log registrati con successo", "success");
            
            // Recupera e mostra i log più recenti
            $logs = ArtPuzzleLogger::getLogs(null, date('Y-m-d', strtotime('-1 day')), date('Y-m-d'), null, 10);
            
            if (empty($logs)) {
                displayMessage("Nessun log recente trovato", "warning");
            } else {
                echo '<h3>Log più recenti</h3>';
                echo '<table>';
                echo '<tr><th>Data/Ora</th><th>Livello</th><th>Messaggio</th></tr>';
                
                foreach ($logs as $log) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($log['timestamp']) . '</td>';
                    echo '<td>' . htmlspecialchars($log['level']) . '</td>';
                    echo '<td>' . htmlspecialchars($log['message']) . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
        } catch (Exception $e) {
            displayMessage("Errore durante il test del logger: " . $e->getMessage(), "error");
        }
    } else {
        displayMessage("Classe ArtPuzzleLogger non trovata", "error");
    }
    ?>
    
    <h2>6. Verifica Template Smarty displayProductButtons.tpl</h2>
    
    <?php
    $buttonsTplPath = $moduleDir . 'views/templates/hook/displayProductButtons.tpl';
    
    if (file_exists($buttonsTplPath)) {
        $content = file_get_contents($buttonsTplPath);
        echo '<h3>Contenuto del Template</h3>';
        echo '<pre style="background-color: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px;">';
        echo htmlspecialchars($content);
        echo '</pre>';
        
        // Verifica la presenza di JS con tabContent.scrollIntoView
        if (strpos($content, 'scrollIntoView') !== false) {
            echo '<h3>Problemi Rilevati</h3>';
            
            if (strpos($content, 'scrollIntoView({"behavior": "smooth"})') !== false && 
                strpos($content, '{literal}') === false && 
                strpos($content, '{ldelim}') === false) {
                displayMessage("Trovato codice problematico: scrollIntoView({\"behavior\": \"smooth\"}) - Le parentesi graffe devono essere escapate in Smarty", "error");
                
                echo '<h3>Soluzione Proposta</h3>';
                echo '<p>Modifica il codice come segue:</p>';
                
                $fixedCode = str_replace(
                    'scrollIntoView({"behavior": "smooth"});',
                    'scrollIntoView({ldelim}behavior: "smooth"{rdelim});',
                    $content
                );
                
                if ($fixedCode !== $content) {
                    echo '<pre style="background-color: #e8f5e9; padding: 10px; overflow: auto;">';
                    echo htmlspecialchars($fixedCode);
                    echo '</pre>';
                    
                    echo '<form method="post" action="">';
                    echo '<input type="hidden" name="fix_template" value="1">';
                    echo '<button type="submit" style="background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">Applica Fix Automatico</button>';
                    echo '</form>';
                } else {
                    displayMessage("Impossibile generare una soluzione automatica", "warning");
                }
            }
        }
        
        // Gestione della richiesta di fix automatico
        if (isset($_POST['fix_template'])) {
            $fixedCode = str_replace(
                'scrollIntoView({"behavior": "smooth"});',
                'scrollIntoView({ldelim}behavior: "smooth"{rdelim});',
                $content
            );
            
            if (file_put_contents($buttonsTplPath, $fixedCode)) {
                displayMessage("Template corretto con successo! Ricarica la pagina per verificare.", "success");
            } else {
                displayMessage("Impossibile salvare il template corretto. Verifica i permessi di scrittura.", "error");
            }
        }
    } else {
        displayMessage("Template displayProductButtons.tpl non trovato", "error");
    }
    ?>
    
    <h2>7. Suggerimenti per Risoluzione Problemi</h2>
    
    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
        <h3>Problemi Comuni e Soluzioni</h3>
        
        <h4>Problema 1: Upload Immagini Non Funziona</h4>
        <ul>
            <li>Verifica che il form HTML abbia <code>enctype="multipart/form-data"</code></li>
            <li>Verifica che il campo di upload abbia il nome corretto (<code>name="image"</code>)</li>
            <li>Controlla i permessi della directory upload</li>
            <li>Verifica le impostazioni PHP per l'upload (<code>upload_max_filesize</code>, <code>post_max_size</code>)</li>
            <li>Modifica validateUploadedImage() nella classe PuzzleImageProcessor per rimuovere controlli troppo restrittivi</li>
        </ul>
        
        <h4>Problema 2: Errori JavaScript/Smarty</h4>
        <ul>
            <li>In Smarty, le parentesi graffe <code>{}</code> in JavaScript devono essere escapate usando <code>{ldelim}{rdelim}</code> o racchiuse in <code>{literal}{/literal}</code></li>
            <li>Verifica che tutte le dipendenze JavaScript (come CropperJS) siano caricate correttamente</li>
            <li>Controlla la console del browser per errori JavaScript</li>
        </ul>
        
        <h4>Problema 3: Hook Non Funzionanti</h4>
        <ul>
            <li>Verifica che tutti gli hook necessari siano registrati</li>
            <li>Usa <code>register_hooks.php</code> per registrare nuovamente gli hook</li>
            <li>Pulisci la cache di PrestaShop dopo la modifica degli hook</li>
        </ul>
        
        <h4>Problema 4: Template Non Visualizzati</h4>
        <ul>
            <li>Verifica che i percorsi dei template siano corretti</li>
            <li>Assicurati che le variabili Smarty necessarie siano assegnate</li>
            <li>Controlla eventuali errori di sintassi nei template</li>
        </ul>
        
        <h4>Problema 5: Anteprima Non Generata</h4>
        <ul>
            <li>Verifica che GD sia correttamente installato e configurato</li>
            <li>Controlla che le funzioni di elaborazione immagini funzionino correttamente</li>
            <li>Verifica che i formati puzzle siano configurati correttamente</li>
        </ul>
    </div>
    
    <div style="margin-top: 30px; text-align: center; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
        <p><strong>Test completato</strong></p>
        <p>Copyright © <?php echo date('Y'); ?> - Tool Diagnostico Modulo Art Puzzle</p>
    </div>
</body>
</html>