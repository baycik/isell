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
\n  <div title="Satın alma idarecisi" href="StockBuyManager/views/stock_buy_manager_main.html" ></div>
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
			App.flash("Sımarış satın alma idarecisine yüklendi");
		    } else {
			App.flash("Sımarış yaratılmadı");
		    }
		});
	    }
EOT;



$filename[]=<<<EOT
views/trade/document.html
EOT;
$search[]=<<<EOT
formatter:function(row_data){
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
            row_data.delivery_group?row_data.delivery_group=row_data.delivery_group.split(','):'';
            row_data.supleftover? row_data.supleftover=row_data.supleftover.split(','):'';
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
{{if delivery_group|notempty}}
        <div class="product-delivery-available">
                <div class="delivery-days">
                    {{delivery_group}}
                    <div>{{.}}
                        {{if supleftover|notequals>1}}
                            {{if supleftover|more>5}}
                                дней:
                            {{else}}
                                дня:
                            {{/if}}
                        {{else}}
                            день:
                            {{/if}}
                     </div>
                    {{/delivery_group}}
                </div> 
                <div class="delivery-leftovers">
                    {{supleftover}}
                        <div>  
                            {{.}}
                        </div>
                    {{/supleftover}}    
                </div>
        </div>  
    {{/if}}
EOT;

$filename[]=<<<EOT
plugins/MobiSell/views/document.html
EOT;
$search[]=<<<EOT
var suggestion = App.json(resp);
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
    for(var i in suggestion){
        if(suggestion[i].delivery_group){
            suggestion[i].delivery_group=suggestion[i].delivery_group.split(',');
            for(var k in suggestion[i].delivery_group){
                suggestion[i].delivery_group[k] = parseInt(suggestion[i].delivery_group[k],10);
            }
        }
        else{
            suggestion[i].delivery_group = '';
        }
        suggestion[i].supleftover? suggestion[i].supleftover=suggestion[i].supleftover.split(','):'';
    };
EOT;


$filename[]=<<<EOT
plugins/MobiSell/views/document.html
EOT;
$search[]=<<<EOT
<div class="five wide column right aligned">{{if stared_leftover}} {{stared_leftover}} {{else}} {{leftover}}{{product_unit}} {{/if}}</div></div>
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
{{if delivery_group|notempty}}
   <div class="product-delivery-available ui right aligned grid" style="    margin-top: 0rem !important; opacity: 0.8; border-top: #e8e8ef 1px solid;color: #2185d0;">
           <div class="four wide column" style="padding-left: .5rem !important; padding-top: 0.2rem !important;"></div>
           <div class="delivery-days six wide column" style="padding-right: 0rem !important; padding-top: 0.2rem !important">
               {{delivery_group}}
               <div>{{.}}
                   {{if .|notequals>1}}
                       {{if .|more>4}}
                           дней:
                       {{else}}
                           дня:
                       {{/if}}
                   {{else}}
                       день:
                       {{/if}}
                </div>
               {{/delivery_group}}
           </div> 
           <div class="delivery-leftovers six wide column" style="padding-right: .5rem !important;padding-left: 0rem !important; padding-top: 0.2rem !important">
               {{supleftover}}
                   <div>  
                       {{.}}
                   </div>
               {{/supleftover}}    
           </div>
   </div>  
{{/if}}    
EOT;


$filename[]=<<<EOT
plugins/MobiSell/views/stock.html
EOT;
$search[]=<<<EOT
</script>
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
    App.stock.suppliers_delivery_count=function(){
        for(let item of App.stock.entries.current_list){
            let delivery_list='';
            if(item.supply_leftovers){
                let leftovers=item.supply_leftovers.split(',');
                let deliveries=item.supplier_deliveries.split(',');
                for(let i in leftovers){
                    delivery_list=`\${leftovers[i]}(\${deliveries[i]}дн)<br>`;
                }
            }
            item.delivery_list=`<span style="color:blue">\${delivery_list}</span>`;;
        }
    }
EOT;
$after[]=<<<EOT
EOT;

$filename[]=<<<EOT
plugins/MobiSell/views/stock.html
EOT;
$search[]=<<<EOT
App.renderTpl("stock_list"
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
                App.stock.suppliers_delivery_count();//INJECTION

EOT;
$after[]=<<<EOT
EOT;

$filename[]=<<<EOT
plugins/MobiSell/views/stock.html
EOT;
$search[]=<<<EOT
{{leftover}}{{product_unit}}
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
 {{delivery_list}}
EOT;















$filename[]=<<<EOT
models/DocumentItems.php
EOT;
$search[]=<<<'EOT'
$suggested=$this->get_list($sql);
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<'EOT'
$sql = "SELECT 
            tmp.*, GROUP_CONCAT(IFNULL(srl.supplier_delivery, null)) as delivery_group, GROUP_CONCAT(COALESCE (CONCAT (sl.supply_leftover,' ',tmp.product_unit), CONCAT (sl1.supply_leftover,' ',tmp.product_unit), null)) as  supleftover
        FROM 
            (  $sql ) AS tmp 
        LEFT JOIN 
        supply_list sl ON (sl.product_code = tmp.product_code AND sl.supply_leftover > 0 ) 
            LEFT JOIN 
        supply_list sl1 ON (sl1.supply_code = tmp.product_code AND sl1.supply_leftover > 0 )
            LEFT JOIN 
        supplier_list srl ON (sl.supplier_id = srl.supplier_id OR sl1.supplier_id = srl.supplier_id) 
        GROUP BY product_code 
        ORDER BY tmp.popularity  DESC
        ";       
EOT;
$after[]=<<<EOT
EOT;

