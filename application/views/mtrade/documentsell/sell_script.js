/*global Slick,holderId,Document,Document.doc_extension,App,Mark*/
Document.head = {
    commited_color:"rgba(0,255,100,0.2)",
    init: function () {
        Document.head.controls.init();
        Document.head.toolbar.init();
    },
    render: function (head_data) {
        Document.data.head = head_data;
        setTimeout(function () {
            Document.head.controls.render(head_data);
        }, 0);
    },
    destroy: function () {
        this.pcompNode && this.pcompNode.combobox && this.pcompNode.combobox('clear');
    },
    update: function (field, value, succes_msg) {
        if(Document.doc_id==0){
            Document.create().then(function(){
                Document.head.update(field, value, succes_msg);
            });
            return true;
        }
        var url = Document.doc_extension + '/headFieldUpdate';
        return $.post(url, {doc_id: Document.doc_id, field: field, value: value}).done(function (ok) {
            if (ok * 1) {
                App.flash(succes_msg);
                App.Topic('documentHeadChanged').publish({doc_id: Document.doc_id, field: field, value: value});
            } else {
                App.flash("Изменения не сохранены");
            }
            Document.reload();
        }).fail(function(xhr){
            App.flash("Изменения не сохранены");
            App.flash( xhr.responseText );
            Document.reload();
        });
    },
    controls: {
        suppress_update: true,
        sformSetup: function (fquery, fvalue, mode) {
            if (!fquery) {
                return false;
            }
            fvalue = fvalue || {};
            let $formElements = $(fquery + " input," + fquery + " textarea," + fquery + " select");
            $formElements.each(function (i, element) {
                let value = fvalue[element.name];
                let $element = $(element);
                if ( $element.attr('type') === 'date' && value) {
                    value=value.split(" ")[0];
                }
                $element.val(value);
                if ($element.attr('type') === 'hidden') {
                    return true;
                }
                if ($element.attr('type') === 'checkbox') {
                    $element.prop('checked', value * 1);
                }
                if ($element.attr('title') && !$element.attr('data-skip')) {
                    let $ele = $element;
                    if ($element.parent().hasClass('input')) {
                        $ele = $element.parent();
                    }
                    $ele.wrap('<div class="field"></div>');
                    $ele.parent().before("<label>" + $element.attr('title') + "</label>");
                }
                $element.attr('data-skip', 1);
            });
            return $formElements;
        },
        init: function () {
            Document.head.controls.sformSetup("#" + holderId + " .x-head form");//
            $("#" + holderId + " .x-head form").change(function (e) {
                let node = e.target;
                let name = $(node).attr('name');
                let title = $(node).attr('title');
                let value = $(`#${holderId} [name=${name}]`).val();
                if ($(node).attr('type') === 'checkbox') {
                    value = $(node).is(':checked') ? 1 : 0;
                }
                Document.head.update(name, value, title);
            });
            Document.head.controls.widgets.init();
        },
        widgets: {
            init: function () {
                $('#' + holderId + ' select[name=doc_status_id]').dropdown({
                    apiSettings: {
                        url: 'DocumentList/statusFetchList',
                        onResponse: function (list) {
                            for (let i in list) {
                                list[i] = Document.head.controls.widgets.formatters.doc_status_id(list[i]);
                            }
                            return {
                                success: true,
                                results: list
                            };
                        }
                    }
                });
                $('#' + holderId + ' select[name=doc_type]').dropdown({
                    apiSettings: {
                        url: 'DocumentList/documentTypeListFetch',
                        onResponse: function (list) {
                            for (let i in list) {
                                list[i] = Document.head.controls.widgets.formatters.doc_type(list[i]);
                            }
                            return {
                                success: true,
                                results: list
                            };
                        }
                    }
                });
                $('#' + holderId + ' select[name=active_company_id]').dropdown({
                    apiSettings: {
                        url: 'Company/listFetch/?mode=active_only&q={query}',
                        onResponse: function (list) {
                            let companies = [];
                            for (let i in list) {
                                companies.push({value: list[i].company_id, name: list[i].label});
                            }
                            return {
                                success: true,
                                results: companies
                            };
                        }
                    }
                });
                $('#' + holderId + ' select[name=passive_company_id]').dropdown({
                    apiSettings: {
                        url: 'Company/listFetch/?q={query}',
                        onResponse: function (list) {
                            let companies = [];
                            for (let i in list) {
                                companies.push({value: list[i].company_id, name: list[i].label});
                            }
                            return {
                                success: true,
                                results: companies
                            };
                        }
                    }
                });
            },
            formatters: {
                doc_status_id: function (item) {
                    if( !item ){
                        return {
                            name:'',
                            value:''
                        };
                    }
                    let icons = {
                        created: 'star outline',
                        reserved: 'clock outline',
                        processed: 'shipping fast'
                    };
                    item.name = `<i class="ui icon ${icons[item.status_code]}"></i> ${item.status_description}`;
                    item.value = item.doc_status_id;
                    return item;
                },
                doc_type: function (item) {
                    item.name = `<img src="img/${item.icon_name}.png" class="ui image" style="margin:1px"> ${item.doc_type_name}`;
                    item.value = item.doc_type;
                    return item;
                }
            }
        },
        render: function (head_data) {
            if( head_data.is_commited==1 ){
                $(`#${holderId}`).css('background-color', Document.head.commited_color );
                $(`#${holderId} .x-toolbar .icon-commit`).css('filter', 'grayscale(100%)' );
            } else {
                $(`#${holderId}`).css('background-color', '' );
                $(`#${holderId} .x-toolbar .icon-commit`).css('filter', 'none' );
            }
            
            //head_data.cstamp = head_data.cstamp.substr(0, 10);
            $('#' + holderId + ' select[name=passive_company_id]')
                    .dropdown('set text', Document.data.head.passive_company_label);
            $('#' + holderId + ' select[name=active_company_id]')
                    .dropdown('set text', Document.data.head.active_company_label);
            let selected_status_item = Document.head.controls.widgets.formatters.doc_status_id(head_data.status);
            $('#' + holderId + ' select[name=doc_status_id]')
                    .dropdown('set text', selected_status_item.name);
            let selected_type_item = Document.head.controls.widgets.formatters.doc_type(head_data.type);
            $('#' + holderId + ' select[name=doc_type]')
                    .dropdown('set text', selected_type_item.name);
            Document.head.controls.sformSetup("#" + holderId + " .x-head form", head_data);
        }
    },
    toolbar: {
        init: function () {
            $("#" + holderId + " .x-head .x-toolbar").click(function (e) {
                var action = $(e.target).data('action');
                if (action) {
                    Document.head.toolbar.actions[action] && Document.head.toolbar.actions[action]();
                }
            });
        },
        actions: {
            reload: function () {
                Document.reload();
            },
            add: function () {
                var url = Document.doc_extension + '/documentAdd';
                var doc_type = $('Doc_type_cmb').val();
                return $.post(url, {doc_type: doc_type}, function (ok) {
                    if (ok * 1) {
                        App.flash('Документ создан');
                    } else {
                        App.flash("Документ не создан");
                    }
                    Document.head.reload();
                });
            },
            commit: function () {
                if (Document.data.head.is_commited * 1) {
                    App.flash("Документ уже проведен");
                    return;
                }
                Document.head.update('is_commited', 1, 'Документ проведен');
            },
            uncommit: function () {
                if (Document.data.head.is_commited * 1) {
                    Document.head.update('is_commited', 0);
                } else {
                    if (confirm("Удалить документ полностью?")) {
                        Document.delete();
                    }
                }
            },
            duplicate: function () {
                if (confirm("Создать копию этого документа?")) {
                    $.post(Document.doc_extension + "/documentDuplicate/",{doc_id:Document.doc_id}, function (doc_id) {
                        if (doc_id * 1) {
                            Document.doc_id = doc_id;
                            Document.reload();
                            //Document.handler.notify('created',doc_id);
                            App.flash("Документ скопирован и загружен");
                        }
                    });
                }
            },
            pay: function () {
                if(Document.data.head.is_commited!=1){
                    App.flash("Документ не проведен");
                    return false;
                }
                App.loadWindow("page/accounts/document_pay", {doc_id: Document.doc_id, total: Document.data.foot.total}).done(function (fvalue) {
                    Document.load(Document.doc_id,["head"]);
                    App.Topic("documentListChanged").publish();
                });
            },
            sendsms: function () {
                $.get("Company/companyGet/" + Document.data.head.passive_company_id, function (xhr) {
                    var passive_data = App.json(xhr);
                    var data = {to: passive_data.company_mobile, body: Document.data.head.doc_data};
                    App.loadWindow('page/dialog/send_sms', data);
                });
            },
            addevent: function () {
                $.get("Company/companyGet/",{company_id:Document.data.head.passive_company_id}, function (xhr) {
                    var passive_data = App.json(xhr);
                    var fvalue = {
                        doc_id: Document.data.head.doc_id,
                        event_id: 0,
                        event_label: 'Доставка',
                        event_creator_user_id: App.user.props.user_id,
                        event_name: 'Документ №' + Document.data.head.doc_num,
                        event_descr: Document.data.head.doc_data,
                        event_target: passive_data.company_person + " (" + passive_data.label + ")",
                        event_place: passive_data.company_address,
                        event_note: passive_data.company_mobile
                    };
                    App.loadWindow('page/events/event', fvalue);
                });
            },
            download:function(){
                window.open("./DocumentItems/documentOut/?out_type=.xlsx&doc_id="+Document.data.head.doc_id);
            },
            entryImport: function () {
                var config = [
                    {name: 'Код товара', field: 'product_code', required: true},
                    {name: 'Кол-во', field: 'product_quantity'},
                    {name: 'Цена', field: 'invoice_price'},
                    {name: 'Партия', field: 'party_label'}
                ];
                App.loadWindow('page/dialog/importer', {label: 'документ', fields_to_import: config}).progress(function (status, fvalue, Importer) {
                    if (status === 'submit') {
                        fvalue.doc_id = Document.doc_id;
                        App.post(Document.doc_extension + "/entryImport/", fvalue, function (ok) {
                            App.flash("Импортировано " + ok);
                            Importer.reload();
                            Document.reload(["body", "foot"]);
                        });
                    }
                });
            },
            absent_split:function(){
                if( confirm("Вынести недостающие позиции в отдельную накладную?") ){
                    var new_doc_comment=`Недостаюшие позиции по накладной #${Document.data.head.doc_num}`;
                    var request={
                        old_doc_id:Document.data.head.doc_id,
                        new_doc_comment:new_doc_comment
                    };
                    App.post(Document.doc_extension + "/entryAbsentSplit/",request,function(doc_id){
                        if( doc_id*1 ){
                            App.flash("Документ с резервом на недостающие позиции создан");
                            App.Topic('documentListChanged').publish();
                        } else {
                            App.flash("Недостающие позиции не найдены");
                        }
                    });                
                }
            },
        }
    }
};
Document.body = {
    vocab: {
        product_stock_error: "Недостаточное количество",
        already_exists: "Строка с таким кодом уже добавлена",
        product_code_unknown: "Неизвестный товар",
        quantity_wrong: "Колличество должно быть больше нуля",
        entry_deleted_before: "Строка уже удалена"
    },
    table_sg: {},
    init: function () {
        Document.body.table.init();
        Document.body.suggest.init();
        Document.body.tools.init();
        App.vocab = $.extend(App.vocab, this.vocab);
    },
    render: function (table) {
        Document.body.table_sg.setData(table);
        Document.body.table_sg.render();
        Document.body.table_sg.autosizeColumns();
    },
    destroy: function () {
        //$("#"+holderId+" .x-body .x-suggest").combobox('clear');
    },
    suggest: {
        row_template: '',
        init: function () {
            $('.x-suggest').search({
                apiSettings: {
                    url: `${Document.doc_extension}/suggestFetch/?q={query}`,
                    onResponse: function (list) {
                        for (let i in list) {
                            let item=list[i];
                            item.title=item.product_code;
                            item.image=`Storage/image_flush/?size=30x30&path=/dynImg/${item.product_img}`;
                            item.description=suggFormatter(item);
                        }
                        return {success: true,results: list};
                    },
                    beforeSend:function(settings){
                        settings.data.doc_id=Document.doc_id;
                        return settings;
                    }
                },
                onSelect:function(row){
                    $("#" + holderId + " .x-body .x-qty").val(row.product_spack).select();
                    return true;
                },
                minCharacters: 2,
                maxResults:0
            });

            Document.body.suggest.row_template = $("#" + holderId + " .x-suggest-row-template").html().replace(/&gt;/g, '>').replace(/<!--/g, '').replace(/-->/g, '');
            function suggFormatter(row) {
                return Mark.up(Document.body.suggest.row_template, row);
            }
            function suggestSubmit() {
                var product_code = $("#" + holderId + " .x-body .x-suggest").search('get value');
                var product_quantity = $("#" + holderId + " .x-body .x-qty").val();
                Document.body.table.entryCreate(product_code, product_quantity);
                $("#" + holderId + " .x-body .x-qty").val('');
                $("#" + holderId + " .x-body .x-suggest input").select();
            }
            $("#" + holderId + " .x-body .x-qty").bind('keydown', function (e) {
                if (e.keyCode === 13) {
                    suggestSubmit();
                }
            });
            $("#" + holderId + " .x-body .x-suggest-submit").click(suggestSubmit);
        }
    },
    picker: {
        init: function () {
            function pickerTreeSelect(branch) {
                picklist.updateOptions({params: {parent_id: branch.branch_id}});
                picklist.reload();
            }
            $("#" + holderId + " .x-body .x-allcath").click(function () {
                pickerTreeSelect({branch_id: 0});
                $("#" + holderId + " .x-body .x-tree").find('.tree-node-selected').removeClass('tree-node-selected');
            });
            $("#" + holderId + " .x-body .x-tree").tree({
                url: 'Stock/branchFetch/',
                loadFilter: function (data) {
                    for (var i in data) {
                        data[i].id = data[i].branch_id;
                        data[i].text = data[i].label;
                        if (data[i].is_leaf * 1) {
                            data[i].iconCls = 'icon-comp';
                        }
                    }
                    return data;
                },
                onSelect: pickerTreeSelect
            });
            function qty_color(row, cell, value, columnDef, dataContext) {
                if (value === 0) {
                    return "<span style='color:red'>0</span>";
                }
                return value;
            }
            var settings = {
                columns: [
                    {id: "product_code", field: "product_code", width: 100, name: "Код", sortable: true},
                    {id: "ru", field: "ru", name: "Название", width: 330, sortable: true},
                    {id: "product_quantity", field: "product_quantity", width: 70, name: "Остаток", cssClass: 'slick-align-right', sortable: true, formatter: qty_color},
                    {id: "price", field: "price", name: "Цена", width: 70, sortable: true, cssClass: 'slick-align-right'}
                ],
                options: {
                    enableColumnReorder: false,
                    enableFilter: true,
                    multiSelect: false,
                    url: Document.doc_extension + '/pickerListFetch'
                }
            };
            var picklist = $("#" + holderId + " .x-body .x-stock").slickgrid(settings);
            picklist.onSelectedRowsChanged.subscribe(function (e, selection) {
                var row = selection.grid.getDataItem(selection.rows[0]);
                $("#" + holderId + " .x-body .x-suggest").search('set value', row.product_code);
                $("#" + holderId + " .x-body .x-qty").val(row.product_spack).select();
            });
            Document.body.picker.inited = true;
        }
    },
    tools: {
        init: function () {
            $(`#${holderId} .x-body .x-body-tools,#${holderId} .x-picker-button`).click(function (e) {
                var action = $(e.target).data('action');
                if (action) {
                    Document.body.tools[ action ] && Document.body.tools[action]();
                }
            });
        },

        pickerToggle: function () {
            if (!Document.body.picker.inited) {
                Document.body.picker.init();
            }
            if (Document.body.pickerVisible) {
                $("#" + holderId + " .x-picker").hide();
            } else {
                $("#" + holderId + " .x-picker").show();
            }
            Document.body.pickerVisible = !Document.body.pickerVisible;
        },
        productCard: function () {
            var selected_rows = Document.body.table_sg.getSelectedRows();
            if (!selected_rows.length) {
                App.flash("Ни одна строка не выбрана!");
                return;
            }
            var row = Document.body.table_sg.getDataItem(selected_rows[0]);
            App.loadWindow('page/stock/product_card', {product_code: row.product_code, loadProductByCode: true});
        },
        entryDelete: function () {
            var selected_rows = Document.body.table_sg.getSelectedRows();
            if ( !selected_rows.length ) {
                App.flash("Ни одна строка не выбрана!");
                return;
            }
            if (!confirm("Удалить выделенные строки?")) {
                return;
            }
            var table_to_delete = [];
            for (var i in selected_rows) {
                var row=Document.body.table_sg.getDataItem(selected_rows[i]);
                row && table_to_delete.push(row.doc_entry_id);
            }
            var url = Document.doc_extension + '/entryListDelete';
            $.post(url, {doc_id: Document.doc_id, doc_entry_ids: JSON.stringify(table_to_delete)}, function (ok) {
                if (!(ok * 1)) {
                    App.flash("Строка не удалена");
                }
                Document.reload(["body", "foot"]);
            });
        },
        recalculate: function () {
            App.loadWindow('page/mtrade/document_recalculate').progress(function (state, data) {
                if (state === 'submit') {
                    data.doc_id=Document.doc_id;
                    $.post(Document.doc_extension + "/entryListRecalculate/",data, function (ok) {
                        if( ok*1 ){
                            Document.reload(["body", "foot"]);
                            App.flash("Перерасчет выполнен");
                        } else {
                            App.flash("Неверные параметры");
                        }
                    });
                }
            });
        },
        expand_collapse:function(){
            if (Document.body.table.columnMode === 'advanced') {
                Document.body.table.columnMode = 'simple';
                $(".x-entries .slick-viewport,.x-entries").css('overflow','visible');
            } else {
                Document.body.table.columnMode = 'advanced';
                $(".x-entries .slick-viewport,.x-entries").css('overflow','auto');
            }
            Document.body.table.render();
            //Document.body.tools.reload();
            Document.body.tools.swap_expand_collapse();
        },
        swap_expand_collapse:function(){
            if(Document.body.table.columnMode === 'advanced'){
                $(".x-body-tools .icon-expand").removeClass("icon-expand").addClass("icon-collapse");
            } else {
                $(".x-body-tools .icon-collapse").removeClass("icon-collapse").addClass("icon-expand");
            }
        }
    },
    table: {
        columnMode:'simple',
        init: function () {
            var settings = {
                columns: Document.body.table.columnsGet(),
                options: {
                    editable: true,
                    autoEdit: true,
                    autoHeight: true,
                    leaveSpaceForNewRows: true,
                    enableCellNavigation: true,
                    enableColumnReorder: false,
                    enableFilter: false,
                    multiSelect: true,
                    enableAsyncPostRender: true,
                    explicitInitialization: true,
                }
            };
            Document.body.table_sg = new SlickInfinite("#" + holderId + " .x-body .x-entries", settings);
            Document.body.table_sg.init();
            //Document.body.table_sg = new Slick.Grid("#" + holderId + " .x-body .x-entries", [], settings.columns, settings.options);
            //Document.body.table_sg.setSelectionModel(new Slick.RowSelectionModel());
            Document.body.table_sg.onCellChange.subscribe(function (e, data) {
                var updatedEntry = data.item;
                var field = settings.columns[data.cell].field;
                var value = updatedEntry[field];
                Document.body.table.entryUpdate(updatedEntry.doc_entry_id, field, value);
            });
            Document.body.table_sg.onRenderFinished.subscribe(function(){
                Document.body.table.setDimensions("#" + holderId + " .x-body .x-entries",DocList.table.columnSummaryWidth);
            });
            $("#" + holderId + " .x-body .x-entries").click(function (e) {
                var row = Document.body.table_sg.getSelectedRows()[0];
                var action = $(e.target).data('action');
                var row_data = Document.body.table_sg.getData()[row];
                if (action) {
                    Document.body.table.actions[ action ] && Document.body.table.actions[action](row_data);
                }
            });
            $(".x-entries .slick-viewport,.x-entries").css('overflow','visible');
        },
        columnsGet:function(){
            var columns_lev1=[
                {id: "queue", name: "№", width: 30, formatter: Document.body.table.formatters.queue},
                {id: "product_code", field: "product_code", name: "Код", width: 80},
                {id: "product_name", field: "product_name", name: "Название", width: 388},
                {id: "product_quantity", field: "product_quantity", name: "Кол-во", width: 70, cssClass: 'slick-align-right', editor: Slick.Editors.Integer},
                {id: "product_unit", field: "product_unit", name: "Ед.", width: 30},
                {id: "product_price", field: "entry_price", name: "Цена", width: 70, cssClass: 'slick-align-right', asyncPostRender: Document.body.table.formatters.priceisloss, editor: Slick.Editors.Float},
                {id: "product_sum", field: "entry_sum", name: "Сумма", width: 80, cssClass: 'slick-align-right', editor: Slick.Editors.Float},
                {id: "row_status", field: "row_status", name: "!", width: 20, formatter: Document.body.table.formatters.tooltip},

            ];
            var columns_adv=[
                {id:"party_label",field:"party_label",name:"Партия",width:200, editor: Slick.Editors.Text},
                {id:"analyse_origin",field:'analyse_origin',name:"Происхождение",width:110}
            ];
            var columns=columns_lev1;
            if( Document.body.table.columnMode==='advanced' ){
                columns=columns.concat(columns_adv);
            }
            return columns;
        },
        render:function(){
            Document.body.table_sg.updateOptions({params:{colmode:Document.body.table.columnMode}});
            var cols=Document.body.table.columnsGet();
            Document.body.table.columnSummaryWidth=0;
            for( var i in cols ){
                Document.body.table.columnSummaryWidth+=cols[i].width+2;
            }
            Document.body.table_sg.setColumnsAndFilters(cols);
        },
        setDimensions:function(query, dw){
            let div = $(query+' .slick-viewport')[0];
            let hasVerticalScrollbar = div.scrollHeight > div.clientHeight;
            let intViewportWidth = window.innerWidth;
            let maxWidth=intViewportWidth-$(query).offset().left-40;
            if(hasVerticalScrollbar){
                dw+=20;
            }
            let width =(Math.min(dw,maxWidth) || 0);
            $(query).css('width', width + 'px');
        },
        actions: {
            err_reserve: function (row_data) {
                location.hash = "#Stock#stock_main_tabs=Резерв&product_code=" + row_data.product_code;
            },
            err_breakeven: function (row_data) {
                Document.body.table.entryUpdate(row_data.doc_entry_id,'entry_price',row_data.breakeven_price);
            }
        },
        formatters: {
            queue: function (row, cell, value, columnDef, dataContext) {
                return row + 1;
            },
            tooltip: function (row, cell, value, columnDef, dataContext) {
                if (value) {
                    var parts = value.split(' ');
                    var cmd = parts.shift();
                    if (cmd) {
                        return '<img src="img/' + cmd + '.png" style="max-width:16px;height:auto; cursor: pointer" title="' + parts.join(' ') + '" data-action="' + cmd + '">';
                    }
                }
                return '';
            },
            priceisloss: function (cellNode, row, dataContext, colDef) {
                if (dataContext.is_loss * 1) {
                    $(cellNode).css('color', 'red');
                }
            }
        },
        entryCreate: function (product_code, product_quantity) {
            if(!Document.doc_id){
                Document.create().then(function(){
                    Document.body.table.entryCreate(product_code, product_quantity);
                });
                return true;
            }
            var url = Document.doc_extension + '/entryCreate';
            var request={
                doc_id: Document.doc_id,
                new_entry_data:JSON.stringify({product_code: product_code, product_quantity: product_quantity})
            };
            $.post(url, request).done( function (ok, status, xhr) {
                Document.reload(["body", "foot"]);
            }).fail(function(xhr){
                App.flash("Строка не изменена");
                App.flash( xhr.responseText );
                if (xhr.responseText.indexOf('product_code_unknown') > -1 && confirm("Добавить новый код " + product_code + " на склад?")) {
                    App.loadWindow('page/stock/product_card', {product_code: product_code});
                }
                Document.reload(["body", "foot"]);
            });
        },
        entryUpdate: function (doc_entry_id, field, value) {
            var url = Document.doc_extension + '/entryUpdate';
            var request={
                doc_entry_id: doc_entry_id,
                new_entry_data:`{"${field}":"${value}"}`
            };
            $.post(url, request).done( function (ok, status, xhr) {
                Document.reload(["body", "foot"]);
            }).fail(function(xhr){
                App.flash("Строка не добавлена");
                App.flash( xhr.responseText );
                Document.reload(["body", "foot"]);
            });
        },
        

    }
    /*
     * @TODO add export table
     */
};
Document.foot = {
    init: function () {
    },
    render: function (footer) {
        footer.total_weight = footer.total_weight || '0.00';
        footer.total_volume = footer.total_volume || '0.00';
        footer.vatless = footer.vatless || '0.00';
        footer.vat = footer.vat || '0.00';
        footer.total = footer.total || '0.00';
        footer.curr_symbol = footer.curr_symbol || '';
        footer.created_by = Document.data.head.created_by;
        footer.modified_by = Document.data.head.modified_by;
        footer.checkout_status = Document.data.head.checkout_status;
        footer.checkout_id = Document.data.head.checkout_id;
        Document.foot.checkout.parse(footer);
        Document.data.foot = footer;
        App.renderTpl(`#${holderId} .x-foot div`, Document.data.foot);
        Document.foot.checkout.setup();
    },
    parse_checkout_statuses: function (footer) {

    },
    go_to_checkout: function () {
        if (Doc.head.props.checkout_status) {
            location.hash = "#Stock#checkout_id=" + Doc.head.props.checkout_id;
        } else {
            this.create_checkout();
        }
    },
    destroy: function () {

    },
    checkout: {
        parse: function (footer) {
            switch (footer.checkout_status) {
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
                    footer.checkout_status_img = 'ProductCard.png';
                    footer.checkout_status_msg = 'Проверить';
                    break;
            }
        },
        setup: function () {

            $("#" + holderId + " .x-foot-checkout").on('click', function () {

            });
        }
    }
};
Document.views = {
    init: function () {
        $('.x-views').click(function (e) {
            var first_class_name = e.target.className.split(" ")[0];
            switch (first_class_name) {
                case 'document_view_settings':
                    Document.views.settings(e.target);
                    event.stopPropagation();
                    return;
                case 'document_views_arrow':
                    Document.views.togglehidden();
                    return;
                case 'document_view_thumb':
                    Document.views.click(e.target);
                    return;
            }
        });
    },
    render: function (views) {
        Document.views.view_list = Document.views.compile(views);
        App.renderTpl('.document_views_tile', {views: Document.views.view_list});
    },
    destroy: function () {

    },
    compile: function (view_list) {
        for (var i in view_list) {
            var view = view_list[i];
            var efield_vals = App.json(view.view_efield_values);
            var efield_labs = App.json(view.view_efield_labels);
            view_list[i].efields = [];
            for (var k in efield_labs) {
                var extra_field={
                        field:k,
                        label:efield_labs[k],
                        value:efield_vals && efield_vals[k]?efield_vals[k]||'':''
                    };
                if( efield_labs[k].label ){
                    extra_field.label=efield_labs[k].label;
                    extra_field.type=efield_labs[k].type;
                }
                view_list[i].efields.push(extra_field);
            }
            view_list[i].view_dmy_date = App.toDmy(view_list[i].view_date);
        }
        return view_list;
    },
    settings: function (node) {
        var i = $(node).attr('data-view-i');
        var view = Document.views.view_list[i];
        if (view.doc_view_id) {
            App.loadWindow('page/mtrade/view_settings', view).progress(function (status,data) {
                if (status === 'close') {// || status==='changed' status==='deleted' || 
                    Document.load(Document.doc_id,["views"]);
                }
                if (status === 'update'){
                    var url = Document.doc_extension + '/viewUpdate';
                    $.post( url,data).done(function(ok){
                        if( ok*1 ){
                            App.flash("Сохранено: "+title);
                        } else {
                            App.flash("Ошибка сохранения "+title);
                        }
                    });
                }
            });
        }
    },
    create: function (view_type_id) {
        if (!view_type_id) {
            return;
        }
        var url = Document.doc_extension + '/viewCreate';
        var doc_id=Document.doc_id;
        $.post(url, {doc_id, view_type_id}).done(function (doc_view_id) {
            if (doc_view_id * 1) {
                Document.views.open(doc_view_id);
                Document.load(Document.doc_id,["views"]);
            }
        }).fail(function(resp){
            if(resp.responseText==='view_type_duplicate'){
                App.flash("Бланк такого типа уже сформирован!");
            }
            if(resp.responseText==='document_uncommited'){
                App.flash('Сначала проведите документ!');
            }
        });
    },
    open: function (doc_view_id) {
        var url = Document.doc_extension + '/viewExport';
        window.open(url+"?out_type=.print&doc_view_id=" + doc_view_id, '_new');
    },
    click: function (node) {
        var doc_view_id = $(node).attr('data-view-id');
        var view_type_id = $(node).attr('data-view-type-id');
        if (doc_view_id * 1) {
            Document.views.open(doc_view_id);
        } else {
            Document.views.create(view_type_id);
        }
    },
    showhidden: false,
    togglehidden: function () {
        this.showhidden = !this.showhidden;
        $('.document_views_arrow').attr('src', 'img/arrow' + (this.showhidden ? 'left' : 'right') + '.png');
        $('.document_view_is_hidden').css('display', (this.showhidden ? 'inline-block' : 'none'));
    }
};

