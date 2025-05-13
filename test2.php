<?php
/**
 * Art Puzzle - Script di correzione automatica
 * 
 * Questo script corregge automaticamente i problemi identificati nei file del modulo art_puzzle.
 * Eseguire questo script dalla directory principale del modulo.
 */

class ArtPuzzleFixer {
    protected $moduleDir;
    protected $backupDir;
    protected $fixes = [];

    public function __construct() {
        // Rileva la directory del modulo
        $this->moduleDir = __DIR__ . '/';
        
        // Crea la directory di backup
        $this->backupDir = $this->moduleDir . 'backup_' . date('Y-m-d_H-i-s') . '/';
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Esegue tutte le correzioni
     */
    public function fixAll() {
        echo "<h1>Art Puzzle - Correzione Automatica</h1>";
        echo "<p>Data e ora: " . date('Y-m-d H:i:s') . "</p>";
        
        // Correzione 1: ArtPuzzleLogger.php
        $this->fixLoggerClass();
        
        // Correzione 2: displayProductButtons.tpl
        $this->fixDisplayProductButtonsTpl();
        
        // Correzione 3: Integrare correttamente front.js con cropper-integration.js
        $this->fixFrontJs();
        
        // Correzione 4: Sincronizzare controllers/front/ajax.php con ajax.php della root
        $this->fixAjaxController();
        
        // Visualizza riassunto delle correzioni
        $this->showSummary();
    }

    /**
     * Corregge la classe ArtPuzzleLogger
     */
    protected function fixLoggerClass() {
        $file = $this->moduleDir . 'classes/ArtPuzzleLogger.php';
        if (!file_exists($file)) {
            $this->addFix('ArtPuzzleLogger.php', 'ERROR', 'File non trovato');
            return;
        }
        
        // Backup del file originale
        $this->backupFile($file);
        
        // Leggi il contenuto del file
        $content = file_get_contents($file);
        
        // Correggi il metodo formatLogMessage
        $oldCode = <<<'EOD'
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
EOD;

        $newCode = <<<'EOD'
    private static function formatLogMessage($message, $level, $context = [])
    {
        // Data e ora correnti
        $timestamp = date('Y-m-d H:i:s');
        
        // ID del contesto (se disponibile)
        $contextId = isset($context['id']) ? ' [' . $context['id'] . ']' : '';
        
        // Unisce tutto in un unico messaggio
        $formattedMessage = "[{$timestamp}] [{$level}]{$contextId} {$message}";
        
        // Aggiunge il contesto in formato JSON (se presente)
        if (!empty($context)) {
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
EOD;

        // Applica la correzione
        $newContent = str_replace($oldCode, $newCode, $content);
        
        // Salva il file modificato
        if ($content !== $newContent) {
            file_put_contents($file, $newContent);
            $this->addFix('ArtPuzzleLogger.php', 'SUCCESS', 'Corretta gestione degli indici nel metodo formatLogMessage');
        } else {
            $this->addFix('ArtPuzzleLogger.php', 'INFO', 'Nessuna modifica necessaria');
        }
    }

    /**
     * Corregge il template displayProductButtons.tpl
     */
    protected function fixDisplayProductButtonsTpl() {
        $file = $this->moduleDir . 'views/templates/hook/displayProductButtons.tpl';
        if (!file_exists($file)) {
            $this->addFix('displayProductButtons.tpl', 'ERROR', 'File non trovato');
            return;
        }
        
        // Backup del file originale
        $this->backupFile($file);
        
        // Leggi il contenuto del file
        $content = file_get_contents($file);
        
        // Correggi la sintassi JavaScript
        $oldCode = 'tabContent.scrollIntoView({ldelim}behavior: "smooth"{rdelim});';
        $newCode = 'tabContent.scrollIntoView({"behavior": "smooth"});';
        
        // Applica la correzione
        $newContent = str_replace($oldCode, $newCode, $content);
        
        // Semplifica il flusso di navigazione
        $oldScript = <<<'EOD'
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Attiva il click sul pulsante di personalizzazione
    document.getElementById('art-puzzle-customize-btn').addEventListener('click', function() {
        // Cerca il tab del puzzle
        var tabs = document.querySelectorAll('.nav-tabs .nav-link');
        var tabFound = false;
        
        for (var i = 0; i < tabs.length; i++) {
            if (tabs[i].textContent.indexOf('Personalizza il tuo puzzle') !== -1) {
                // Attiva questo tab
                tabs[i].click();
                tabFound = true;
                
                // Scroll alla sezione
                setTimeout(function() {
                    var tabContent = document.getElementById('art-puzzle-tab-content');
                    if (tabContent) {
                        tabContent.scrollIntoView({ldelim}behavior: "smooth"{rdelim});
                        
                        // Trova e attiva il pulsante "Inizia a personalizzare"
                        var startBtn = document.getElementById('art-puzzle-start-customize');
                        if (startBtn) {
                            startBtn.click();
                        }
                    }
                }, 300);
                
                break;
            }
        }
        
        // Se non trova il tab (per qualche motivo), reindirizza alla pagina del personalizzatore
        if (!tabFound) {
            window.location.href = '{$link->getModuleLink('art_puzzle', 'customizer', ['id_product' => $id_product])|escape:'javascript'}';
        }
    });
});
</script>
EOD;

        $newScript = <<<'EOD'
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Attiva il click sul pulsante di personalizzazione
    document.getElementById('art-puzzle-customize-btn').addEventListener('click', function() {
        // Reindirizza direttamente alla pagina del personalizzatore
        window.location.href = '{$link->getModuleLink('art_puzzle', 'customizer', ['id_product' => $id_product])|escape:'javascript'}';
    });
});
</script>
EOD;

        // Applica la correzione del flusso
        $newContent = str_replace($oldScript, $newScript, $newContent);
        
        // Salva il file modificato
        if ($content !== $newContent) {
            file_put_contents($file, $newContent);
            $this->addFix('displayProductButtons.tpl', 'SUCCESS', 'Corretta sintassi JS e semplificato flusso di navigazione');
        } else {
            $this->addFix('displayProductButtons.tpl', 'INFO', 'Nessuna modifica necessaria');
        }
    }

    /**
     * Corregge l'integrazione front.js con cropper-integration.js
     */
    protected function fixFrontJs() {
        $file = $this->moduleDir . 'views/js/front.js';
        if (!file_exists($file)) {
            $this->addFix('front.js', 'ERROR', 'File non trovato');
            return;
        }
        
        // Backup del file originale
        $this->backupFile($file);
        
        // Leggi il contenuto del file
        $content = file_get_contents($file);
        
        // Correggi l'inizializzazione di Cropper
        $oldCode = <<<'EOD'
    /**
     * Inizializza il ritaglio dell'immagine
     */
    function initCropper() {
        if (!cropImg || !artPuzzleEnableCropTool) {
            return;
        }
        
        // Distruggi l'istanza precedente se esiste
        destroyCropper();
        
        // Crea una nuova istanza
        cropper = new Cropper(cropImg, {
            aspectRatio: 1, // Default 1:1, sarà aggiornato in base al formato scelto
            viewMode: 1,
            guides: true,
            center: true,
            movable: true,
            zoomable: true,
            scalable: false,
            rotatable: false, // Gestiamo la rotazione manualmente
            autoCropArea: 0.8,
            responsive: true,
            ready: function() {
                // Carica i formati disponibili per il puzzle
                loadPuzzleFormats();
            }
        });
EOD;

        $newCode = <<<'EOD'
    /**
     * Inizializza il ritaglio dell'immagine
     */
    function initCropper() {
        if (!cropImg || !artPuzzleEnableCropTool) {
            return;
        }
        
        // Distruggi l'istanza precedente se esiste
        destroyCropper();
        
        // Verifica se la classe ArtPuzzleCropper è disponibile
        if (window.ArtPuzzleCropper && typeof window.ArtPuzzleCropper === 'function') {
            // Usa la classe di integrazione personalizzata
            cropper = new ArtPuzzleCropper({
                imageElement: cropImg,
                aspectRatio: 1,
                onReadyCallback: function() {
                    // Carica i formati disponibili per il puzzle
                    loadPuzzleFormats();
                }
            });
        } else {
            // Fallback alla libreria Cropper nativa
            cropper = new Cropper(cropImg, {
                aspectRatio: 1, // Default 1:1, sarà aggiornato in base al formato scelto
                viewMode: 1,
                guides: true,
                center: true,
                movable: true,
                zoomable: true,
                scalable: false,
                rotatable: false, // Gestiamo la rotazione manualmente
                autoCropArea: 0.8,
                responsive: true,
                ready: function() {
                    // Carica i formati disponibili per il puzzle
                    loadPuzzleFormats();
                }
            });
        }
EOD;

        // Applica la correzione
        $newContent = str_replace($oldCode, $newCode, $content);
        
        // Correggi anche la funzione di distruzione del cropper
        $oldDestroyCode = <<<'EOD'
    /**
     * Distrugge l'istanza del cropper
     */
    function destroyCropper() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }
EOD;

        $newDestroyCode = <<<'EOD'
    /**
     * Distrugge l'istanza del cropper
     */
    function destroyCropper() {
        if (cropper) {
            // Verifica se è un'istanza di ArtPuzzleCropper o Cropper
            if (typeof cropper.destroy === 'function') {
                cropper.destroy();
            }
            cropper = null;
        }
    }
EOD;

        // Applica la correzione
        $newContent = str_replace($oldDestroyCode, $newDestroyCode, $newContent);
        
        // Salva il file modificato
        if ($content !== $newContent) {
            file_put_contents($file, $newContent);
            $this->addFix('front.js', 'SUCCESS', 'Migliorata integrazione con ArtPuzzleCropper');
        } else {
            $this->addFix('front.js', 'INFO', 'Nessuna modifica necessaria');
        }
    }

    /**
     * Corregge il controller AJAX
     */
    protected function fixAjaxController() {
        // Il file principale ajax.php già contiene tutti i metodi necessari
        // Dobbiamo solo assicurarci che il controller front/ajax.php sia sincronizzato
        
        $mainAjaxFile = $this->moduleDir . 'ajax.php';
        $frontAjaxFile = $this->moduleDir . 'controllers/front/ajax.php';
        
        if (!file_exists($mainAjaxFile) || !file_exists($frontAjaxFile)) {
            $this->addFix('controllers/front/ajax.php', 'ERROR', 'File necessari non trovati');
            return;
        }
        
        // Backup del file front/ajax.php originale
        $this->backupFile($frontAjaxFile);
        
        // Leggi il contenuto del file ajax.php principale
        $mainContent = file_get_contents($mainAjaxFile);
        
        // Estrai il blocco postProcess che contiene i case statement per le azioni
        preg_match('/public function postProcess\(\)\s*\{(.*?)switch \(\$action\) \{(.*?)\}\s*\} catch/s', $mainContent, $matches);
        
        if (!isset($matches[2])) {
            $this->addFix('controllers/front/ajax.php', 'ERROR', 'Impossibile estrarre i metodi dal file principale');
            return;
        }
        
        $caseBlocks = $matches[2];
        
        // Leggi il contenuto del file controllers/front/ajax.php
        $frontContent = file_get_contents($frontAjaxFile);
        
        // Aggiorna il blocco switch nel controller front
        $pattern = '/switch \(\$action\) \{(.*?)\}\s*\} catch/s';
        $replacement = 'switch ($action) {' . $caseBlocks . '} catch';
        
        // Applica la sostituzione solo se il pattern corrisponde
        if (preg_match($pattern, $frontContent)) {
            $newFrontContent = preg_replace($pattern, $replacement, $frontContent);
            
            // Salva il file modificato
            file_put_contents($frontAjaxFile, $newFrontContent);
            $this->addFix('controllers/front/ajax.php', 'SUCCESS', 'Aggiornati i metodi per gestire tutte le azioni necessarie');
        } else {
            $this->addFix('controllers/front/ajax.php', 'WARNING', 'Impossibile aggiornare il blocco switch. Verifica manualmente.');
        }
    }

    /**
     * Esegue backup di un file
     */
    protected function backupFile($file) {
        $relativePath = str_replace($this->moduleDir, '', $file);
        $backupPath = $this->backupDir . $relativePath;
        
        // Assicurati che la directory di destinazione esista
        $backupDir = dirname($backupPath);
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Copia il file
        copy($file, $backupPath);
    }

    /**
     * Aggiunge una correzione al registro
     */
    protected function addFix($file, $status, $message) {
        $this->fixes[] = [
            'file' => $file,
            'status' => $status,
            'message' => $message
        ];
    }

    /**
     * Mostra il riepilogo delle correzioni
     */
    protected function showSummary() {
        echo "<h2>Riepilogo delle correzioni</h2>";
        echo "<p>Backup creato in: <code>" . $this->backupDir . "</code></p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>File</th><th>Stato</th><th>Messaggio</th></tr>";
        
        foreach ($this->fixes as $fix) {
            $statusColor = '';
            switch ($fix['status']) {
                case 'SUCCESS':
                    $statusColor = 'green';
                    break;
                case 'WARNING':
                    $statusColor = 'orange';
                    break;
                case 'ERROR':
                    $statusColor = 'red';
                    break;
                case 'INFO':
                    $statusColor = 'blue';
                    break;
            }
            
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($fix['file']) . "</code></td>";
            echo "<td style='color: $statusColor; font-weight: bold;'>" . htmlspecialchars($fix['status']) . "</td>";
            echo "<td>" . htmlspecialchars($fix['message']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<h2>Prossimi passaggi</h2>";
        echo "<ol>";
        echo "<li>Verifica che il modulo funzioni correttamente</li>";
        echo "<li>In caso di problemi, ripristina i file di backup dalla directory: <code>" . $this->backupDir . "</code></li>";
        echo "<li>Se necessario, consulta la documentazione del modulo o contatta il supporto tecnico</li>";
        echo "</ol>";
    }
}

// Esegui lo script di correzione
$fixer = new ArtPuzzleFixer();
$fixer->fixAll();