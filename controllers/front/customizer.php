<?php
/**
 * Art Puzzle - Customizer Controller
 * Controller per la pagina di personalizzazione puzzle
 */

class Art_PuzzleCustomizerModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        
        // Controlla che l'utente sia loggato
        if (!$this->context->customer->isLogged() && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED')) {
            Tools::redirect('index.php?controller=authentication&back=' . urlencode($this->context->link->getModuleLink('art_puzzle', 'customizer', array('id_product' => Tools::getValue('id_product')))));
            return;
        }
        
        $id_product = (int)Tools::getValue('id_product');
        
        // Verificare che il prodotto esista e sia un puzzle personalizzabile
        if (!$id_product || !$this->module->isPuzzleProduct($id_product)) {
            $this->errors[] = $this->trans('Questo prodotto non è disponibile o non è personalizzabile.', [], 'Modules.Artpuzzle.Shop');
            $this->redirectWithNotifications($this->context->link->getPageLink('index'));
            return;
        }
        
        // Carica i dati del prodotto
        $product = new Product($id_product, true, $this->context->language->id);
        if (!Validate::isLoadedObject($product)) {
            $this->errors[] = $this->trans('Prodotto non trovato', [], 'Modules.Artpuzzle.Shop');
            $this->redirectWithNotifications($this->context->link->getPageLink('index'));
            return;
        }
        
        // Prepara i dati per il template
        $boxColors = Configuration::get('ART_PUZZLE_BOX_COLORS');
        $fonts = Configuration::get('ART_PUZZLE_FONTS');
        
        $this->context->smarty->assign([
            'id_product' => $id_product,
            'product' => $product,
            'product_url' => $this->context->link->getProductLink($id_product),
            'upload_max_size' => Configuration::get('ART_PUZZLE_MAX_UPLOAD_SIZE'),
            'allowed_file_types' => explode(',', Configuration::get('ART_PUZZLE_ALLOWED_FILE_TYPES')),
            'upload_folder' => Configuration::get('ART_PUZZLE_UPLOAD_FOLDER'),
            'enable_orientation' => Configuration::get('ART_PUZZLE_ENABLE_ORIENTATION'),
            'enable_crop_tool' => Configuration::get('ART_PUZZLE_ENABLE_CROP_TOOL'),
            'default_box_text' => Configuration::get('ART_PUZZLE_DEFAULT_BOX_TEXT'),
            'max_box_text_length' => Configuration::get('ART_PUZZLE_MAX_BOX_TEXT_LENGTH'),
            'boxColors' => $boxColors ? json_decode($boxColors, true) : [],
            'fonts' => $fonts ? explode(',', $fonts) : [],
            'puzzleAjaxUrl' => $this->context->link->getModuleLink('art_puzzle', 'ajax'),
            'securityToken' => Tools::getToken(false),
            'cart_url' => $this->context->link->getPageLink('cart')
        ]);
        
        // Registra CSS e JS
        $this->registerStylesheet(
            'module-art-puzzle-style',
            'modules/art_puzzle/views/css/front.css',
            ['media' => 'all', 'priority' => 150]
        );
        
        $this->registerJavascript(
            'module-art-puzzle-script',
            'modules/art_puzzle/views/js/front.js',
            ['position' => 'bottom', 'priority' => 150]
        );
        
        // Aggiungi Cropper.js se abilitato
        if (Configuration::get('ART_PUZZLE_ENABLE_CROP_TOOL')) {
            $this->registerJavascript(
                'cropperjs',
                'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js',
                ['server' => 'remote', 'position' => 'bottom', 'priority' => 140]
            );
            
            $this->registerStylesheet(
                'cropperjs-style',
                'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css',
                ['server' => 'remote', 'media' => 'all', 'priority' => 140]
            );
        }
        
        // Assegna variabili JavaScript
        Media::addJsDef([
            'artPuzzleAjaxUrl' => $this->context->link->getModuleLink('art_puzzle', 'ajax'),
            'artPuzzleProductId' => $id_product,
            'artPuzzleToken' => Tools::getToken(false)
        ]);
        
        // Assegna il template della pagina di personalizzazione
        $this->setTemplate('module:art_puzzle/views/templates/front/customizer.tpl');
    }
}