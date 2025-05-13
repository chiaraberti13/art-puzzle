<?php
/**
 * Art Puzzle Module - Preview Controller
 * Controller per generare le anteprime del puzzle e della scatola
 */

class Art_PuzzlePreviewModuleFrontController extends ModuleFrontController
{
    /** @var bool Disattiva il rendering della colonna sinistra */
    public $display_column_left = false;
    
    /** @var bool Disattiva il rendering della colonna destra */
    public $display_column_right = false;
    
    /** @var bool Disattiva il rendering dell'header */
    public $display_header = false;
    
    /** @var bool Disattiva il rendering del footer */
    public $display_footer = false;
    
    /**
     * Inizializzazione del controller
     */
    public function init()
    {
        parent::init();
        
        // Verifica token di sicurezza
        if (!Tools::getIsset('token') || Tools::getValue('token') != Tools::getToken(false)) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Token di sicurezza non valido'
            ]));
        }
    }
    
    /**
     * Elabora la richiesta POST
     */
    public function postProcess()
    {
        // Verifica che c'è una action
        if (!Tools::getIsset('action')) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Nessuna azione specificata'
            ]));
            return;
        }
        
        $action = Tools::getValue('action');
        
        switch ($action) {
            case 'generateBoxPreview':
                $this->generateBoxPreview();
                break;
                
            case 'generatePuzzlePreview':
                $this->generatePuzzlePreview();
                break;
                
            case 'generateSummaryPreview':
                $this->generateSummaryPreview();
                break;
                
            default:
                $this->ajaxDie(json_encode([
                    'success' => false,
                    'message' => 'Azione non valida'
                ]));
        }
    }
    
    /**
     * Genera anteprima della scatola del puzzle
     */
    protected function generateBoxPreview()
    {
        // Carica la classe del box manager
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
            
            $this->ajaxDie(json_encode([
                'success' => true,
                'preview' => $previewData
            ]));
        } else {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Impossibile generare l\'anteprima della scatola'
            ]));
        }
    }
    
    /**
     * Genera anteprima del puzzle
     */
    protected function generatePuzzlePreview()
    {
        // Carica le classi necessarie
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleImageProcessor.php');
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleFormatManager.php');
        
        // Recupera i parametri
        $formatId = Tools::getValue('format', '');
        $imageBase64 = Tools::getValue('image', '');
        $rotate = (int)Tools::getValue('rotate', 0);
        $cropData = Tools::getValue('crop', null);
        
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
                        
                        $this->ajaxDie(json_encode([
                            'success' => true,
                            'preview' => $previewData,
                            'format' => PuzzleFormatManager::getFormat($formatId)
                        ]));
                        return;
                    }
                }
            }
            
            // Se arriviamo qui, non abbiamo trovato l'immagine
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Immagine non trovata'
            ]));
            return;
        }
        
        // Estrai i dati dell'immagine dal base64
        $imageData = explode(';base64,', $imageBase64);
        if (count($imageData) != 2) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Formato immagine non valido'
            ]));
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
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Errore nel salvataggio dell\'immagine'
            ]));
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
            $this->ajaxDie(json_encode([
                'success' => true,
                'preview' => $previewData,
                'format' => PuzzleFormatManager::getFormat($formatId)
            ]));
        } else {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Impossibile generare l\'anteprima del puzzle'
            ]));
        }
    }
    
    /**
     * Genera anteprima del riepilogo per l'ordine (puzzle + scatola)
     */
    protected function generateSummaryPreview()
    {
        // Carica le classi necessarie
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
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Dati di personalizzazione mancanti'
            ]));
            return;
        }
        
        // Decodifica i dati
        $boxData = json_decode($boxDataJson, true);
        $cropData = $cropDataJson ? json_decode($cropDataJson, true) : null;
        
        if (!$boxData) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Dati della scatola non validi'
            ]));
            return;
        }
        
        // Recupera il formato
        $format = PuzzleFormatManager::getFormat($formatId);
        if (!$format) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Formato puzzle non valido'
            ]));
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
            $this->ajaxDie(json_encode([
                'success' => true,
                'puzzlePreview' => $puzzlePreview,
                'boxPreview' => $boxPreview,
                'format' => $format,
                'boxData' => $boxData
            ]));
        } else {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Impossibile generare le anteprime'
            ]));
        }
    }
}