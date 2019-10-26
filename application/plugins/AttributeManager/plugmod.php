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
se.product_img
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
 ,GROUP_CONCAT(CONCAT(al.attribute_name,':',al.attribute_prefix,av.attribute_value,'', al.attribute_unit) SEPARATOR '~') attributes 
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
plugins/MobiSell/views/product.html
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
                <table class="ui table">
                    <thead>
                        <tr>
                            <th colspan="2">Характеристики</th>
                        </tr>
                    </thead>
                    <tbody>
                    <!--{{attributes}}{{.|split>~}}-->
                    <tr>
                        <td>{{.|split>:|limit>1>0}}</td>
                        <td>{{.|split>:|limit>10>1}}</td>
                    </tr>
                    <!--{{/.}}{{/attributes}}-->
                    </tbody>
                </table>
                {{/if}}
EOT;
 