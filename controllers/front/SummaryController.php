<?php

class Art_PuzzleSummaryModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $id_product = Tools::getValue('id_product');

        if (isset($_FILES['puzzle_image']) && $_FILES['puzzle_image']['error'] == 0) {
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['puzzle_image']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_mimes)) {
                die('Tipo di file non supportato. Usa JPG, PNG o GIF.');
            }

            $file_name = uniqid('puzzle_') . '.' . pathinfo($_FILES['puzzle_image']['name'], PATHINFO_EXTENSION);
            $upload_dir = _PS_MODULE_DIR_ . 'art_puzzle/upload/';
            $file_path = $upload_dir . $file_name;

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            move_uploaded_file($_FILES['puzzle_image']['tmp_name'], $file_path);

            // Salva il path per uso futuro (es. PDF, preview, ecc.)
            $this->context->cookie->__set('art_puzzle_uploaded_image', $file_name);
        }

        Tools::redirect($this->context->link->getModuleLink('art_puzzle', 'preview', ['id_product' => $id_product]));
    }
}
