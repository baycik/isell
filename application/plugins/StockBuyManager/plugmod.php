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
/*PLUGINS-UTILS*/
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
