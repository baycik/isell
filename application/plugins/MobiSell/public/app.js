App = {
    state: {},
    url_file: '',
    init: function () {
        App.sidebar.init();
        App.holder.init();
        App.user.init();
        //App.user.signInPopup();
        if (localStorage.getItem('pcomp_id')) {
            App.pcomp = {company_id: localStorage.getItem('pcomp_id')};
        }
    },
    holder: {
        init: function () {
            $(window).bind('hashchange', function (e) {
                App.holder.hashchanged(location.hash.substring(1));
            });
            App.holder.hashchanged(location.hash.substring(1));
        },
        hashchanged: function (url) {
            url = url || 'home.html';
            if (url == 'home.html') {
                $('.mobisell_backbutton').hide();
            } else {
                $('.mobisell_backbutton').show();
            }
            var url_chunks = url.split("#");

            App.url_query = url_chunks[1];
            App.parseState(App.url_query);
            if (url_chunks[0] && App.url_file != url_chunks[0]) {
                this.unload();
                App.url_file = url_chunks[0];
                this.load(url);
            }
            App.Topic('hashChange').publish(App.state);
            App.sidebar.hide();
        },
        unload: function () {
            var holder_id = App.url_file.split('.')[0];
            App[holder_id] && App[holder_id].blur && App[holder_id].blur();

        },
        load: function (url) {
            var holder_id = App.url_file.split('.')[0];
            if (!$("#" + holder_id).length) {
                $("#main_container").append("<div id='" + holder_id + "'></div>");
                $("#" + holder_id).load('view/?path=' + App.url_file, function () {
                    App[holder_id] && App[holder_id].init && App[holder_id].init();
                    //App[holder_id] && App[holder_id].focus && App[holder_id].focus();           
                });
            }
            $("#main_container>div").hide();
            $("#" + holder_id).show();
            App[holder_id] && App[holder_id].focus && App[holder_id].focus();
        }
    },
    sidebar: {
        init: function () {
            $("#toggle").click(function () {
                $(".ui.sidebar").sidebar('setting', 'transition', 'overlay').sidebar('toggle');
            });
            $("#backbutton").click(function () {
                history.back();
            });
        },
        hide: function () {
            $(".ui.sidebar").sidebar('hide');
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
    store: function (key, object) {
        localStorage.setItem(key, JSON.stringify(object));
    },
    restore: function (key) {
        return localStorage.getItem(key);
    },
    toIso: function (dmy) {
        if (!dmy) {
            return null;
        }
        var chunks = dmy.split('.');
        return chunks[2] + '-' + chunks[1] + '-' + chunks[0];
    },
    renderTpl: function (id, data, mode) {
        if( $('#' + id).length==0 ){
            return;
        }
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
        if (!msg) {
            return;
        }
        $("#status").html(msg).show();
        clearTimeout(App.flashClock);
        App.flashClock = setTimeout(function () {
            $("#status").hide();
        }, 1500);
    },
    alert: function (msg) {
        $("#index_alert_popup .content").html(msg);
        $("#index_alert_popup").modal("show");
        $("#index_confirm_popup .approve").focus();
        //$("#index_alert_popup button").c
    },
    confirm: function (msg) {
        var def = $.Deferred();
        $("#index_confirm_popup .content").html(msg);
        $("#index_confirm_popup").modal({
            onApprove: function () {
                def.resolve();
            },
            onDeny: function () {
                def.reject();
            }
        }).modal("show");
        $("#index_confirm_popup .approve").focus();
        return def.promise();
    },
    prompt: function (msg, value) {
        var def = $.Deferred();
        $("#index_prompt_popup .header").html(msg);
        $("#index_prompt_popup input").val(value || '');
        $("#index_prompt_popup").modal({
            onApprove: function () {
                def.resolve($("#index_prompt_popup input").val());
            },
            onDeny: function () {
                def.reject(null);
            }
        }).modal("show");
        $("#index_prompt_popup .approve").focus();
        return def.promise();
    },
    title: function (msg) {
        $("#title").html(msg);
    },
    user: {
        props: {},
        acomp: {},
        pcomp: {},
        init: function () {
            this.restoreCompanies();
            App.user.props = JSON.parse(localStorage.getItem('user_props'));
            if( !App.user.props ){
                App.user.getData();
            }
        },
        getData: function () {
            $.post("./userPropsGet", function (resp) {
                App.user.props = App.json(resp);
                if (App.user.props) {
                    App.renderTpl("user_info", App.user.props);
                    localStorage.setItem('user_props', JSON.stringify(App.user.props));
                }
            }).fail(function (jqxhr, settings, ex) {
                console.log(jqxhr, settings, ex);
                App.user.signInPopup();
            });
        },
        signOut: function () {
            $.post("../User/SignOut", function (resp) {
                location.reload();
            });
        },
        signIn: function () {
            var login = $("input[name=user_login]").val();
            var pass = $("input[name=user_pass]").val();
            $('#signInModal .primary').addClass('loading');
            $.post("../User/SignIn", {login: login, pass: pass}).done(function (ok) {
                $('#signInModal').modal('hide');
                location.reload();
                $('#signInModal .loading').removeClass('loading');
            }).fail(function () {
                $('#signInModal .loading').removeClass('loading');
            });
        },
        signInPopup: function () {
            $('#signInModal').modal('show');
        },
        setPassiveCompany: function (company) {
            App.user.pcomp = company;
            localStorage.setItem('pcomp', JSON.stringify(App.user.pcomp));
            App.Topic('passiveCompanySelected').publish(company);
        },
        setActiveCompany: function (company) {
            App.user.acomp = company;
            localStorage.setItem('acomp', JSON.stringify(App.user.acomp));
            App.Topic('activeCompanySelected').publish(company);
        },
        restoreCompanies: function () {
            var pcomp=JSON.parse(localStorage.getItem('pcomp')) || {};
            var acomp=JSON.parse(localStorage.getItem('acomp')) || {};
            App.user.pcompSelect(pcomp);
            App.user.acompSelect(acomp);
            
            App.user.pcomp = pcomp;
            App.user.acomp = acomp;
        },
        pcompSelect: function (company) {
            var company_id = company.company_id || 0;
            if (App.pcomp && App.pcomp.company_id === company_id) {
                return;
            }
            return $.post('../Company/selectPassiveCompany/' + company_id, function (xhr) {
                App.user.setPassiveCompany(App.json(xhr));
            });
        },
        acompSelect: function (company) {
            var company_id = company.company_id || 0;
            if (App.acomp && App.acomp.company_id === company_id) {
                return;
            }
            return $.post('../Company/selectActiveCompany/' + company_id, function (xhr) {
                App.user.setActiveCompany(App.json(xhr));
            });
        }
    },
    topics: [],
    Topic: function (id) {
        var callbacks, topic = id && App.topics[ id ];
        if (!topic) {
            callbacks = jQuery.Callbacks("memory");
            topic = {
                publish: callbacks.fire,
                subscribe: callbacks.add,
                unsubscribe: callbacks.remove
            };
            if (id) {
                App.topics[ id ] = topic;
            }
        }
        return topic;
    },
    clearCache: function () {
        navigator.serviceWorker.controller && navigator.serviceWorker.controller.postMessage('clear_cache');
        console.log("Cache cleared");
        location.reload();
    },
    retryConnection: function () {
        $('#offline_popup').modal('hide');
        if (navigator.onLine) {
            console.log();
            location.reload();
        } else {
            $('#offline_popup').modal('show');
        }
    },
    speech:{
        synth:window.speechSynthesis,
        say:function( text ){
            App.speech.hush();
            var utterThis = new SpeechSynthesisUtterance(text);
            utterThis.pitch = 1;
            utterThis.rate = 2;
            App.speech.synth.speak(utterThis);
        },
        hush:function(){
            App.speech.synth.cancel();
        }
    }
};
$(App.init);

$(document).ajaxComplete(function (event, xhr, settings) {
    var msg = xhr.getResponseHeader('X-isell-msg');
    if (msg) {
        var msg = decodeURIComponent(msg.replace(/\+/g, " "));
        App.flash(msg);
    }
});

Mark.pipes.format = function (num) {
    return Number.parseFloat(num).toLocaleString(
            'en-US',
            {style: 'decimal',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
            .replace(/,/g, ' ');
};

if (localStorage.getItem("disableSW") != 1 && ('serviceWorker' in navigator)) {
    navigator.serviceWorker.register('../MobiSell/sw.js', {scope: '../MobiSell/'})
            .then(function (reg) {
                //console.log('serviceWorker Registration succeeded. Scope is ' + reg.scope);
            }).catch(function (error) {
        console.log('serviceWorker Registration failed with ' + error);
    });

    navigator.serviceWorker.addEventListener('message', function (event) {
        var msg = event.data;
        if (msg.status == '401') {
            App.user.signInPopup();
        }
        if (msg.status == '408') {
            $("#offline_popup").modal('show');
        }
        console.log(event.data);
    });

    $.post('./version', function (current_version) {
        var stored_version = localStorage.getItem("stored_version");
        if (current_version != stored_version) {
            localStorage.setItem("stored_version", current_version);
            App.clearCache();
        }
    });
}