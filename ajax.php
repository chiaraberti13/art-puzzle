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
    
    /** @var bool Imposta la richiesta come AJAX */
    public $ajax = true;
    
    /**
     * Inizializzazione del controller
     *
     * @see FrontController::init()
     */
    public function init()
    {
        parent::init();
        
        // Verifica se è una richiesta AJAX
        if (!$this->isXmlHttpRequest() && !Tools::getValue('ajax')) {
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
                
                // Nuove azioni per il puzzle personalizzato
                case 'uploadImage':
                    $this->handleUploadImage();
                    break;
                    
                case 'getPuzzleFormats':
                    $this->handleGetPuzzleFormats();
                    break;
                    
                case 'getBoxTemplates':
                    $this->handleGetBoxTemplates();
                    break;
                    
                case 'generateBoxPreview':
                    $this->handleGenerateBoxPreview();
                    break;
                    
                case 'generatePuzzlePreview':
                    $this->handleGeneratePuzzlePreview();
                    break;
                    
                case 'generateSummaryPreview':
                    $this->handleGenerateSummaryPreview();
                    break;
                    
                case 'add_to_cart':
                    $this->handleAddToCart();
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
     * Gestisce il caricamento dell'immagine
     */
    protected function handleUploadImage()
    {
        // Debug
        require_once(_PS_MODULE_DIR_.'art_puzzle/classes/ArtPuzzleLogger.php');
        ArtPuzzleLogger::log('Tentativo di upload immagine - $_FILES: ' . print_r($_FILES, true));
        ArtPuzzleLogger::log('Tentativo di upload immagine - $_POST: ' . print_r($_POST, true));
        
        // Verifica se è presente un file
        if (!isset($_FILES['image']) && !isset($_FILES['puzzle_image']) && !isset($_FILES['file'])) {
            ArtPuzzleLogger::log('Nessun file trovato negli array $_FILES - chiavi disponibili: ' . implode(', ', array_keys($_FILES)));
            $this->returnResponse(false, 'Nessuna immagine caricata. Verifica che il nome del campo file sia corretto.');
            return;
        }
        
        // Determina il nome del campo file
        $fileKey = isset($_FILES['image']) ? 'image' : (isset($_FILES['puzzle_image']) ? 'puzzle_image' : 'file');
        
        // Verifica se c'è un file caricato
        if (empty($_FILES[$fileKey]['name'])) {
            $this->returnResponse(false, 'Nessuna immagine selezionata');
            return;
        }
        
        // Carica le classi necessarie
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleImageProcessor.php');
        
        // Configura la validazione
        $config = [
            'max_size' => (int)Configuration::get('ART_PUZZLE_MAX_UPLOAD_SIZE', 10) * 1024 * 1024,
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif']
        ];
        
        ArtPuzzleLogger::log('Configurazione validazione: ' . print_r($config, true));
        
        // Valida l'immagine caricata
        $validation = PuzzleImageProcessor::validateUploadedImage($_FILES[$fileKey], $config);
        
        ArtPuzzleLogger::log('Risultato validazione: ' . print_r($validation, true));
        
        if (!$validation['valid']) {
            $this->returnResponse(false, $validation['message']);
            return;
        }
        
        // Genera un nome file univoco
        $uploadDir = _PS_MODULE_DIR_ . 'art_puzzle/upload/';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                ArtPuzzleLogger::log('Errore: Impossibile creare la directory di upload');
                $this->returnResponse(false, 'Errore del server: impossibile creare la directory di upload');
                return;
            }
        }
        
        // Verifica se la directory è scrivibile
        if (!is_writable($uploadDir)) {
            ArtPuzzleLogger::log('Errore: La directory di upload non è scrivibile: ' . $uploadDir);
            $this->returnResponse(false, 'Errore del server: directory di upload non scrivibile');
            return;
        }
        
        $filename = PuzzleImageProcessor::generateUniqueFilename($validation['extension']);
        $destination = $uploadDir . $filename;
        
        // Sposta il file nella directory di upload
        if (!move_uploaded_file($validation['tmp_name'], $destination)) {
            ArtPuzzleLogger::log('Errore: Impossibile spostare il file caricato da ' . $validation['tmp_name'] . ' a ' . $destination);
            $this->returnResponse(false, 'Errore durante il salvataggio dell\'immagine');
            return;
        }
        
        // Analizza la qualità dell'immagine
        $quality = PuzzleImageProcessor::checkImageQuality($destination);
        
        // Salva il percorso dell'immagine nella sessione
        $sessionKey = 'art_puzzle_image';
        $this->context->cookie->{$sessionKey} = $destination;
        $this->context->cookie->write();
        
        // Registra il successo
        ArtPuzzleLogger::log('Immagine caricata con successo: ' . $destination);
        
        // Pulisci vecchi file temporanei
        PuzzleImageProcessor::cleanupTempFiles($uploadDir);
        
        // Calcola l'URL dell'immagine per anteprima
        $imageUrl = $this->context->link->getBaseLink() . 'modules/art_puzzle/upload/' . $filename;
        
        // Restituisci informazioni sull'immagine
        $this->returnResponse(true, 'Immagine caricata con successo', [
            'filename' => $filename,
            'path' => $destination,
            'url' => $imageUrl,
            'width' => $quality['width'],
            'height' => $quality['height'],
            'quality' => $quality['quality'],
            'message' => $quality['message'],
            'alert_type' => $quality['alert_type'],
            'suggested_formats' => $quality['suggested_formats']
        ]);
    }
    
    /**
     * Gestisce la richiesta dei formati puzzle disponibili
     */
    protected function handleGetPuzzleFormats()
    {
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleFormatManager.php');
        
        // Controlla se è richiesto un filtro per orientamento
        $orientation = Tools::getValue('orientation', '');
        
        if (!empty($orientation)) {
            $formats = PuzzleFormatManager::getFormatsByOrientation($orientation);
        } else {
            $formats = PuzzleFormatManager::getAllFormats();
        }
        
        // Aggiungi prezzi e informazioni aggiuntive ai formati
        foreach ($formats as $id => &$format) {
            // In una versione completa, qui andrebbero recuperati i prezzi dal database
            // o dalle combinazioni di prodotti. Per ora utilizziamo valori di esempio.
            $format['price'] = [
                'value' => 19.99 + (count(explode('_', $id)) * 5),
                'formatted' => number_format(19.99 + (count(explode('_', $id)) * 5), 2) . ' €'
            ];
        }
        
        $this->returnResponse(true, 'Formati puzzle caricati con successo', $formats);
    }
    
    /**
     * Gestisce la richiesta dei template di scatole disponibili
     */
    protected function handleGetBoxTemplates()
    {
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleBoxManager.php');
        
        $templates = PuzzleBoxManager::getAllBoxTemplates();
        $fonts = PuzzleBoxManager::getAllFonts();
        
        $this->returnResponse(true, 'Template scatole caricati con successo', [
            'templates' => $templates,
            'fonts' => $fonts
        ]);
    }
    
    /**
     * Gestisce la generazione dell'anteprima della scatola
     */
    protected function handleGenerateBoxPreview()
    {
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleBoxManager.php');
        
        // Recupera i parametri
        $boxData = [
            'template' => Tools::getValue('template', 'classic'),
            'color' => Tools::getValue('color', 'white'),
            'text' => Tools::getValue('text', 'Il mio puzzle'),
            'font' => Tools::getValue('font', 'default')
        ];
        
        // Recupera il percorso dell'immagine se necessario
        $imagePath = null;
        
        if ($boxData['template'] == 'photobox') {
            // Se è una scatola con foto, recupera l'immagine dalla sessione
            $imageSessionKey = 'art_puzzle_image';
            
            if (isset($this->context->cookie->{$imageSessionKey})) {
                $imagePath = $this->context->cookie->{$imageSessionKey};
                
                // Verifica che il file esista
                if (!file_exists($imagePath)) {
                    $imagePath = null;
                }
            }
        }
        
        // Genera l'anteprima
        $previewData = PuzzleBoxManager::generateBoxPreview($boxData, $imagePath, true);
        
        if ($previewData) {
            // Salva la configurazione della scatola nella sessione
            $sessionKey = 'art_puzzle_box_data';
            $this->context->cookie->{$sessionKey} = json_encode($boxData);
            $this->context->cookie->write();
            
            $this->returnResponse(true, 'Anteprima generata con successo', [
                'preview' => $previewData
            ]);
        } else {
            $this->returnResponse(false, 'Impossibile generare l\'anteprima della scatola');
        }
    }
    
    /**
     * Gestisce la generazione dell'anteprima del puzzle
     */
    protected function handleGeneratePuzzlePreview()
    {
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleImageProcessor.php');
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleFormatManager.php');
        
        // Recupera i parametri
        $formatId = Tools::getValue('format', '');
        $imageBase64 = Tools::getValue('image', '');
        $rotate = (int)Tools::getValue('rotate', 0);
        $cropData = Tools::getValue('crop', null);
        
        if (!empty($cropData) && is_string($cropData)) {
            $cropData = json_decode($cropData, true);
        }
        
        // Se non c'è immagine, cerca nella sessione
        if (empty($imageBase64)) {
            $imageSessionKey = 'art_puzzle_image';
            
            if (isset($this->context->cookie->{$imageSessionKey})) {
                $imagePath = $this->context->cookie->{$imageSessionKey};
                
                // Verifica che il file esista
                if (file_exists($imagePath)) {
                    // Processa l'immagine
                    $options = [
                        'format_id' => $formatId,
                        'rotate' => $rotate,
                        'return_base64' => true
                    ];
                    
                    // Aggiungi dati di ritaglio se presenti
                    if ($cropData && is_array($cropData)) {
                        $options['crop'] = true;
                        $options['crop_data'] = $cropData;
                    }
                    
                    $previewData = PuzzleImageProcessor::processImage($imagePath, null, $options);
                    
                    if ($previewData) {
                        // Salva le opzioni di formato nella sessione
                        $sessionKey = 'art_puzzle_format';
                        $this->context->cookie->{$sessionKey} = $formatId;
                        
                        // Salva anche le opzioni di ritaglio
                        if ($options['crop']) {
                            $cropSessionKey = 'art_puzzle_crop';
                            $this->context->cookie->{$cropSessionKey} = json_encode($options['crop_data']);
                        }
                        
                        $this->context->cookie->write();
                        
                        $this->returnResponse(true, 'Anteprima generata con successo', [
                            'preview' => $previewData,
                            'format' => PuzzleFormatManager::getFormat($formatId)
                        ]);
                        return;
                    }
                }
            }
            
            // Se arriviamo qui, non abbiamo trovato l'immagine
            $this->returnResponse(false, 'Immagine non trovata');
            return;
        }
        
        // Estrai i dati dell'immagine dal base64
        $imageData = explode(';base64,', $imageBase64);
        if (count($imageData) != 2) {
            $this->returnResponse(false, 'Formato immagine non valido');
            return;
        }
        
        // Salva temporaneamente l'immagine
        $tempDir = _PS_MODULE_DIR_ . 'art_puzzle/upload/';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $tempFilename = 'temp_' . time() . '_' . Tools::passwdGen(8) . '.png';
        $tempPath = $tempDir . $tempFilename;
        
        if (!file_put_contents($tempPath, base64_decode($imageData[1]))) {
            $this->returnResponse(false, 'Errore nel salvataggio dell\'immagine');
            return;
        }
        
        // Processa l'immagine
        $options = [
            'format_id' => $formatId,
            'rotate' => $rotate,
            'return_base64' => true
        ];
        
        // Aggiungi dati di ritaglio se presenti
        if ($cropData && is_array($cropData)) {
            $options['crop'] = true;
            $options['crop_data'] = $cropData;
        }
        
        $previewData = PuzzleImageProcessor::processImage($tempPath, null, $options);
        
        // Salva il percorso dell'immagine temporanea nella sessione
        $imageSessionKey = 'art_puzzle_image';
        $this->context->cookie->{$imageSessionKey} = $tempPath;
        
        // Salva le opzioni di formato nella sessione
        $formatSessionKey = 'art_puzzle_format';
        $this->context->cookie->{$formatSessionKey} = $formatId;
        
        // Salva anche le opzioni di ritaglio
        if ($options['crop']) {
            $cropSessionKey = 'art_puzzle_crop';
            $this->context->cookie->{$cropSessionKey} = json_encode($options['crop_data']);
        }
        
        $this->context->cookie->write();
        
        if ($previewData) {
            $this->returnResponse(true, 'Anteprima generata con successo', [
                'preview' => $previewData,
                'format' => PuzzleFormatManager::getFormat($formatId)
            ]);
        } else {
            $this->returnResponse(false, 'Impossibile generare l\'anteprima del puzzle');
        }
    }
    
    /**
     * Gestisce la generazione dell'anteprima riepilogativa
     */
    protected function handleGenerateSummaryPreview()
    {
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleBoxManager.php');
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleImageProcessor.php');
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleFormatManager.php');
        
        // Recupera dati dalla sessione
        $imageSessionKey = 'art_puzzle_image';
        $formatSessionKey = 'art_puzzle_format';
        $boxSessionKey = 'art_puzzle_box_data';
        $cropSessionKey = 'art_puzzle_crop';
        
        $imagePath = isset($this->context->cookie->{$imageSessionKey}) ? $this->context->cookie->{$imageSessionKey} : null;
        $formatId = isset($this->context->cookie->{$formatSessionKey}) ? $this->context->cookie->{$formatSessionKey} : null;
        $boxDataJson = isset($this->context->cookie->{$boxSessionKey}) ? $this->context->cookie->{$boxSessionKey} : null;
        $cropDataJson = isset($this->context->cookie->{$cropSessionKey}) ? $this->context->cookie->{$cropSessionKey} : null;
        
        if (!$imagePath || !file_exists($imagePath) || !$formatId || !$boxDataJson) {
            $this->returnResponse(false, 'Dati di personalizzazione mancanti');
            return;
        }
        
        // Decodifica i dati
        $boxData = json_decode($boxDataJson, true);
        $cropData = $cropDataJson ? json_decode($cropDataJson, true) : null;
        
        if (!$boxData) {
            $this->returnResponse(false, 'Dati della scatola non validi');
            return;
        }
        
        // Recupera il formato
        $format = PuzzleFormatManager::getFormat($formatId);
        if (!$format) {
            $this->returnResponse(false, 'Formato puzzle non valido');
            return;
        }
        
        // Genera anteprima puzzle
        $puzzleOptions = [
            'format_id' => $formatId,
            'return_base64' => true
        ];
        
        // Aggiungi dati di ritaglio se presenti
        if ($cropData) {
            $puzzleOptions['crop'] = true;
            $puzzleOptions['crop_data'] = $cropData;
        }
        
        $puzzlePreview = PuzzleImageProcessor::processImage($imagePath, null, $puzzleOptions);
        
        // Genera anteprima scatola
        $boxPreview = PuzzleBoxManager::generateBoxPreview($boxData, $imagePath, true);
        
        if ($puzzlePreview && $boxPreview) {
            $this->returnResponse(true, 'Anteprime generate con successo', [
                'puzzlePreview' => $puzzlePreview,
                'boxPreview' => $boxPreview,
                'format' => $format,
                'boxData' => $boxData
            ]);
        } else {
            $this->returnResponse(false, 'Impossibile generare le anteprime');
        }
    }
    
    /**
     * Gestisce l'aggiunta al carrello
     */
    protected function handleAddToCart()
    {
        // Verifica che l'utente abbia confermato la personalizzazione
        if (!Tools::getValue('confirm-customization')) {
            $this->returnResponse(false, 'È necessario confermare la personalizzazione');
            return;
        }
        
        // Recupera l'ID prodotto
        $id_product = (int)Tools::getValue('id_product');
        if (!$id_product) {
            $this->returnResponse(false, 'ID prodotto non valido');
            return;
        }
        
        // Verifica che sia un puzzle personalizzabile
        $module = Module::getInstanceByName('art_puzzle');
        if (!$module->isPuzzleProduct($id_product)) {
            $this->returnResponse(false, 'Questo prodotto non è personalizzabile come puzzle');
            return;
        }
        
        // Recupera dati dalla sessione
        $imageSessionKey = 'art_puzzle_image';
        $formatSessionKey = 'art_puzzle_format';
        $boxSessionKey = 'art_puzzle_box_data';
        $cropSessionKey = 'art_puzzle_crop';
        
        $imagePath = isset($this->context->cookie->{$imageSessionKey}) ? $this->context->cookie->{$imageSessionKey} : null;
        $formatId = isset($this->context->cookie->{$formatSessionKey}) ? $this->context->cookie->{$formatSessionKey} : null;
        $boxDataJson = isset($this->context->cookie->{$boxSessionKey}) ? $this->context->cookie->{$boxSessionKey} : null;
        $cropDataJson = isset($this->context->cookie->{$cropSessionKey}) ? $this->context->cookie->{$cropSessionKey} : null;
        
        if (!$imagePath || !file_exists($imagePath) || !$formatId || !$boxDataJson) {
            $this->returnResponse(false, 'Dati di personalizzazione mancanti');
            return;
        }
        
        // Carica le classi necessarie
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleBoxManager.php');
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleImageProcessor.php');
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleFormatManager.php');
        
        // Decodifica i dati
        $boxData = json_decode($boxDataJson, true);
        $cropData = $cropDataJson ? json_decode($cropDataJson, true) : null;
        
        // Prepara i dati per il salvataggio della personalizzazione
        $format = PuzzleFormatManager::getFormat($formatId);
        
        if (!$format) {
            $this->returnResponse(false, 'Formato puzzle non valido');
            return;
        }
        
        // Genera un nome file definitivo e copia l'immagine
        $uploadDir = _PS_MODULE_DIR_ . 'art_puzzle/upload/';
        $finalFilename = 'puzzle_' . time() . '_' . Tools::passwdGen(8) . '.png';
        $finalPath = $uploadDir . $finalFilename;
        
        // Elabora l'immagine con le impostazioni di formato e ritaglio finali
        $processOptions = [
            'format_id' => $formatId,
            'quality' => 100
        ];
        
        if ($cropData) {
            $processOptions['crop'] = true;
            $processOptions['crop_data'] = $cropData;
        }
        
        $processedImage = PuzzleImageProcessor::processImage($imagePath, $finalPath, $processOptions);
        
        if (!$processedImage) {
            $this->returnResponse(false, 'Errore durante l\'elaborazione dell\'immagine finale');
            return;
        }
        
        // Verifica e crea campi di personalizzazione
        $customization_fields = Db::getInstance()->executeS('
            SELECT cf.`id_customization_field`, cf.`type`
            FROM `'._DB_PREFIX_.'customization_field` cf
            WHERE cf.`id_product` = '.(int)$id_product
        );
        
        if (!$customization_fields) {
            // Se non ci sono campi di personalizzazione, creali
            $this->createCustomizationFields($id_product);
            
            // Ricarica i campi
            $customization_fields = Db::getInstance()->executeS('
                SELECT cf.`id_customization_field`, cf.`type`
                FROM `'._DB_PREFIX_.'customization_field` cf
                WHERE cf.`id_product` = '.(int)$id_product
            );
        }
        
        // Assicurati che ci sia un carrello valido
        if (!$this->context->cart->id) {
            $this->context->cart->add();
            $this->context->cookie->id_cart = (int)$this->context->cart->id;
        }
        
        // Registra la personalizzazione
        $customization_id = $this->getOrCreateCustomization($this->context->cart->id, $id_product);
        
        // Dati completi di personalizzazione
        $customization_data = [
            'format_id' => $formatId,
            'format_name' => $format['name'],
            'dimensions' => $format['dimensions'],
            'pieces' => $format['pieces'],
            'orientation' => $format['orientation'],
            'box_template' => $boxData['template'],
            'box_color' => $boxData['color'],
            'box_text' => $boxData['text'],
            'box_font' => $boxData['font'],
            'image_path' => $finalPath
        ];
        
        // Mappa i campi di personalizzazione
        foreach ($customization_fields as $field) {
            if ($field['type'] == 0) { // Campo File (immagine)
                $this->saveFileCustomization(
                    $customization_id, 
                    $field['id_customization_field'], 
                    $finalPath, 
                    $finalFilename
                );
            } elseif ($field['type'] == 1) { // Campo Testo (dati personalizzazione)
                $this->saveTextCustomization(
                    $customization_id, 
                    $field['id_customization_field'], 
                    json_encode($customization_data)
                );
            }
        }
        
        // Aggiungi al carrello
        $this->context->cart->updateQty(1, $id_product, 0, $customization_id);
        
        // Aggiorna il carrello se è già stato creato
        if (isset($this->context->cookie->id_cart)) {
            $this->context->cart = new Cart($this->context->cookie->id_cart);
        }
        
        // Registra il successo nel log
        require_once(_PS_MODULE_DIR_.'art_puzzle/classes/ArtPuzzleLogger.php');
        ArtPuzzleLogger::log('Puzzle personalizzato aggiunto al carrello. ID Customization: ' . $customization_id);
        
        // Invia email di notifica se richiesto
        if (Configuration::get('ART_PUZZLE_SEND_PREVIEW_USER_EMAIL') || 
            Configuration::get('ART_PUZZLE_SEND_PREVIEW_ADMIN_EMAIL')) {
            $this->sendNotifications($customization_data, $finalPath);
        }
        
        // Pulisci vecchi file temporanei (file più vecchi di 24 ore)
        PuzzleImageProcessor::cleanupTempFiles($uploadDir, 86400); // 86400 secondi = 24 ore
        
        // Restituisci successo
        $this->returnResponse(true, 'Puzzle personalizzato aggiunto al carrello', [
            'idCustomization' => $customization_id,
            'idProduct' => $id_product,
            'idProductAttribute' => 0, // 0 per i prodotti senza attributi
            'cartUrl' => $this->context->link->getPageLink('cart', null, null, ['action' => 'show'])
        ]);
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
        
        // Salva l'immagine dal base64
        $image_data = $data['customization']['image'];
        $image_parts = explode(";base64,", $image_data);
        $image_base64 = isset($image_parts[1]) ? $image_parts[1] : null;
        
        if (!$image_base64) {
            $this->returnResponse(false, 'Formato immagine non valido');
            return;
        }
        
        // Verifica validità immagine
        $image_decoded = base64_decode($image_base64);
        if (!$image_decoded) {
            $this->returnResponse(false, 'Immagine non valida');
            return;
        }
        
        // Verifica che sia un'immagine reale
        $img = @imagecreatefromstring($image_decoded);
        if (!$img) {
            $this->returnResponse(false, 'Il file caricato non è un\'immagine valida');
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