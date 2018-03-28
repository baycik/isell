/*global Slick,body,holderId,doc,document_model,App,doc_id*/

head={
    pcompNode:null,
    init:function(){
	head.initControls();
	head.initToolbar();
    },
    render:function(head){
	document_data.head=head;
	$("#"+holderId+" .x-head form").form('load',head);
	$("#"+holderId+" .x-head form input[name=is_commited]").prop("checked",head.is_commited*1);
	this.pcompNode && this.pcompNode.combobox("setText",head.label);
    },
    destroy:function(){
	//this.pcompNode && this.pcompNode.combobox && this.pcompNode.combobox('destroy');
    },
    update:function(field,value,succes_msg){
	var url=document_model+'/documentUpdate';
	return $.post(url,{doc_id:doc_id,field:field,value:value},function(ok){
	    if(ok*1){
		App.flash(succes_msg);
	    } else {
		App.flash("Изменения не сохранены");
	    }
	    doc.reload();
	});
    },
    initToolbar:function(){
	$("#"+holderId+" .x-head .x-toolbar").click(function(e){
	    var action=$(e.target).data('action');
	    if( action ){
		head.do( action );
	    }
	});
    },
    do:function( action ){
	head[action] && head[action]();
    },
    reload:function(){
	doc.reload();
    },
    commit:function(){
	head.update('is_commited',1,'Документ проведен');
    },
    uncommit:function(){
	head.update('is_commited',0,'Документ не проведен');
    },
    initControls:function(){
	App.setupForm("#"+holderId+" .x-head form");
	$("#"+holderId+" .x-head form").change(function(e){
	    e.target;

	    if( $(e.target).attr('type')==='checkbox' ){
		var value=$(e.target).prop('checked')?1:0;
	    } else {
		var value=$(e.target).val();
	    }
	    console.log($(e.target).attr('name'));
	    head.update( $(e.target).attr('name'), value, $(e.target).attr('title') );
	});
	$.parser.parse("#"+holderId+" .x-head form");//for easy ui
	head.pcompComboInit();
    },
    pcompComboInit:function(){
	if( this.pcompNode ){
	    return;
	}
	this.pcompNode=$("#"+holderId+" .x-head input[name=passive_company_id]");
	var options={
	    valueField: 'company_id',
	    textField: 'label',
	    loader:head.pcompLoader,
	    mode: 'remote',
	    hasDownArrow:false,
	    selectOnNavigation:false,
	    formatter:head.pcompListfrm,
	    width:220,
	    icons: [
		{iconCls:'icon-settings16',handler: App.user.pcompSelectionDialog},
		{iconCls:'icon-change16',handler:head.pcompDetails}
	     ]
	};
	this.pcompNode.combobox(options);
    },
    pcompLoader:function(param, success, error){
	if( param.q===undefined ){
	    success([{company_id:document_data.head.passive_company_id,label:document_data.head.label}]);
	    return;
	}
	$.get('Company/listFetch/', param, function (xhr) {
	    var resp = App.json(xhr);
	    success(resp[0] ? resp : []);
	});
    },
    pcompListfrm:function(row){
	var label=row.label;
	if( row.path ){
	    var path_chunks=row.path.split('>');
	    var label=path_chunks.slice(path_chunks.length-2).reverse().join('/ ');
	}
	return label;
    },
    pcompDetails:function(){
	App.loadWindow('page/company/details',{company_id:document_data.head.passive_company_id});
    }
};
body={
    vocab:{
	not_enough:"На складе не хватает:",
	already_exists:"Строка с таким кодом уже добавлена",
	product_code_unknown:"Неизвестный товар",
	quantity_wrong:"Колличество должно быть больше нуля",
	entry_deleted_before:"Строка уже удалена"
    },
    table_sg:{},
    init:function(){
	body.table.init();
	body.suggest.init();
	body.tools.init();
	App.vocab=$.extend(App.vocab,this.vocab);
    },
    render:function(table){
	body.table_sg.setData(table);
	body.table_sg.render();
    },
    destroy:function(){
	$("#"+holderId+" .x-body .x-suggest").combobox('destroy');
    },
    suggest:{
	init:function(){
	    function suggFormatter(row){
		var html ='<div class="sugg_'+(row['leftover']>0?'instock':'outofstock')+'">';
		html+='<div class="sugg_img"><img src="Storage/image_flush/?size=30x30&path=/dynImg/'+row['product_img']+'"></div>';
		html+='<div class="sugg_name"><div class="sugg_code">'+row['product_code']+'</div> '+row['product_name']+' [x'+row['product_spack']+row['product_unit']+']</div>';
		html+='<div class="sugg_price">'+Number(row.product_price_total).toFixed(2)+'</div>';
		html+='<div class="sugg_leftover">'+row['leftover']+row['product_unit']+'</div>';
		html+='</div>';
		return html;
	    };
	    var suggPrevCode='';
	    function suggOnselect( row ){
		suggPrevCode=row.product_code;
		$("#"+holderId+" .x-body .x-qty").val(row.product_spack).select();
	    };
	    $("#"+holderId+" .x-body .x-suggest").combobox({
		valueField: 'product_code',
		textboxextField: 'product_code',
		formatter:suggFormatter,
		selectOnNavigation:false,
		url: 'DocumentItems/suggestFetch/',
		panelHeight:'auto',
		mode: 'remote',
		method:'get',
		hasDownArrow:false,
		panelWidth:600,
		panelMinWidth:400,
		prompt:'код, название или штрихкод',
		onSelect: suggOnselect,
		onBeforeLoad: function(param){
		    param.doc_id = doc_id;
		}
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
		body.table.entryAdd(product_code,product_quantity);
		$("#"+holderId+" .x-body .x-qty").val('');
		$("#"+holderId+" .x-body .x-suggest").combobox('textbox').select();
	    }
	    $("#"+holderId+" .x-body .x-qty").bind( 'keydown', function(e){
		if( e.keyCode===13 ){
		    suggestSubmit();
		}
	    });
	    $("#"+holderId+" .x-body .x-suggest-submit").click(suggestSubmit);
	}
    },
    picker:{
	init:function(){
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
		    {id:"ru", field: "ru",name: "Название",width:330, sortable: true},
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
	    body.picker.inited=true;
	}
    },
    tools:{
	init:function(){
	    $("#"+holderId+" .x-body .x-body-tools").click(function(e){
		var action=$(e.target).data('action');
		if( action ){
		    body.tools[ action ] && body.tools[action]();
		}
	    });	    
	},
	pickerToggle:function(){
	    if(!body.picker.inited){
		body.picker.init();
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
	productCard:function(){
	    var selected_rows=body.table_sg.getSelectedRows();
	    if(!selected_rows.length){
		App.flash("Ни одна строка не выбрана!");
		return;
	    }
	    var row=body.table_sg.getDataItem(selected_rows[0]);
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
		    App.post("DocumentSell/entryImport/",fvalue,function(ok){
			App.flash("Импортировано "+ok);
			Importer.reload();
			doc.reload(["body","foot"]);
		    });
		}
	    });
	},
	entryDelete:function(){
	    var selected_rows=body.table_sg.getSelectedRows();
	    if(!selected_rows.length){
		App.flash("Ни одна строка не выбрана!");
		return;
	    }
	    if( !confirm("Удалить выделенные строки?") ){
		return;
	    }
	    var table_to_delete=[];
	    for(var i in selected_rows){
		table_to_delete.push(body.table_sg.getDataItem(selected_rows[i]).doc_entry_id);
	    }
	    var url=document_model+'/entryDelete';
	    $.post(url,{doc_id:doc_id,doc_entry_ids:JSON.stringify(table_to_delete)},function(ok){
		if( !(ok*1) ){
		    App.flash("Строка не удалена");
		}
		doc.reload(["body","foot"]);
	    });
	}
    },
    table:{
	init:function(){
	    var settings={
		columns:[
		    {id:"queue",name: "№", width: 30,formatter:body.table.formatters.queue },
		    {id:"product_code", field: "product_code",name: "Код", sortable: true, width: 80},
		    {id:"product_name", field: "product_name",name: "Название", sortable: true, width: 400},
		    {id:"product_quantity", field: "product_quantity",name: "Кол-во", sortable: true, width: 70, cssClass:'slick-align-right', editor: Slick.Editors.Integer},
		    {id:"product_unit", field: "product_unit",name: "Ед.", width: 30, sortable: true },
		    {id:"product_price_total", field: "product_price_total",name: "Цена", sortable: true, width: 70, cssClass:'slick-align-right',asyncPostRender:body.table.formatters.priceisloss, editor: Slick.Editors.Float},
		    {id:"product_sum_total", field: "product_sum_total",name: "Сумма", sortable: true, width: 80,cssClass:'slick-align-right', editor: Slick.Editors.Float},
		    {id:"row_status", field: "row_status",name: "!",sortable: true, width: 25,formatter:body.table.formatters.tooltip },
		    //{id:"party_label",field:"party_label",name:"Партия",width:120, editor: Slick.Editors.Text},
		    //{id:"analyse_origin",field:'analyse_origin',name:"Происхождение",width:70},
		    //{id:"self_price",field:'self_price',name:"maliyet",width:60,cssClass:'slick-align-right'}
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
	    body.table_sg = new Slick.Grid("#"+holderId+" .x-body .x-entries", [], settings.columns, settings.options);
	    body.table_sg.setSelectionModel(new Slick.RowSelectionModel());
	    body.table_sg.onCellChange.subscribe(function(e,data){
		var updatedEntry=data.item;
		var field=settings.columns[data.cell].field;
		var value=updatedEntry[field];
		body.table.entryUpdate(updatedEntry.doc_entry_id,field,value);
	    });	    
	},
	formatters:{
	    queue:function(row, cell, value, columnDef, dataContext){
		return row+1;
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
	    }
	},
	entryAdd:function(product_code,product_quantity){
	    var url=document_model+'/entryAdd';
	    $.post(url,{doc_id:doc_id,product_code:product_code,product_quantity:product_quantity},function(ok,status,xhr){
		if( !(ok*1) ){
		    App.flash("Строка не добавлена");
		}
		var msg = xhr.getResponseHeader('X-isell-msg');
		if( msg && msg.indexOf('product_code_unknown')>-1 && confirm("Добавить новый код "+product_code+" на склад?") ){
		    App.loadWindow('page/stock/product_card',{product_code:product_code});
		}
		doc.reload(["body","foot"]);
	    });	
	},
	entryUpdate:function(doc_entry_id,field,value){
	    var url=document_model+'/entryUpdate';
	    $.post(url,{doc_id:doc_id,doc_entry_id:doc_entry_id,field:field,value:value},function(ok){
		if( !(ok*1) ){
		    App.flash("Строка не изменена");
		}
		doc.reload(["body","foot"]);
	    });
	}
    }
    /*
     * @TODO add export table
     */
};
