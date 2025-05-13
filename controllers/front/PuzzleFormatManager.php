<?php

class Art_PuzzleFormatModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $id_product = Tools::getValue('id_product');
        $box_text = Tools::getValue('box_text');

        // Salva il testo della scatola se è stato inviato
        if ($box_text) {
            $this->context->cookie->__set('art_puzzle_box_text', $box_text);
        }

        // Recupera i formati disponibili dal FormatManager
        $formats = PuzzleFormatManager::getAllFormats();

        $this->context->smarty->assign([
            'formats' => $formats,
            'id_product' => $id_product
        ]);

        $this->setTemplate('module:art_puzzle/views/templates/front/format.tpl');
    }
} 
