<?php
/**
 * Art Puzzle - Logger
 * Classe che gestisce il logging delle operazioni del modulo
 */

class ArtPuzzleLogger
{
    /**
     * Livelli di log disponibili
     */
    const LOG_LEVEL_INFO = 'INFO';
    const LOG_LEVEL_WARNING = 'WARNING';
    const LOG_LEVEL_ERROR = 'ERROR';
    const LOG_LEVEL_DEBUG = 'DEBUG';
    
    /**
     * Registra un messaggio nel file di log
     * @param string $message Messaggio da registrare
     * @param string $level Livello di log (INFO, WARNING, ERROR, DEBUG)
     * @param array $context Dati di contesto aggiuntivi
     * @return bool True se il logging è riuscito
     */
    public static function log($message, $level = self::LOG_LEVEL_INFO, $context = [])
    {
        // Verifica che il messaggio non sia vuoto
        if (empty($message)) {
            return false;
        }
        
        // Verifica che il livello sia valido
        $validLevels = [self::LOG_LEVEL_INFO, self::LOG_LEVEL_WARNING, self::LOG_LEVEL_ERROR, self::LOG_LEVEL_DEBUG];
        
        if (!in_array($level, $validLevels)) {
            $level = self::LOG_LEVEL_INFO;
        }
        
        // Ottieni la directory dei log
        $logDir = _PS_MODULE_DIR_ . 'art_puzzle/logs/';
        
        // Crea la directory se non esiste
        if (!file_exists($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                return false;
            }
        }
        
        // Nome del file di log (uno per giorno)
        $logFile = $logDir . 'art_puzzle_' . date('Y-m-d') . '.log';
        
        // Formatta il messaggio di log
        $logMessage = self::formatLogMessage($message, $level, $context);
        
        // Scrivi nel file di log
        return file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND) !== false;
    }
    
    /**
     * Registra un messaggio di livello INFO
     * @param string $message Messaggio da registrare
     * @param array $context Dati di contesto aggiuntivi
     * @return bool True se il logging è riuscito
     */
    public static function info($message, $context = [])
    {
        return self::log($message, self::LOG_LEVEL_INFO, $context);
    }
    
    /**
     * Registra un messaggio di livello WARNING
     * @param string $message Messaggio da registrare
     * @param array $context Dati di contesto aggiuntivi
     * @return bool True se il logging è riuscito
     */
    public static function warning($message, $context = [])
    {
        return self::log($message, self::LOG_LEVEL_WARNING, $context);
    }
    
    /**
     * Registra un messaggio di livello ERROR
     * @param string $message Messaggio da registrare
     * @param array $context Dati di contesto aggiuntivi
     * @return bool True se il logging è riuscito
     */
    public static function error($message, $context = [])
    {
        return self::log($message, self::LOG_LEVEL_ERROR, $context);
    }
    
    /**
     * Registra un messaggio di livello DEBUG
     * @param string $message Messaggio da registrare
     * @param array $context Dati di contesto aggiuntivi
     * @return bool True se il logging è riuscito
     */
    public static function debug($message, $context = [])
    {
        // Verifica che il debug sia abilitato
        if (!Configuration::get('ART_PUZZLE_DEBUG_MODE')) {
            return false;
        }
        
        return self::log($message, self::LOG_LEVEL_DEBUG, $context);
    }
    
    /**
     * Formatta un messaggio di log
     * @param string $message Messaggio da formattare
     * @param string $level Livello di log
     * @param array $context Dati di contesto
     * @return string Messaggio formattato
     */
    private static function formatLogMessage($message, $level, $context = [])
    {
        // Data e ora correnti
        $timestamp = date('Y-m-d H:i:s');
        
        // ID del contesto (se disponibile)
        $contextId = isset($context['id']) ? ' [' . $context['id'] . ']' : '';
        
        // Unisce tutto in un unico messaggio
        $formattedMessage = "[{$timestamp}] [{$level}]{$contextId} {$message}";
        
        // Aggiunge il contesto in formato JSON (se presente)
        if (!empty($context) && $context !== ['id' => $context['id']]) {
            $contextData = $context;
            
            // Rimuove l'ID dal contesto per evitare duplicazioni
            if (isset($contextData['id'])) {
                unset($contextData['id']);
            }
            
            // Aggiunge il contesto solo se ci sono ancora dati
            if (!empty($contextData)) {
                $formattedMessage .= ' ' . json_encode($contextData);
            }
        }
        
        return $formattedMessage;
    }
    
    /**
     * Ottiene i log filtrati
     * @param string $level Filtra per livello di log (opzionale)
     * @param string $dateFrom Data di inizio (Y-m-d, opzionale)
     * @param string $dateTo Data di fine (Y-m-d, opzionale)
     * @param string $search Testo da cercare (opzionale)
     * @param int $limit Numero massimo di log da restituire (opzionale)
     * @return array Log filtrati
     */
    public static function getLogs($level = null, $dateFrom = null, $dateTo = null, $search = null, $limit = 100)
    {
        $logs = [];
        $logDir = _PS_MODULE_DIR_ . 'art_puzzle/logs/';
        
        // Verifica che la directory esista
        if (!file_exists($logDir)) {
            return $logs;
        }
        
        // Determina i file di log da esaminare
        $logFiles = [];
        
        if ($dateFrom && $dateTo) {
            // Intervallo di date
            $currentDate = new DateTime($dateFrom);
            $endDate = new DateTime($dateTo);
            
            while ($currentDate <= $endDate) {
                $filename = 'art_puzzle_' . $currentDate->format('Y-m-d') . '.log';
                $filePath = $logDir . $filename;
                
                if (file_exists($filePath)) {
                    $logFiles[] = $filePath;
                }
                
                $currentDate->modify('+1 day');
            }
        } else if ($dateFrom) {
            // Solo data di inizio
            $filename = 'art_puzzle_' . date('Y-m-d', strtotime($dateFrom)) . '.log';
            $filePath = $logDir . $filename;
            
            if (file_exists($filePath)) {
                $logFiles[] = $filePath;
            }
        } else {
            // Tutti i file di log disponibili
            $files = scandir($logDir);
            
            foreach ($files as $file) {
                if (strpos($file, 'art_puzzle_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                    $logFiles[] = $logDir . $file;
                }
            }
            
            // Ordina i file per data (dal più recente)
            usort($logFiles, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
        }
        
        // Legge i log dai file
        $count = 0;
        
        foreach ($logFiles as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            if (!$lines) {
                continue;
            }
            
            // Inverte l'ordine per avere i log più recenti prima
            $lines = array_reverse($lines);
            
            foreach ($lines as $line) {
                // Estrai le informazioni dalla riga di log
                $logInfo = self::parseLogLine($line);
                
                if (!$logInfo) {
                    continue;
                }
                
                // Filtra per livello
                if ($level && $logInfo['level'] !== $level) {
                    continue;
                }
                
                // Filtra per testo di ricerca
                if ($search && stripos($logInfo['message'], $search) === false) {
                    continue;
                }
                
                // Aggiungi il log all'array
                $logs[] = $logInfo;
                
                $count++;
                
                // Limita il numero di log
                if ($limit && $count >= $limit) {
                    break 2; // Esce da entrambi i cicli
                }
            }
        }
        
        return $logs;
    }
    
    /**
     * Analizza una riga di log e ne estrae le informazioni
     * @param string $line Riga di log
     * @return array|false Informazioni estratte o false se la riga non è valida
     */
    private static function parseLogLine($line)
    {
        // Pattern per estrarre le informazioni dalla riga di log
        $pattern = '/\[([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})\] \[(INFO|WARNING|ERROR|DEBUG)\](?:\s+\[([^\]]+)\])?\s+(.+?)(?:\s+(\{.+\}))?$/';
        
        if (!preg_match($pattern, $line, $matches)) {
            return false;
        }
        
        // Estrai le informazioni
        $timestamp = $matches[1];
        $level = $matches[2];
        $contextId = isset($matches[3]) ? $matches[3] : null;
        $message = $matches[4];
        $contextJson = isset($matches[5]) ? $matches[5] : null;
        
        // Decodifica il contesto JSON
        $context = [];
        
        if ($contextJson) {
            $context = json_decode($contextJson, true) ?: [];
        }
        
        // Aggiungi l'ID al contesto
        if ($contextId) {
            $context['id'] = $contextId;
        }
        
        return [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];
    }
    
    /**
     * Pulisce i log più vecchi di un certo numero di giorni
     * @param int $days Numero di giorni da mantenere
     * @return int Numero di file eliminati
     */
    public static function cleanLogs($days = 30)
    {
        $logDir = _PS_MODULE_DIR_ . 'art_puzzle/logs/';
        
        // Verifica che la directory esista
        if (!file_exists($logDir)) {
            return 0;
        }
        
        $files = scandir($logDir);
        $now = time();
        $count = 0;
        
        foreach ($files as $file) {
            if (strpos($file, 'art_puzzle_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                $filePath = $logDir . $file;
                $fileTime = filemtime($filePath);
                
                // Se il file è più vecchio del limite, eliminalo
                if ($now - $fileTime > $days * 86400) {
                    if (unlink($filePath)) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }
}