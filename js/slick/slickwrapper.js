function SlickWrapper(settings){
    this.loaded=false;
    this.id=settings.id;
    this.data = settings.data;
    this.columns=settings.columns;
    this.options=settings.options;
};

SlickWrapper.prototype.init=function(){
    this.grid=new Slick.Grid(this.id, this.data, this.columns, this.options);
    return this.grid;
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