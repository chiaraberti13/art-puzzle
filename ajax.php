<?php
/**
 * Art Puzzle Module - AJAX Handler
 */

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');
require_once(dirname(__FILE__).'/art_puzzle.php');

// Controllo di sicurezza
if (!defined('_PS_VERSION_')) {
    exit;
}

$art_puzzle = new Art_Puzzle();

// Verifica la richiesta AJAX
if (Tools::isSubmit('action')) {
    $action = Tools::getValue('action');
    
    switch ($action) {
        case 'savePuzzleCustomization':
            handleSavePuzzleCustomization();
            break;
            
        default:
            returnError('Azione non valida');
    }
} else {
    returnError('Nessuna azione specificata');
}

/**
 * Gestisce il salvataggio della personalizzazione del puzzle
 */
function handleSavePuzzleCustomization() {
    $context = Context::getContext();
    $data = json_decode(Tools::getValue('data'), true);
    
    if (!$data) {
        returnError('Dati non validi');
        return;
    }
    
    try {
        // Verifica ID prodotto
        $product_id = (int)$data['product_id'];
        if (!$product_id) {
            returnError('ID prodotto non valido');
            return;
        }
        
        // Verifica che il prodotto sia un puzzle personalizzabile
        $product_ids = explode(',', Configuration::get('ART_PUZZLE_PRODUCT_IDS'));
        if (!in_array($product_id, $product_ids)) {
            returnError('Questo prodotto non è personalizzabile');
            return;
        }
        
        // Salva l'immagine caricata
        $upload_dir = _PS_MODULE_DIR_.'art_puzzle'.Configuration::get('ART_PUZZLE_UPLOAD_FOLDER');
        
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
            returnError('Formato immagine non valido');
            return;
        }
        
        $image_decoded = base64_decode($image_base64);
        file_put_contents($filepath, $image_decoded);
        
        // Crea la personalizzazione in PrestaShop
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
        sendNotifications($data, $filepath);
        
        // Restituisci successo
        echo json_encode([
            'success' => true,
            'idCustomization' => $customization_id,
            'idProductAttribute' => 0 // 0 per i prodotti senza attributi
        ]);
        
    } catch (Exception $e) {
        returnError('Errore: ' . $e->getMessage());
    }
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
        '{font}' => $data['customization']['font']
    ];
    
    // Email all'utente
    if (Configuration::get('ART_PUZZLE_SEND_PREVIEW_USER_EMAIL')) {
        $customer = new Customer($context->customer->id);
        
        // Allega l'immagine se abilitato
        $fileAttachment = null;
        if (Configuration::get('ART_PUZZLE_ENABLE_PDF_USER')) {
            $fileAttachment = [
                'content' => file_get_contents($imagePath),
                'name' => 'puzzle_preview.png',
                'mime' => 'image/png'
            ];
        }
        
        Mail::Send(
            (int)$context->language->id,
            'art_puzzle_user',
            'La tua personalizzazione del puzzle',
            $templateVars,
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
            // Allega l'immagine se abilitato
            $fileAttachment = null;
            if (Configuration::get('ART_PUZZLE_ENABLE_PDF_ADMIN')) {
                $fileAttachment = [
                    'content' => file_get_contents($imagePath),
                    'name' => 'puzzle_preview.png',
                    'mime' => 'image/png'
                ];
            }
            
            // Aggiungi info cliente per l'admin
            $adminTemplateVars = $templateVars;
            $adminTemplateVars['{customer_name}'] = $context->customer->firstname . ' ' . $context->customer->lastname;
            $adminTemplateVars['{customer_email}'] = $context->customer->email;
            
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
 * Restituisce un errore in formato JSON
 */
function returnError($message) {
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}