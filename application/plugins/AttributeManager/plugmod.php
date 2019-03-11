<?php
    
$filename[]=<<<EOT
views/stock/stock_main.html
EOT;
$search[]=<<<EOT
<!--PLUGIN-TABS-->
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
\n  <div title="Менеджер атрибутов" href="AttributeManager/attribute_manager.html" ></div>
EOT;

$filename[]=<<<EOT
views/stock/product_card.html
EOT;
$search[]=<<<EOT
/*PLUGINS GO HERE!!DO NOT ERASE THIS TEXT, ITS AN ANCHOR*/
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
$("#attributes").load('AttributeManager/product_cart_attributes.html');
EOT;


$filename[]=<<<EOT
views/stock/product_card.html
EOT;
$search[]=<<<EOT
<!--PLUGINS-->
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
<div class="easyui-panel" title="Атрибуты" data-options="collapsible:true,collapsed:true" id="attributes"></div>
EOT;
 