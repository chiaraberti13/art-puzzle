<?php
/**
 * Art Puzzle - Generatore PDF
 * Classe che gestisce la creazione di PDF per i puzzle personalizzati
 */

class PDFGeneratorPuzzle
{
    /**
     * Genera un PDF per il cliente con l'anteprima del puzzle
     * @param string $imagePath Percorso dell'immagine del puzzle
     * @param string $customerName Nome del cliente
     * @param string $outputPath Percorso di output del PDF
     * @return bool True se la generazione è riuscita
     */
    public static function generateClientPDF($imagePath, $customerName, $outputPath)
    {
        // Verifica che l'immagine esista
        if (!file_exists($imagePath)) {
            return false;
        }
        
        // Crea un nuovo documento PDF
        require_once(_PS_TOOL_DIR_ . 'tcpdf/tcpdf.php');
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Imposta le informazioni del documento
        $pdf->SetCreator('Art Puzzle');
        $pdf->SetAuthor('Art Puzzle');
        $pdf->SetTitle('Il tuo puzzle personalizzato');
        $pdf->SetSubject('Anteprima del puzzle personalizzato');
        $pdf->SetKeywords('puzzle, personalizzato, anteprima');
        
        // Rimuovi header e footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Imposta margini
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        
        // Imposta auto page breaks
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        
        // Imposta il fattore di scala dell'immagine
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Aggiungi una pagina
        $pdf->AddPage();
        
        // Titolo
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 15, 'Il tuo puzzle personalizzato', 0, true, 'C');
        
        // Aggiungi un po' di spazio
        $pdf->Ln(10);
        
        // Dati cliente
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Cliente: ' . $customerName, 0, true, 'L');
        $pdf->Cell(0, 10, 'Data: ' . date('d/m/Y'), 0, true, 'L');
        
        // Aggiungi un po' di spazio
        $pdf->Ln(10);
        
        // Testo informativo
        $pdf->SetFont('helvetica', 'I', 12);
        $pdf->MultiCell(0, 10, 'Ecco l\'anteprima del tuo puzzle personalizzato. Questo documento serve come riferimento per il tuo ordine.', 0, 'L', 0, 1, '', '', true);
        
        // Aggiungi un po' di spazio
        $pdf->Ln(10);
        
        // Immagine del puzzle
        $pdf->Image($imagePath, '', '', 160, 0, '', '', 'T', false, 300, 'C');
        
        // Aggiungi un po' di spazio
        $pdf->Ln(10);
        
        // Note finali
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 10, 'Nota: L\'immagine finale potrebbe differire leggermente in colore e proporzioni rispetto a questa anteprima, a seconda del processo di stampa.', 0, 'L', 0, 1, '', '', true);
        
        // Salva il PDF
        return $pdf->Output($outputPath, 'F');
    }
    
    /**
     * Genera un PDF per l'amministratore con tutti i dettagli dell'ordine
     * @param string $puzzleImagePath Percorso dell'immagine del puzzle
     * @param string $boxImagePath Percorso dell'immagine della scatola
     * @param string $boxText Testo sulla scatola
     * @param string $outputPath Percorso di output del PDF
     * @return bool True se la generazione è riuscita
     */
    public static function generateAdminPDF($puzzleImagePath, $boxImagePath, $boxText, $outputPath)
    {
        // Verifica che l'immagine del puzzle esista
        if (!file_exists($puzzleImagePath)) {
            return false;
        }
        
        // Crea un nuovo documento PDF
        require_once(_PS_TOOL_DIR_ . 'tcpdf/tcpdf.php');
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Imposta le informazioni del documento
        $pdf->SetCreator('Art Puzzle');
        $pdf->SetAuthor('Art Puzzle');
        $pdf->SetTitle('Dettagli puzzle personalizzato');
        $pdf->SetSubject('Dettagli completi dell\'ordine per puzzle personalizzato');
        $pdf->SetKeywords('puzzle, personalizzato, amministrazione, ordine');
        
        // Rimuovi header e footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Imposta margini
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        
        // Imposta auto page breaks
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        
        // Imposta il fattore di scala dell'immagine
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Aggiungi una pagina
        $pdf->AddPage();
        
        // Titolo
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 15, 'Dettagli puzzle personalizzato', 0, true, 'C');
        
        // Data e ora generazione
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'Generato il: ' . date('d/m/Y H:i:s'), 0, true, 'R');
        
        // Aggiungi un po' di spazio
        $pdf->Ln(10);
        
        // Dettagli personalizzazione
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Dettagli personalizzazione', 0, true, 'L');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Testo sulla scatola: ' . $boxText, 0, true, 'L');
        
        // Ottieni le dimensioni dell'immagine
        $imageInfo = getimagesize($puzzleImagePath);
        if ($imageInfo) {
            $pdf->Cell(0, 10, 'Dimensioni immagine: ' . $imageInfo[0] . 'x' . $imageInfo[1] . ' pixel', 0, true, 'L');
        }
        
        // Aggiungi un po' di spazio
        $pdf->Ln(10);
        
        // Immagine del puzzle
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Anteprima puzzle', 0, true, 'L');
        
        $pdf->Image($puzzleImagePath, '', '', 120, 0, '', '', 'T', false, 300, 'C');
        
        // Aggiungi un po' di spazio
        $pdf->Ln(15);
        
        // Immagine della scatola (se disponibile)
        if (!empty($boxImagePath) && file_exists($boxImagePath)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Anteprima scatola', 0, true, 'L');
            
            $pdf->Image($boxImagePath, '', '', 120, 0, '', '', 'T', false, 300, 'C');
            
            // Aggiungi un po' di spazio
            $pdf->Ln(15);
        }
        
        // Istruzioni di produzione
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Istruzioni di produzione', 0, true, 'L');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(0, 10, '1. Stampare l\'immagine del puzzle ad alta risoluzione.', 0, 'L', 0, 1, '', '', true);
        $pdf->MultiCell(0, 10, '2. Verificare che i colori corrispondano all\'immagine originale.', 0, 'L', 0, 1, '', '', true);
        $pdf->MultiCell(0, 10, '3. Tagliare il puzzle secondo lo schema di taglio selezionato.', 0, 'L', 0, 1, '', '', true);
        $pdf->MultiCell(0, 10, '4. Preparare la scatola con il testo e il colore specificati.', 0, 'L', 0, 1, '', '', true);
        
        // Aggiungi un po' di spazio
        $pdf->Ln(10);
        
        // Note per la produzione
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Note', 0, true, 'L');
        
        $pdf->SetFont('helvetica', 'I', 12);
        $pdf->MultiCell(0, 10, 'Assicurarsi che l\'immagine sia centrata correttamente durante il processo di stampa. Verificare la qualità del testo sulla scatola.', 0, 'L', 0, 1, '', '', true);
        
        // Aggiungi una seconda pagina per le istruzioni dettagliate
        $pdf->AddPage();
        
        // Titolo
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 15, 'Specifiche tecniche', 0, true, 'C');
        
        // Aggiungi un po' di spazio
        $pdf->Ln(10);
        
        // Tabella delle specifiche
        $pdf->SetFont('helvetica', 'B', 12);
        
        // Intestazioni tabella
        $pdf->Cell(60, 10, 'Parametro', 1, 0, 'C', 0);
        $pdf->Cell(120, 10, 'Valore', 1, 1, 'C', 0);
        
        // Contenuto tabella
        $pdf->SetFont('helvetica', '', 12);
        
        // Dimensioni immagine
        if ($imageInfo) {
            $pdf->Cell(60, 10, 'Dimensioni immagine', 1, 0, 'L', 0);
            $pdf->Cell(120, 10, $imageInfo[0] . 'x' . $imageInfo[1] . ' pixel', 1, 1, 'L', 0);
        }
        
        // Formato file
        $pdf->Cell(60, 10, 'Formato file', 1, 0, 'L', 0);
        $pdf->Cell(120, 10, pathinfo($puzzleImagePath, PATHINFO_EXTENSION), 1, 1, 'L', 0);
        
        // Testo scatola
        $pdf->Cell(60, 10, 'Testo scatola', 1, 0, 'L', 0);
        $pdf->Cell(120, 10, $boxText, 1, 1, 'L', 0);
        
        // Timestamp creazione
        $pdf->Cell(60, 10, 'Data creazione', 1, 0, 'L', 0);
        $pdf->Cell(120, 10, date('d/m/Y H:i:s'), 1, 1, 'L', 0);
        
        // Nome file
        $pdf->Cell(60, 10, 'Nome file immagine', 1, 0, 'L', 0);
        $pdf->Cell(120, 10, basename($puzzleImagePath), 1, 1, 'L', 0);
        
        // Aggiungi un po' di spazio
        $pdf->Ln(10);
        
        // Note amministrative
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Note amministrative', 0, true, 'L');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(0, 10, 'Si prega di conservare una copia di questo documento insieme all\'ordine. Per qualsiasi domanda o chiarimento, contattare il reparto produzione.', 0, 'L', 0, 1, '', '', true);
        
        // Aggiungi un po' di spazio
        $pdf->Ln(10);
        
        // Informazioni di produzione
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Informazioni di produzione', 0, true, 'L');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(0, 10, 'ID Produzione: ' . time() . '_' . substr(md5(uniqid()), 0, 8), 0, 'L', 0, 1, '', '', true);
        $pdf->MultiCell(0, 10, 'Operatore: ___________________________', 0, 'L', 0, 1, '', '', true);
        $pdf->MultiCell(0, 10, 'Data completamento: _____ / _____ / _________', 0, 'L', 0, 1, '', '', true);
        $pdf->MultiCell(0, 10, 'Note aggiuntive: ____________________________________________', 0, 'L', 0, 1, '', '', true);
        
        // Salva il PDF
        return $pdf->Output($outputPath, 'F');
    }
    
    /**
     * Genera un PDF con tutte le facce della scatola per la stampa
     * @param string $boxData Dati della scatola
     * @param string $puzzleImagePath Percorso dell'immagine del puzzle
     * @param string $outputPath Percorso di output del PDF
     * @return bool True se la generazione è riuscita
     */
    public static function generateBoxPrintablePDF($boxData, $puzzleImagePath, $outputPath)
    {
        // Verifica che l'immagine del puzzle esista
        if (!file_exists($puzzleImagePath)) {
            return false;
        }
        
        // Crea un nuovo documento PDF
        require_once(_PS_TOOL_DIR_ . 'tcpdf/tcpdf.php');
        
        // Usa un formato più grande per la stampa
        $pdf = new TCPDF('L', PDF_UNIT, 'A3', true, 'UTF-8', false);
        
        // Imposta le informazioni del documento
        $pdf->SetCreator('Art Puzzle');
        $pdf->SetAuthor('Art Puzzle');
        $pdf->SetTitle('Schema di stampa scatola puzzle');
        $pdf->SetSubject('Schema per la stampa della scatola del puzzle personalizzato');
        $pdf->SetKeywords('puzzle, scatola, stampa, schema');
        
        // Rimuovi header e footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Imposta margini minimi
        $pdf->SetMargins(10, 10, 10);
        
        // Imposta auto page breaks
        $pdf->SetAutoPageBreak(true, 10);
        
        // Aggiungi una pagina
        $pdf->AddPage();
        
        // Titolo
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 15, 'Schema di stampa - Scatola puzzle personalizzata', 0, true, 'C');
        
        // Data e ID
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'ID: ' . time() . '_' . substr(md5(uniqid()), 0, 8), 0, true, 'R');
        $pdf->Cell(0, 10, 'Data: ' . date('d/m/Y'), 0, true, 'R');
        
        // Aggiungi un po' di spazio
        $pdf->Ln(10);
        
        // Carica la classe del box manager
        require_once(_PS_MODULE_DIR_ . 'art_puzzle/classes/PuzzleBoxManager.php');
        
        // Imposta l'orientamento del documento per adattarsi allo schema
        $pdf->setPageOrientation('L');
        
        // Verifica che ci siano i dati della scatola
        if (!isset($boxData['template'])) {
            $boxData['template'] = 'classic';
        }
        
        if (!isset($boxData['color'])) {
            $boxData['color'] = '#ffffff';
        }
        
        if (!isset($boxData['text'])) {
            $boxData['text'] = 'Il mio puzzle';
        }
        
        // Ottieni il template
        $template = PuzzleBoxManager::getBoxTemplate($boxData['template']);
        
        if (!$template) {
            // Se il template non esiste, usa il template predefinito
            $templates = PuzzleBoxManager::getDefaultBoxTemplates();
            $template = reset($templates);
        }
        
        // Ottieni l'immagine della scatola (solo la copertina)
        $boxImagePath = PuzzleBoxManager::generateBoxPreview($boxData, $puzzleImagePath);
        
        if (!$boxImagePath || !file_exists($boxImagePath)) {
            // Se non è possibile generare l'anteprima, usa un'immagine vuota
            $boxImagePath = _PS_MODULE_DIR_ . 'art_puzzle/views/img/scatole_base/' . $template['background'];
        }
        
        // Definisci le dimensioni della scatola (in mm)
        $boxWidth = 220;  // Larghezza
        $boxHeight = 160; // Altezza
        $boxDepth = 40;   // Profondità
        
        // Schema delle facce della scatola (coperchio)
        
        // Faccia superiore (top)
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Coperchio - Faccia superiore', 0, true, 'L');
        
        // Disegna un rettangolo per la faccia superiore
        $pdf->Rect(20, 60, $boxWidth, $boxHeight, 'D');
        
        // Inserisci l'immagine della faccia superiore
        $pdf->Image($boxImagePath, 20, 60, $boxWidth, $boxHeight);
        
        // Etichetta la faccia
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Text(20, 55, 'Faccia superiore (TOP)');
        
        // Faccia frontale (front)
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Coperchio - Faccia frontale', 0, true, 'L');
        
        // Disegna un rettangolo per la faccia frontale
        $pdf->Rect(20, 230, $boxWidth, $boxDepth, 'D');
        
        // Etichetta la faccia
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Text(20, 225, 'Faccia frontale (FRONT)');
        
        // Faccia posteriore (back)
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Coperchio - Faccia posteriore', 0, true, 'L');
        
        // Disegna un rettangolo per la faccia posteriore
        $pdf->Rect(250, 230, $boxWidth, $boxDepth, 'D');
        
        // Etichetta la faccia
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Text(250, 225, 'Faccia posteriore (BACK)');
        
        // Faccia laterale sinistra (left)
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Coperchio - Faccia laterale sinistra', 0, true, 'L');
        
        // Disegna un rettangolo per la faccia laterale sinistra
        $pdf->Rect(480, 60, $boxDepth, $boxHeight, 'D');
        
        // Etichetta la faccia
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Text(480, 55, 'Faccia laterale sinistra (LEFT)');
        
        // Faccia laterale destra (right)
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Coperchio - Faccia laterale destra', 0, true, 'L');
        
        // Disegna un rettangolo per la faccia laterale destra
        $pdf->Rect(530, 60, $boxDepth, $boxHeight, 'D');
        
        // Etichetta la faccia
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Text(530, 55, 'Faccia laterale destra (RIGHT)');
        
        // Note e istruzioni
        $pdf->SetY(-40);
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->MultiCell(0, 10, 'Note: Questo schema mostra le dimensioni e la disposizione delle facce della scatola del puzzle. Utilizzare come guida per il taglio e la piegatura del materiale.', 0, 'L', 0, 1, '', '', true);
        
        // Aggiungi una seconda pagina per la base della scatola
        $pdf->AddPage();
        
        // Titolo
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 15, 'Schema di stampa - Base scatola puzzle', 0, true, 'C');
        
        // Schema delle facce della scatola (base)
        
        // Faccia inferiore (bottom)
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Base - Faccia inferiore', 0, true, 'L');
        
        // Disegna un rettangolo per la faccia inferiore
        $pdf->Rect(20, 60, $boxWidth, $boxHeight, 'D');
        
        // Etichetta la faccia
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Text(20, 55, 'Faccia inferiore (BOTTOM)');
        
        // Faccia frontale (front)
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Base - Faccia frontale', 0, true, 'L');
        
        // Disegna un rettangolo per la faccia frontale
        $pdf->Rect(20, 230, $boxWidth, $boxDepth, 'D');
        
        // Etichetta la faccia
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Text(20, 225, 'Faccia frontale (FRONT)');
        
        // Faccia posteriore (back)
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Base - Faccia posteriore', 0, true, 'L');
        
        // Disegna un rettangolo per la faccia posteriore
        $pdf->Rect(250, 230, $boxWidth, $boxDepth, 'D');
        
        // Etichetta la faccia
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Text(250, 225, 'Faccia posteriore (BACK)');
        
        // Faccia laterale sinistra (left)
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Base - Faccia laterale sinistra', 0, true, 'L');
        
        // Disegna un rettangolo per la faccia laterale sinistra
        $pdf->Rect(480, 60, $boxDepth, $boxHeight, 'D');
        
        // Etichetta la faccia
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Text(480, 55, 'Faccia laterale sinistra (LEFT)');
        
        // Faccia laterale destra (right)
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Base - Faccia laterale destra', 0, true, 'L');
        
        // Disegna un rettangolo per la faccia laterale destra
        $pdf->Rect(530, 60, $boxDepth, $boxHeight, 'D');
        
        // Etichetta la faccia
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Text(530, 55, 'Faccia laterale destra (RIGHT)');
        
        // Note e istruzioni
        $pdf->SetY(-40);
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->MultiCell(0, 10, 'Note: Le linee tratteggiate indicano i punti di piegatura. Le linee continue indicano i punti di taglio. Lasciare un margine di 5mm per le alette di incollaggio.', 0, 'L', 0, 1, '', '', true);
        
        // Pulisci file temporanei (anteprima scatola)
        if ($boxImagePath != _PS_MODULE_DIR_ . 'art_puzzle/views/img/scatole_base/' . $template['background']) {
            @unlink($boxImagePath);
        }
        
        // Salva il PDF
        return $pdf->Output($outputPath, 'F');
    }
}