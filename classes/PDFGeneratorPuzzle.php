<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class PDFGeneratorPuzzle
{
    public static function generateClientPDF($puzzleImagePath, $clientName, $outputFile)
    {
        require_once(_PS_TOOL_DIR_.'tcpdf/tcpdf.php');
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Puzzle di ' . $clientName, 0, 1, 'C');
        if (file_exists($puzzleImagePath)) {
            $pdf->Image($puzzleImagePath, '', '', 150, 150, '', '', 'T');
        } else {
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, 'Immagine non disponibile', 0, 1, 'C');
        }
        $pdf->Output($outputFile, 'F');
        return $outputFile;
    }

    public static function generateAdminPDF($puzzleImagePath, $boxImagePath, $customText, $outputFile)
    {
        require_once(_PS_TOOL_DIR_.'tcpdf/tcpdf.php');
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Dettagli Puzzle per Admin', 0, 1, 'C');
        if (file_exists($puzzleImagePath)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Puzzle:', 0, 1);
            $pdf->Image($puzzleImagePath, '', '', 150, 150, '', '', 'T');
        }
        if (file_exists($boxImagePath)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Scatola:', 0, 1);
            $pdf->Image($boxImagePath, '', '', 150, 150, '', '', 'T');
        }
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Ln(10);
        $pdf->MultiCell(0, 10, 'Testo personalizzato: ' . $customText);
        $pdf->Output($outputFile, 'F');
        return $outputFile;
    }
}
