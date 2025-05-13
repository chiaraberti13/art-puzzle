<?php
/**
 * Art Puzzle - Processor immagini
 * Classe che gestisce l'elaborazione delle immagini per i puzzle personalizzati
 */

class PuzzleImageProcessor
{
    /**
     * Valida un'immagine caricata tramite form
     * @param array $file File caricato ($_FILES[])
     * @param array $config Configurazione (max_size, allowed_extensions)
     * @return array Risultato della validazione
     */
    public static function validateUploadedImage($file, $config = [])
    {
        // Debug
        if (!isset($file) || empty($file)) {
            return [
                'valid' => false,
                'message' => 'Nessuna immagine caricata'
            ];
        }
        
        // Configurazione predefinita
        $defaultConfig = [
            'max_size' => 10 * 1024 * 1024, // 10MB
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif']
        ];
        
        // Unisce la configurazione predefinita con quella fornita
        $config = array_merge($defaultConfig, $config);
        
        // Verifica errori di caricamento
        if (!isset($file['error']) || is_array($file['error'])) {
            return [
                'valid' => false,
                'message' => 'Parametri non validi'
            ];
        }
        
        // Verifica codici di errore specifici
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return [
                    'valid' => false,
                    'message' => 'File troppo grande'
                ];
            case UPLOAD_ERR_PARTIAL:
                return [
                    'valid' => false,
                    'message' => 'File caricato solo parzialmente'
                ];
            case UPLOAD_ERR_NO_FILE:
                return [
                    'valid' => false,
                    'message' => 'Nessun file caricato'
                ];
            case UPLOAD_ERR_NO_TMP_DIR:
                return [
                    'valid' => false,
                    'message' => 'Directory temporanea mancante'
                ];
            case UPLOAD_ERR_CANT_WRITE:
                return [
                    'valid' => false,
                    'message' => 'Impossibile scrivere il file su disco'
                ];
            case UPLOAD_ERR_EXTENSION:
                return [
                    'valid' => false,
                    'message' => 'Caricamento file interrotto da un\'estensione'
                ];
            default:
                return [
                    'valid' => false,
                    'message' => 'Errore sconosciuto durante il caricamento'
                ];
        }
        
        // Verifica dimensione file
        if ($file['size'] > $config['max_size']) {
            return [
                'valid' => false,
                'message' => 'Il file è troppo grande. Dimensione massima: ' . self::formatSize($config['max_size'])
            ];
        }
        
        // Verifica estensione prima del MIME
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Verifica che l'estensione sia valida
        if (!in_array($extension, $config['allowed_extensions'])) {
            return [
                'valid' => false,
                'message' => 'Estensione file non valida. Sono consentite solo: ' . implode(', ', $config['allowed_extensions'])
            ];
        }
        
        // Metodo alternativo per verificare il tipo MIME usando getimagesize
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return [
                'valid' => false,
                'message' => 'Il file non è un\'immagine valida'
            ];
        }
        
        // Verifica il tipo di immagine
        $imageType = $imageInfo[2];
        // Log diagnostico più completo
        file_put_contents(_PS_ROOT_DIR_.'/log_validate_debug.txt', print_r([
            'image_type' => $imageType,
            'image_info' => $imageInfo,
            'extension' => $extension,
            'allowed_extensions' => $config['allowed_extensions'],
            'mime_type' => function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : 'function not available',
            'file_info' => $file
        ], true));

        // Controllo più flessibile dei tipi di immagine
        $isValidType = false;
        
        // Verifica prima per le costanti IMAGETYPE
        if (in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF])) {
            $isValidType = true;
        }
        
        // Verifica alternativa basata sull'estensione
        if (!$isValidType && in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])) {
            if (function_exists('mime_content_type')) {
                // Ottieni il tipo MIME usando mime_content_type
                $mimeType = mime_content_type($file['tmp_name']);
                if (strpos($mimeType, 'image/') === 0) {
                    $isValidType = true;
                }
            } else {
                // Se mime_content_type non è disponibile, fidati dell'estensione
                $isValidType = true;
            }
        }
        
        if (!$isValidType) {
            return [
                'valid' => false,
                'message' => 'Tipo di file non valido. Sono consentiti solo immagini JPG, PNG o GIF'
            ];
        }
        
        // Tutte le verifiche passate
        return [
            'valid' => true,
            'tmp_name' => $file['tmp_name'],
            'name' => $file['name'],
            'size' => $file['size'],
            'extension' => $extension,
            'width' => $imageInfo[0],
            'height' => $imageInfo[1]
        ];
    }
    
    /**
     * Verifica se un file è un'immagine valida
     * @param string $path Percorso del file
     * @return bool True se è un'immagine valida
     */
    public static function isValidImage($path)
    {
        // Tenta di ottenere le informazioni sull'immagine
        $imageInfo = @getimagesize($path);
        
        // Se getimagesize fallisce, non è un'immagine valida
        if ($imageInfo === false) {
            return false;
        }
        
        // Verifica che il tipo di immagine sia supportato
        $imageType = $imageInfo[2];
        
        return in_array($imageType, [
            IMAGETYPE_JPEG,
            IMAGETYPE_PNG,
            IMAGETYPE_GIF
        ]);
    }
    
    /**
     * Genera un nome file unico
     * @param string $extension Estensione del file
     * @return string Nome file unico
     */
    public static function generateUniqueFilename($extension)
    {
        $base = 'puzzle_' . time() . '_' . substr(md5(uniqid(rand(), true)), 0, 8);
        return $base . '.' . $extension;
    }
    
    /**
     * Controlla la qualità dell'immagine
     * @param string $path Percorso dell'immagine
     * @return array Informazioni sulla qualità
     */
    public static function checkImageQuality($path)
    {
        // Ottieni le dimensioni dell'immagine
        $imageInfo = getimagesize($path);
        
        if ($imageInfo === false) {
            return [
                'quality' => 'unknown',
                'message' => 'Impossibile determinare la qualità dell\'immagine',
                'alert_type' => 'warning',
                'width' => 0,
                'height' => 0,
                'suggested_formats' => []
            ];
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        // Determina la qualità in base alle dimensioni
        $quality = 'alta';
        $message = 'L\'immagine è di ottima qualità per la stampa del puzzle!';
        $alertType = 'success';
        $suggestedFormats = [];
        
        // Se l'immagine è troppo piccola
        if ($width < 800 || $height < 800) {
            $quality = 'bassa';
            $message = 'L\'immagine è di bassa risoluzione. Il puzzle potrebbe apparire pixelato.';
            $alertType = 'danger';
            
            // Suggerisci formati piccoli
            $suggestedFormats = ['small'];
        } else if ($width < 1200 || $height < 1200) {
            $quality = 'media';
            $message = 'L\'immagine è di media risoluzione. La qualità dovrebbe essere accettabile.';
            $alertType = 'warning';
            
            // Suggerisci formati medi
            $suggestedFormats = ['small', 'medium'];
        } else {
            // Per immagini di alta qualità, tutti i formati sono disponibili
            $suggestedFormats = ['small', 'medium', 'large'];
        }
        
        // Aggiungi informazioni sull'orientamento
        $orientation = self::determineOrientation($width, $height);
        
        return [
            'quality' => $quality,
            'message' => $message,
            'alert_type' => $alertType,
            'width' => $width,
            'height' => $height,
            'orientation' => $orientation,
            'suggested_formats' => $suggestedFormats
        ];
    }
    
    /**
     * Determina l'orientamento dell'immagine
     * @param int $width Larghezza
     * @param int $height Altezza
     * @return string Orientamento (landscape, portrait, square)
     */
    public static function determineOrientation($width, $height)
    {
        $ratio = $width / $height;
        
        if ($ratio > 1.2) {
            return 'landscape';
        } else if ($ratio < 0.8) {
            return 'portrait';
        } else {
            return 'square';
        }
    }
    
    /**
     * Processa un'immagine per adattarla al formato puzzle
     * @param string $srcPath Percorso immagine sorgente
     * @param string|null $destPath Percorso di destinazione (se null, non salva)
     * @param array $options Opzioni di elaborazione
     * @return mixed Percorso risultato o data URL se return_base64 è true
     */
    public static function processImage($srcPath, $destPath = null, $options = [])
    {
        // Opzioni predefinite
        $defaultOptions = [
            'format_id' => '',        // ID del formato puzzle
            'rotate' => 0,            // Rotazione in gradi
            'crop' => false,          // Abilita ritaglio
            'crop_data' => null,      // Dati di ritaglio (x, y, width, height)
            'quality' => 90,          // Qualità JPEG (0-100)
            'return_base64' => false  // Restituisce un data URL invece del percorso
        ];
        
        // Unisce le opzioni predefinite con quelle fornite
        $options = array_merge($defaultOptions, $options);
        
        // Carica l'immagine sorgente
        $sourceImage = self::loadImage($srcPath);
        
        if (!$sourceImage) {
            return false;
        }
        
        // Ottieni le dimensioni originali
        $srcWidth = imagesx($sourceImage);
        $srcHeight = imagesy($sourceImage);
        
        // Se è richiesta la rotazione
        if ($options['rotate'] !== 0) {
            $sourceImage = self::rotateImage($sourceImage, $options['rotate']);
            
            // Aggiorna le dimensioni dopo la rotazione
            $srcWidth = imagesx($sourceImage);
            $srcHeight = imagesy($sourceImage);
        }
        
        // Se è richiesto il ritaglio
        if ($options['crop'] && $options['crop_data']) {
            $sourceImage = self::cropImage(
                $sourceImage,
                $options['crop_data']['x'],
                $options['crop_data']['y'],
                $options['crop_data']['width'],
                $options['crop_data']['height']
            );
            
            // Aggiorna le dimensioni dopo il ritaglio
            $srcWidth = imagesx($sourceImage);
            $srcHeight = imagesy($sourceImage);
        }
        
        // Se è specificato un formato, adatta l'immagine al formato
        if (!empty($options['format_id'])) {
            // Importa la classe del formato
            require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleFormatManager.php');
            
            // Ottiene le informazioni sul formato
            $format = PuzzleFormatManager::getFormat($options['format_id']);
            
            if ($format) {
                // Calcola le dimensioni di destinazione
                list($destWidth, $destHeight) = self::calculateDestinationSize(
                    $srcWidth,
                    $srcHeight,
                    $format['width'],
                    $format['height'],
                    $format['ratio']
                );
                
                // Ridimensiona l'immagine mantenendo le proporzioni
                $sourceImage = self::resizeImage($sourceImage, $destWidth, $destHeight);
            }
        }
        
        // Se è richiesto il salvataggio su file
        if ($destPath !== null) {
            // Salva l'immagine
            $result = self::saveImage($sourceImage, $destPath, $options['quality']);
            
            // Libera la memoria
            imagedestroy($sourceImage);
            
            return $result ? $destPath : false;
        }
        
        // Se è richiesto il data URL
        if ($options['return_base64']) {
            // Genera il data URL
            $dataUrl = self::getImageDataUrl($sourceImage, $options['quality']);
            
            // Libera la memoria
            imagedestroy($sourceImage);
            
            return $dataUrl;
        }
        
        // Restituisce l'immagine elaborata
        return $sourceImage;
    }
    
    /**
     * Carica un'immagine da un percorso
     * @param string $path Percorso dell'immagine
     * @return resource|false Immagine caricata o false in caso di errore
     */
    public static function loadImage($path)
    {
        // Ottieni il tipo di immagine
        $imageInfo = getimagesize($path);
        
        if ($imageInfo === false) {
            return false;
        }
        
        $imageType = $imageInfo[2];
        
        // Carica l'immagine in base al tipo
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
                
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
                
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
                
            default:
                return false;
        }
    }
    
    /**
     * Salva un'immagine su file
     * @param resource $image Immagine da salvare
     * @param string $path Percorso di destinazione
     * @param int $quality Qualità di compressione (0-100)
     * @return bool True se il salvataggio è riuscito
     */
    public static function saveImage($image, $path, $quality = 90)
    {
        // Crea la directory se non esiste
        $dir = dirname($path);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Determina il tipo di immagine dall'estensione
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        // Salva l'immagine in base al tipo
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($image, $path, $quality);
                
            case 'png':
                // Converte la qualità JPEG (0-100) in qualità PNG (0-9)
                $pngQuality = round(9 - (($quality / 100) * 9));
                return imagepng($image, $path, $pngQuality);
                
            case 'gif':
                return imagegif($image, $path);
                
            default:
                // Default a JPEG
                return imagejpeg($image, $path, $quality);
        }
    }
    
    /**
     * Genera un data URL da un'immagine
     * @param resource $image Immagine
     * @param int $quality Qualità di compressione (0-100)
     * @return string Data URL
     */
    public static function getImageDataUrl($image, $quality = 90)
    {
        // Inizia l'output buffering
        ob_start();
        
        // Stampa l'immagine nel buffer
        imagepng($image, null, round(9 - (($quality / 100) * 9)));
        
        // Ottieni il contenuto del buffer
        $imageData = ob_get_clean();
        
        // Codifica in base64
        $base64 = base64_encode($imageData);
        
        // Restituisci il data URL
        return 'data:image/png;base64,' . $base64;
    }
    
    /**
     * Ruota un'immagine
     * @param resource $image Immagine da ruotare
     * @param float $degrees Gradi di rotazione
     * @return resource Immagine ruotata
     */
    public static function rotateImage($image, $degrees)
    {
        // Calcola l'angolo in radianti
        $radians = deg2rad($degrees);
        
        // Ottieni le dimensioni originali
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Ruota l'immagine
        $rotated = imagerotate($image, -$degrees, imagecolorallocatealpha($image, 0, 0, 0, 127));
        
        // Libera la memoria dell'immagine originale
        imagedestroy($image);
        
        // Se l'immagine è PNG o GIF, mantieni la trasparenza
        imagesavealpha($rotated, true);
        
        return $rotated;
    }
    
    /**
     * Ritaglia un'immagine
     * @param resource $image Immagine da ritagliare
     * @param int $x Coordinata X di inizio
     * @param int $y Coordinata Y di inizio
     * @param int $width Larghezza del ritaglio
     * @param int $height Altezza del ritaglio
     * @return resource Immagine ritagliata
     */
    public static function cropImage($image, $x, $y, $width, $height)
    {
        // Crea una nuova immagine con le dimensioni specificate
        $cropped = imagecreatetruecolor($width, $height);
        
        // Se l'immagine originale ha il canale alpha, mantieni la trasparenza
        if (imageistruecolor($image) && imagecolortransparent($image) == -1) {
            // Attiva il blending a pieno della trasparenza
            imagealphablending($cropped, false);
            // Salva il canale alpha
            imagesavealpha($cropped, true);
        }
        
        // Copia la porzione dell'immagine originale nella nuova immagine
        imagecopy($cropped, $image, 0, 0, $x, $y, $width, $height);
        
        // Libera la memoria dell'immagine originale
        imagedestroy($image);
        
        return $cropped;
    }
    
    /**
     * Ridimensiona un'immagine mantenendo le proporzioni
     * @param resource $image Immagine da ridimensionare
     * @param int $width Larghezza desiderata
     * @param int $height Altezza desiderata
     * @return resource Immagine ridimensionata
     */
    public static function resizeImage($image, $width, $height)
    {
        // Ottieni le dimensioni originali
        $srcWidth = imagesx($image);
        $srcHeight = imagesy($image);
        
        // Crea una nuova immagine con le dimensioni specificate
        $resized = imagecreatetruecolor($width, $height);
        
        // Se l'immagine originale ha il canale alpha, mantieni la trasparenza
        if (imageistruecolor($image) && imagecolortransparent($image) == -1) {
            // Attiva il blending a pieno della trasparenza
            imagealphablending($resized, false);
            // Salva il canale alpha
            imagesavealpha($resized, true);
        }
        
        // Ridimensiona l'immagine originale nella nuova immagine
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
        
        // Libera la memoria dell'immagine originale
        imagedestroy($image);
        
        return $resized;
    }
    
    /**
     * Calcola le dimensioni di destinazione mantenendo le proporzioni
     * @param int $srcWidth Larghezza sorgente
     * @param int $srcHeight Altezza sorgente
     * @param int $maxWidth Larghezza massima
     * @param int $maxHeight Altezza massima
     * @param float|null $targetRatio Rapporto di aspetto target (null per mantenere originale)
     * @return array [width, height]
     */
    public static function calculateDestinationSize($srcWidth, $srcHeight, $maxWidth, $maxHeight, $targetRatio = null)
    {
        // Se è specificato un rapporto di aspetto target
        if ($targetRatio !== null) {
            // Determina se adattare per larghezza o altezza in base al rapporto target
            if ($srcWidth / $srcHeight > $targetRatio) {
                // L'immagine è più larga del target, adatta per altezza
                $height = $maxHeight;
                $width = $height * $targetRatio;
            } else {
                // L'immagine è più alta del target, adatta per larghezza
                $width = $maxWidth;
                $height = $width / $targetRatio;
            }
            
            return [$width, $height];
        }
        
        // Altrimenti, mantieni le proporzioni originali
        $ratio = $srcWidth / $srcHeight;
        
        if ($maxWidth / $maxHeight > $ratio) {
            // Il contenitore è più largo dell'immagine, adatta per altezza
            $height = $maxHeight;
            $width = $height * $ratio;
        } else {
            // Il contenitore è più alto dell'immagine, adatta per larghezza
            $width = $maxWidth;
            $height = $width / $ratio;
        }
        
        return [$width, $height];
    }
    
    /**
     * Formatta una dimensione in byte in una stringa leggibile
     * @param int $size Dimensione in byte
     * @return string Dimensione formattata
     */
    public static function formatSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
    
    /**
     * Applica un watermark all'immagine
     * @param resource $image Immagine
     * @param string $text Testo del watermark
     * @param array $options Opzioni del watermark
     * @return resource Immagine con watermark
     */
    public static function applyWatermark($image, $text, $options = [])
    {
        // Opzioni predefinite
        $defaultOptions = [
            'font_size' => 20,
            'angle' => -45,
            'color' => [50, 50, 50, 75], // RGBA
            'position' => 'center', // center, top-left, top-right, bottom-left, bottom-right
            'padding' => 10,
            'font_path' => _PS_MODULE_DIR_ . 'art_puzzle/views/fonts/Arial.ttf' // Font predefinito
        ];
        
        // Unisce le opzioni predefinite con quelle fornite
        $options = array_merge($defaultOptions, $options);
        
        // Verifica che il font esista
        if (!file_exists($options['font_path'])) {
            // Usa un font di sistema se quello specificato non esiste
            $options['font_path'] = 5; // Font di sistema MEDIUM
        }
        
        // Ottieni le dimensioni dell'immagine
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Crea il colore per il testo
        $textColor = imagecolorallocatealpha(
            $image,
            $options['color'][0],
            $options['color'][1],
            $options['color'][2],
            $options['color'][3]
        );
        
        // Calcola le dimensioni del testo
        $textBox = imagettfbbox($options['font_size'], $options['angle'], $options['font_path'], $text);
        $textWidth = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        
        // Calcola la posizione del testo
        switch ($options['position']) {
            case 'top-left':
                $x = $options['padding'];
                $y = $textHeight + $options['padding'];
                break;
                
            case 'top-right':
                $x = $width - $textWidth - $options['padding'];
                $y = $textHeight + $options['padding'];
                break;
                
            case 'bottom-left':
                $x = $options['padding'];
                $y = $height - $options['padding'];
                break;
                
            case 'bottom-right':
                $x = $width - $textWidth - $options['padding'];
                $y = $height - $options['padding'];
                break;
                
            case 'center':
            default:
                $x = ($width - $textWidth) / 2;
                $y = ($height + $textHeight) / 2;
                break;
        }
        
        // Aggiungi il testo all'immagine
        imagettftext($image, $options['font_size'], $options['angle'], $x, $y, $textColor, $options['font_path'], $text);
        
        return $image;
    }
    
    /**
     * Pulisce i file temporanei
     * @param string $directory Directory da pulire
     * @param int $maxAge Età massima in secondi (default: 86400 = 24 ore)
     * @return int Numero di file eliminati
     */
    public static function cleanupTempFiles($directory, $maxAge = 86400)
    {
        if (!is_dir($directory)) {
            return 0;
        }
        
        $now = time();
        $count = 0;
        
        $files = scandir($directory);
        
        foreach ($files as $file) {
            if ($file == '.' || $file == '..' || $file == 'index.php') {
                continue;
            }
            
            $filePath = $directory . $file;
            
            if (is_file($filePath)) {
                // Se il file è più vecchio del tempo massimo, eliminalo
                if ($now - filemtime($filePath) > $maxAge) {
                    if (@unlink($filePath)) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }
}