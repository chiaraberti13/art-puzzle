<?php
/**
 * Art Puzzle - Box Manager
 * Classe che gestisce i template delle scatole puzzle e la loro personalizzazione
 */

class PuzzleBoxManager
{
    /**
     * Ottiene tutti i template di scatole disponibili
     * @return array Lista di template
     */
    public static function getAllBoxTemplates()
    {
        // Recupera i template dalla configurazione
        $templatesJson = Configuration::get('ART_PUZZLE_BOX_TEMPLATES');
        
        // Se non ci sono template configurati, restituisci i template predefiniti
        if (!$templatesJson) {
            return self::getDefaultBoxTemplates();
        }
        
        // Decodifica il JSON
        $templates = json_decode($templatesJson, true);
        
        // Se la decodifica fallisce, restituisci i template predefiniti
        if (!$templates) {
            return self::getDefaultBoxTemplates();
        }
        
        return $templates;
    }
    
    /**
     * Ottiene un template specifico
     * @param string $templateId ID del template
     * @return array|null Dati del template o null se non trovato
     */
    public static function getBoxTemplate($templateId)
    {
        $templates = self::getAllBoxTemplates();
        
        return isset($templates[$templateId]) ? $templates[$templateId] : null;
    }
    
    /**
     * Ottiene tutti i colori disponibili per le scatole
     * @return array Lista di colori
     */
    public static function getAllBoxColors()
    {
        // Recupera i colori dalla configurazione
        $colorsJson = Configuration::get('ART_PUZZLE_BOX_COLORS');
        
        // Se non ci sono colori configurati, restituisci i colori predefiniti
        if (!$colorsJson) {
            return self::getDefaultBoxColors();
        }
        
        // Decodifica il JSON
        $colors = json_decode($colorsJson, true);
        
        // Se la decodifica fallisce, restituisci i colori predefiniti
        if (!$colors) {
            return self::getDefaultBoxColors();
        }
        
        return $colors;
    }
    
    /**
     * Ottiene un colore specifico
     * @param string $colorHex Codice colore esadecimale
     * @return array|null Dati del colore o null se non trovato
     */
    public static function getBoxColor($colorHex)
    {
        $colors = self::getAllBoxColors();
        
        foreach ($colors as $color) {
            if ($color['hex'] === $colorHex) {
                return $color;
            }
        }
        
        return null;
    }
    
    /**
     * Ottiene tutti i font disponibili
     * @return array Lista di font
     */
    public static function getAllFonts()
    {
        // Recupera i font dalla configurazione
        $fonts = Configuration::get('ART_PUZZLE_FONTS');
        
        // Se non ci sono font configurati, restituisci i font predefiniti
        if (!$fonts) {
            return self::getDefaultFonts();
        }
        
        // Divide la stringa in un array
        $fontList = explode(',', $fonts);
        
        // Verifica che i file esistano
        $validFonts = [];
        
        foreach ($fontList as $font) {
            $fontPath = _PS_MODULE_DIR_ . 'art_puzzle/views/fonts/' . trim($font);
            
            if (file_exists($fontPath)) {
                $validFonts[] = trim($font);
            }
        }
        
        // Se non ci sono font validi, restituisci i font predefiniti
        if (empty($validFonts)) {
            return self::getDefaultFonts();
        }
        
        return $validFonts;
    }
    
    /**
     * Genera l'anteprima di una scatola puzzle
     * @param array $boxData Dati della scatola
     * @param string|null $imagePath Percorso dell'immagine del puzzle (per scatole con immagine)
     * @param bool $returnBase64 Se true, restituisce un data URL invece di salvare l'immagine
     * @return string|false Percorso dell'anteprima, data URL o false in caso di errore
     */
    public static function generateBoxPreview($boxData, $imagePath = null, $returnBase64 = false)
    {
        // Verifica i dati necessari
        if (!isset($boxData['template'])) {
            $boxData['template'] = 'classic';
        }
        
        if (!isset($boxData['color'])) {
            $boxData['color'] = '#ffffff';
        }
        
        if (!isset($boxData['text'])) {
            $boxData['text'] = 'Il mio puzzle';
        }
        
        if (!isset($boxData['font'])) {
            $boxData['font'] = 'default';
        }
        
        // Ottieni il template
        $template = self::getBoxTemplate($boxData['template']);
        
        if (!$template) {
            // Se il template non esiste, usa il template predefinito
            $templates = self::getDefaultBoxTemplates();
            $template = reset($templates);
        }
        
        // Carica l'immagine di sfondo
        $backgroundPath = _PS_MODULE_DIR_ . 'art_puzzle/views/img/scatole_base/' . $template['background'];
        
        if (!file_exists($backgroundPath)) {
            return false;
        }
        
        // Crea l'immagine
        $background = self::loadImage($backgroundPath);
        
        if (!$background) {
            return false;
        }
        
        // Ottieni le dimensioni dell'immagine
        $width = imagesx($background);
        $height = imagesy($background);
        
        // Crea una nuova immagine con lo sfondo trasparente
        $image = imagecreatetruecolor($width, $height);
        
        // Attiva la trasparenza
        imagealphablending($image, false);
        imagesavealpha($image, true);
        
        // Riempi con trasparenza
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        
        // Abilita il blending per disegnare su sfondo trasparente
        imagealphablending($image, true);
        
        // Copia lo sfondo
        imagecopy($image, $background, 0, 0, 0, 0, $width, $height);
        
        // Libera memoria
        imagedestroy($background);
        
        // Se il template è di tipo photobox e c'è un'immagine
        if ($template['type'] === 'photobox' && $imagePath && file_exists($imagePath)) {
            // Carica l'immagine
            $puzzleImage = self::loadImage($imagePath);
            
            if ($puzzleImage) {
                // Area dove inserire l'immagine
                $imageArea = $template['image_area'];
                
                // Ridimensiona l'immagine per adattarla all'area
                $puzzleImage = self::resizeImageToFit(
                    $puzzleImage,
                    $imageArea['width'],
                    $imageArea['height']
                );
                
                // Copia l'immagine nella posizione corretta
                imagecopy(
                    $image,
                    $puzzleImage,
                    $imageArea['x'],
                    $imageArea['y'],
                    0,
                    0,
                    imagesx($puzzleImage),
                    imagesy($puzzleImage)
                );
                
                // Libera memoria
                imagedestroy($puzzleImage);
            }
        }
        
        // Converte il colore HEX in RGB
        $colorRGB = self::hexToRgb($boxData['color']);
        
        // Area dove applicare il colore
        if (isset($template['color_area'])) {
            $colorArea = $template['color_area'];
            
            // Crea un'immagine per il rettangolo colorato
            $colorRect = imagecreatetruecolor($colorArea['width'], $colorArea['height']);
            
            // Crea il colore
            $color = imagecolorallocate($colorRect, $colorRGB['r'], $colorRGB['g'], $colorRGB['b']);
            
            // Riempi il rettangolo con il colore
            imagefill($colorRect, 0, 0, $color);
            
            // Copia il rettangolo colorato nell'immagine principale con trasparenza
            imagecopymerge(
                $image,
                $colorRect,
                $colorArea['x'],
                $colorArea['y'],
                0,
                0,
                $colorArea['width'],
                $colorArea['height'],
                $colorArea['opacity'] * 100 // Converte 0-1 in 0-100
            );
            
            // Libera memoria
            imagedestroy($colorRect);
        }
        
        // Aggiungi il testo
        if (!empty($boxData['text']) && isset($template['text_area'])) {
            $textArea = $template['text_area'];
            
            // Carica il font
            $fontPath = _PS_MODULE_DIR_ . 'art_puzzle/views/fonts/' . $boxData['font'];
            
            // Se il font non esiste, usa un font predefinito
            if (!file_exists($fontPath)) {
                $fontPath = _PS_MODULE_DIR_ . 'art_puzzle/views/fonts/default.ttf';
                
                // Se anche il font predefinito non esiste, usa un font di sistema
                if (!file_exists($fontPath)) {
                    $fontPath = 5; // Font di sistema
                }
            }
            
            // Colore del testo (default: nero)
            $textColor = imagecolorallocate($image, 0, 0, 0);
            
            // Calcola le dimensioni ottimali del testo
            $fontSize = self::calculateOptimalFontSize(
                $boxData['text'],
                $fontPath,
                $textArea['width'],
                $textArea['height']
            );
            
            // Calcola la posizione del testo
            $textBox = imagettfbbox($fontSize, 0, $fontPath, $boxData['text']);
            
            $textWidth = abs($textBox[4] - $textBox[0]);
            $textHeight = abs($textBox[5] - $textBox[1]);
            
            $textX = $textArea['x'] + ($textArea['width'] - $textWidth) / 2;
            $textY = $textArea['y'] + ($textArea['height'] + $textHeight) / 2;
            
            // Aggiungi il testo
            imagettftext($image, $fontSize, 0, $textX, $textY, $textColor, $fontPath, $boxData['text']);
        }
        
        // Se è richiesto il data URL
        if ($returnBase64) {
            // Genera il data URL
            ob_start();
            imagepng($image);
            $imageData = ob_get_clean();
            
            // Libera memoria
            imagedestroy($image);
            
            // Restituisci il data URL
            return 'data:image/png;base64,' . base64_encode($imageData);
        }
        
        // Altrimenti salva l'immagine
        $tempDir = _PS_MODULE_DIR_ . 'art_puzzle/upload/';
        
        // Crea la directory se non esiste
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        // Nome file temporaneo
        $tempFile = $tempDir . 'box_preview_' . time() . '_' . substr(md5(uniqid(rand(), true)), 0, 8) . '.png';
        
        // Salva l'immagine
        imagepng($image, $tempFile);
        
        // Libera memoria
        imagedestroy($image);
        
        return $tempFile;
    }
    
    /**
     * Genera un'immagine completa della scatola (sviluppo)
     * @param array $boxData Dati della scatola
     * @param string|null $imagePath Percorso dell'immagine del puzzle
     * @param string $outputPath Percorso di output
     * @return bool True se la generazione è riuscita
     */
    public static function generateBoxFull($boxData, $imagePath, $outputPath)
    {
        // Implementazione richiesta ma non completata in questa versione
        // Questa funzione genererebbe tutte le facce della scatola per la stampa
        
        return false;
    }
    
    /**
     * Ottiene i template di scatole predefiniti
     * @return array Template predefiniti
     */
    public static function getDefaultBoxTemplates()
    {
        return [
            'classic' => [
                'name' => 'Classica',
                'description' => 'Scatola classica con testo personalizzato',
                'background' => 'scatola_classic.png',
                'type' => 'color',
                'color_area' => [
                    'x' => 100,
                    'y' => 100,
                    'width' => 600,
                    'height' => 400,
                    'opacity' => 0.8
                ],
                'text_area' => [
                    'x' => 150,
                    'y' => 150,
                    'width' => 500,
                    'height' => 300
                ]
            ],
            'elegant' => [
                'name' => 'Elegante',
                'description' => 'Scatola elegante con cornice dorata e testo',
                'background' => 'scatola_elegant.png',
                'type' => 'color',
                'color_area' => [
                    'x' => 120,
                    'y' => 120,
                    'width' => 560,
                    'height' => 360,
                    'opacity' => 0.7
                ],
                'text_area' => [
                    'x' => 180,
                    'y' => 180,
                    'width' => 440,
                    'height' => 240
                ]
            ],
            'photobox' => [
                'name' => 'Foto personalizzata',
                'description' => 'Scatola con l\'immagine del puzzle sulla copertina',
                'background' => 'scatola_photo.png',
                'type' => 'photobox',
                'image_area' => [
                    'x' => 150,
                    'y' => 100,
                    'width' => 500,
                    'height' => 350
                ],
                'text_area' => [
                    'x' => 150,
                    'y' => 480,
                    'width' => 500,
                    'height' => 100
                ]
            ],
            'minimal' => [
                'name' => 'Minimalista',
                'description' => 'Scatola dal design semplice ed essenziale',
                'background' => 'scatola_minimal.png',
                'type' => 'color',
                'color_area' => [
                    'x' => 80,
                    'y' => 80,
                    'width' => 640,
                    'height' => 440,
                    'opacity' => 0.9
                ],
                'text_area' => [
                    'x' => 120,
                    'y' => 250,
                    'width' => 560,
                    'height' => 100
                ]
            ]
        ];
    }
    
    /**
     * Ottiene i colori predefiniti per le scatole
     * @return array Colori predefiniti
     */
    public static function getDefaultBoxColors()
    {
        return [
            [
                'name' => 'Bianco',
                'hex' => '#ffffff'
            ],
            [
                'name' => 'Nero',
                'hex' => '#000000'
            ],
            [
                'name' => 'Rosso',
                'hex' => '#ff0000'
            ],
            [
                'name' => 'Verde',
                'hex' => '#00aa00'
            ],
            [
                'name' => 'Blu',
                'hex' => '#0000ff'
            ],
            [
                'name' => 'Giallo',
                'hex' => '#ffcc00'
            ],
            [
                'name' => 'Arancione',
                'hex' => '#ff6600'
            ],
            [
                'name' => 'Viola',
                'hex' => '#9900cc'
            ],
            [
                'name' => 'Rosa',
                'hex' => '#ff66cc'
            ],
            [
                'name' => 'Azzurro',
                'hex' => '#66ccff'
            ],
            [
                'name' => 'Marrone',
                'hex' => '#663300'
            ],
            [
                'name' => 'Grigio',
                'hex' => '#999999'
            ]
        ];
    }
    
    /**
     * Ottiene i font predefiniti
     * @return array Font predefiniti
     */
    public static function getDefaultFonts()
    {
        return [
            'default.ttf',
            'arial.ttf',
            'times.ttf',
            'georgia.ttf',
            'courier.ttf',
            'impact.ttf',
            'comic.ttf'
        ];
    }
    
    /**
     * Carica un'immagine da un percorso
     * @param string $path Percorso dell'immagine
     * @return resource|false Immagine caricata o false in caso di errore
     */
    private static function loadImage($path)
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
     * Ridimensiona un'immagine per adattarla a un'area
     * @param resource $image Immagine da ridimensionare
     * @param int $maxWidth Larghezza massima
     * @param int $maxHeight Altezza massima
     * @return resource Immagine ridimensionata
     */
    private static function resizeImageToFit($image, $maxWidth, $maxHeight)
    {
        // Ottieni le dimensioni originali
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Calcola le nuove dimensioni mantenendo le proporzioni
        $ratio = $width / $height;
        
        if ($maxWidth / $maxHeight > $ratio) {
            // Il contenitore è più largo dell'immagine, adatta per altezza
            $newHeight = $maxHeight;
            $newWidth = $newHeight * $ratio;
        } else {
            // Il contenitore è più alto dell'immagine, adatta per larghezza
            $newWidth = $maxWidth;
            $newHeight = $newWidth / $ratio;
        }
        
        // Crea una nuova immagine con le dimensioni calcolate
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Mantieni la trasparenza per PNG
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        
        // Ridimensiona l'immagine
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Libera memoria dell'immagine originale
        imagedestroy($image);
        
        return $resized;
    }
    
    /**
     * Calcola la dimensione ottimale del font per adattarsi a un'area
     * @param string $text Testo
     * @param string|int $fontPath Percorso del font o indice font di sistema
     * @param int $maxWidth Larghezza massima
     * @param int $maxHeight Altezza massima
     * @return float Dimensione ottimale del font
     */
    private static function calculateOptimalFontSize($text, $fontPath, $maxWidth, $maxHeight)
    {
        // Dimensione di partenza
        $fontSize = 100;
        
        // Dimensione minima
        $minFontSize = 8;
        
        // Riduzione per iterazione
        $decrement = 2;
        
        // Margine di sicurezza (90% dell'area disponibile)
        $maxWidth *= 0.9;
        $maxHeight *= 0.9;
        
        // Trova la dimensione ottimale
        do {
            // Calcola le dimensioni del testo con la dimensione corrente
            $textBox = imagettfbbox($fontSize, 0, $fontPath, $text);
            
            $textWidth = abs($textBox[4] - $textBox[0]);
            $textHeight = abs($textBox[5] - $textBox[1]);
            
            // Se il testo è troppo grande, riduci la dimensione
            if ($textWidth > $maxWidth || $textHeight > $maxHeight) {
                $fontSize -= $decrement;
            } else {
                // Dimensione ottimale trovata
                break;
            }
        } while ($fontSize > $minFontSize);
        
        return $fontSize;
    }
    
    /**
     * Converte un colore HEX in RGB
     * @param string $hex Codice colore esadecimale
     * @return array Componenti RGB
     */
    private static function hexToRgb($hex)
    {
        // Rimuovi il carattere # se presente
        $hex = ltrim($hex, '#');
        
        // Verifica che sia un codice HEX valido
        if (strlen($hex) !== 6) {
            // Codice non valido, restituisci bianco
            return [
                'r' => 255,
                'g' => 255,
                'b' => 255
            ];
        }
        
        // Converti HEX in RGB
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }
    
    /**
     * Salva i template di scatole
     * @param array $templates Template da salvare
     * @return bool True se il salvataggio è riuscito
     */
    public static function saveBoxTemplates($templates)
    {
        // Codifica l'array in JSON
        $templatesJson = json_encode($templates);
        
        // Salva nella configurazione
        return Configuration::updateValue('ART_PUZZLE_BOX_TEMPLATES', $templatesJson);
    }
    
    /**
     * Salva i colori delle scatole
     * @param array $colors Colori da salvare
     * @return bool True se il salvataggio è riuscito
     */
    public static function saveBoxColors($colors)
    {
        // Codifica l'array in JSON
        $colorsJson = json_encode($colors);
        
        // Salva nella configurazione
        return Configuration::updateValue('ART_PUZZLE_BOX_COLORS', $colorsJson);
    }
    
    /**
     * Salva i font
     * @param array $fonts Font da salvare
     * @return bool True se il salvataggio è riuscito
     */
    public static function saveFonts($fonts)
    {
        // Converte l'array in stringa
        $fontsString = implode(',', $fonts);
        
        // Salva nella configurazione
        return Configuration::updateValue('ART_PUZZLE_FONTS', $fontsString);
    }
    
    /**
     * Reimposta i template ai valori predefiniti
     * @return bool True se il reset è riuscito
     */
    public static function resetToDefaultTemplates()
    {
        $defaultTemplates = self::getDefaultBoxTemplates();
        return self::saveBoxTemplates($defaultTemplates);
    }
    
    /**
     * Reimposta i colori ai valori predefiniti
     * @return bool True se il reset è riuscito
     */
    public static function resetToDefaultColors()
    {
        $defaultColors = self::getDefaultBoxColors();
        return self::saveBoxColors($defaultColors);
    }
    
    /**
     * Reimposta i font ai valori predefiniti
     * @return bool True se il reset è riuscito
     */
    public static function resetToDefaultFonts()
    {
        $defaultFonts = self::getDefaultFonts();
        return self::saveFonts($defaultFonts);
    }
}