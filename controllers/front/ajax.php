<?php
/**
 * Art Puzzle - AJAX Controller
 */

class Art_PuzzleAjaxModuleFrontController extends ModuleFrontController
{
    /** @var bool Disattiva il rendering della colonna sinistra */
    public $display_column_left = false;
    
    /** @var bool Disattiva il rendering della colonna destra */
    public $display_column_right = false;
    
    /** @var bool Disattiva il rendering dell'header */
    public $display_header = false;
    
    /** @var bool Disattiva il rendering del footer */
    public $display_footer = false;
    
    /** @var string Imposta il content-type come JSON */
    protected $content_type = 'application/json';
    
    /**
     * Inizializzazione del controller
     *
     * @see FrontController::init()
     */
    public function init()
    {
        parent::init();
        
        // Verifica se è una richiesta AJAX
        if (!$this->isXmlHttpRequest()) {
            $this->returnResponse(false, 'Richiesta non valida');
            exit;
        }
        
        // Verifica token CSRF eccetto per le richieste di visualizzazione in anteprima
        if (!Tools::getValue('preview_mode') && 
            (!Tools::getValue('token') || Tools::getValue('token') != Tools::getToken(false))) {
            $this->returnResponse(false, 'Token di sicurezza non valido');
            exit;
        }
    }
    
    /**
     * Gestisce le richieste POST
     */
    public function postProcess()
    {
        // Verifica che ci sia un'azione specificata
        if (!Tools::isSubmit('action')) {
            $this->returnResponse(false, 'Nessuna azione specificata');
            return;
        }
        
        $action = Tools::getValue('action');
        
        try {
            switch ($action) {
                case 'savePuzzleCustomization':
                    $this->handleSavePuzzleCustomization();
                    break;
                
                case 'checkImageQuality':
                    $this->handleCheckImageQuality();
                    break;
                
                case 'getBoxColors':
                    $this->handleGetBoxColors();
                    break;
                
                case 'getFonts':
                    $this->handleGetFonts();
                    break;
                
                case 'checkDirectoryPermissions':
                    $this->handleCheckDirectoryPermissions();
                    break;
                
                default:
                    $this->returnResponse(false, 'Azione non valida: ' . $action);
            }
        } catch (Exception $e) {
            // Registra l'errore
            require_once(_PS_MODULE_DIR_.'art_puzzle/classes/ArtPuzzleLogger.php');
            ArtPuzzleLogger::log('Errore AJAX: ' . $e->getMessage() . ' - ' . $e->getTraceAsString(), 'ERROR');
            
            // Risponde con l'errore
            $this->returnResponse(false, 'Errore: ' . $e->getMessage());
        }
    }
    
    /**
     * Restituisce una risposta JSON
     */
    protected function returnResponse($success, $message, $data = [])
    {
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if (!empty($data)) {
            $response['data'] = $data;
        }
        
        die(json_encode($response));
    }
    
    /**
     * Verifica se è una richiesta AJAX
     */
    protected function isXmlHttpRequest()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }
    
    /**
     * Gestisce il salvataggio della personalizzazione del puzzle
     */
    protected function handleSavePuzzleCustomization()
    {
        // Verifica che l'utente sia loggato
        if (!$this->context->customer->isLogged()) {
            $this->returnResponse(false, 'Utente non autenticato');
            return;
        }
        
        $data = json_decode(Tools::getValue('data'), true);
        
        if (!$data) {
            $this->returnResponse(false, 'Dati non validi');
            return;
        }
        
        // Verifica ID prodotto
        $product_id = (int)$data['product_id'];
        if (!$product_id) {
            $this->returnResponse(false, 'ID prodotto non valido');
            return;
        }
        
        // Verifica che il prodotto sia un puzzle personalizzabile
        $product_ids = explode(',', Configuration::get('ART_PUZZLE_PRODUCT_IDS'));
        if (!in_array((string)$product_id, $product_ids)) {
            $this->returnResponse(false, 'Questo prodotto non è personalizzabile');
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
        
        $image_data = $data['customization']['image'];
$image_parts = explode(";base64,", $image_data);
$mime_info = explode(":", $image_parts[0]);
$mime_type = isset($mime_info[1]) ? explode(";", $mime_info[1])[0] : null;
$image_base64 = $image_parts[1] ?? null;

if (!$image_base64 || !$mime_type) {
    $this->returnResponse(false, 'Formato immagine non valido');
    return;
}

$allowed_mime = ['image/jpeg', 'image/png', 'image/gif'];
$ext_map = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
];

if (!in_array($mime_type, $allowed_mime)) {
    $this->returnResponse(false, 'Tipo di file non supportato. Utilizza solo immagini JPG, PNG o GIF.');
    return;
}

$image_decoded = base64_decode($image_base64);
        ArtPuzzleLogger::log('✅ base64_decode riuscita', 'INFO');
if (!$image_decoded) {
        ArtPuzzleLogger::log('❌ Errore: base64_decode fallita', 'ERROR');
    $this->returnResponse(false, 'Immagine non valida');
    return;
}

if (!@imagecreatefromstring($image_decoded)) {
        ArtPuzzleLogger::log('❌ Errore: imagecreatefromstring fallita', 'ERROR');
    $this->returnResponse(false, 'Il file caricato non è un\'immagine valida');
    return;
}

$ext = $ext_map[$mime_type];
$filename = 'puzzle_' . time() . '_' . Tools::passwdGen(8) . '.' . $ext;
$filepath = _PS_MODULE_DIR_ . 'art_puzzle/upload/' . $filename;

if (!file_put_contents($filepath, $image_decoded)) {
            ArtPuzzleLogger::log('❌ Errore: file_put_contents fallita. Path: ' . $filepath, 'ERROR');
            $this->returnResponse(false, 'Errore durante il salvataggio dell\'immagine');
            return;
        }
        ArtPuzzleLogger::log('✅ Immagine salvata correttamente in: ' . $filepath, 'INFO');

        
        // Verifica e crea campi di personalizzazione
        $customization_fields = Db::getInstance()->executeS('
            SELECT cf.`id_customization_field`, cf.`type`
            FROM `'._DB_PREFIX_.'customization_field` cf
            WHERE cf.`id_product` = '.(int)$product_id
        );
        
        if (!$customization_fields) {
            // Se non ci sono campi di personalizzazione, creali
            $this->createCustomizationFields($product_id);
            
            // Ricarica i campi
            $customization_fields = Db::getInstance()->executeS('
                SELECT cf.`id_customization_field`, cf.`type`
                FROM `'._DB_PREFIX_.'customization_field` cf
                WHERE cf.`id_product` = '.(int)$product_id
            );
        }
        
        // Assicurati che ci sia un carrello valido
        if (!$this->context->cart->id) {
            $this->context->cart->add();
            $this->context->cookie->id_cart = (int)$this->context->cart->id;
        }
        
        // Registra la personalizzazione
        $customization_id = $this->getOrCreateCustomization($this->context->cart->id, $product_id);
        
        // Mappa i campi di personalizzazione
        foreach ($customization_fields as $field) {
            if ($field['type'] == 0) { // Campo File
                $this->saveFileCustomization(
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
                
                $this->saveTextCustomization(
                    $customization_id, 
                    $field['id_customization_field'], 
                    $boxDetails
                );
            }
        }
        
        // Invia email di notifica se richiesto
        if (Configuration::get('ART_PUZZLE_SEND_PREVIEW_USER_EMAIL') || 
            Configuration::get('ART_PUZZLE_SEND_PREVIEW_ADMIN_EMAIL')) {
            $this->sendNotifications($data, $filepath);
        }
        
        // Pulisci vecchi file temporanei (file più vecchi di 24 ore)
        $this->cleanupTempFiles($upload_dir, 86400); // 86400 secondi = 24 ore
        
        // Registra il successo nel log
        require_once(_PS_MODULE_DIR_.'art_puzzle/classes/ArtPuzzleLogger.php');
        ArtPuzzleLogger::log('Personalizzazione puzzle salvata con successo. ID: ' . $customization_id);
        
        // Restituisci successo
        $this->returnResponse(true, 'Personalizzazione salvata con successo', [
            'idCustomization' => $customization_id,
            'idProductAttribute' => 0, // 0 per i prodotti senza attributi
            'filename' => $filename
        ]);
    }
    
    /**
     * Gestisce il controllo della qualità dell'immagine
     */
    protected function handleCheckImageQuality()
    {
        $image_data = Tools::getValue('imageData');
        $image_parts = explode(";base64,", $image_data);
        $image_base64 = isset($image_parts[1]) ? $image_parts[1] : null;
        
        if (!$image_base64) {
            $this->returnResponse(false, 'Formato immagine non valido');
            return;
        }
        
        $image_decoded = base64_decode($image_base64);
        ArtPuzzleLogger::log('✅ base64_decode riuscita', 'INFO');
        
        // Verifica che sia un'immagine valida
        $img = @imagecreatefromstring($image_decoded);
        if (!$img) {
            $this->returnResponse(false, 'Immagine non valida');
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
        
        ArtPuzzleLogger::log('✅ imagecreatefromstring riuscita', 'INFO');
        imagedestroy($img);
        
        $this->returnResponse(true, $message, [
            'quality' => $quality,
            'width' => $width,
            'height' => $height
        ]);
    }
    
    /**
     * Restituisce i colori disponibili per la scatola
     */
    protected function handleGetBoxColors()
    {
        $box_colors = Configuration::get('ART_PUZZLE_BOX_COLORS');
        $colors_array = json_decode($box_colors, true) ?: [];
        
        $this->returnResponse(true, 'Colori caricati con successo', $colors_array);
    }
    
    /**
     * Restituisce i font disponibili
     */
    protected function handleGetFonts()
    {
        $fonts = Configuration::get('ART_PUZZLE_FONTS');
        $fonts_array = $fonts ? explode(',', $fonts) : [];
        
        $this->returnResponse(true, 'Font caricati con successo', $fonts_array);
    }
    
    /**
     * Verifica i permessi delle directory
     */
    protected function handleCheckDirectoryPermissions()
    {
        $directories = [
            'upload' => _PS_MODULE_DIR_.'art_puzzle/upload/',
            'logs' => _PS_MODULE_DIR_.'art_puzzle/logs/',
            'fonts' => _PS_MODULE_DIR_.'art_puzzle/views/fonts/'
        ];
        
        $errors = [];
        
        foreach ($directories as $name => $path) {
            if (!file_exists($path)) {
                if (!@mkdir($path, 0755, true)) {
                    $errors[] = "Impossibile creare la directory '$name': $path";
                }
            } elseif (!is_writable($path)) {
                $errors[] = "La directory '$name' non è scrivibile: $path";
            }
        }
        
        if (empty($errors)) {
            $this->returnResponse(true, 'Tutte le directory sono scrivibili');
        } else {
            $this->returnResponse(false, implode('; ', $errors));
        }
    }
    
    /**
     * Crea i campi di personalizzazione per un prodotto
     */
    protected function createCustomizationFields($product_id)
    {
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
    protected function getOrCreateCustomization($id_cart, $id_product)
    {
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
    protected function saveFileCustomization($id_customization, $id_customization_field, $filepath, $filename)
    {
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
    protected function saveTextCustomization($id_customization, $id_customization_field, $value)
    {
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
    protected function sendNotifications($data, $imagePath)
    {
        $product = new Product($data['product_id'], false, $this->context->language->id);
        
        // Prepara dati comuni
        $templateVars = [
            '{product_name}' => $product->name,
            '{box_text}' => $data['customization']['boxText'],
            '{box_color}' => $data['customization']['boxColor'],
            '{text_color}' => $data['customization']['textColor'],
            '{font}' => $data['customization']['font'],
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{shop_url}' => $this->context->link->getBaseLink(),
            '{shop_logo}' => _PS_IMG_DIR_ . Configuration::get('PS_LOGO')
        ];
        
        // Email all'utente
        if (Configuration::get('ART_PUZZLE_SEND_PREVIEW_USER_EMAIL') && $this->context->customer->id) {
            $customer = new Customer($this->context->customer->id);
            
            // Estendi template con valori specifici per il cliente
            $userTemplateVars = array_merge($templateVars, [
                '{my_account_url}' => $this->context->link->getPageLink('my-account'),
                '{history_url}' => $this->context->link->getPageLink('history')
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
                (int)$this->context->language->id,
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
                (int)$this->context->shop->id
            );
        }
        
        // Email all'admin
        if (Configuration::get('ART_PUZZLE_SEND_PREVIEW_ADMIN_EMAIL')) {
            $adminEmail = Configuration::get('ART_PUZZLE_ADMIN_EMAIL');
            
            if (!empty($adminEmail)) {
                // Aggiungi info cliente per l'admin
                $adminTemplateVars = array_merge($templateVars, []);
                
                if ($this->context->customer->id) {
                    $customer = new Customer($this->context->customer->id);
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
                    (int)$this->context->language->id,
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
                    (int)$this->context->shop->id
                );
            }
        }
    }
    
    /**
     * Pulisce i file temporanei
     */
    protected function cleanupTempFiles($directory, $maxAge)
    {
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
                    
                    require_once(_PS_MODULE_DIR_.'art_puzzle/classes/ArtPuzzleLogger.php');
                    ArtPuzzleLogger::log('File temporaneo eliminato: ' . $file);
                }
            }
        }
    }
}