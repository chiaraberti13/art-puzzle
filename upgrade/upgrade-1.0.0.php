<?php
/**
 * Aggiornamento del modulo Art Puzzle alla versione 1.0.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Funzione aggiornamento alla versione 1.0.0
 */
function upgrade_module_1_0_0($module)
{
    // Registra l'evento di aggiornamento
    require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/ArtPuzzleLogger.php');
    ArtPuzzleLogger::log('Inizio aggiornamento alla versione 1.0.0', 'INFO');
    
    $upgrade_success = true;
    
    // Aggiorna configurazioni
    $upgrade_success &= initDefaultSettings($module);
    
    // Crea le directory necessarie
    $upgrade_success &= createRequiredDirectories();
    
    // Aggiorna hook
    $upgrade_success &= registerMissingHooks($module);
    
    // Verifica correttezza associazione prodotti
    $upgrade_success &= validateProductAssociations();
    
    if ($upgrade_success) {
        ArtPuzzleLogger::log('Aggiornamento alla versione 1.0.0 completato con successo', 'INFO');
    } else {
        ArtPuzzleLogger::log('Errori durante l\'aggiornamento alla versione 1.0.0', 'ERROR');
    }
    
    return $upgrade_success;
}

/**
 * Inizializza le configurazioni di default se mancanti
 */
function initDefaultSettings($module)
{
    $success = true;
    
    // Configurazioni e valori di default
    $default_config = [
        'ART_PUZZLE_MAX_UPLOAD_SIZE' => '20',
        'ART_PUZZLE_ALLOWED_FILE_TYPES' => 'jpg,jpeg,png',
        'ART_PUZZLE_UPLOAD_FOLDER' => '/upload/',
        'ART_PUZZLE_SEND_PREVIEW_USER_EMAIL' => '1',
        'ART_PUZZLE_SEND_PREVIEW_ADMIN_EMAIL' => '1',
        'ART_PUZZLE_DEFAULT_BOX_TEXT' => 'Il mio puzzle',
        'ART_PUZZLE_MAX_BOX_TEXT_LENGTH' => '30',
        'ART_PUZZLE_ENABLE_ORIENTATION' => '1',
        'ART_PUZZLE_ENABLE_CROP_TOOL' => '1',
        'ART_PUZZLE_ENABLE_PDF_USER' => '1',
        'ART_PUZZLE_ENABLE_PDF_ADMIN' => '1',
        'ART_PUZZLE_ADMIN_EMAIL' => Configuration::get('PS_SHOP_EMAIL')
    ];
    
    // Configura colori predefiniti se non presenti
    if (!Configuration::get('ART_PUZZLE_BOX_COLORS')) {
        $default_colors = [
            ['box' => '#FFFFFF', 'text' => '#000000'],
            ['box' => '#000000', 'text' => '#FFFFFF'],
            ['box' => '#FF0000', 'text' => '#FFFFFF'],
            ['box' => '#0000FF', 'text' => '#FFFFFF']
        ];
        Configuration::updateValue('ART_PUZZLE_BOX_COLORS', json_encode($default_colors));
    }
    
    // Aggiorna o crea le configurazioni mancanti
    foreach ($default_config as $key => $value) {
        if (!Configuration::get($key)) {
            $success &= Configuration::updateValue($key, $value);
        }
    }
    
    return $success;
}

/**
 * Crea le directory necessarie per il modulo
 */
function createRequiredDirectories()
{
    $success = true;
    
    // Elenco directory da creare
    $directories = [
        _PS_MODULE_DIR_ . 'art_puzzle/upload/',
        _PS_MODULE_DIR_ . 'art_puzzle/logs/',
        _PS_MODULE_DIR_ . 'art_puzzle/views/fonts/'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            $success &= @mkdir($dir, 0755, true);
            
            // Crea file index.php di protezione
            if ($success && !file_exists($dir . 'index.php')) {
                $index_content = "<?php\n/**\n * Protezione directory\n */\nheader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');\nheader('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');\nheader('Cache-Control: no-store, no-cache, must-revalidate');\nheader('Cache-Control: post-check=0, pre-check=0', false);\nheader('Pragma: no-cache');\nheader('Location: ../index.php');\nexit;";
                $success &= @file_put_contents($dir . 'index.php', $index_content);
            }
        }
        
        // Verifica permessi di scrittura
        if (!is_writable($dir)) {
            $success &= @chmod($dir, 0755);
        }
    }
    
    return $success;
}

/**
 * Registra hook mancanti
 */
function registerMissingHooks($module)
{
    $success = true;
    
    $hooks = [
        'displayProductButtons',
        'displayHeader',
        'actionFrontControllerSetMedia',
        'displayProductExtraContent',
        'displayBackOfficeHeader',
        'displayAdminProductsExtra',
        'displayAdminProductsMainStepLeftColumnMiddle'
    ];
    
    foreach ($hooks as $hook) {
        if (!$module->isRegisteredInHook($hook)) {
            $success &= $module->registerHook($hook);
        }
    }
    
    return $success;
}

/**
 * Verifica e ripara le associazioni prodotti
 */
function validateProductAssociations()
{
    $success = true;
    
    // Ottieni la lista dei prodotti associati
    $product_ids = Configuration::get('ART_PUZZLE_PRODUCT_IDS');
    if ($product_ids) {
        $ids_array = explode(',', $product_ids);
        
        // Rimuovi eventuali ID vuoti o non numerici
        $cleaned_ids = [];
        foreach ($ids_array as $id) {
            $id = trim($id);
            if (is_numeric($id) && $id > 0) {
                // Verifica che il prodotto esista
                $product = new Product((int)$id);
                if (Validate::isLoadedObject($product)) {
                    $cleaned_ids[] = $id;
                    
                    // Assicurati che il prodotto sia impostato come personalizzabile
                    if (!$product->customizable) {
                        $product->customizable = 1;
                        $product->uploadable_files = 1;
                        $product->text_fields = 1;
                        $success &= $product->save();
                    }
                }
            }
        }
        
        // Aggiorna la configurazione con i soli ID validi
        $success &= Configuration::updateValue('ART_PUZZLE_PRODUCT_IDS', implode(',', $cleaned_ids));
    }
    
    return $success;
}