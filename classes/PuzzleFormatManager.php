<?php
/**
 * Art Puzzle - Format Manager
 * Classe che gestisce i formati dei puzzle disponibili
 */

class PuzzleFormatManager
{
    /**
     * Ottiene tutti i formati puzzle disponibili
     * @return array Lista di formati puzzle
     */
    public static function getAllFormats()
    {
        // Recupera i formati dalla configurazione
        $formatsJson = Configuration::get('ART_PUZZLE_FORMATS');
        
        // Se non ci sono formati configurati, restituisci i formati predefiniti
        if (!$formatsJson) {
            return self::getDefaultFormats();
        }
        
        // Decodifica il JSON
        $formats = json_decode($formatsJson, true);
        
        // Se la decodifica fallisce, restituisci i formati predefiniti
        if (!$formats) {
            return self::getDefaultFormats();
        }
        
        return $formats;
    }
    
    /**
     * Ottiene un formato puzzle specifico
     * @param string $formatId ID del formato
     * @return array|null Dati del formato o null se non trovato
     */
    public static function getFormat($formatId)
    {
        $formats = self::getAllFormats();
        
        return isset($formats[$formatId]) ? $formats[$formatId] : null;
    }
    
    /**
     * Ottiene i formati puzzle filtrati per orientamento
     * @param string $orientation Orientamento (landscape, portrait, square)
     * @return array Lista di formati filtrati
     */
    public static function getFormatsByOrientation($orientation)
    {
        $allFormats = self::getAllFormats();
        $filteredFormats = [];
        
        foreach ($allFormats as $id => $format) {
            if ($format['orientation'] === $orientation) {
                $filteredFormats[$id] = $format;
            }
        }
        
        return $filteredFormats;
    }
    
    /**
     * Ottiene i formati puzzle filtrati per numero di pezzi
     * @param int $minPieces Numero minimo di pezzi
     * @param int $maxPieces Numero massimo di pezzi
     * @return array Lista di formati filtrati
     */
    public static function getFormatsByPieces($minPieces, $maxPieces = null)
    {
        $allFormats = self::getAllFormats();
        $filteredFormats = [];
        
        foreach ($allFormats as $id => $format) {
            $pieces = (int)$format['pieces'];
            
            if ($pieces >= $minPieces && ($maxPieces === null || $pieces <= $maxPieces)) {
                $filteredFormats[$id] = $format;
            }
        }
        
        return $filteredFormats;
    }
    
    /**
     * Ottiene i formati per una specifica difficoltà
     * @param string $difficulty Livello di difficoltà (easy, medium, hard)
     * @return array Lista di formati filtrati
     */
    public static function getFormatsByDifficulty($difficulty)
    {
        $allFormats = self::getAllFormats();
        $filteredFormats = [];
        
        // Mappatura numero pezzi -> difficoltà
        $difficultyMap = [
            'easy' => [0, 500],     // Fino a 500 pezzi
            'medium' => [501, 1000], // Da 501 a 1000 pezzi
            'hard' => [1001, 9999]   // Più di 1000 pezzi
        ];
        
        // Se la difficoltà non è valida, restituisci un array vuoto
        if (!isset($difficultyMap[$difficulty])) {
            return [];
        }
        
        $piecesRange = $difficultyMap[$difficulty];
        
        foreach ($allFormats as $id => $format) {
            $pieces = (int)$format['pieces'];
            
            if ($pieces >= $piecesRange[0] && $pieces <= $piecesRange[1]) {
                $filteredFormats[$id] = $format;
            }
        }
        
        return $filteredFormats;
    }
    
    /**
     * Determina il formato più adatto per un'immagine
     * @param int $width Larghezza dell'immagine
     * @param int $height Altezza dell'immagine
     * @return string|null ID del formato o null se non trovato
     */
    public static function getSuggestedFormat($width, $height)
    {
        // Calcola il rapporto di aspetto dell'immagine
        $ratio = $width / $height;
        
        // Determina l'orientamento
        $orientation = 'square';
        
        if ($ratio > 1.2) {
            $orientation = 'landscape';
        } else if ($ratio < 0.8) {
            $orientation = 'portrait';
        }
        
        // Ottieni i formati per questo orientamento
        $formats = self::getFormatsByOrientation($orientation);
        
        // Se non ci sono formati disponibili, restituisci null
        if (empty($formats)) {
            return null;
        }
        
        // Determina la qualità in base alle dimensioni
        $quality = 'alta';
        
        if ($width < 800 || $height < 800) {
            $quality = 'bassa';
        } else if ($width < 1200 || $height < 1200) {
            $quality = 'media';
        }
        
        // Filtra i formati in base alla qualità
        $suitableFormats = [];
        
        foreach ($formats as $id => $format) {
            // Se la qualità è bassa, suggerisci solo formati piccoli
            if ($quality === 'bassa' && (int)$format['pieces'] <= 500) {
                $suitableFormats[$id] = $format;
            }
            // Se la qualità è media, suggerisci formati piccoli e medi
            else if ($quality === 'media' && (int)$format['pieces'] <= 1000) {
                $suitableFormats[$id] = $format;
            }
            // Se la qualità è alta, suggerisci tutti i formati
            else if ($quality === 'alta') {
                $suitableFormats[$id] = $format;
            }
        }
        
        // Se non ci sono formati adatti, usa tutti quelli disponibili per l'orientamento
        if (empty($suitableFormats)) {
            $suitableFormats = $formats;
        }
        
        // Ordina i formati per numero di pezzi (crescente)
        uasort($suitableFormats, function($a, $b) {
            return (int)$a['pieces'] - (int)$b['pieces'];
        });
        
        // Restituisci il primo ID
        reset($suitableFormats);
        return key($suitableFormats);
    }
    
    /**
     * Ottiene i formati puzzle predefiniti
     * @return array Lista di formati predefiniti
     */
    public static function getDefaultFormats()
    {
        return [
            // Formati quadrati
            'square_small' => [
                'name' => 'Quadrato piccolo',
                'dimensions' => '30x30 cm',
                'width' => 600,
                'height' => 600,
                'pieces' => 100,
                'orientation' => 'square',
                'ratio' => 1,
                'difficulty' => 'easy'
            ],
            'square_medium' => [
                'name' => 'Quadrato medio',
                'dimensions' => '45x45 cm',
                'width' => 900,
                'height' => 900,
                'pieces' => 500,
                'orientation' => 'square',
                'ratio' => 1,
                'difficulty' => 'medium'
            ],
            'square_large' => [
                'name' => 'Quadrato grande',
                'dimensions' => '60x60 cm',
                'width' => 1200,
                'height' => 1200,
                'pieces' => 1000,
                'orientation' => 'square',
                'ratio' => 1,
                'difficulty' => 'hard'
            ],
            
            // Formati orizzontali (landscape)
            'landscape_small' => [
                'name' => 'Orizzontale piccolo',
                'dimensions' => '40x30 cm',
                'width' => 800,
                'height' => 600,
                'pieces' => 150,
                'orientation' => 'landscape',
                'ratio' => 1.33,
                'difficulty' => 'easy'
            ],
            'landscape_medium' => [
                'name' => 'Orizzontale medio',
                'dimensions' => '60x45 cm',
                'width' => 1200,
                'height' => 900,
                'pieces' => 750,
                'orientation' => 'landscape',
                'ratio' => 1.33,
                'difficulty' => 'medium'
            ],
            'landscape_large' => [
                'name' => 'Orizzontale grande',
                'dimensions' => '80x60 cm',
                'width' => 1600,
                'height' => 1200,
                'pieces' => 1500,
                'orientation' => 'landscape',
                'ratio' => 1.33,
                'difficulty' => 'hard'
            ],
            
            // Formati verticali (portrait)
            'portrait_small' => [
                'name' => 'Verticale piccolo',
                'dimensions' => '30x40 cm',
                'width' => 600,
                'height' => 800,
                'pieces' => 150,
                'orientation' => 'portrait',
                'ratio' => 0.75,
                'difficulty' => 'easy'
            ],
            'portrait_medium' => [
                'name' => 'Verticale medio',
                'dimensions' => '45x60 cm',
                'width' => 900,
                'height' => 1200,
                'pieces' => 750,
                'orientation' => 'portrait',
                'ratio' => 0.75,
                'difficulty' => 'medium'
            ],
            'portrait_large' => [
                'name' => 'Verticale grande',
                'dimensions' => '60x80 cm',
                'width' => 1200,
                'height' => 1600,
                'pieces' => 1500,
                'orientation' => 'portrait',
                'ratio' => 0.75,
                'difficulty' => 'hard'
            ],
            
            // Formati speciali
            'panoramic' => [
                'name' => 'Panoramico',
                'dimensions' => '90x30 cm',
                'width' => 1800,
                'height' => 600,
                'pieces' => 750,
                'orientation' => 'landscape',
                'ratio' => 3,
                'difficulty' => 'medium'
            ],
            'mini' => [
                'name' => 'Mini puzzle',
                'dimensions' => '15x15 cm',
                'width' => 300,
                'height' => 300,
                'pieces' => 50,
                'orientation' => 'square',
                'ratio' => 1,
                'difficulty' => 'easy'
            ]
        ];
    }
    
    /**
     * Salva i formati puzzle
     * @param array $formats Formati da salvare
     * @return bool True se il salvataggio è riuscito
     */
    public static function saveFormats($formats)
    {
        // Codifica l'array in JSON
        $formatsJson = json_encode($formats);
        
        // Salva nella configurazione
        return Configuration::updateValue('ART_PUZZLE_FORMATS', $formatsJson);
    }
    
    /**
     * Aggiunge un nuovo formato puzzle
     * @param string $id ID del formato
     * @param array $formatData Dati del formato
     * @return bool True se l'aggiunta è riuscita
     */
    public static function addFormat($id, $formatData)
    {
        // Ottieni i formati esistenti
        $formats = self::getAllFormats();
        
        // Verifica che l'ID non esista già
        if (isset($formats[$id])) {
            return false;
        }
        
        // Campi obbligatori
        $requiredFields = ['name', 'dimensions', 'width', 'height', 'pieces', 'orientation'];
        
        // Verifica che ci siano tutti i campi obbligatori
        foreach ($requiredFields as $field) {
            if (!isset($formatData[$field])) {
                return false;
            }
        }
        
        // Calcola il rapporto di aspetto se non è specificato
        if (!isset($formatData['ratio'])) {
            $formatData['ratio'] = $formatData['width'] / $formatData['height'];
        }
        
        // Determina la difficoltà se non è specificata
        if (!isset($formatData['difficulty'])) {
            $pieces = (int)$formatData['pieces'];
            
            if ($pieces <= 500) {
                $formatData['difficulty'] = 'easy';
            } else if ($pieces <= 1000) {
                $formatData['difficulty'] = 'medium';
            } else {
                $formatData['difficulty'] = 'hard';
            }
        }
        
        // Aggiungi il nuovo formato
        $formats[$id] = $formatData;
        
        // Salva i formati aggiornati
        return self::saveFormats($formats);
    }
    
    /**
     * Aggiorna un formato puzzle esistente
     * @param string $id ID del formato
     * @param array $formatData Dati del formato
     * @return bool True se l'aggiornamento è riuscito
     */
    public static function updateFormat($id, $formatData)
    {
        // Ottieni i formati esistenti
        $formats = self::getAllFormats();
        
        // Verifica che l'ID esista
        if (!isset($formats[$id])) {
            return false;
        }
        
        // Aggiorna i dati del formato
        $formats[$id] = array_merge($formats[$id], $formatData);
        
        // Calcola il rapporto di aspetto se sono state modificate le dimensioni
        if (isset($formatData['width']) && isset($formatData['height'])) {
            $formats[$id]['ratio'] = $formatData['width'] / $formatData['height'];
        }
        
        // Salva i formati aggiornati
        return self::saveFormats($formats);
    }
    
    /**
     * Elimina un formato puzzle
     * @param string $id ID del formato
     * @return bool True se l'eliminazione è riuscita
     */
    public static function deleteFormat($id)
    {
        // Ottieni i formati esistenti
        $formats = self::getAllFormats();
        
        // Verifica che l'ID esista
        if (!isset($formats[$id])) {
            return false;
        }
        
        // Rimuovi il formato
        unset($formats[$id]);
        
        // Salva i formati aggiornati
        return self::saveFormats($formats);
    }
    
    /**
     * Reimposta i formati ai valori predefiniti
     * @return bool True se il reset è riuscito
     */
    public static function resetToDefaultFormats()
    {
        $defaultFormats = self::getDefaultFormats();
        return self::saveFormats($defaultFormats);
    }
}