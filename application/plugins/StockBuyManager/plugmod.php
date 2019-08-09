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
if ( load_more ) {
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
    for(var i in new_loaded_items){
        if(new_loaded_items[i].delivery_group){
            new_loaded_items[i].delivery_group=new_loaded_items[i].delivery_group.split(',');
            for(var k in new_loaded_items[i].delivery_group){
                new_loaded_items[i].delivery_group[k] = parseInt(new_loaded_items[i].delivery_group[k],10);
            }
        }
        else{
            new_loaded_items[i].delivery_group = '';
        }
        new_loaded_items[i].supleftover? new_loaded_items[i].supleftover=new_loaded_items[i].supleftover.split(','):'';
    };
EOT;
$after[]=<<<EOT
   
EOT;


$filename[]=<<<EOT
plugins/MobiSell/views/stock.html
EOT;
$search[]=<<<EOT
<div class="four wide column" style="text-align: right">{{if stared_leftover}} {{stared_leftover}} {{else}} {{leftover}}{{product_unit}} {{/if}}</div>
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
        
{{if delivery_group|notempty}}
    <div class="sixteen wide column" style="padding-top: 0rem !important;">
        <div class="product-delivery-available ui right aligned grid" style="color: #2185d0;">
                <div class="delivery-days two wide column" style="padding: 0rem !important; "></div> 
                <div class="delivery-days seven wide column" style="padding: 0rem !important; ">
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
                <div class="delivery-leftovers seven wide column" style="padding: 0rem !important; ">
                    {{supleftover}}
                        <div>  
                            {{.}}
                        </div>
                    {{/supleftover}}    
                </div>
        </div>  
    </div>
 {{/if}}    
EOT;

$filename[]=<<<EOT
plugins/MobiSell/views/stock.html
EOT;
$search[]=<<<EOT
<div class="product-item-grid" >
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
{{if delivery_group|notempty}}
    <div class="product-delivery-available ui left aligned grid" style="    margin-top: 0rem !important; opacity: 0.8; border-top: #e8e8ef 1px solid;color: #2185d0;">
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
            <div class="delivery-leftovers six wide column" style="padding-right: .5rem !important;padding-left: 0.5rem !important; padding-top: 0.2rem !important">
                {{supleftover}}
                    <div>  
                        {{.}}
                    </div>
                {{/supleftover}}    
            </div>
    </div>  
 {{/if}} 
EOT;
$after[]=<<<EOT
EOT;

$filename[]=<<<EOT
models/DocumentItems.php
EOT;
$search[]=<<<'EOT'
 GET_PRICE(product_code,{$pcomp_id},{$usd_ratio}) product_price_total_raw
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
,fetch_count-DATEDIFF(NOW(),fetch_stamp) AS popularity 
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

