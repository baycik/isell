<script type="text/javascript">
DataJs={
    filterEnabled:false,
    tableKey:[],
    loadTable:function(){
	var row=$("#data_table_list").datagrid('getSelected');
	if( row ){
	    $.get("Data/tableStructure/"+row.table_name,function(resp){
		var struct=App.json(resp);
		DataJs.processStruct(struct);
		DataJs.initTable(row.table_name);
	    });
	    $("#data_table_holder").show();
	    $("#data_table_name").html(row.table_title);
	    row.editable*1?$("#data_grid_editables").show():$("#data_grid_editables").hide();
	}
    },
    processStruct:function(struct){
	this.tableWidth=10;
    	for(var i in struct){
	    struct[i].title=struct[i].name=struct[i].field=struct[i].Field;
	    if( struct[i].Comment ){
		struct[i].title=struct[i].name=struct[i].Comment;
	    }
	    if( struct[i].Type.indexOf('varchar')>-1 ){
		var chars=parseInt(struct[i].Type.split('(')[1]);
		if( chars<50 ){
		    struct[i].width=100;
		} else {
		    struct[i].width=200;
		}
	    }
	    if( struct[i].Type.indexOf('double')>-1 || struct[i].Type.indexOf('decimal')>-1 || struct[i].Type.indexOf('int')>-1 ){
		struct[i].width=70;
	    }
	    this.tableWidth+=struct[i].width;
	    if( struct[i].Key==="PRI" ){
		DataJs.tableKey.push(struct[i].Field);
	    }
	}
	DataJs.currentStruct=struct;	
    },
    initTable:function(table_name){
	var grid_id='data_grid_'+table_name;
	$('#data_grid').html('<div id="'+grid_id+'"></div>');
	DataJs.currentGrid=$('#'+grid_id);
	DataJs.currentGrid.datagrid({
	    width:this.tableWidth<900?this.tableWidth:900,
	    remoteFilter:true,
	    pagination:true,
	    pageSize:30,
	    pageList:[30,60,120,600],
	    columns:[this.currentStruct],
	    onDblClickCell:DataJs.list.edit,
	    onSelect:function(){
		DataJs.currentGrid.datagrid('resize');
	    },
	    onBeforeLoad:function(){
		//DataJs.filterEnabled?'':DataJs.currentGrid.datagrid('removeFilterRule');
	    },
	    loader:function( param, success, error ){
		DataJs.list.currPage=param.page;
		DataJs.list.filterRules=param.filterRules;
		DataJs.list.rows=param.rows;
		$.get("Data/tableData/"+table_name, param,function( xhr ){
		    var resp=App.json(xhr);
		    success(resp);
		});
	    }
	});
	DataJs.currentGrid.on('rowEdit',function(){
	    DataJs.list.editClick();
	});
	DataJs.currentGrid.datagrid('enableFilter');
	this.currentTable=table_name;
    },
    list:{
	create:function(){
	    this.openUnivEditor();
	},
	openUnivEditor:function(row){
	    var rowKey=DataJs.list.getRowKey(row);
	    var create=!row?1:0;
	    App.loadWindow("page/data/universal_editor",{fvalue:row,fstruct:DataJs.currentStruct}).progress(function(status,createdRowKey,data,title){
		if( status==='change' ){
		    rowKey=createdRowKey||rowKey;
		    $.post("Data/tableRowCreateUpdate/"+DataJs.currentTable,{rowKey:JSON.stringify(rowKey),data:JSON.stringify(data),create:create},function(ok){
			if( ok>0 ){
			    App.flash("Сохранено: "+title);
			    DataJs.currentGrid.datagrid('reload');
			    create=0;
			} else {
			    App.flash("Не сохранено!");
			}
		    });
		}
	    });
	},
	editClick:function(){
	    var row=DataJs.currentGrid.datagrid('getSelected');
	    if( row ){
		DataJs.list.openUnivEditor(row);
	    }	    
	},
	edit:function(index,field,value){
	    DataJs.currentGrid.datagrid('unselectAll');
	    DataJs.currentGrid.datagrid('selectRow',index);
	    var row=DataJs.currentGrid.datagrid('getSelected');
	    if( row ){
		row.focus=field;
		DataJs.list.openUnivEditor(row);
	    }
	},
	getRowKey:function(row){
	    if( !row ){
		return null;
	    }
	    var key={};
	    for(var i in DataJs.tableKey){
		key[DataJs.tableKey[i]]=row[DataJs.tableKey[i]];
	    }
	    return key;
	},
	delete:function(){
	    if(!confirm("Удалить выделенные строки?")){
		return false;
	    }
	    var rowKey=[];
	    var rows=DataJs.currentGrid.datagrid('getSelections');
	    for(var i in rows){
		rowKey.push(DataJs.list.getRowKey(rows[i]));
	    }
	    $.post("Data/tableRowsDelete/"+DataJs.currentTable,{rowKey:JSON.stringify(rowKey)},function(ok){
		if(ok>0){
		    DataJs.currentGrid.datagrid('reload');
		    App.flash("Удалено строк: "+ok);
		} else {
		    App.flash("Удаление не удалось!");
		}
	    });
	},
	toggleFilter: function () {
	    if (DataJs.filterEnabled) {
		DataJs.currentGrid.datagrid('disableFilter');
		DataJs.filterEnabled = false;
	    }
	    else {
		DataJs.currentGrid.datagrid('enableFilter');
		DataJs.filterEnabled = true;
	    }
	},
	import:function(){
	    App.loadWindow('page/dialog/importer',{label:'данные',fields_to_import:DataJs.currentStruct}).progress(function(status,fvalue,Importer){
		if( status==='submit' ){
		    $.post("Data/import/"+DataJs.currentTable,fvalue,function(ok){
			App.flash("Добавлено строк "+ok);
			DataJs.currentGrid.datagrid('reload');
			Importer.reload();
		    });
		}
	    });
	},
	out:function(out_type){
	    var params={
		page:DataJs.list.currPage,
		filterRules:DataJs.list.filterRules,
		out_type:out_type
	    };
	    var url="Data/tableViewGet/"+DataJs.currentTable+"?"+$.param( params );
	    if( out_type==='.print' ){
		window.open(url+"&rows="+DataJs.list.rows,'print_tab');
	    } else {
		location.href=url+"&rows=10000";
	    }
	}
    }
};
</script>
<table style="width:100%">
    <tr>
	<td style="vertical-align: top;width: 205px">
	    <table class="easyui-datagrid" id="data_table_list" data-options="
		   url:'Data/permitedTableList',
		   singleSelect:true,
		   onSelect:function(){
			$('#data_table_list').datagrid('resize');
			DataJs.loadTable();
		    }
		   ">
		<thead>
		    <tr>
			<th  data-options="field:'table_title',width:200">Доступные таблицы</th>
		    </tr>
		</thead>
	    </table>
	</td>
	<td style="vertical-align: top;text-align: center;display: none;" id="data_table_holder">
	    <div style="float: left;font-weight: bold;padding-top: 8px;">
		<img src="img/table16.png" style="width:16px;height: 16px;"> <span id="data_table_name"></span>
	    </div>
	    <div style="text-align: right;padding-right: 5px;">
		<span id="data_grid_editables">
		    <span class="icon-24 icon-create" title="Добавить" onclick="DataJs.list.create();"> </span>
		    <span class="icon-24 icon-change" title="Изменить" onclick="DataJs.list.editClick();"> </span>
		    <span class="icon-24 icon-delete" title="Удалить" onclick="DataJs.list.delete();"> </span>
		    <span class="icon-24 icon-import" title="Импорт" onclick="DataJs.list.import();"> </span>
		</span>
		<span class="icon-24 icon-tablefilter" title="Фильтр таблицы" onclick="DataJs.list.toggleFilter()"> </span>
		<span class="icon-24 icon-refresh" title="Обновить" onclick="DataJs.currentGrid.datagrid('reload');"> </span>
		<span class="icon-24" style="background-image: url(img/file_download.png);background-repeat: no-repeat" title="Скачать таблицу" onclick="DataJs.list.out('.xlsx');"> </span>
		<span class="icon-24 icon-print" title="Печать" onclick="DataJs.list.out('.print')"> </span>
	    </div>
	    <div id="data_grid"></div>
	</td>
    </tr>
</table>

