/*global Slick,body,holderId*/
	//var entries_sg_height = $(window).height()-$("#"+holderId+" .x-body").position().top-30;
	//$("#"+holderId+" .x-body").css('height',entries_sg_height+'px');
body={
    entries_sg:{},
    row_queue:0,
    settings:{
	columns:[
	    {id:"queue",name: "№", width: 30,formatter:body.queue },
	    {id:"product_code", field: "product_code",name: "Код", sortable: true, width: 80},
	    {id:"product_name", field: "product_name",name: "Название", sortable: true, width: 300},
	    {id:"product_quantity", field: "product_quantity",name: "Кол-во", sortable: true, width: 70, cssClass:'slick-align-right', editor: Slick.Editors.Integer},
	    {id:"product_unit", field: "product_unit",name: "Ед.", width: 50, sortable: true },
	    {id:"product_price_total", field: "product_price_total",name: "Цена", sortable: true, width: 70, cssClass:'slick-align-right', editor: Slick.Editors.Float},
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
	body.entries_sg = new Slick.Grid("#"+holderId+" .x-body", {length:0}, body.settings.columns, body.settings.options);
    },
    
    render:function(entries){
	var entries_sg_height = $(window).height()-$("#"+holderId+" .x-body").position().top-30;
	$("#"+holderId+" .x-body").css('height',entries_sg_height+'px');
	
//	body.entries_sg.onCellChange.subscribe(function(e,data){
//	    var updatedEntry=data.item;
//	    var field=body.settings.columns[data.cell];
//	    var value=updatedEntry[field];
//	    entryUpdate(updatedEntry.document_entry_id,field,value);
//	});
	
	
	
	
	body.entries_sg.setData(entries);
	body.entries_sg.updateRowCount();
	body.entries_sg.render();
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