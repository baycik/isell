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
	    if (options.enableSearch) {
		//initSearch();
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
	function initSearch() {
	    node.prepend('<input style="width:100%" placeholder="&#128269; Поиск в таблице..." class="slick-searchinput"/>');
	    searchInput = node.find('input');
	    searchInput.keyup(function (e) {
		if (e.which === 13) {
		    startsearch();
		} else {
		    clearTimeout(searchClock);
		    searchClock = setTimeout(startsearch, 500);
		}
	    });
	    function startsearch() {
		loader.setSearch(searchInput.val());
		var vp = grid.getViewport();
		loader.ensureData(vp.top, vp.bottom);
	    }
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
	// private
	var PAGESIZE = 30;
	var data = {length: 0};
	var searchstr = "";
	var sortcol = null;
	var sortdir = 1;
	var req = null; // ajax request
	var offset=0;
	var requestClock;

	// events
	var onDataLoading = new Slick.Event();
	var onDataLoaded = new Slick.Event();

	function init() {
	}

	function isDataLoaded(from, to) {
	    for (var i = from; i <= to; i++) {
		if (data[i] == undefined || data[i] == null) {
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
	    console.log('ensure',from,to);
	    var skipped_from=from;
	    for(var i=from;i<=to;i++){
		//console.log('check',i,data[i]);
		if( data[i]===undefined ){
		    continue;
		}
		skipped_from=i;
	    }
	    console.log('skipped',skipped_from,to);
	    if( to<=skipped_from ){//all rows in range are loaded
		onDataLoaded.notify({from: from, to: to});
		return;
	    }
	    var limit=Math.max(to-skipped_from,PAGESIZE);
	    console.log('corrected',skipped_from,skipped_from+limit);
	    
	    clearTimeout(requestClock);
	    requestClock=setTimeout(function(){
		onDataLoading.notify({from: from, to: to});
		for (var i = from; i <= limit; i++){
		    data[i] = null;
		}
		makeRequest(skipped_from,limit);
	    },50);
	}
	function cancelRequest(){
	    console.log('cancel');
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
		console.log('loaded',from,to);
		for (var i = 0; i < rows.length; i++) {
		    data[from + i] = rows[i];
		}
		data.length=to+PAGESIZE;
		req = null;
		onDataLoaded.notify({from: from, to: to});
	    });
	    req.from = from;
	    req.limit=limit;
	}

	function onSuccess(resp) {
	    var rows=App.json(resp);
	    var to = offset+rows.length;
	    for (var i = 0; i < rows.length; i++) {
		data[from + i] = rows[i];
	    }
	    last_loaded_row=Math.max(last_loaded_row,to);
	    data.length=last_loaded_row+PAGESIZE;
	    req = null;
	    onDataLoaded.notify({from: from, to: to});
	}
	function ensureData22222222222222(from, to) {
	    cancelRequest();
	    if (from < 0) {
		from = 0;
	    }
	    if (data.length > 0) {
		to = Math.min(to, data.length - 1);
	    }
	    
	    var fromPage = Math.floor(from / PAGESIZE);
	    var toPage = Math.floor(to / PAGESIZE);

	    
	    while (data[fromPage * PAGESIZE] !== undefined && fromPage < toPage){
		fromPage++;
	    }
	    while (data[toPage * PAGESIZE] !== undefined && fromPage < toPage){
		toPage--;
	    }
	    
	    
	    
	    console.log('ensure',from, to, fromPage, toPage);
	    
	    
	    if (fromPage > toPage || ((fromPage === toPage) && data[fromPage * PAGESIZE] !== undefined)) {
		// TODO:  look-ahead
		onDataLoaded.notify({from: from, to: to});
		return;
	    }
	    
	    
	    limit=(((toPage - fromPage) * PAGESIZE) + PAGESIZE);
	    
	    
	    clearTimeout(requestClock);
	    requestClock=setTimeout(function(){
		for (var i = fromPage; i <= toPage; i++){
		    data[i * PAGESIZE] = null; // null indicates a 'requested but not available yet'
		}
		onDataLoading.notify({from: from, to: to});
	    },50);
	    
	    
	    req=makeRequest(from);
	    req.fromPage = fromPage;
	    req.toPage = toPage; 
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