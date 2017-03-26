/*global Slick,body,holderId,doc,document_model,App,doc_id*/
body={
    entries_sg:{},
    row_queue:1,
    init:function(){
	this.settings={
	    columns:[
		{id:"queue",name: "№", width: 30,formatter:body.queue },
		{id:"product_code", field: "product_code",name: "Код", sortable: true, width: 80},
		{id:"product_name", field: "product_name",name: "Название", sortable: true, width: 400},
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
		enableColumnReorder : false,
		enableFilter:false,
		multiSelect :true,
		enableAsyncPostRender: true
	    }
	};
	body.entries_sg = new Slick.Grid("#"+holderId+" .x-body .x-entries", [], body.settings.columns, body.settings.options);
	body.entries_sg.setSelectionModel(new Slick.RowSelectionModel());
	body.entries_sg.onCellChange.subscribe(function(e,data){
	    var updatedEntry=data.item;
	    var field=body.settings.columns[data.cell].field;
	    var value=updatedEntry[field];
	    body.entryUpdate(updatedEntry.doc_entry_id,field,value);
	});
	/*
	 * Init entry tools
	 */
	body.suggestInit();
	body.etoolsInit();
    },
    render:function(entries){
	body.row_queue=1;
	body.entries_sg.setData(entries);
	body.entries_sg.render();
    },
    etoolsInit:function(){
	$("#"+holderId+" .x-body .x-body-tools").click(function(e){
	    var action=$(e.target).data('action');
	    if( action ){
		body.do( action );
	    }
	});
    },
    do:function( action ){
	body[action] && body[action]();
    },
    suggestInit:function(){
	function suggFormatter(row){
	    return ' <b>'+row['product_code']+'</b> '+row['label']+' <b>[x'+row['product_spack']+']'+' <font color=green>'+row['product_quantity']+'</font></b>';
	};
	var suggPrevCode='';
	function suggOnselect( row ){
	    suggPrevCode=row.product_code;
	    $("#"+holderId+" .x-body .x-qty").val(row.product_spack).select();
	};
	$("#"+holderId+" .x-body .x-suggest").combobox({
	    valueField: 'product_code',
	    textField: 'product_code',
	    formatter:suggFormatter,
	    selectOnNavigation:false,
	    url: 'DocumentSell/suggestFetch/',
	    panelHeight:'auto',
	    mode: 'remote',
	    method:'get',
	    hasDownArrow:false,
	    panelWidth:400,
	    panelMinWidth:400,
	    prompt:'код, название или штрихкод',
	    onSelect: suggOnselect
	}).combobox('textbox').bind( 'keydown', function(e){
	    if( e.keyCode===38 && $("#"+holderId+" .x-body .x-suggest").combobox('getValue')==='' ){
		$("#"+holderId+" .x-body .x-suggest").combobox('setValue',suggPrevCode);
	    }
	    else if( e.keyCode===13 ){
		$("#"+holderId+" .x-body .x-qty").select();
	    }
	});
	function suggestSubmit(){
	    var product_code=$("#"+holderId+" .x-body .x-suggest").combobox('getValue');
	    var product_quantity=$("#"+holderId+" .x-body .x-qty").val();
	    body.entryAdd(product_code,product_quantity);
	    $("#"+holderId+" .x-body .x-qty").val('');
	    $("#"+holderId+" .x-body .x-suggest").combobox('textbox').select();
	}
	$("#"+holderId+" .x-body .x-qty").bind( 'keydown', function(e){
	    if( e.keyCode===13 ){
		suggestSubmit();
	    }
	});
	$("#"+holderId+" .x-body .x-suggest-submit").click(suggestSubmit);
    },
    pickerInit:function(){
	function pickerTreeSelect(branch){
	    picklist.updateOptions({params:{parent_id:branch.branch_id}});
	    picklist.reload();
	}
	$("#"+holderId+" .x-body .x-allcath").click(function(){
	    pickerTreeSelect({branch_id:0});
	    $("#"+holderId+" .x-body .x-tree").find('.tree-node-selected').removeClass('tree-node-selected');
	});
	$("#"+holderId+" .x-body .x-tree").tree({
	    url:'Stock/branchFetch/',
	    loadFilter:function(data){
		for(var i in data){
		    data[i].id=data[i].branch_id;
		    data[i].text=data[i].label;
		    if( data[i].is_leaf*1 ){
			data[i].iconCls='icon-comp';
		    }
		}
		return data;
	    },
	    onSelect:pickerTreeSelect
	});
	function qty_color(row, cell, value, columnDef, dataContext){
	    if(value==0){
		return "<span style='color:red'>0</span>";
	    }
	    return value;
	}
	var settings={
	    columns:[
		{id:"product_code", field: "product_code",width:110,name: "Код", sortable: true },
		{id:"ru", field: "ru",name: "Название",width:400, sortable: true},
		{id:"product_quantity", field: "product_quantity",width:70,name: "Остаток",cssClass:'slick-align-right', sortable: true,formatter:qty_color },
		{id:"price", field: "price",name: "Цена",width:70, sortable: true,cssClass:'slick-align-right' }
	    ],
	    options:{
		enableColumnReorder: false,
		enableFilter:true,
		multiSelect :false,
		url:'DocumentSell/pickerListFetch'
	    }
	};
	var picklist=$("#"+holderId+" .x-body .x-stock").slickgrid(settings);
	picklist.onSelectedRowsChanged.subscribe(function(e,selection){
	    var row=selection.grid.getDataItem(selection.rows[0]);
	    $("#"+holderId+" .x-body .x-suggest").combobox('setValue',row.product_code);
	    $("#"+holderId+" .x-body .x-qty").val(row.product_spack).select();
	});
	body.pickerInited=true;
    },
    pickerToggle:function(){
	if(!body.pickerInited){
	    body.pickerInit();
	}
	if( body.pickerVisible ){
	    $("#"+holderId+" .x-picker").hide();
	    $("#"+holderId+" .x-picker-button").text("Открыть подбор");
	} else {
	    $("#"+holderId+" .x-picker").show();
	    $("#"+holderId+" .x-picker-button").text("Скрыть подбор");
	}
	body.pickerVisible=!body.pickerVisible;
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
    responseCodes:{
	not_enough:"На складе не хватает:",
	already_exists:"Строка с таким кодом уже добавлена",
	product_code_unknown:"Неизвестный товар",
	quantity_wrong:"Колличество должно быть больше нуля"
    },
    entryAdd:function(product_code,product_quantity){
	var url=document_model+'/entryAdd';
	$.post(url,{doc_id:doc_id,product_code:product_code,product_quantity:product_quantity},function(ok,status,xhr){
	    var rcode = xhr.getResponseHeader('X-isell-rcode');
	    if( !(ok*1) ){
		App.flash(body.responseCodes[rcode]||'');
		App.flash("Строка не добавлена");
	    }
	    if( rcode==='product_code_unknown' && confirm("Добавить новый код "+product_code+" на склад?") ){
		App.loadWindow('page/stock/product_card',{product_code:product_code});
	    }
	    doc.reload(["body","foot"]);
	});	
    },
    entryUpdate:function(doc_entry_id,field,value){
	var url=document_model+'/entryUpdate';
	$.post(url,{doc_id:doc_id,doc_entry_id:doc_entry_id,field:field,value:value},function(ok,status,xhr){
	    var rcode = xhr.getResponseHeader('X-isell-rcode');
	    if( !(ok*1) ){
		App.flash("Строка не изменена");
		App.flash(body.responseCodes[rcode]);
	    }
	    doc.reload(["body","foot"]);
	});
    },
    entryDelete:function(){
	var selected_rows=body.entries_sg.getSelectedRows();
	if(!selected_rows.length){
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
	$.post(url,{doc_id:doc_id,doc_entry_ids:JSON.stringify(entries_to_delete)},function(ok,status,xhr){
	    var rcode = xhr.getResponseHeader('X-isell-rcode');
	    if( !(ok*1) ){
		App.flash(body.responseCodes[rcode]||'');
		App.flash("Строка не удалена");
	    }
	    doc.reload(["body","foot"]);
	});
    },
    productCard:function(){
	var selected_rows=body.entries_sg.getSelectedRows();
	if(!selected_rows.length){
	    App.flash("Ни одна строка не выбрана!");
	    return;
	}
	var row=body.entries_sg.getDataItem(selected_rows[0]);
	App.loadWindow('page/stock/product_card',{product_code:row.product_code,loadProductByCode:true});
    },
    entryImport:function(){
	var config=[
	    {name:'Код товара',field:'product_code',required:true},
	    {name:'Кол-во',field:'product_quantity'},
	    {name:'Цена',field:'invoice_price'},
	    {name:'Партия',field:'party_label'}		    
	];
	App.loadWindow('page/dialog/importer',{label:'документ',fields_to_import:config}).progress(function(status,fvalue,Importer){
	    if( status==='submit' ){
		fvalue.doc_id=doc_id;
		App.post("DocumentSell/import/",fvalue,function(ok){
		    App.flash("Импортировано "+ok);
		    Importer.reload();
		    doc.reload(["body","foot"]);
		});
	    }
	});
    }
    /*
     * @TODO add export entries
     */
};