<?php
$filename[]=<<<EOT
views/stock/leftovers.html
EOT;
$search[]=<<<EOT
var columns_lev2=[
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
\n                        {id: "sector", field: "product_sector", name: "Сектор", width: 60, sortable: true, editor: Slick.Editors.Text},
EOT;

$filename[]=<<<EOT
views/stock/leftovers.html
EOT;
$search[]=<<<EOT
{name: 'Штрихкод', field: 'product_barcode'},
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
\n                        {name: 'Сектор', field: 'product_sector'},
EOT;


$filename[]=<<<EOT
views/events/scheduler.html
EOT;
$search[]=<<<EOT
                    <span class="icon-24 icon-print" title="Печать" onclick="App.page_events_scheduler.tile.out($(this).parent().data('header'),'.print');"> </span>
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
                    <span class="icon-24" style="background-image:url(img/checklist.png)" title="Печать складскую накладную" onclick="App.page_events_scheduler.tile.stock_bill_print();"> </span>
                    <script type="text/javascript">
                        App.page_events_scheduler.tile.stock_bill_print=function(){
                            var doc_ids=[];
                            $(".selected").each(function(i,node){
                                var index=\$(node).data('event-index');
                                doc_ids.push( App.page_events_scheduler.tile.event_list[index].doc_id );
                            });
                            if( doc_ids.length ){
                                window.open("StockSectorManager/viewOut?doc_ids="+doc_ids.join(','));
                            } else {
                                App.flash("Выберите строчки");
                            }
                        }
                    </script>
EOT;



$filename[]=<<<EOT
views/stock/product_card.html
EOT;
$search[]=<<<EOT
<div class="easyui-panel" title="Кода" data-options="collapsible:true,collapsed:true">
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
\n	    <input name="product_sector" title="Сектор">
EOT;


$filename[]=<<<EOT
models/Stock.php
EOT;
$search[]=<<<EOT
se.product_img,
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
\n                    se.product_sector,
EOT;






$filename[]=<<<EOT
models/Stock.php
EOT;
$search[]=<<<EOT
'product_img' => \$this->request('product_img'),
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
\n            'product_sector' => \$this->request('product_sector'),
EOT;

$filename[]=<<<EOT
models/Stock.php
EOT;
$search[]=<<<EOT
\$lvl2 = ",
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
product_sector,
EOT;

$filename[]=<<<EOT
models/Stock.php
EOT;
$search[]=<<<EOT
/product_code/party_label/parent_id/
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
product_sector/
EOT;

