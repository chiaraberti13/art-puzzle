<?php
/**
 * Art Puzzle Module - Logger Class
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ArtPuzzleLogger
{
    /** @var string Percorso del file di log */
    protected static $logFile;
    
    /** @var int Dimensione massima del file di log in bytes (5MB) */
    protected static $maxFileSize = 5242880;
    
    /** @var array Livelli di log disponibili */
    protected static $logLevels = ['INFO', 'WARNING', 'ERROR', 'DEBUG'];
    
    /**
     * Inizializza il logger
     */
    public static function init()
    {
        // Imposta il percorso del file di log
        self::$logFile = _PS_MODULE_DIR_ . 'art_puzzle/logs/art_puzzle.log';
        
        // Verifica che la directory logs esista
        $logDir = _PS_MODULE_DIR_ . 'art_puzzle/logs/';
        if (!file_exists($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        // Crea il file se non esiste
        if (!file_exists(self::$logFile)) {
            @file_put_contents(self::$logFile, '');
        }
        
        // Verifica se il file è scrivibile
        if (!is_writable(self::$logFile)) {
            // Se non è scrivibile, tenta di cambiare i permessi
            @chmod(self::$logFile, 0666);
        }
    }
    
    /**
     * Registra un messaggio nel file di log
     *
     * @param string $message Il messaggio da loggare
     * @param string $level Il livello di log (INFO, WARNING, ERROR, DEBUG)
     * @param bool $includeBacktrace Se includere il backtrace
     * @return bool True se il messaggio è stato registrato con successo
     */
    public static function log($message, $level = 'INFO', $includeBacktrace = false)
    {
        // Inizializza il logger se non è già stato fatto
        if (!isset(self::$logFile)) {
            self::init();
        }
        
        // Verifica il livello di log
        $level = strtoupper($level);
        if (!in_array($level, self::$logLevels)) {
            $level = 'INFO';
        }
        
        // Prepara il messaggio di log
        $date = date('Y-m-d H:i:s');
        $entry = "[$date] [$level] $message" . PHP_EOL;
        
        // Aggiungi backtrace se richiesto
        if ($includeBacktrace && $level === 'ERROR') {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            if (count($backtrace) > 1) {
                $entry .= "Backtrace:" . PHP_EOL;
                for ($i = 1; $i < count($backtrace); $i++) {
                    $file = isset($backtrace[$i]['file']) ? $backtrace[$i]['file'] : 'unknown';
                    $line = isset($backtrace[$i]['line']) ? $backtrace[$i]['line'] : 'unknown';
                    $function = isset($backtrace[$i]['function']) ? $backtrace[$i]['function'] : 'unknown';
                    $entry .= "  $file:$line - $function()" . PHP_EOL;
                }
            }
        }
        
        // Controlla se il file è troppo grande
        self::rotateLogIfNeeded();
        
        // Scrivi nel file di log
        return @file_put_contents(self::$logFile, $entry, FILE_APPEND) !== false;
    }
    
    /**
     * Ruota il file di log se diventa troppo grande
     */
    protected static function rotateLogIfNeeded()
    {
        if (!file_exists(self::$logFile)) {
            return;
        }
        
        $fileSize = filesize(self::$logFile);
        if ($fileSize > self::$maxFileSize) {
            // Crea un nuovo nome per il file di backup
            $backupFile = self::$logFile . '.' . date('Y-m-d-H-i-s') . '.bak';
            
            // Rinomina il file corrente
            @rename(self::$logFile, $backupFile);
            
            // Crea un nuovo file di log
            @file_put_contents(self::$logFile, date('Y-m-d H:i:s') . " [INFO] Log file rotated, old log saved as " . basename($backupFile) . PHP_EOL);
            
            // Pulisci i vecchi file di backup (mantieni solo gli ultimi 5)
            self::cleanupOldLogs();
        }
    }
    
    /**
     * Elimina i vecchi file di log di backup
     */
    protected static function cleanupOldLogs()
    {
        $logDir = _PS_MODULE_DIR_ . 'art_puzzle/logs/';
        if (!is_dir($logDir)) {
            return;
        }
        
        // Trova tutti i file di backup
        $backupFiles = glob($logDir . 'art_puzzle.log.*.bak');
        
        // Ordina per data (più recenti prima)
        usort($backupFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Elimina tutti tranne i più recenti 5
        $filesToKeep = 5;
        if (count($backupFiles) > $filesToKeep) {
            for ($i = $filesToKeep; $i < count($backupFiles); $i++) {
                @unlink($backupFiles[$i]);
            }
        }
    }
    
    /**
     * Pulisce completamente i log
     *
     * @return bool True se i log sono stati puliti con successo
     */
    public static function clearLogs()
    {
        // Inizializza il logger se non è già stato fatto
        if (!isset(self::$logFile)) {
            self::init();
        }
        
        // Elimina tutti i file di backup
        $logDir = _PS_MODULE_DIR_ . 'art_puzzle/logs/';
        if (is_dir($logDir)) {
            $backupFiles = glob($logDir . 'art_puzzle.log.*.bak');
            foreach ($backupFiles as $file) {
                @unlink($file);
            }
        }
        
        // Svuota il file di log principale
        $success = @file_put_contents(self::$logFile, date('Y-m-d H:i:s') . " [INFO] Logs cleared" . PHP_EOL) !== false;
        
        return $success;
    }
    
    /**
     * Restituisce il contenuto del file di log
     *
     * @param int $lines Numero di righe da leggere (0 = tutte)
     * @return string Il contenuto del file di log
     */
    public static function getLogContent($lines = 0)
    {
        // Inizializza il logger se non è già stato fatto
        if (!isset(self::$logFile)) {
            self::init();
        }
        
        if (!file_exists(self::$logFile)) {
            return '';
        }
        
        if ($lines <= 0) {
            // Leggi l'intero file
            return @file_get_contents(self::$logFile);
        } else {
            // Leggi solo le ultime n righe
            $file = new SplFileObject(self::$logFile, 'r');
            $file->seek(PHP_INT_MAX); // Vai alla fine del file
            $totalLines = $file->key(); // Ottieni il numero totale di righe
            
            if ($totalLines <= $lines) {
                // Se ci sono meno righe di quelle richieste, leggi tutto il file
                return @file_get_contents(self::$logFile);
            }
            
            // Vai alla riga desiderata
            $file->seek($totalLines - $lines);
            
            // Leggi le righe rimanenti
            $content = '';
            while (!$file->eof()) {
                $content .= $file->fgets();
            }
            
            return $content;
        }
    }
}