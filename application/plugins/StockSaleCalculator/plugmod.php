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
\n  <button data-action="sale_set"><img src="img/calc.png" style="width:24px;height: 24px;"> Установка распродажи для категории</button><br>
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
\n  ,sale_set:function(){
            if( !Leftovers.parent_id ){
                alert('Надо выбрать категорию склада для рассчета распродажи!')
                return;
            }
                App.loadWindow("StockSaleCalculator/dialog",{branch_id:Leftovers.parent_id}).done(Leftovers.tools.reload);
            }
EOT;

