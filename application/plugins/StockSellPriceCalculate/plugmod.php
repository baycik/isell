<?php
    
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
\n  <button data-action="calc_sell"><img src="img/calc.png" style="width:24px;height: 24px;"> Рассчитать цену продажи</button><br>
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
\n  ,calc_sell:function(){
                App.loadWindow("StockSellPriceCalculate/dialog").done(Leftovers.tools.reload);
            }
EOT;

