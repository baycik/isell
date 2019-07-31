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
 
$filename[]=<<<EOT
plugins/MobiSell/models/MobiSell.php
EOT;
$search[]=<<<EOT
se.product_img,
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
 GROUP_CONCAT(CONCAT(al.attribute_name,': ',av.attribute_value,' ', al.attribute_unit)) attributes 
EOT;
 
$filename[]=<<<EOT
plugins/MobiSell/models/MobiSell.php
EOT;
$search[]=<<<EOT
stock_tree st ON se.parent_id=branch_id
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
    LEFT JOIN
attribute_values av ON pl.product_id = av.product_id
    LEFT JOIN
attribute_list al USING(attribute_id)
EOT;
 
 
$filename[]=<<<EOT
plugins/MobiSell/views/stock.html
EOT;
$search[]=<<<EOT
<div class="plugins"></div>
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
{{if attributes}}
<div class="product-attributes">
    <label class="header-label">Характеристики</label>
    {{attributes}}
    <div class="attribute">
        <div class="info-row">
            <label>{{label}}: </label>
            <div class="info-cell">{{value}}</div>
        </div>
    </div>
    {{/attributes}}
</div>    
{{/if}}
EOT;
 