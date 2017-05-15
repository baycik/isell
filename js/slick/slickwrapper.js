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
	remoteModel = new Slick.Data.RemoteModel(options.url,options.params,options.loader);
	grid = new Slick.Grid(node, remoteModel.data, columns, options);
	grid.setSelectionModel(new Slick.RowSelectionModel());
	grid.reload = function () {
	    grid.scrollRowToTop(0);
	    var vp = grid.getViewport();
	    remoteModel.reloadData(vp.top, vp.bottom);
	};
	grid.updateOptions=function(new_options){
	    options=$.extend(true, options, new_options);
	    remoteModel.updateOptions(options.url,options.params,options.loader);
	};
	initLoader();
	if (options.enableFilter) {
	    initFilter();
	}
	if (readycallback) {
	    readycallback(grid);
	}

	function initFilter() {
	    $('.slick-headerrow-columns .slick-headerrow-column', node).each(function () {
		var column_field = $(this).data('column').field;
		$(this).empty();
		$("<input data-field='"+column_field+"'>")
			.data("field", column_field)
			.appendTo(this);
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
	    grid.onViewportChanged.subscribe(function (e, args) {
		var vp = grid.getViewport();
		remoteModel.ensureData(vp.top, vp.bottom);
	    });
	    grid.onSort.subscribe(function (e, args) {
		remoteModel.setSort(args.sortCol.field, args.sortAsc ? 1 : -1);
		var vp = grid.getViewport();
		remoteModel.ensureData(vp.top, vp.bottom);
	    });
	    remoteModel.onDataLoaded.subscribe(function (e, args) {
		for (var i = args.from; i <= args.to; i++) {
		    grid.invalidateRow(i);
		}
		grid.updateRowCount();
		grid.render();
	    });
	    grid.reload();
	}
	return grid;
    };
    window.SlickInfinite = SlickInfinite;
})(jQuery);

$.fn.slickgrid = function (settings) {
    return new SlickInfinite(this, settings);
};







(function ($) {
    function RemoteModel(url,def_params,loader) {
	var PAGESIZE = 15;
	var total_row_count = 0;
	var data = {length: 0};
	var filter = {};
	var sortcol = null;
	var sortdir = 1;
	var req = null; // ajax request
	var requestClock;
	var table_finished=false;
	
	
	function updateOptions(newurl,newdef_params,newloader){
	    url=newurl||url;
	    def_params=newdef_params||def_params;
	    loader=newloader||loader;
	}
	
	if( !loader ){
	    loader=function(params,success){
		return $.get(url, $.extend(true, def_params, params), function (resp) {
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
	    if( table_finished ){
		return;
	    }
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

	function makeRequest(from, limit) {
	    var params = {
		offset: from < 0 ? 0 : from,
		limit: limit,
		sortby: sortcol,
		sortdir: ((sortdir > 0) ? "ASC" : "DESC"),
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
	    "setSort": setSort,
	    "setFilter": setFilter,
	    "updateOptions":updateOptions,
	    // events
	    "onDataLoading": onDataLoading,
	    "onDataLoaded": onDataLoaded
	};
    }
    // Slick.Data.RemoteModel
    $.extend(true, window, {Slick: {Data: {RemoteModel: RemoteModel}}});
})(jQuery);