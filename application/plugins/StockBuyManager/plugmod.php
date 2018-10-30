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
