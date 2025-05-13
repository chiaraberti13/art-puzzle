<?php
/**
 * Script per la registrazione degli hook mancanti
 */

// Includi config
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

// Ottieni l'istanza del modulo
$module = Module::getInstanceByName('art_puzzle');

if (!$module) {
    die("Errore: Modulo art_puzzle non trovato");
}

// Lista degli hook da registrare
$hooks_to_register = [
    'displayProductButtons',
    'displayShoppingCartFooter',
    'actionProductCancel'
];

$success = true;
$messages = [];

// Registra ogni hook mancante
foreach ($hooks_to_register as $hook_name) {
    if (!$module->isRegisteredInHook($hook_name)) {
        if ($module->registerHook($hook_name)) {
            $messages[] = "Hook '$hook_name' registrato con successo.";
        } else {
            $success = false;
            $messages[] = "Errore nella registrazione dell'hook '$hook_name'.";
        }
    } else {
        $messages[] = "Hook '$hook_name' era già registrato.";
    }
}

// Output dei risultati
echo '<h1>Registrazione Hook</h1>';

if ($success) {
    echo '<div style="color: green; padding: 10px; background-color: #e8f5e9; border-radius: 5px; margin-bottom: 10px;">Tutti gli hook sono stati registrati correttamente.</div>';
} else {
    echo '<div style="color: red; padding: 10px; background-color: #ffebee; border-radius: 5px; margin-bottom: 10px;">Si sono verificati errori durante la registrazione degli hook.</div>';
}

echo '<ul>';
foreach ($messages as $message) {
    echo '<li>' . $message . '</li>';
}
echo '</ul>';

echo '<p><a href="art_puzzle_debug_simple.php">Torna alla diagnostica</a></p>';