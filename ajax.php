<?php
/**
 * Art Puzzle Module - AJAX Handler
 */

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');
require_once(dirname(__FILE__).'/art_puzzle.php');
require_once(dirname(__FILE__).'/classes/ArtPuzzleLogger.php');

// Controllo di sicurezza
if (!defined('_PS_VERSION_')) {
    exit;
}

$art_puzzle = new Art_Puzzle();
$context = Context::getContext();

// Verifica che l'utente sia loggato
if (!$context->customer->isLogged() && !Tools::getValue('preview_mode')) {
    returnResponse(false, 'Utente non autenticato');
    exit;
}

// Verifica token CSRF
if (!Tools::getValue('token') || Tools::getValue('token') != Tools::getToken(false)) {
    returnResponse(false, 'Token di sicurezza non valido');
    exit;
}

// Verifica la richiesta AJAX
if (Tools::isSubmit('action')) {
    $action = Tools::getValue('action');
    
    try {
        switch ($action) {
            case 'savePuzzleCustomization':
                handleSavePuzzleCustomization();
                break;
                
            case 'checkImageQuality':
                handleCheckImageQuality();
                break;
                
            case 'getBoxColors':
                handleGetBoxColors();
                break;
                
            case 'getFonts':
                handleGetFonts();
                break;
                
            case 'togglePuzzleProduct':
                handleTogglePuzzleProduct();
                break;
                
            default:
                returnResponse(false, 'Azione non valida');
        }
    } catch (Exception $e) {
        ArtPuzzleLogger::log('Errore AJAX: ' . $e->getMessage(), 'ERROR');
        returnResponse(false, 'Errore: ' . $e->getMessage());
    }
} else {
    returnResponse(false, 'Nessuna azione specificata');
}

/**
 * Gestisce il salvataggio della personalizzazione del puzzle
 */
function handleSavePuzzleCustomization() {
    $context = Context::getContext();
    $data = json_decode(Tools::getValue('data'), true);
    
    if (!$data) {
        returnResponse(false, 'Dati non validi');
        return;
    }
    
    try {
        // Verifica ID prodotto
        $product_id = (int)$data['product_id'];
        if (!$product_id) {
            returnResponse(false, 'ID prodotto non valido');
            return;
        }
        
        // Verifica che il prodotto sia un puzzle personalizzabile
        $product_ids = explode(',', Configuration::get('ART_PUZZLE_PRODUCT_IDS'));
        if (!in_array((string)$product_id, $product_ids)) {
            returnResponse(false, 'Questo prodotto non è personalizzabile');
            return;
        }
        
        // Salva l'immagine caricata
        $upload_dir = _PS_MODULE_DIR_.'art_puzzle/upload/';
        
        // Crea la directory se non esiste
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Genera un nome file unico
        $filename = 'puzzle_'.time().'_'.Tools::passwdGen(8).'.png';
        $filepath = $upload_dir.$filename;
        
        // Salva l'immagine dal base64
        $image_data = $data['customization']['image'];
        $image_parts = explode(";base64,", $image_data);
        $image_base64 = isset($image_parts[1]) ? $image_parts[1] : null;
        
        if (!$image_base64) {
            returnResponse(false, 'Formato immagine non valido');
            return;
        }
        
        // Verifica validità immagine
        $image_decoded = base64_decode($image_base64);
        if (!$image_decoded) {
            returnResponse(false, 'Immagine non valida');
            return;
        }
        
        // Verifica che sia un'immagine reale
        $img = @imagecreatefromstring($image_decoded);
        if (!$img) {
            returnResponse(false, 'Il file caricato non è un\'immagine valida');
            return;
        }
        imagedestroy($img);
        
        // Salva l'immagine
        file_put_contents($filepath, $image_decoded);
        
        // Verifica e crea campi di personalizzazione
        $customization_fields = Db::getInstance()->executeS('
            SELECT cf.`id_customization_field`, cf.`type`
            FROM `'._DB_PREFIX_.'customization_field` cf
            WHERE cf.`id_product` = '.(int)$product_id
        );
        
        if (!$customization_fields) {
            // Se non ci sono campi di personalizzazione, creali
            createCustomizationFields($product_id);
            
            // Ricarica i campi
            $customization_fields = Db::getInstance()->executeS('
                SELECT cf.`id_customization_field`, cf.`type`
                FROM `'._DB_PREFIX_.'customization_field` cf
                WHERE cf.`id_product` = '.(int)$product_id
            );
        }
        
        // Assicurati che ci sia un carrello valido
        if (!$context->cart->id) {
            $context->cart->add();
            $context->cookie->id_cart = (int)$context->cart->id;
        }
        
        // Registra la personalizzazione
        $customization_id = getOrCreateCustomization($context->cart->id, $product_id);
        
        // Mappa i campi di personalizzazione
        foreach ($customization_fields as $field) {
            if ($field['type'] == 0) { // Campo File
                saveFileCustomization(
                    $customization_id, 
                    $field['id_customization_field'], 
                    $filepath, 
                    $filename
                );
            } elseif ($field['type'] == 1) { // Campo Testo
                $boxDetails = json_encode([
                    'text' => $data['customization']['boxText'],
                    'boxColor' => $data['customization']['boxColor'],
                    'textColor' => $data['customization']['textColor'],
                    'font' => $data['customization']['font']
                ]);
                
                saveTextCustomization(
                    $customization_id, 
                    $field['id_customization_field'], 
                    $boxDetails
                );
            }
        }
        
        // Invia email di notifica se richiesto
        if (Configuration::get('ART_PUZZLE_SEND_PREVIEW_USER_EMAIL') || 
            Configuration::get('ART_PUZZLE_SEND_PREVIEW_ADMIN_EMAIL')) {
            sendNotifications($data, $filepath);
        }
        
        // Pulisci vecchi file temporanei (file più vecchi di 24 ore)
        cleanupTempFiles($upload_dir, 86400); // 86400 secondi = 24 ore
        
        // Registra il successo nel log
        ArtPuzzleLogger::log('Personalizzazione puzzle salvata con successo. ID: ' . $customization_id);
        
        // Restituisci successo
        returnResponse(true, 'Personalizzazione salvata con successo', [
            'idCustomization' => $customization_id,
            'idProductAttribute' => 0, // 0 per i prodotti senza attributi
            'filename' => $filename
        ]);
        
    } catch (Exception $e) {
        ArtPuzzleLogger::log('Errore durante il salvataggio: ' . $e->getMessage(), 'ERROR');
        returnResponse(false, 'Errore: ' . $e->getMessage());
    }
}

/**
 * Gestisce il controllo della qualità dell'immagine
 */
function handleCheckImageQuality() {
    $image_data = Tools::getValue('imageData');
    $image_parts = explode(";base64,", $image_data);
    $image_base64 = isset($image_parts[1]) ? $image_parts[1] : null;
    
    if (!$image_base64) {
        returnResponse(false, 'Formato immagine non valido');
        return;
    }
    
    $image_decoded = base64_decode($image_base64);
    
    // Verifica che sia un'immagine valida
    $img = @imagecreatefromstring($image_decoded);
    if (!$img) {
        returnResponse(false, 'Immagine non valida');
        return;
    }
    
    // Ottieni dimensioni
    $width = imagesx($img);
    $height = imagesy($img);
    
    // Valuta qualità
    $quality = 'alta';
    $message = 'L\'immagine è di ottima qualità!';
    
    // Se l'immagine è troppo piccola, avvisa l'utente
    if ($width < 800 || $height < 800) {
        $quality = 'bassa';
        $message = 'L\'immagine è di bassa risoluzione. Potrebbe apparire pixelata sul puzzle.';
    } else if ($width < 1200 || $height < 1200) {
        $quality = 'media';
        $message = 'L\'immagine è di media risoluzione. La qualità dovrebbe essere accettabile.';
    }
    
    imagedestroy($img);
    
    returnResponse(true, $message, [
        'quality' => $quality,
        'width' => $width,
        'height' => $height
    ]);
}

/**
 * Restituisce i colori disponibili per la scatola
 */
function handleGetBoxColors() {
    $box_colors = Configuration::get('ART_PUZZLE_BOX_COLORS');
    $colors_array = json_decode($box_colors, true) ?: [];
    
    returnResponse(true, 'Colori caricati con successo', $colors_array);
}

/**
 * Restituisce i font disponibili
 */
function handleGetFonts() {
    $fonts = Configuration::get('ART_PUZZLE_FONTS');
    $fonts_array = $fonts ? explode(',', $fonts) : [];
    
    returnResponse(true, 'Font caricati con successo', $fonts_array);
}

/**
 * Crea i campi di personalizzazione per un prodotto
 */
function createCustomizationFields($product_id) {
    // Crea campo per l'immagine
    Db::getInstance()->execute('
        INSERT INTO `'._DB_PREFIX_.'customization_field` 
        (`id_product`, `type`, `required`) 
        VALUES ('.(int)$product_id.', 0, 0)'
    );
    
    $id_field_image = Db::getInstance()->Insert_ID();
    
    // Aggiungi label per tutte le lingue
    $languages = Language::getLanguages();
    foreach ($languages as $language) {
        Db::getInstance()->execute('
            INSERT INTO `'._DB_PREFIX_.'customization_field_lang` 
            (`id_customization_field`, `id_lang`, `name`) 
            VALUES (
                '.(int)$id_field_image.', 
                '.(int)$language['id_lang'].', 
                \'Immagine Puzzle\'
            )
        ');
    }
    
    // Crea campo per i dettagli della scatola
    Db::getInstance()->execute('
        INSERT INTO `'._DB_PREFIX_.'customization_field` 
        (`id_product`, `type`, `required`) 
        VALUES ('.(int)$product_id.', 1, 0)'
    );
    
    $id_field_box = Db::getInstance()->Insert_ID();
    
    // Aggiungi label per tutte le lingue
    foreach ($languages as $language) {
        Db::getInstance()->execute('
            INSERT INTO `'._DB_PREFIX_.'customization_field_lang` 
            (`id_customization_field`, `id_lang`, `name`) 
            VALUES (
                '.(int)$id_field_box.', 
                '.(int)$language['id_lang'].', 
                \'Dettagli Scatola\'
            )
        ');
    }
    
    // Imposta il prodotto come personalizzabile
    $product = new Product($product_id);
    $product->customizable = 1;
    $product->uploadable_files = 1;
    $product->text_fields = 1;
    $product->save();
}

/**
 * Ottieni o crea un ID personalizzazione
 */
function getOrCreateCustomization($id_cart, $id_product) {
    $context = Context::getContext();
    
    $id_customization = null;
    
    // Controlla se esiste già una personalizzazione per questo prodotto nel carrello
    $result = Db::getInstance()->getRow('
        SELECT `id_customization` 
        FROM `'._DB_PREFIX_.'customization` 
        WHERE `id_cart` = '.(int)$id_cart.' 
        AND `id_product` = '.(int)$id_product.'
        AND `in_cart` = 0
    ');
    
    if ($result && isset($result['id_customization'])) {
        $id_customization = (int)$result['id_customization'];
    } else {
        // Crea una nuova personalizzazione
        Db::getInstance()->execute('
            INSERT INTO `'._DB_PREFIX_.'customization` 
            (`id_cart`, `id_product`, `id_product_attribute`, `quantity`, `in_cart`) 
            VALUES (
                '.(int)$id_cart.', 
                '.(int)$id_product.', 
                0, 
                0, 
                0
            )
        ');
        
        $id_customization = Db::getInstance()->Insert_ID();
    }
    
    return $id_customization;
}

/**
 * Salva la personalizzazione di tipo file
 */
function saveFileCustomization($id_customization, $id_customization_field, $filepath, $filename) {
    // Controlla se esiste già una personalizzazione per questo campo
    $exists = Db::getInstance()->getValue('
        SELECT COUNT(*) 
        FROM `'._DB_PREFIX_.'customized_data` 
        WHERE `id_customization` = '.(int)$id_customization.' 
        AND `type` = 0 
        AND `index` = '.(int)$id_customization_field
    );
    
    if ($exists) {
        // Aggiorna la personalizzazione esistente
        Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'customized_data` 
            SET `value` = \''.pSQL($filename).'\' 
            WHERE `id_customization` = '.(int)$id_customization.' 
            AND `type` = 0 
            AND `index` = '.(int)$id_customization_field
        );
    } else {
        // Crea una nuova personalizzazione
        Db::getInstance()->execute('
            INSERT INTO `'._DB_PREFIX_.'customized_data` 
            (`id_customization`, `type`, `index`, `value`) 
            VALUES (
                '.(int)$id_customization.', 
                0, 
                '.(int)$id_customization_field.', 
                \''.pSQL($filename).'\'
            )
        ');
    }
}

/**
 * Salva la personalizzazione di tipo testo
 */
function saveTextCustomization($id_customization, $id_customization_field, $value) {
    // Controlla se esiste già una personalizzazione per questo campo
    $exists = Db::getInstance()->getValue('
        SELECT COUNT(*) 
        FROM `'._DB_PREFIX_.'customized_data` 
        WHERE `id_customization` = '.(int)$id_customization.' 
        AND `type` = 1 
        AND `index` = '.(int)$id_customization_field
    );
    
    if ($exists) {
        // Aggiorna la personalizzazione esistente
        Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'customized_data` 
            SET `value` = \''.pSQL($value).'\' 
            WHERE `id_customization` = '.(int)$id_customization.' 
            AND `type` = 1 
            AND `index` = '.(int)$id_customization_field
        );
    } else {
        // Crea una nuova personalizzazione
        Db::getInstance()->execute('
            INSERT INTO `'._DB_PREFIX_.'customized_data` 
            (`id_customization`, `type`, `index`, `value`) 
            VALUES (
                '.(int)$id_customization.', 
                1, 
                '.(int)$id_customization_field.', 
                \''.pSQL($value).'\'
            )
        ');
    }
}

/**
 * Invia le email di notifica
 */
function sendNotifications($data, $imagePath) {
    $context = Context::getContext();
    $product = new Product($data['product_id'], false, $context->language->id);
    
    // Prepara dati comuni
    $templateVars = [
        '{product_name}' => $product->name,
        '{box_text}' => $data['customization']['boxText'],
        '{box_color}' => $data['customization']['boxColor'],
        '{text_color}' => $data['customization']['textColor'],
        '{font}' => $data['customization']['font'],
        '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
        '{shop_url}' => Context::getContext()->link->getBaseLink(),
        '{shop_logo}' => _PS_IMG_DIR_ . Configuration::get('PS_LOGO')
    ];
    
    // Email all'utente
    if (Configuration::get('ART_PUZZLE_SEND_PREVIEW_USER_EMAIL') && $context->customer->id) {
        $customer = new Customer($context->customer->id);
        
        // Estendi template con valori specifici per il cliente
        $userTemplateVars = array_merge($templateVars, [
            '{my_account_url}' => Context::getContext()->link->getPageLink('my-account'),
            '{history_url}' => Context::getContext()->link->getPageLink('history')
        ]);
        
        // Allega l'immagine se abilitato
        $fileAttachment = null;
        if (Configuration::get('ART_PUZZLE_ENABLE_PDF_USER') && file_exists($imagePath)) {
            // Crea PDF se abilitato
            require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PDFGeneratorPuzzle.php');
            $pdfPath = _PS_MODULE_DIR_ . 'art_puzzle/upload/pdf_' . time() . '_' . Tools::passwdGen(8) . '.pdf';
            PDFGeneratorPuzzle::generateClientPDF($imagePath, $customer->firstname . ' ' . $customer->lastname, $pdfPath);
            
            $fileAttachment = [
                'content' => file_get_contents($pdfPath),
                'name' => 'puzzle_personalizzato.pdf',
                'mime' => 'application/pdf'
            ];
            
            // Pulisci il PDF temporaneo
            @unlink($pdfPath);
        }
        
        Mail::Send(
            (int)$context->language->id,
            'art_puzzle_user',
            'La tua personalizzazione del puzzle',
            $userTemplateVars,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null,
            null,
            $fileAttachment,
            null,
            _PS_MODULE_DIR_ . 'art_puzzle/mails/',
            false,
            (int)$context->shop->id
        );
    }
    
    // Email all'admin
    if (Configuration::get('ART_PUZZLE_SEND_PREVIEW_ADMIN_EMAIL')) {
        $adminEmail = Configuration::get('ART_PUZZLE_ADMIN_EMAIL');
        
        if (!empty($adminEmail)) {
            // Aggiungi info cliente per l'admin
            $adminTemplateVars = array_merge($templateVars, []);
            
            if ($context->customer->id) {
                $customer = new Customer($context->customer->id);
                $adminTemplateVars['{customer_name}'] = $customer->firstname . ' ' . $customer->lastname;
                $adminTemplateVars['{customer_email}'] = $customer->email;
            } else {
                $adminTemplateVars['{customer_name}'] = 'Visitatore';
                $adminTemplateVars['{customer_email}'] = 'N/A';
            }
            
            // Allega l'immagine o PDF
            $fileAttachment = null;
            if (Configuration::get('ART_PUZZLE_ENABLE_PDF_ADMIN') && file_exists($imagePath)) {
                // Crea PDF se abilitato
                require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PDFGeneratorPuzzle.php');
                $pdfPath = _PS_MODULE_DIR_ . 'art_puzzle/upload/pdf_admin_' . time() . '_' . Tools::passwdGen(8) . '.pdf';
                $boxImagePath = ""; // In una versione completa, qui andrebbe generata l'immagine della scatola
                PDFGeneratorPuzzle::generateAdminPDF($imagePath, $boxImagePath, $data['customization']['boxText'], $pdfPath);
                
                $fileAttachment = [
                    'content' => file_get_contents($pdfPath),
                    'name' => 'puzzle_personalizzato_admin.pdf',
                    'mime' => 'application/pdf'
                ];
                
                // Pulisci il PDF temporaneo
                @unlink($pdfPath);
            } elseif (file_exists($imagePath)) {
                $fileAttachment = [
                    'content' => file_get_contents($imagePath),
                    'name' => 'puzzle_preview.png',
                    'mime' => 'image/png'
                ];
            }
            
            Mail::Send(
                (int)$context->language->id,
                'art_puzzle_admin',
                'Nuova personalizzazione puzzle',
                $adminTemplateVars,
                $adminEmail,
                'Amministratore',
                null,
                null,
                $fileAttachment,
                null,
                _PS_MODULE_DIR_ . 'art_puzzle/mails/',
                false,
                (int)$context->shop->id
            );
        }
    }
}

/**
 * Pulisce i file temporanei
 */
function cleanupTempFiles($directory, $maxAge) {
    if (!is_dir($directory)) {
        return;
    }
    
    $now = time();
    $files = scandir($directory);
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..' || $file == 'index.php') {
            continue;
        }
        
        $filePath = $directory . $file;
        if (is_file($filePath)) {
            // Se il file è più vecchio del tempo massimo, eliminalo
            if ($now - filemtime($filePath) > $maxAge) {
                @unlink($filePath);
                ArtPuzzleLogger::log('File temporaneo eliminato: ' . $file);
            }
        }
    }
}

/**
 * Restituisce una risposta in formato JSON
 */
function returnResponse($success, $message, $data = []) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

/**
 * Gestisce l'attivazione/disattivazione di un prodotto come puzzle
 */
function handleTogglePuzzleProduct() {
    $product_id = (int)Tools::getValue('id_product');
    $enabled = (int)Tools::getValue('enabled');
    
    if (!$product_id) {
        returnResponse(false, 'ID prodotto non valido');
        return;
    }
    
    // Ottieni la configurazione attuale
    $product_ids = Configuration::get('ART_PUZZLE_PRODUCT_IDS');
    $product_ids_array = $product_ids ? explode(',', $product_ids) : [];
    
    // Rimuovi eventuali voci vuote
    $product_ids_array = array_filter($product_ids_array, function($v) { return trim($v) != ''; });
    
    if ($enabled) {
        // Aggiungi il prodotto se non è già presente
        if (!in_array((string)$product_id, $product_ids_array)) {
            $product_ids_array[] = (string)$product_id;
            
            // Imposta il prodotto come personalizzabile
            $product = new Product($product_id);
            $product->customizable = 1;
            $product->uploadable_files = 1;
            $product->text_fields = 1;
            $product->save();
        }
        
        returnResponse(true, 'Prodotto abilitato come puzzle personalizzabile');
    } else {
        // Rimuovi il prodotto se presente
        $product_ids_array = array_filter($product_ids_array, function($v) { return $v != (string)$product_id; });
        returnResponse(true, 'Personalizzazione puzzle disabilitata per questo prodotto');
    }
    
    // Aggiorna la configurazione
    Configuration::updateValue('ART_PUZZLE_PRODUCT_IDS', implode(',', $product_ids_array));
}