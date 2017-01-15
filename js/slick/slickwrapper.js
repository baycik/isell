/*global App*/

(function ($) {
    function SlickWrapper(node, settings) {
	var grid;
	var loader;
	var loadingIndicator = null;
	var searchInput = null;
	var searchClock;
	var data = settings.data;
	var columns = settings.columns;
	var options = settings.options;
	

	function init() {
	    loader = new Slick.Data.RemoteModel(options.url);
	    grid = new Slick.Grid(node, loader.data, columns, options);
	    grid.setSelectionModel(new Slick.RowSelectionModel());
	    if (options.tools) {
		initTools();
	    }
	    initLoader();
	}
	;
	var urls = [
	    "js/slick/lib/jquery.event.drag-2.3.0.js",
	    "js/slick/slick.core.js",
	    "js/slick/slick.grid.js",
	    "js/slick/plugins/slick.rowselectionmodel.js",
	    'js/slick/lib/jquery.jsonp-2.4.min.js'];
	App.require(urls, init);
	
	function initTools(){
	    var tool_bar=$(options.tools);
	    tool_bar.find('.slick-tool-reload').click(function(){
		alert('haha');
	    });
	    tool_bar.find('.slick-tool-search input').keyup(function(e){
		if (e.which === 13) {
		    startsearch();
		} else {
		    clearTimeout(searchClock);
		    searchClock = setTimeout(startsearch, 500);
		}
	    });
	}
	
	function startsearch() {
	    var filter=$(options.tools).find('.slick-tool-search input').val();
	    loader.setSearch(filter);
	    var vp = grid.getViewport();
	    loader.ensureData(vp.top, vp.bottom);
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
	    loader.onDataLoading.subscribe(function () {
		if (!loadingIndicator) {
		    loadingIndicator = $("<span class='loading-indicator'><label>Buffering...</label></span>").appendTo(document.body);
		    var $g = node;
		    loadingIndicator
			    .css("position", "absolute")
			    .css("top", $g.position().top + $g.height() / 2 - loadingIndicator.height() / 2)
			    .css("left", $g.position().left + $g.width() / 2 - loadingIndicator.width() / 2);
		}
		loadingIndicator.show();
	    });
	    loader.onDataLoaded.subscribe(function (e, args) {
		for (var i = args.from; i <= args.to; i++) {
		    grid.invalidateRow(i);
		}
		grid.updateRowCount();
		grid.render();
		loadingIndicator.fadeOut();
	    });
	    //loader.setSort("score", -1);
	    //grid.setSortColumn("score", false);
	    grid.onViewportChanged.notify();
	}
	;

	return this;
    }
    ;
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
	var PAGESIZE = 30;
	var total_row_count=0;
	var data = {length: 0};
	var searchstr = "";
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
		offset:from,
		limit:limit,
		sortby:sortcol,
		sortdir:((sortdir > 0) ? "ASC" : "DESC"),
		filter:searchstr
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
		onDataLoaded.notify({from: from, to: to});
	    });
	    req.from = from;
	    req.limit=limit;
	}


	function reloadData(from, to) {
	    for (var i = from; i <= to; i++)
		delete data[i];
	    ensureData(from, to);
	}

	function setSort(column, dir) {
	    sortcol = column;
	    sortdir = dir;
	    clear();
	}

	function setSearch(str) {
	    searchstr = str;
	    clear();
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
	    "setSearch": setSearch,
	    // events
	    "onDataLoading": onDataLoading,
	    "onDataLoaded": onDataLoaded
	};
    }
    // Slick.Data.RemoteModel
    $.extend(true, window, {Slick: {Data: {RemoteModel: RemoteModel}}});
})(jQuery);