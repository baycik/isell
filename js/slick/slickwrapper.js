/*global App,Slick*/

(function ($) {
    function SlickWrapper(node, settings) {
	var grid;
	var loader;
	var columns = settings.columns;
	var options = settings.options;
	var columnFilters={};
	

	function init() {
	    if( options.enableFilter ){
		options.showHeaderRow=true;
	    }
	    loader = new Slick.Data.RemoteModel(options.url);
	    grid = new Slick.Grid(node, loader.data, columns, options);
	    grid.setSelectionModel(new Slick.RowSelectionModel());
	    initLoader();
	    if( options.enableFilter ){
		initFilter();
	    }
	}
	var urls = [
	    "js/slick/lib/jquery.event.drag-2.3.0.js",
	    "js/slick/slick.core.js",
	    "js/slick/slick.grid.js",
	    "js/slick/plugins/slick.rowselectionmodel.js"];
	App.require(urls, init);

	function initFilter(){
	    $('.slick-headerrow-columns .slick-headerrow-column',node).each(function(){
		var column_field=$(this).data('column').field;
		$(this).empty();
		$("<input type='text'>")
		    .data("field",column_field)
		    .appendTo(this);
	    });
	    var filterClock;
	    function do_filter(input_node){
		var field = $(input_node).data("field");
		if (field !== null) {
		    columnFilters[field] = $.trim($(input_node).val());
		}
		loader.setFilter(columnFilters);
		reload();
	    }
	    $('.slick-headerrow-columns .slick-headerrow-column',node).on("change keyup", ":input", function (e) {
		var input_node=this;
		clearTimeout(filterClock);
		filterClock=setTimeout(function(){do_filter(input_node);},500);
	    });
	}
	function reload(){
	    var vp = grid.getViewport();
	    loader.reloadData(vp.top, vp.bottom);
	}
	function initLoader() {
	    grid.onViewportChanged.subscribe(function (e, args) {
		var vp = grid.getViewport();
		loader.ensureData(vp.top, vp.bottom);
	    });
	    grid.onSort.subscribe(function (e, args) {
		loader.setSort(args.sortCol.field, args.sortAsc ? 1 : -1);
		var vp = grid.getViewport();
		loader.ensureData(vp.top, vp.bottom);
	    });
	    loader.onDataLoaded.subscribe(function (e, args) {
		for (var i = args.from; i <= args.to; i++) {
		    grid.invalidateRow(i);
		}
		grid.updateRowCount();
		grid.render();
	    });
	    reload();
	}

	return {
	    "reload":reload
	};
    };
    window.SlickWrapper = SlickWrapper;
})(jQuery);

$.fn.slickgrid = function (settings) {
    return new SlickWrapper(this, settings);
};















(function ($) {
    /***
     * A sample AJAX data store implementation.
     * Right now, it's hooked up to load search results from Octopart, but can
     * easily be extended to support any JSONP-compatible backend that accepts paging parameters.
     */
    function RemoteModel(url) {
	var PAGESIZE = 15;
	var total_row_count=0;
	var data = {length: 0};
	var filter = {};
	var sortcol = null;
	var sortdir = 1;
	var req = null; // ajax request
	var requestClock;

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
	    total_row_count=0;
	}

	function reloadData(from, to) {
	    for (var i = from; i <= to; i++){
		delete data[i];
	    }
	    ensureData(from, to);
	}

	function setSort(column, dir) {
	    sortcol = column;
	    sortdir = dir;
	    clear();
	}

	function setFilter(flt) {
	    filter = flt;
	    clear();
	}

	function ensureData(from, to) {
	    cancelRequest();
	    var rows_to_load=[];
	    for(var i=from;i<=to+10;i++){
		if( data[i]===undefined ){
		    rows_to_load.push(i);
		}
	    }
	    var skipped_from=Math.min.apply(null, rows_to_load);
	    var skipped_to=Math.max.apply(null, rows_to_load);
	    if( rows_to_load.length===0 ){//all rows in range are loaded
		onDataLoaded.notify({from: skipped_from, to: skipped_to});
		return;
	    }
	    var limit=skipped_to-skipped_from+1;
	    if(skipped_to>total_row_count){//rows are loaded at end of table 
		limit=Math.max(limit,PAGESIZE);
	    }
	    clearTimeout(requestClock);
	    requestClock=setTimeout(function(){
		onDataLoading.notify({from: from, to: to});
		makeRequest(skipped_from,limit);
	    },50);
	}
	function cancelRequest(){
	    if (req) {
		req.abort();
		for (var i = req.from; i <= req.limit; i++){
		    data[i] = undefined;
		}
	    }
	}
	
	function makeRequest(from,limit){
	    var params={
		offset:from<0?0:from,
		limit:limit,
		sortby:sortcol,
		sortdir:((sortdir > 0) ? "ASC" : "DESC"),
		filter:JSON.stringify(filter)
	    };
	    req=$.get(url,params,function(resp){
		var rows=App.json(resp);
		var to = from+rows.length;
		for (var i = 0; i < rows.length; i++) {
		    data[from + i] = rows[i];
		    data[from + i].num = from + i;
		}
		total_row_count=Math.max(total_row_count,to);
		data.length=total_row_count+PAGESIZE;
		req = null;
		onDataLoaded.notify({from: from, to: from+limit});
	    });
	    req.from = from;
	    req.limit=limit;
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
	    // events
	    "onDataLoading": onDataLoading,
	    "onDataLoaded": onDataLoaded
	};
    }
    // Slick.Data.RemoteModel
    $.extend(true, window, {Slick: {Data: {RemoteModel: RemoteModel}}});
})(jQuery);