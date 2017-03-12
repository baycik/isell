/*global Slick,body,holderId,doc,document_model,App*/
body={
    entries_sg:{},
    row_queue:1,
    init:function(){
	this.settings={
	    columns:[
		{id:"queue",name: "№", width: 30,formatter:body.queue },
		{id:"product_code", field: "product_code",name: "Код", sortable: true, width: 80},
		{id:"product_name", field: "product_name",name: "Название", sortable: true, width: 300},
		{id:"product_quantity", field: "product_quantity",name: "Кол-во", sortable: true, width: 70, cssClass:'slick-align-right', editor: Slick.Editors.Integer},
		{id:"product_unit", field: "product_unit",name: "Ед.", width: 50, sortable: true },
		{id:"product_price_total", field: "product_price_total",name: "Цена", sortable: true, width: 70, cssClass:'slick-align-right',asyncPostRender:body.priceisloss, editor: Slick.Editors.Float},
		{id:"product_sum_total", field: "product_sum_total",name: "Сумма", sortable: true, width: 80,cssClass:'slick-align-right', editor: Slick.Editors.Float},
		{id:"row_status", field: "row_status",name: "!",sortable: true, width: 25,formatter:body.tooltip },
		{id:"party_label",field:"party_label",name:"Партия",width:100, editor: Slick.Editors.Text},
		{id:"product_uktzet",field:'product_uktzet',name:"Происхождение",width:70},
		{id:"self_price",field:'self_price',name:"maliet",width:60,cssClass:'slick-align-right'}
	    ],
	    options:{
		editable: true,
		autoEdit: true,
		autoHeight: true,
		enableCellNavigation: true,
		enableColumnReorder: false,
		enableFilter:false,
		multiSelect :true,
		enableAsyncPostRender: true
	    }
	};
	body.entries_sg = new Slick.Grid("#"+holderId+" .x-body", [], body.settings.columns, body.settings.options);
	body.entries_sg.onCellChange.subscribe(function(e,data){
	    var updatedEntry=data.item;
	    var field=body.settings.columns[data.cell].field;
	    var value=updatedEntry[field];
	    body.entryUpdate(updatedEntry.doc_entry_id,field,value);
	});
    },
    render:function(entries){
	body.row_queue=1;
	body.entries_sg.setData(entries);
	body.entries_sg.render();
    },
    queue:function(){
	return body.row_queue++;
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
    },
    priceisloss:function(cellNode, row, dataContext, colDef){
	if( dataContext.is_loss*1 ){
	    $(cellNode).css('color','red');
	}
    },
    entryUpdate:function(doc_entry_id,field,value){
	var url=document_model+'/entryUpdate';
	$.post(url,{doc_id:doc_id,doc_entry_id:doc_entry_id,field:field,value:value},function(resp){
	    var result=App.json(resp);
	    if(result.errtype==='not_enough'){
		App.flash("Не достаточное колличество. Нехватает: ".result.errmsg);
	    }
	    if(result.errtype!=='ok'){
		App.flash("Ошибка изменения строки ".result.errmsg);
	    }
	    doc.reload(["body","foot"]);
	});
    },
    entryDelete:function(){
	var selected_rows=body.entries_sg.getSelectedRows();
	if(!selected_rows){
	    App.flash("Ни одна строка не выбрана!");
	    return;
	}
	if( !confirm("Удалить выделенные строки?") ){
	    return;
	}
	var entries_to_delete=[];
	for(var i in selected_rows){
	    entries_to_delete.push(body.entries_sg.getDataItem(selected_rows[i]).doc_entry_id);
	}
	var url=document_model+'/entryDelete';
	$.post(url,{doc_entry_ids:JSON.stringify(entries_to_delete)},function(ok){
	    if(ok*1){
		doc.reload(["body","foot"]);
		App.flash("Удалено:"+ok+" строк");
	    } else {
		App.flash("Ошибка удаления строки");
	    }
	});
    },
    entryAdd:function(){
	var url=document_model+'/entryDelete';
	$.post(url,{doc_id:doc_id,quantity:quantity},function(ok){
	    if(ok*1){
		doc.reload(["body","foot"]);
		App.flash("Добавлено:"+ok+" строк");
	    } else {
		App.flash("Ошибка добавления строки");
	    }
	});	
    }
};