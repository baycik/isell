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
\n  <div title="Аналоги" href="StockAnalog/stock_analog.html" ></div>
EOT;
    
$filename[]=<<<EOT
models/DocumentItems.php
EOT;
$search[]=<<<EOT
CHK_ENTRY(doc_entry_id) AS row_status,
EOT;
$replace[]=<<<EOT
PLUGIN_CHK_ANALOG(CHK_ENTRY(doc_entry_id),doc_type,product_id,product_quantity,analyse_class) AS row_status,
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
EOT;



$filename[]=<<<EOT
views/trade/document.html
EOT;
$search[]=<<<EOT
tooltip:function(value, row){
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
\n            if( value.indexOf('analog')>-1 ){
\n                return App.datagrid.tooltip(value, row, "App.loadWindow('StockAnalog/select_analog',{doc_entry_id:'"+row.doc_entry_id+"'}).progress(function(){Doc.entries.reload()})");
\n            }
EOT;



$filename[]=<<<EOT
plugins/MobiSell/views/document.html
EOT;
$search[]=<<<EOT
if ( status == 'wrn' ) {
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
                        if ( status.indexOf('ok_analog')>-1 ) {
			    App.document.doc.entries[i].status_icon = "undo";
			    App.document.doc.entries[i].status_color = "green";
			    App.document.doc.entries[i].status_message = '';
			} else 
                        if ( status.indexOf('analog')>-1 ) {
			    App.document.doc.entries[i].status_icon = "undo";
			    App.document.doc.entries[i].status_color = "orange";
			    App.document.doc.entries[i].status_message = 'Есть аналог';
			} else 
EOT;
$after[]=<<<EOT
EOT;


$filename[]=<<<EOT
plugins/MobiSell/views/document.html
EOT;
$search[]=<<<EOT
	    handleClick: function (row_node) {
		var index = $(row_node).data('row-index');
		var entry = App.document.doc.entries[index];
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
        
            swap:function(node){
                $('#document_entry_analog_dialog').modal('hide');
                var product_id=$(node).data('product_id');
                $.post("../StockAnalog/analogEntrySwap",{doc_entry_id:App.document.current_entry_id,product_id:product_id},function(ok){
                    if(ok*1){
                        App.flash("Товар изменен на аналог");
                        let doc_id=App.document.doc.head.doc_id;
                        App.document.load(doc_id);
                    } else {
                        App.flash("Не удалось заменить на аналог");
                    }
                });
            },
EOT;
$after[]=<<<EOT
EOT;

$filename[]=<<<EOT
plugins/MobiSell/views/document.html
EOT;
$search[]=<<<EOT
	    handleClick: function (row_node) {
		var index = $(row_node).data('row-index');
		var entry = App.document.doc.entries[index];
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
                if(event.target && event.target.outerHTML.indexOf('icon undo')>-1 ){
                    event.stopPropagation();
                    $('#document_entry_analog_dialog').modal().modal('show');
                    App.document.current_entry_id=entry.doc_entry_id;
                    $.post("../StockAnalog/analogListGet",{doc_entry_id:entry.doc_entry_id},function(resp){
                        var analog_list=App.json(resp);
                        if( analog_list ){
                            App.renderTpl('document_entry_analog_dialog_list', analog_list);
                        }
                    });
                    return;
                }
EOT;

$filename[]=<<<EOT
plugins/MobiSell/views/document.html
EOT;
$search[]=<<<EOT
{{##}}) {{product_name}}
EOT;
$replace[]=<<<EOT
<span>{{##}}) {{product_name}}</span>
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
EOT;





$filename[]=<<<EOT
plugins/MobiSell/views/document.html
EOT;
$search[]=<<<EOT
<div class="ui modal" id="document_entry_dialog">
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT

<div class="ui modal" id="document_entry_analog_dialog">
    <div class="header">Замена на аналог</div>
    <div class="content" style="height: 500px;overflow: auto;">
        <div id="document_entry_analog_dialog_list">
            <table class="ui selectable  table  stackable orange">
                <thead>
                    <tr>
                        <th>
                            Код/Название
                        </th>
                        <th>
                            <div class="ui grid">
                                <div class="five wide column">Остаток</div>
                                <div class="five wide column">Цена</div>
                                <div class="six wide column"></div>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!--{{.}}-->
                    <tr onclick="App.document.row.swap( this )" data-product_id="{{product_id}}">
                        <td>
                            <b>{{product_code}}</b> {{product_name}} 
                            {{if analyse_class|equals>C}}
                            <i class="icon star"></i>
                            {{/if}}
                        </td>
                        <td>
                            <div class="ui grid">
                                <div class="five wide column">{{product_quantity}}{{product_unit}}</div>
                                <div class="five wide column">{{product_price}}</div>
                                <div class="six wide column">
                                    {{if product_img}}
                                        <img src="../Storage/image_flush/?size=50x50&path=/dynImg/{{product_img}}">
                                    {{/if}}
                                </div>
                            </div>
                        </td>
                    </tr>
                    <!-- {{/.}}-->
                </tbody>
            </table>
            <div class="ui grey close fluid button" onclick="$('#document_entry_analog_dialog').modal('hide');"><i class="icon close"></i> Отмена</div>
        </div>
    </div>
</div>
EOT;
$after[]=<<<EOT
EOT;
