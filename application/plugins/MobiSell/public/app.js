App = {
    state: {},
    init: function () {
	App.sidebar.init();
	App.holder.init();
	//App.user.getData();
    },
    holder: {
	init: function () {
	    $(window).bind('hashchange', function (e) {
		App.holder.load(location.hash.substring(1));
	    });
	    if (location.hash) {
		App.holder.load(location.hash.substring(1));
	    }
	},
	load: function (url) {
	    var url_chunks = url.split("#");
	    App.url_file = url_chunks[0];
	    App.url_query = url_chunks[1];
	    App.parseState(App.url_query);
	    $("#main_container").load(App.url_file);
	    $(".ui.sidebar").sidebar('hide');
	}
    },
    sidebar: {
	init: function () {
	    $("#toggle").click(function () {
		$(".ui.sidebar").sidebar('toggle');
	    });
	}
    },
    tplcache: {},
    json: function (text) {
	try {
	    return text === '' ? null : JSON.parse(text);
	} catch (e) {
	    console.log('isell-app-json-err: ' + e + text);
	    return null;
	}
    },
    toIso: function (dmy) {
	if (!dmy) {
	    return null;
	}
	var chunks = dmy.split('.');
	return chunks[2] + '-' + chunks[1] + '-' + chunks[0];
    },
    renderTpl: function (id, data, mode) {
	if (!this.tplcache[id] || mode === 'nocache') {
	    this.tplcache[id] = $('#' + id).html().replace(/&gt;/g, '>').replace(/<!--/g, '').replace(/-->/g, '');
	}
	$('#' + id).html(Mark.up(App.tplcache[id], data));
	$('#' + id).removeClass('covert');
    },
    parseState: function (text) {
	var newstate = {};
	if (text) {
	    var pairs = text.split('&');
	    for (var i in pairs) {
		var keyval = pairs[i].split('=');
		newstate[keyval[0]] = keyval[1];
	    }
	}
	App.state = newstate;
    },
    flash: function (msg) {
	$("#status").html(msg).show();
	clearTimeout(App.flashClock);
	App.flashClock = setTimeout(function () {
	    $("#status").hide();
	}, 1500);
    },
    user: {
	props: {},
	getData: function () {
	    $.get("../User/userFetch", function (resp) {
		App.user.props = App.json(resp);
		if (App.user.props) {
		    App.renderTpl("user_info", App.user.props);
		}
	    });
	},
	signOut: function () {
	    $.get("../User/SignOut", function (resp) {
		location.reload();
	    });
	}
    }
};
$(App.init);