<?php

class Art_PuzzlePreviewModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $id_product = Tools::getValue('id_product');

        $uploaded_image = $this->context->cookie->__get('art_puzzle_uploaded_image');
        $box_image = $this->context->cookie->__get('art_puzzle_box_image');
        $box_text = $this->context->cookie->__get('art_puzzle_box_text');
        $selected_format = $this->context->cookie->__get('art_puzzle_selected_format');

        $this->context->smarty->assign([
            'id_product' => $id_product,
            'uploaded_image' => _MODULE_DIR_ . 'art_puzzle/upload/' . $uploaded_image,
            'box_image' => $box_image,
            'box_text' => $box_text,
            'selected_format' => $selected_format
        ]);

        $this->setTemplate('module:art_puzzle/views/templates/front/summary.tpl');
    }
}
