/*global Slick,body,holderId*/
body={
    entries_sg:{},
    row_queue:0,
    settings:{
	columns:[
	    {id:"queue",name: "№", width: 30,formatter:body.queue },
	    {id:"product_code", field: "product_code",name: "Код", sortable: true, width: 80, sortable: true },
	    {id:"product_name", field: "product_name",name: "Название", sortable: true, width: 300},
	    {id:"product_quantity", field: "product_quantity",name: "Кол-во", sortable: true, width: 70, sortable: true,cssClass:'slick-align-right', editor: Slick.Editors.Integer},
	    {id:"product_unit", field: "product_unit",name: "Ед.", width: 50, sortable: true },
	    {id:"product_price_total", field: "product_price_total",name: "Цена", sortable: true, width: 70, sortable: true,cssClass:'slick-align-right', editor: Slick.Editors.Float},
	    {id:"product_sum_total", field: "product_sum_total",name: "Сумма", sortable: true, width: 80,cssClass:'slick-align-right'},
	    {id:"row_status", field: "row_status",name: "!",sortable: true, width: 25,formatter:body.tooltip },
	    {id:"party_label",field:"party_label",name:"Партия",width:100, editor: Slick.Editors.Text},
	    {id:"product_uktzet",field:'product_uktzet',name:"Происхождение",width:70},
	    {id:"vat_rate",field:'vat_rate',name:"НДС %",width:60,cssClass:'slick-align-right'}
	],
	options:{
	    editable: true,
	    autoEdit: true,
	    enableCellNavigation: true,
	    enableColumnReorder: false,
	    enableFilter:false,
	    multiSelect :true
	}
    },
    init:function(){
	var h = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
	$("#"+holderId+" .x-body").css('height',(h-$("#"+holderId+" .x-body").position().top-30)+'px');
	body.entries_sg=$("#"+holderId+" .x-body").slickgrid(body.settings);
	body.entries_sg.onCellChange.subscribe(function(e,data){
	    var updatedEntry=data.item;
	    var field=body.settings.columns[data.cell];
	    var value=updatedEntry[field];
	    entryUpdate(updatedEntry.document_entry_id,field,value);
	});
    },
    render:function(entries){
	body.entries_sg.setData(entries);
    },
    queue:function(){
	body.row_queue++;
    },
    tooltip:function(row, cell, value, columnDef, dataContext){
	if( value ){
	    var parts = value.split(' ');
	    var cmd = parts.shift();
	    if (cmd){
		return '<img src="img/' + cmd + '.png" style="max-width:16px;height:auto" title="' + parts.join(' ') + '">';
	    }
	}
	return '';
    }
};