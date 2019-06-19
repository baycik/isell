/*global Slick,holderId,Document,Document.doc_extension,App*/

Document.head={
    init:function(){
	Document.head.controls.init();
	Document.head.toolbar.init();
    },
    render:function(head_data){
	Document.data.head=head_data;
        Document.head.controls.render(head_data);
    },
    destroy:function(){
        this.pcompNode && this.pcompNode.combobox && this.pcompNode.combobox('clear');
    },
    update:function(field,value,succes_msg){
	var url=Document.doc_extension+'/documentUpdate';
	return $.post(url,{doc_id:Document.doc_id,field:field,value:value},function(ok){
	    if(ok*1){
		App.flash(succes_msg);
	    } else {
		App.flash("Изменения не сохранены");
	    }
            Document.reload();
	});
    },
    controls:{
        init:function(){
            App.setupForm("#"+holderId+" .x-head form");
            Document.head.controls.initEasyUiWidgets();
            $("#"+holderId+" .x-head form,#"+holderId+" .document_comment").form({
                onChange: function(node){
                    let name=$(node).attr('name') || $(node).attr('textboxname');
                    let value=$(node).val();
                    if ( $(node).attr('type') === 'checkbox' ) {
                        value=$(node).is(':checked') ? 1 : 0;
                    }
                    let title=$(node).attr('title');
                    Document.head.controls.handleChange(name,value,title);
                }
            });
        },
        initEasyUiWidgets:function(){
            //$.parser.parse("#"+holderId+" .x-head form");//for easy ui
            $("#"+holderId+" .x-head input[name=passive_company_id]").prop('id',holderId+'_pcomp');
            $("#"+holderId+"_pcomp").combobox({
                valueField: 'company_id',
                textField: 'label',
                mode: 'remote',
                hasDownArrow:false,
                selectOnNavigation:false,
                loader:function(param, success, error){
                    if( param.q===undefined ){
                        success([{company_id:Document.data.head.passive_company_id,label:Document.data.head.label}]);
                        return;
                    }
                    $.get('Company/listFetch/', param, function (xhr) {
                        var resp = App.json(xhr);
                        success(resp.length ? resp : []);
                    });
                },
                formatter:Document.head.controls.companyFormatter,
                icons: [
                    {iconCls:'icon-settings16',handler: Document.head.controls.companyDetailsShow},
                    {iconCls:'icon-change16',handler:Document.head.controls.companyTreeShow}
                ]
            });
            $("#"+holderId+" .x-head input[name=active_company_id]").prop('id',holderId+'_acomp');
            $("#"+holderId+"_acomp").combobox({
                valueField: 'company_id',
                textField: 'label',
                mode: 'remote',
                hasDownArrow:true,
                selectOnNavigation:false,
                loader:function(param, success, error){
                    if( param.q===undefined ){
                        success([{company_id:Document.data.head.active_company_id,label:Document.data.head.active_company_label}]);
                        return;
                    }
                    $.get('Company/listFetch/active_only', param, function (xhr) {
                        var resp = App.json(xhr);
                        success(resp.length ? resp : []);
                    });
                }
            });
            $("#"+holderId+" .x-head input[name=doc_status_id]").prop('id',holderId+'_doc_status_id');
            $("#"+holderId+"_doc_status_id").combobox({
                valueField: 'doc_status_id',
                textField: 'status_description',
                url:'DocumentList/statusFetchList',
                mode: 'remote',
                selectOnNavigation:false,
                panelHeight:''
            });
        },
        handleChange:function( name, value, title ){
            console.log(name,value,title);
        },
        render:function(head_data){
            $("#"+holderId+" .x-head form").form('load',head_data);
            $("#"+holderId+" .x-toolbar .icon-commit").css("filter","grayscale("+(head_data.is_commited*1?100:0)+"%)");
            $("#"+holderId+" .x-foot .document_comment").val(Document.data.head.doc_data);
            $("#"+holderId+" .x-head :checkbox").each(function(){
                $(this).prop("checked",head_data[$(this).attr('name')]*1);
            });
            $("#"+holderId+"_pcomp").combobox('reload');
            $("#"+holderId+"_acomp").combobox('reload');
        },
        companyTreeShow:function(){
            App.loadWindow('page/company/tree',{}).progress(function(status,company){
                if( status==='select' ){
                    App.user.pcompSelect(company);
                    $(Document.head.pcompNode).combobox('setValue', company.company_id);
                    $(Document.head.pcompNode).combobox('setText', company.label);
                    //Document.head.update( 'passive_company_id', company.company_id,'passive_company_id is changed');
                }
            });
        },
        companyDetailsShow:function(){
            App.loadWindow('page/company/details',{company_id:Document.data.head.passive_company_id});
        },
        companyFormatter:function(row){
            var label=row.label;
            if( row.path ){
                var path_chunks=row.path.replace(/^\/|\/$/g,'').split('>');
                label=path_chunks.slice(path_chunks.length-2).reverse().join('/ ');
            }
            return label;
        }
    },
    toolbar:{
        init:function(){
            $("#"+holderId+" .x-head .x-toolbar").click(function(e){
                var action=$(e.target).data('action');
                if( action ){
                    Document.head.toolbar.actions[action] && Document.head.toolbar.actions[action]();
                }
            });
        },
        actions:{
            reload:function(){
                Document.reload();
            },
            add:function(){
                var url=Document.doc_extension+'/documentAdd';
                var doc_type = $('Doc_type_cmb').val();
                return $.post(url,{doc_type:doc_type},function(ok){
                    if(ok*1){
                        App.flash('Документ создан');
                    } else {
                        App.flash("Документ не создан");
                    }
                    Document.head.reload();
                });
            },
            commit:function(){
                if( Document.data.head.is_commited*1 ){
                    return;
                }
                Document.head.update('is_commited',1,'Документ проведен');
            },
            uncommit:function(){
                if( Document.data.head.is_commited*1 ){
                    Document.head.update('is_commited',0,'Документ не проведен');
                } else {
                    if( confirm("Удалить документ полностью?") ){
                        Document.delete();
                    }
                }	
            },
            duplicate:function(){
                if( confirm("Создать копию этого документа?") ){
                    App.post(Document.doc_extension+"/documentDuplicate/"+Document.doc_id,function(doc_id){
                        if( doc_id*1 ){
                            Document.doc_id = doc_id;
                            Document.reload();
                            //Document.handler.notify('created',doc_id);
                            App.flash("Документ скопирован и загружен");
                        }
                    });
                }
            },
            pay:function(){
                App.loadWindow("page/accounts/document_pay",{doc_id:Document.doc_id,total:Document.data.foot.total}).done(function(fvalue){
                    App.flash("head_changed!");
                });
            },
            sendsms:function(){
                $.get("Company/companyGet/"+Document.data.head.passive_company_id, function ( xhr ) {
                    var passive_data=App.json(xhr);
                    var data={to:passive_data.company_mobile,body:Document.data.head.doc_data};
                    App.loadWindow('page/dialog/send_sms',data);	    
                });
            },
            addevent:function(){
                $.get("Company/companyGet/"+Document.data.head.passive_company_id, function ( xhr ) {
                    var passive_data=App.json(xhr);
                    var fvalue={
                        doc_id:Document.data.head.doc_id,
                        event_id:0,
                        event_label:'Доставка',
                        event_creator_user_id: App.user.props.user_id,
                        event_name: 'Документ №' + Document.data.head.doc_num,
                        event_descr: Document.data.head.doc_data,
                        event_target: passive_data.company_person + " (" + passive_data.label + ")",
                        event_place: passive_data.company_address,
                        event_note: passive_data.company_mobile
                    };
                    App.loadWindow('page/events/event',fvalue);	    
                });
            },
        }
    }
};
Document.body={
    vocab:{
	not_enough:"На складе не хватает:",
	already_exists:"Строка с таким кодом уже добавлена",
	product_code_unknown:"Неизвестный товар",
	quantity_wrong:"Колличество должно быть больше нуля",
	entry_deleted_before:"Строка уже удалена"
    },
    table_sg:{},
    init:function(){
	Document.body.table.init();
	Document.body.suggest.init();
	Document.body.tools.init();
	App.vocab=$.extend(App.vocab,this.vocab);
    },
    render:function(table){
	Document.body.table_sg.setData(table);
	Document.body.table_sg.render();
    },
    destroy:function(){
	$("#"+holderId+" .x-body .x-suggest").combobox('clear');
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
		url: Document.doc_extension+'/entrySuggestFetch/',
		panelHeight:'auto',
		mode: 'remote',
		method:'get',
		hasDownArrow:false,
		panelWidth:600,
		panelMinWidth:400,
		prompt:'код, название или штрихкод',
		onSelect: suggOnselect,
		onBeforeLoad: function(param){
		    param.doc_id = Document.doc_id;
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
		Document.body.table.entryAdd(product_code,product_quantity);
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
		    url:Document.doc_extension+'/pickerListFetch'
		}
	    };
	    var picklist=$("#"+holderId+" .x-body .x-stock").slickgrid(settings);
	    picklist.onSelectedRowsChanged.subscribe(function(e,selection){
		var row=selection.grid.getDataItem(selection.rows[0]);
		$("#"+holderId+" .x-body .x-suggest").combobox('setValue',row.product_code);
		$("#"+holderId+" .x-body .x-qty").val(row.product_spack).select();
	    });
	    Document.body.picker.inited=true;
	}
    },
    tools:{
	init:function(){
	    $("#"+holderId+" .x-body .x-body-tools").click(function(e){
		var action=$(e.target).data('action');
		if( action ){
		    Document.body.tools[ action ] && Document.body.tools[action]();
		}
	    });	    
	},
        
	pickerToggle:function(){
	    if(!Document.body.picker.inited){
		Document.body.picker.init();
	    }
	    if( Document.body.pickerVisible ){
		$("#"+holderId+" .x-picker").hide();
		$("#"+holderId+" .x-picker-button").text("Открыть подбор");
	    } else {
		$("#"+holderId+" .x-picker").show();
		$("#"+holderId+" .x-picker-button").text("Скрыть подбор");
	    }
	    Document.body.pickerVisible=!Document.body.pickerVisible;
	},
	productCard:function(){
	    var selected_rows=Document.body.table_sg.getSelectedRows();
	    if(!selected_rows.length){
		App.flash("Ни одна строка не выбрана!");
		return;
	    }
	    var row=Document.body.table_sg.getDataItem(selected_rows[0]);
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
		    fvalue.doc_id=Document.doc_id;
		    App.post(Document.doc_extension+"/entryImport/",fvalue,function(ok){
			App.flash("Импортировано "+ok);
			Importer.reload();
			Document.reload(["body","foot"]);
		    });
		}
	    });
	},
	entryDelete:function(){
	    var selected_rows=Document.body.table_sg.getSelectedRows();
	    if(!selected_rows.length){
		App.flash("Ни одна строка не выбрана!");
		return;
	    }
	    if( !confirm("Удалить выделенные строки?") ){
		return;
	    }
	    var table_to_delete=[];
	    for(var i in selected_rows){
		table_to_delete.push(Document.body.table_sg.getDataItem(selected_rows[i]).doc_entry_id);
	    }
	    var url=Document.doc_extension+'/entryDelete';
	    $.post(url,{doc_id:Document.doc_id,doc_entry_ids:JSON.stringify(table_to_delete)},function(ok){
		if( !(ok*1) ){
		    App.flash("Строка не удалена");
		}
		Document.reload(["body","foot"]);
	    });
	},
        recalculate:function(){
	    App.loadWindow('page/trade/document_recalculate').progress(function(state,data){
		if( state==='submit' ){
		    App.post(Document.doc_extension+"/entryRecalc/"+Document.doc_id+'/'+(data.recalc_proc*1||0), function ( xhr ) {
                        Document.reload(["body","foot"]);
                        App.flash("Перерасчет выполнен");
		    });
                }
	    });
	}
    },
    table:{
	init:function(){

	    var settings={
		columns:[
		    {id:"queue",name: "№", width: 30,formatter:Document.body.table.formatters.queue },
		    {id:"product_code", field: "product_code",name: "Код", sortable: true, width: 80},
		    {id:"product_name", field: "product_name",name: "Название", sortable: true, width: 388},
		    {id:"product_quantity", field: "product_quantity",name: "Кол-во", sortable: true, width: 70, cssClass:'slick-align-right', editor: Slick.Editors.Integer},
		    {id:"product_unit", field: "product_unit",name: "Ед.", width: 30, sortable: true },
		    {id:"product_price", field: "product_price",name: "Цена", sortable: true, width: 70, cssClass:'slick-align-right',asyncPostRender:Document.body.table.formatters.priceisloss, editor: Slick.Editors.Float},
		    {id:"product_sum", field: "product_sum",name: "Сумма", sortable: true, width: 80,cssClass:'slick-align-right', editor: Slick.Editors.Float},
		    {id:"row_status", field: "row_status",name: "!",sortable: true, width: 25,formatter:Document.body.table.formatters.tooltip },
		    //{id:"party_label",field:"party_label",name:"Партия",width:120, editor: Slick.Editors.Text},
		    //{id:"analyse_origin",field:'analyse_origin',name:"Происхождение",width:70},
		    //{id:"self_price",field:'self_price',name:"maliyet",width:60,cssClass:'slick-align-right'}
		],
		options:{
		    editable: true,
		    autoEdit: true,
		    autoHeight: true,
                    leaveSpaceForNewRows:true,
		    enableCellNavigation: true,
		    enableColumnReorder : false,
		    enableFilter:false,
		    multiSelect :true,
		    enableAsyncPostRender: true
		}
	    };
	    Document.body.table_sg = new Slick.Grid("#"+holderId+" .x-body .x-entries", [], settings.columns, settings.options);
	    Document.body.table_sg.setSelectionModel(new Slick.RowSelectionModel());
	    Document.body.table_sg.onCellChange.subscribe(function(e,data){
		var updatedEntry=data.item;
		var field=settings.columns[data.cell].field;
		var value=updatedEntry[field];
		Document.body.table.entryUpdate(updatedEntry.doc_entry_id,field,value);
	    });	  
            $("#"+holderId+" .x-body .x-entries").click(function(e){
                var row = Document.body.table_sg.getSelectedRows()[0];
                var action = $(e.target).data('action');
                var row_data = Document.body.table_sg.getData()[row];
		if( action ){
		    Document.body.table.actions[ action ] && Document.body.table.actions[action](row_data);
		}
            });
             
	},
        actions:{
            err_reserve:function(row_data){
                location.hash="#Stock#stock_main_tabs=Резерв&product_code="+row_data.product_code;
            },
            err_breakeven:function(row_data){
                var url=Document.doc_extension+'/entryUpdate';
                $.post(url,{doc_id:Document.doc_id,doc_entry_id:row_data.doc_entry_id,field:'product_price',value:row_data.breakeven_price},function(ok){
                    if( !(ok*1) ){
                        App.flash("Строка не изменена");
                    }
                    Document.reload(["body","foot"]);
                });
                App.flash("Цена изменена на "+row_data.breakeven_price);
            }
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
			return '<img src="img/' + cmd + '.png" style="max-width:16px;height:auto; cursor: pointer" title="' + parts.join(' ') + '" data-action="' + cmd + '">';
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
	    var url=Document.doc_extension+'/entryAdd';
	    $.post(url,{doc_id:Document.doc_id,product_code:product_code,product_quantity:product_quantity},function(ok,status,xhr){
		if( !(ok*1) ){
		    App.flash("Строка не добавлена");
		}
		var msg = xhr.getResponseHeader('X-isell-msg');
		if( msg && msg.indexOf('product_code_unknown')>-1 && confirm("Добавить новый код "+product_code+" на склад?") ){
		    App.loadWindow('page/stock/product_card',{product_code:product_code});
		}
		Document.reload(["body","foot"]);
	    });	
	},
	entryUpdate:function(doc_entry_id,field,value){
	    var url=Document.doc_extension+'/entryUpdate';
	    $.post(url,{doc_id:Document.doc_id,doc_entry_id:doc_entry_id,field:field,value:value},function(ok){
		if( !(ok*1) ){
		    App.flash("Строка не изменена");
		}
		Document.reload(["body","foot"]);
	    });
	}
        
    }
    /*
     * @TODO add export table
     */
};
Document.foot = {
    init: function(){
    },
    render:function(footer){
        footer.total_weight=footer.total_weight||'0.00';
        footer.total_volume=footer.total_volume||'0.00';
        footer.vatless=footer.vatless||'0.00';
        footer.vat=footer.vat||'0.00';
        footer.total=footer.total||'0.00';
        footer.curr_symbol=footer.curr_symbol||'';
        footer.created_by=Document.data.head.created_by;
        footer.modified_by=Document.data.head.modified_by;
        footer.checkout_status=Document.data.head.checkout_status;
        footer.checkout_id=Document.data.head.checkout_id;
        Document.foot.checkout.parse(footer);
        Document.data.foot=footer;
        App.renderTpl("#"+holderId+" .x-foot div",Document.data.foot);
        Document.foot.checkout.setup();
    },
    parse_checkout_statuses:function(footer){
        
    },
    go_to_checkout: function(){
        if(Doc.head.props.checkout_status){
            location.hash="#Stock#checkout_id="+Doc.head.props.checkout_id;
        } else {
            this.create_checkout();  
        }
    },
    destroy:function(){
        
    },
    checkout:{
        parse:function(footer){
            switch(footer.checkout_status){
                case 'not_checked':
                    footer.checkout_status_img = 'red.png';
                    footer.checkout_status_msg = 'Не проверено';
                    break;
                case 'is_checking':
                    footer.checkout_status_img = 'bolt.png';
                    footer.checkout_status_msg = 'Проверяется';
                    break;
                case 'checked':
                    footer.checkout_status_img = 'ok.png';
                    footer.checkout_status_msg = 'Проверено';  
                    break;
                case 'checked_with_divergence':
                    footer.checkout_status_img = 'ok.png';
                    footer.checkout_status_msg = 'Проверено с корректировками';  
                    break;
               default:
                    footer.checkout_status_img= 'ProductCard.png';
                    footer.checkout_status_msg = 'Проверить';
                    break;
            }
        },
        setup:function(){
            
            $("#"+holderId+" .x-foot-checkout").on('click',function(){

            });
        }
    }
};
Document.views={
    init:function(){
    },
    render: function(views){
        Document.views.view_list = Document.views.compile(views);
        App.renderTpl('Document_view_tile',{views:Document.views.view_list});
        Document.views.initControls();
    },
    initControls: function(){
        $('#load_views_button').click(function(){
            Document.views.render(Document.views.view_list);
        });
        $('#Document_view_tile img, #Document_view_tile .view_button').click(function(e){
            switch(e.target.className){
                case 'Document_view_settings':
                    Document.views.settings(e.target);
                    event.stopPropagation();
                    return;
                case 'Document_view_arrow':
                    Document.views.togglehidden();
                    return;
                default:
                    Document.views.click(e.target);
                    return;
            }
        });
    },
    destroy:function(){
        
    },
    compile:function( view_list ){
        for( var i in view_list ){
            var view=view_list[i];
            var efield_vals=App.json(view.view_efield_values);
            var efield_labs=App.json(view.view_efield_labels);
            view_list[i].efields=[];
            for( var k in efield_labs ){
                view_list[i].efields.push({field:k,label:efield_labs[k],value:efield_vals?efield_vals[k]||'':''});
            }
        }
        return view_list;
    },
    settings:function( node ){
        var i=$(node).attr('data-view-i');
        var view=Document.views.view_list[i];
        if( view.doc_view_id ){
            App.loadWindow('page/trade/view_settings',view).progress(function(status){
                if( status==='deleted' || status==='changed' || status==='close' ){
                    Document.reload();
                }
            });
        }
    },
    create:function(view_type_id){
        if(!view_type_id){
            return;
        }
        App.post("DocumentView/viewCreate/"+view_type_id,function(doc_view_id){
            if( doc_view_id*1 ){
                Document.views.open(doc_view_id);
                Document.reload();
            }
        });
    },
    open:function(doc_view_id){
        window.open("./DocumentView/documentViewGet/?out_type=.print&doc_view_id="+doc_view_id, '_new');
    },
    click:function( node ){
        var doc_view_id=$(node).attr('data-view-id');
        var view_type_id=$(node).attr('data-view-type-id');
        if( doc_view_id*1 ){
            Document.views.open(doc_view_id);
        }
        else{
            Document.views.create(view_type_id);
        }
    },
    showhidden:false,
    togglehidden:function(){
        this.showhidden=!this.showhidden;
        $('.Document_view_arrow').attr('src','img/arrow'+(this.showhidden?'left':'right')+'.png');
        $('.view_is_hidden').css('display',(this.showhidden?'inline-block':'none'));
    }
};

