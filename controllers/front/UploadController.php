<?php

class Art_PuzzleUploadModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $id_product = Tools::getValue('id_product');
        $format = Tools::getValue('puzzle_format');

        if ($format) {
            $this->context->cookie->__set('art_puzzle_selected_format', $format);
        }

        // Usa estensioni di file invece di tipi MIME
        $this->context->smarty->assign('allowed_file_types', ['jpg', 'jpeg', 'png', 'gif']);
        $this->context->smarty->assign([
            'id_product' => $id_product,
            'selected_format' => $format,
            'upload_max_size' => Configuration::get('ART_PUZZLE_MAX_UPLOAD_SIZE', 10), // dimensione massima in MB
            'enable_orientation' => true,
            'enable_crop_tool' => true,
            'max_box_text_length' => 50,
            'default_box_text' => 'Il mio puzzle personalizzato',
            'puzzleAjaxUrl' => $this->context->link->getModuleLink('art_puzzle', 'ajax'),
            'securityToken' => Tools::getToken(false)
        ]);

        $this->setTemplate('module:art_puzzle/views/templates/front/customizer.tpl');
    }
}