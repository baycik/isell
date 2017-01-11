function SlickWrapper(node,settings){
    this.loaded=false;
    this.node=node;
    this.data = settings.data;
    this.columns=settings.columns;
    this.options=settings.options;
    this.scriptsLoad();
};
$.fn.slickgrid=function(settings){
    return new SlickWrapper(this,settings);
};
SlickWrapper.prototype.scriptsLoad=function(){
    var wrapper=this;
    $("body").append("<div id='slickscriptloader'></div>");
    $("#slickscriptloader").load('js/slick/scriptloader.html', function() {
	wrapper.init();
    });
};

SlickWrapper.prototype.init=function(){
    this.grid=new Slick.Grid(this.node, this.data, this.columns, this.options);
};
SlickWrapper.prototype.initLoader=function(){
    var grid=this.grid;
    var loader = new Slick.Data.RemoteModel();
    grid.onViewportChanged.subscribe(function (e, args) {
	var vp = grid.getViewport();
	loader.ensureData(vp.top, vp.bottom);
    });
    loader.onDataLoading.subscribe(function () {
	if (!loadingIndicator) {
	    loadingIndicator = $("<span class='loading-indicator'><label>Buffering...</label></span>").appendTo(document.body);
	    var $g = $("#myGrid");
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
};