<?php
/**
 * Script diagnostico SENZA controllo admin per art_puzzle
 * Da usare SOLO per test temporanei.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';

echo "<h1>Diagnostica modulo art_puzzle (versione sbloccata)</h1>";

$moduleName = 'art_puzzle';
$module = Module::getInstanceByName($moduleName);

if (!$module || !Validate::isLoadedObject($module)) {
    die("<p style='color:red'>Modulo {$moduleName} non trovato o non valido.</p>");
}

// Elenco dei principali hook richiesti
$hooks = [
    'displayProductButtons',
    'displayHeader',
    'actionFrontControllerSetMedia',
    'displayProductExtraContent',
    'displayBackOfficeHeader',
    'displayAdminProductsExtra',
    'displayAdminProductsMainStepLeftColumnMiddle'
];

echo "<h2>Hook registrati:</h2><ul>";
foreach ($hooks as $hook) {
    $status = $module->isRegisteredInHook($hook) ? "✅ Registrato" : "❌ Non registrato";
    echo "<li><strong>{$hook}</strong>: {$status}</li>";
}
echo "</ul>";

// Controllo prodotti associati
echo "<h2>Prodotti associati (ART_PUZZLE_PRODUCT_IDS):</h2>";
$productIdsConf = Configuration::get('ART_PUZZLE_PRODUCT_IDS');
if (!$productIdsConf) {
    echo "<p style='color:red'>❌ Nessun prodotto associato configurato.</p>";
} else {
    $ids = array_filter(explode(',', $productIdsConf));
    echo "<ul>";
    foreach ($ids as $id) {
        $product = new Product((int)$id, false, Context::getContext()->language->id);
        if (Validate::isLoadedObject($product)) {
            echo "<li>ID: {$id} - Nome: {$product->name}</li>";
        } else {
            echo "<li style='color:red'>ID: {$id} - Prodotto non valido!</li>";
        }
    }
    echo "</ul>";
}

// Verifica directory fondamentali
$paths = [
    'upload' => _PS_MODULE_DIR_ . 'art_puzzle/upload/',
    'logs' => _PS_MODULE_DIR_ . 'art_puzzle/logs/',
    'fonts' => _PS_MODULE_DIR_ . 'art_puzzle/views/fonts/'
];

echo "<h2>Controllo cartelle:</h2><ul>";
foreach ($paths as $key => $path) {
    if (!file_exists($path)) {
        echo "<li style='color:red'>❌ Cartella <strong>{$key}</strong> non esiste: {$path}</li>";
    } elseif (!is_writable($path)) {
        echo "<li style='color:orange'>⚠️ Cartella <strong>{$key}</strong> non scrivibile: {$path}</li>";
    } else {
        echo "<li style='color:green'>✅ Cartella <strong>{$key}</strong> OK</li>";
    }
}
echo "</ul>";

$context = Context::getContext();
echo "<h2>Contesto attuale:</h2>";
echo "<ul>";
echo "<li>Lingua: " . $context->language->name . "</li>";
echo "</ul>";

echo "<hr><p><strong>Fine diagnostica.</strong></p>";
?>
