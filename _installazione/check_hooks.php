<?php
include_once('../../config/config.inc.php');
include_once('../../init.php');

$module = Module::getInstanceByName('art_puzzle');
$hooks = $module->getHooks();

echo "<h1>Hook registrati per Art Puzzle</h1>";
echo "<ul>";
foreach ($hooks as $hook) {
    echo "<li>$hook</li>";
}
echo "</ul>";

echo "<h2>Verifica hooks necessari</h2>";
$required_hooks = [
    'displayProductButtons',
    'displayProductExtraContent',
    'displayShoppingCartFooter',
    'actionProductCancel',
    'displayAdminProductsExtra'
];

echo "<ul>";
foreach ($required_hooks as $hook) {
    $registered = in_array($hook, $hooks) ? "<span style='color:green'>Registrato</span>" : "<span style='color:red'>Non registrato</span>";
    echo "<li>$hook: $registered</li>";
}
echo "</ul>";