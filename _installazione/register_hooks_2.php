<?php
/**
 * Script completo e definitivo per registrare tutti gli hook del modulo art_puzzle.
 * Include forzatura di quelli critici e stampa dettagliata per debugging.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';

echo "<h1>Registrazione completa e forzata degli hook per art_puzzle</h1>";

$module = Module::getInstanceByName('art_puzzle');
if (!$module || !Validate::isLoadedObject($module)) {
    die("<p style='color:red'>❌ Modulo art_puzzle non trovato o non valido.</p>");
}

$hooks = [
    'displayProductButtons',
    'displayHeader',
    'actionFrontControllerSetMedia',
    'displayProductExtraContent',
    'displayBackOfficeHeader',
    'displayAdminProductsExtra',
    'displayAdminProductsMainStepLeftColumnMiddle'
];

echo "<ul>";
foreach ($hooks as $hook) {
    $isRegistered = $module->isRegisteredInHook($hook);
    if ($isRegistered) {
        echo "<li style='color:gray'>{$hook}: Già registrato</li>";
    } else {
        try {
            $res = $module->registerHook($hook);
            if ($res) {
                echo "<li style='color:green'>{$hook}: ✅ Registrato con successo</li>";
            } else {
                echo "<li style='color:red'>{$hook}: ❌ Fallito (registerHook ha restituito false)</li>";
            }
        } catch (Exception $e) {
            echo "<li style='color:red'>{$hook}: ❌ Eccezione: " . $e->getMessage() . "</li>";
        }
    }
}
echo "</ul>";

echo "<p><strong>Fine registrazione. Puoi ora tornare al prodotto e testare la visualizzazione del modulo.</strong></p>";
?>
