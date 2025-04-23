<?php
/**
 * Script di verifica hook Art Puzzle
 * Accedi a questo file da: https://tuo-sito.com/modules/art_puzzle/check_hooks.php
 */

// Inizializzazione PrestaShop
include_once('../../config/config.inc.php');
include_once('../../init.php');

// Controllo di sicurezza - solo admin può accedere
if (!Context::getContext()->employee->isLoggedBack()) {
    die('Accesso negato. Solo gli amministratori possono accedere a questa pagina.');
}

echo '<h1>Verifica Hook Art Puzzle</h1>';

// Ottieni modulo
$module = Module::getInstanceByName('art_puzzle');
if (!$module) {
    die('Modulo Art Puzzle non trovato!');
}

// Verifica hooks
$hooks = [
    'displayProductButtons',
    'displayHeader',
    'actionFrontControllerSetMedia',
    'displayProductExtraContent',
    'displayBackOfficeHeader',
    'displayAdminProductsExtra',
    'displayAdminProductsMainStepLeftColumnMiddle'
];

echo '<h2>Stato registrazione hook:</h2>';
echo '<ul>';
foreach ($hooks as $hook) {
    $isRegistered = $module->isRegisteredInHook($hook);
    $color = $isRegistered ? 'green' : 'red';
    $status = $isRegistered ? 'Registrato' : 'Non registrato';
    
    echo "<li style='color:{$color}'>{$hook}: {$status}</li>";
    
    // Se non è registrato, permettiamo di registrarlo
    if (!$isRegistered) {
        echo " <a href='?register_hook={$hook}' style='color:blue'>[Registra]</a>";
    }
}
echo '</ul>';

// Registra hook se richiesto
if (isset($_GET['register_hook']) && in_array($_GET['register_hook'], $hooks)) {
    $hookToRegister = $_GET['register_hook'];
    $result = $module->registerHook($hookToRegister);
    
    if ($result) {
        echo "<p style='color:green'>Hook {$hookToRegister} registrato con successo!</p>";
    } else {
        echo "<p style='color:red'>Errore nella registrazione dell'hook {$hookToRegister}!</p>";
    }
    
    echo "<p><a href='check_hooks.php'>Torna alla verifica</a></p>";
}

// Verifica prodotti associati
echo '<h2>Prodotti associati:</h2>';
$productIds = Configuration::get('ART_PUZZLE_PRODUCT_IDS');
if (!$productIds) {
    echo "<p style='color:red'>Nessun prodotto associato al modulo!</p>";
} else {
    $ids = explode(',', $productIds);
    echo '<ul>';
    foreach ($ids as $id) {
        if (empty($id)) continue;
        
        $product = new Product((int)$id);
        if (Validate::isLoadedObject($product)) {
            echo "<li>ID: {$id} - Nome: {$product->name}</li>";
            
            // Verifica campi di personalizzazione
            $customizationFields = Db::getInstance()->executeS('
                SELECT cf.`id_customization_field`, cf.`type`, cfl.`name`
                FROM `'._DB_PREFIX_.'customization_field` cf
                LEFT JOIN `'._DB_PREFIX_.'customization_field_lang` cfl 
                ON cf.`id_customization_field` = cfl.`id_customization_field` 
                AND cfl.`id_lang` = '.(int)Context::getContext()->language->id.'
                WHERE cf.`id_product` = '.(int)$id
            );
            
            if (!$customizationFields) {
                echo "<span style='color:red'> - Nessun campo di personalizzazione! </span>";
                echo " <a href='?create_customization={$id}' style='color:blue'>[Crea campi]</a>";
            } else {
                echo "<ul>";
                foreach ($customizationFields as $field) {
                    $type = ($field['type'] == 0) ? 'File' : 'Testo';
                    echo "<li>Campo: {$field['name']} - Tipo: {$type}</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<li style='color:red'>ID: {$id} - Prodotto non valido!</li>";
        }
    }
    echo '</ul>';
}

// Crea campi di personalizzazione se richiesto
if (isset($_GET['create_customization']) && is_numeric($_GET['create_customization'])) {
    $productId = (int)$_GET['create_customization'];
    
    // Crea campo per l'immagine
    Db::getInstance()->execute('
        INSERT INTO `'._DB_PREFIX_.'customization_field` 
        (`id_product`, `type`, `required`) 
        VALUES ('.(int)$productId.', 0, 0)'
    );
    
    $idFieldImage = Db::getInstance()->Insert_ID();
    
    // Aggiungi label per tutte le lingue
    $languages = Language::getLanguages();
    foreach ($languages as $language) {
        Db::getInstance()->execute('
            INSERT INTO `'._DB_PREFIX_.'customization_field_lang` 
            (`id_customization_field`, `id_lang`, `name`) 
            VALUES (
                '.(int)$idFieldImage.', 
                '.(int)$language['id_lang'].', 
                \'Immagine Puzzle\'
            )
        ');
    }
    
    // Crea campo per i dettagli della scatola
    Db::getInstance()->execute('
        INSERT INTO `'._DB_PREFIX_.'customization_field` 
        (`id_product`, `type`, `required`) 
        VALUES ('.(int)$productId.', 1, 0)'
    );
    
    $idFieldBox = Db::getInstance()->Insert_ID();
    
    // Aggiungi label per tutte le lingue
    foreach ($languages as $language) {
        Db::getInstance()->execute('
            INSERT INTO `'._DB_PREFIX_.'customization_field_lang` 
            (`id_customization_field`, `id_lang`, `name`) 
            VALUES (
                '.(int)$idFieldBox.', 
                '.(int)$language['id_lang'].', 
                \'Dettagli Scatola\'
            )
        ');
    }
    
    // Imposta il prodotto come personalizzabile
    $product = new Product($productId);
    if (Validate::isLoadedObject($product)) {
        $product->customizable = 1;
        $product->uploadable_files = 1;
        $product->text_fields = 1;
        $product->save();
        
        echo "<p style='color:green'>Campi di personalizzazione creati con successo per il prodotto ID {$productId}!</p>";
    } else {
        echo "<p style='color:red'>Prodotto non valido!</p>";
    }
    
    echo "<p><a href='check_hooks.php'>Torna alla verifica</a></p>";
}

// Verifica permessi directory
$directories = [
    'upload' => _PS_MODULE_DIR_.'art_puzzle/upload/',
    'logs' => _PS_MODULE_DIR_.'art_puzzle/logs/',
    'fonts' => _PS_MODULE_DIR_.'art_puzzle/views/fonts/'
];

echo '<h2>Permessi directory:</h2>';
echo '<ul>';
foreach ($directories as $name => $path) {
    if (!file_exists($path)) {
        echo "<li style='color:red'>Directory {$name}: Non esiste!</li>";
        echo " <a href='?create_dir={$name}' style='color:blue'>[Crea]</a>";
    } elseif (!is_writable($path)) {
        echo "<li style='color:red'>Directory {$name}: Esiste ma non è scrivibile!</li>";
        echo " <a href='?fix_permissions={$name}' style='color:blue'>[Correggi permessi]</a>";
    } else {
        echo "<li style='color:green'>Directory {$name}: OK</li>";
    }
}
echo '</ul>';

// Crea directory se richiesto
if (isset($_GET['create_dir']) && isset($directories[$_GET['create_dir']])) {
    $dirName = $_GET['create_dir'];
    $dirPath = $directories[$dirName];
    
    if (@mkdir($dirPath, 0755, true)) {
        // Crea file index.php di protezione
        $indexContent = "<?php\n/**\n * Protezione directory\n */\nheader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');\nheader('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');\nheader('Cache-Control: no-store, no-cache, must-revalidate');\nheader('Cache-Control: post-check=0, pre-check=0', false);\nheader('Pragma: no-cache');\nheader('Location: ../index.php');\nexit;";
        @file_put_contents($dirPath . 'index.php', $indexContent);
        
        echo "<p style='color:green'>Directory {$dirName} creata con successo!</p>";
    } else {
        echo "<p style='color:red'>Errore nella creazione della directory {$dirName}!</p>";
    }
    
    echo "<p><a href='check_hooks.php'>Torna alla verifica</a></p>";
}

// Correggi permessi se richiesto
if (isset($_GET['fix_permissions']) && isset($directories[$_GET['fix_permissions']])) {
    $dirName = $_GET['fix_permissions'];
    $dirPath = $directories[$dirName];
    
    if (@chmod($dirPath, 0755)) {
        echo "<p style='color:green'>Permessi della directory {$dirName} corretti con successo!</p>";
    } else {
        echo "<p style='color:red'>Errore nella correzione dei permessi della directory {$dirName}!</p>";
    }
    
    echo "<p><a href='check_hooks.php'>Torna alla verifica</a></p>";
}