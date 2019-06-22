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
\n  <div title="Менеджер закупок" href="StockBuyManager/views/stock_buy_manager_main.html" ></div>
EOT;
    
$filename[]=<<<EOT
views/stock/leftovers.html
EOT;
$search[]=<<<EOT
/*PLUGIN-UTILS*/
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
\n  ,
	    send_to_buy_manager: function () {
		var params = Leftovers.table.currentParamsGet();
		$.post("StockBuyManager/orderFromStock/", params, function (ok) {
		    if (ok * 1) {
			App.flash("Заказ загружен в менеджер закупок");
		    } else {
			App.flash("Заказ не сформирован");
		    }
		});
	    }
EOT;

$filename[]=<<<EOT
views/stock/leftovers.html
EOT;
$search[]=<<<EOT
<!--PLUGIN-BUTTONS-->
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
<button data-action="send_to_buy_manager"><img src="img/docnew.png" style="width:24px;height: 24px;"> Отправить в менеджер закупок</button><br>
EOT;


$filename[]=<<<EOT
views/trade/document.html
EOT;
$search[]=<<<EOT
<div class="grid-item" style="color:green">{{ leftover }}{{ product_unit }}</div></div>
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
 {{ if supply_leftover|more>0 }}
                <div class="grid-container2">
                    <div class="grid-item">Под заказ:</div>
                    <div class="grid-item">
                        {{if supplier_delivery|more>0}}
                            {{ supplier_delivery }} 
                            {{if supplier_delivery|notequals>1}}
                                {{if supplier_delivery|more>5}}
                                    дней
                                {{else}}
                                    дня
                                {{/if}}
                            {{else}}
                                день
                            {{/if}}
                        {{/if}}
                    </div>
                    <div class="grid-item">{{ supply_leftover }} шт.</div>
                </div>
                {{/if}}
EOT;


$filename[]=<<<EOT
models/DocumentItems.php
EOT;
$search[]=<<<EOT
$output=$this->get_list($sql);
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
        $sql = "SELECT 
                    *, suprl.supplier_delivery, suprl.supplier_name, supll.supply_leftover 
                FROM 
            ( $sql ) AS tmp 
                LEFT JOIN 
            supply_list supll USING (product_code)
                LEFT JOIN 
            supplier_list suprl ON (supll.supplier_id = suprl.supplier_id) 
            ";        
EOT;
$after[]=<<<EOT
EOT;


