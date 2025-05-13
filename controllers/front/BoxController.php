<?php

class Art_PuzzleBoxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $id_product = Tools::getValue('id_product');
        $box_image = Tools::getValue('box_image');
        $box_text = Tools::getValue('box_text');

        // Salva i dati nella sessione o nel cookie per il recupero successivo
        $this->context->cookie->__set('art_puzzle_box_image', $box_image);
        $this->context->cookie->__set('art_puzzle_box_text', $box_text);

        $this->context->smarty->assign([
            'box_image' => $box_image,
            'box_text' => $box_text,
            'id_product' => $id_product
        ]);

        $this->setTemplate('module:art_puzzle/views/templates/front/box.tpl');
    }
} 
