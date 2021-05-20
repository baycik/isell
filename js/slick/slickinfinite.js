/*global App,Slick*/

(function ($) {
    function SlickInfinite(query, settings) {
	var node=$(query);
	var grid = {};
	var remoteModel;
	var columns = settings.columns;
	var options = settings.options;
	var columnFilters = {};
	var readycallback;

	if (options.enableFilter) {
	    options.showHeaderRow = true;
	}
        if( options.fitWidth ){
            var width = $(query).parent().width();
            $(query).css('width', width);
        }
	remoteModel = new Slick.Data.RemoteModel(options.url,options.params,options.loader);
        remoteModel.setPagesize(options.pagesize||30);
        
	grid = new Slick.Grid(node, remoteModel.data, columns, options);
	grid.setSelectionModel(new Slick.RowSelectionModel());
        grid.onRenderFinished = new Slick.Event();
        
        grid.loadNext=remoteModel.loadNext;
	grid.updateOptions=function(new_options){
	    options=$.extend(true, options, new_options);
	    remoteModel.updateOptions(options.url,options.params,options.loader);
	};
        grid.getOptions=function(){
            return remoteModel.getOptions();
        }
	initLoader();
	if (options.enableFilter) {
	    initFilter();
	}
	if (readycallback) {
	    readycallback(grid);
	}
        if( options.disableLoadScroll ){
            var a=$("<div style='text-align:center'>Загрузить еще</div>").insertAfter( node );
            a.on('click',function(e){
                e.preventDefault();
                remoteModel.loadNext();
            });
            a.css('backgroundColor','rgba(255,255,255,.6)').css("borderRadius","5px").css('padding','5px').css('margin-top','5px').css('cursor','pointer');
        }

	function initFilter() {
	    $('.slick-headerrow-columns .slick-headerrow-column', node).each(function () {
		var column_field = $(this).data('column').field;
		if(column_field){
		    $(this).empty();
		    $("<input data-field='"+column_field+"'>")
			    .data("field", column_field)
			    .appendTo(this);
		}
	    });
	    var filterClock;
	    function do_filter(input_node) {
		var field = $(input_node).data("field");
		if (field !== null) {
		    columnFilters[field] = $.trim($(input_node).val());
		}
		remoteModel.setFilter(columnFilters);
		grid.reload();
	    }
	    $('.slick-headerrow-columns .slick-headerrow-column', node).on("change keyup", ":input", function (e) {
		var input_node = this;
		clearTimeout(filterClock);
		filterClock = setTimeout(function () {
		    do_filter(input_node);
		}, 500);
	    });
	    grid.setFilter=function(filter){
		columnFilters=filter;
		$('.slick-headerrow-columns .slick-headerrow-column input', node).each(function () {
		    var field = $(this).data('field');
		    $(this).val( columnFilters[field]||'' );
		});
		remoteModel.setFilter(columnFilters);
		grid.reload();
	    };
	    grid.getFilter=function(){
		return columnFilters;
	    };
	}
	function initLoader() {
	    grid.rowReload=function(index){
		remoteModel.reloadRow(index);
	    };
            if( options.disableLoadScroll ){
                grid.reload = function () {
                    remoteModel.reloadAll();
                };
            } else {
                grid.reload = function () {
                    var vp = grid.getViewport();
                    remoteModel.reloadData(vp.top, vp.bottom);
                    //grid.scrollRowToTop(0);
                };
                grid.onViewportChanged.subscribe(function (e, args) {
                    var vp = grid.getViewport();
                    remoteModel.ensureData(vp.top, vp.bottom);
                });
            }
	    grid.onSort.subscribe(function (e, args) {
		remoteModel.setSort(args.sortCol.field, args.sortAsc ? 'ASC' : 'DESC');
		var vp = grid.getViewport();
		remoteModel.ensureData(vp.top, vp.bottom);
	    });
	    remoteModel.onDataLoaded.subscribe(function (e, args) {
		for (var i = args.from; i <= args.to; i++) {
		    grid.invalidateRow(i);
		}
		grid.updateRowCount();
		grid.render();
                grid.onRenderFinished.notify(args);
	    });
	    !options.disableAutoload && grid.reload();
	}
	grid.setColumnsAndFilters=function(cols){
	    grid.setColumns(cols);
	    setTimeout(function(){
		initFilter();
	    },0);
	};
	return grid;
    };
    window.SlickInfinite = SlickInfinite;
})(jQuery);

$.fn.slickgrid = function (settings) {
    return new SlickInfinite(this, settings);
};







(function ($) {
    function RemoteModel(url,def_params,loader) {
	var PAGESIZE = 30;
	var total_row_count = 0;
	var data = {length: 0};
	var filter = {};
	var sortcol = null;
	var sortdir = null;
	var req = null; // ajax request
	var requestClock;
	var table_finished=false;
	
	
	function updateOptions(newurl,newdef_params,newloader){
	    url=newurl||url;
	    def_params=newdef_params||def_params;
	    loader=newloader||loader;
	}
        
        function getOptions(){
            return {
                'url':url,
                'params':def_params
            };
        }
        
        function setPagesize( pagesize ){
            PAGESIZE=pagesize;
        }
	
	if( !loader ){
	    loader=function(params,success){
		return $.post(url, $.extend(true, def_params, params), function (resp) {
			    var rows = App.json(resp);
			    success(rows);

			});
	    };
	}
	

	// events
	var onDataLoading = new Slick.Event();
	var onDataLoaded = new Slick.Event();

	function init() {
	}

	function isDataLoaded(from, to) {
	    for (var i = from; i <= to; i++) {
		if (data[i] === undefined) {
		    return false;
		}
	    }
	    return true;
	}

	function clear() {
	    for (var key in data) {
		delete data[key];
	    }
	    data.length = 0;
	    total_row_count = 0;
	    table_finished=false;
	}

        function loadNext( count ){
            count=count?count:PAGESIZE;
            table_finished=false;
            ensureData(total_row_count, total_row_count+count);
        }

        function reloadAll(){
            var _total_row_count=total_row_count;
            clear();
            makeRequest(0, Math.max(_total_row_count,PAGESIZE) );
        }

	function reloadData(from, to) {
            clear();
            ensureData(from, to);
	}

	function setSort(column, dir) {
	    sortcol = column;
	    sortdir = dir;
	    clear();
	}

	function setFilter(flt) {
	    for(var i in flt){
		if(flt[i]==""){
		    delete flt[i];
		}
	    }
	    filter = flt;
	    clear();
	}
        
	function ensureData(from, to) {
	    cancelRequest();
	    from=Math.max(from,0);
	    var rows_to_load = [];
	    for (var i = from; i <= to + 10; i++) {
		if (data[i] === undefined) {
		    rows_to_load.push(i);
		}
	    }
	    var skipped_from = Math.min.apply(null, rows_to_load);
	    var skipped_to = Math.max.apply(null, rows_to_load);
	    if (rows_to_load.length === 0) {//all rows in range are loaded
		onDataLoaded.notify({from: skipped_from, to: skipped_to});
		return;
	    }
	    if( table_finished && skipped_from>=total_row_count ){
		onDataLoaded.notify({from: skipped_from, to: skipped_to});
		return;
	    }
            var limit = skipped_to - skipped_from + 1;
	    if (skipped_to > total_row_count) {//rows are loaded at end of table 
		limit = Math.max(limit, PAGESIZE);
	    }
	    clearTimeout(requestClock);
	    requestClock = setTimeout(function () {
		onDataLoading.notify({from: from, to: to});
		makeRequest(skipped_from, limit);
	    }, 50);
	}
	function cancelRequest() {
	    if (req) {
		req.abort();
		for (var i = req.from; i <= req.limit; i++) {
		    data[i] = undefined;
		}
	    }
	}
	function reloadRow(index){
	    makeRequest(index, 1);
	}

	function makeRequest(from, limit) {
	    var params = {
		offset: from < 0 ? 0 : from,
		limit: limit,
		sortby: sortcol,
		sortdir: sortdir,
		filter: JSON.stringify(filter)
	    };
	    function success(rows){
		var to = from + rows.length;
		for (var i = 0; i < rows.length; i++) {
		    data[from + i] = rows[i];
		    data[from + i].num = from + i;
		}
		total_row_count = Math.max(total_row_count, to);
		data.length = total_row_count;
		if( rows.length<limit ){
		    table_finished=true;
		    data.length+=1;
		} else {
		    data.length+=1;
		}
		req = null;
		onDataLoaded.notify({from: from, to: from + limit});
	    }
	    
	    
	    req = loader(params,success);
	    req.from = from;
	    req.limit = limit;
	}
	init();
	return {
	    // properties
	    "data": data,
	    // methods
	    "clear": clear,
	    "isDataLoaded": isDataLoaded,
	    "ensureData": ensureData,
	    "reloadData": reloadData,
	    "reloadAll": reloadAll,
	    "reloadRow":reloadRow,
	    "setSort": setSort,
	    "setFilter": setFilter,
	    "updateOptions":updateOptions,
            "getOptions":getOptions,
            "loadNext":loadNext,
            "setPagesize":setPagesize,
	    // events
	    "onDataLoading": onDataLoading,
	    "onDataLoaded": onDataLoaded
	};
    }
    // Slick.Data.RemoteModel
    $.extend(true, window, {Slick: {Data: {RemoteModel: RemoteModel}}});
})(jQuery);