/* global Mark, encodeURIComponent */
var App = {
    tplcache:{},
    handler:$.Deferred(),
    init: function () {
	App.loadBg();
	App.updaterInit();
        App.chatInit();
	App.onReady && App.onReady();
        App.search.init();
    },
    flash:function (msg, type) {
        if( !msg ){
            return;
        }
	clearTimeout(App.flashClock);
	App.flashClock = setTimeout(function () {
	    $.messager.show({ msg: App.msg, showType: 'slide',width:300,height:150});
	    App.msg = '';
	}, 300);
	App.msg = (App.msg || '') + App.translate(msg) + '<br>';
    },
    translate:function(msg){
	var translated=[];
	var lines=msg.split("\n");
	for(var i in lines){
	    translated.push(App.lang(lines[i]));
	}
	return translated.join("\n");
    },
    lang:function(key){
	return App.vocab[key] || key;
    },
    vocab:{},
    setTitle:function( title ){
        this.title = title||this.title||'';
        var title_data={
            acomp_name:App.acomp?App.acomp.label:'',
            pcomp_name:App.pcomp?App.pcomp.label:'',
            module_name:this.title
        };
        App.renderTpl("module_title",title_data);
        document.title = this.title + ': ' +  (App.acomp?App.acomp.company_name:'');
    },
    initTabs: function (tab_id) {
        var selected_default=App.state[tab_id] || App.store(tab_id) || 0;
	$('#' + tab_id).tabs({
	    selected: selected_default,
	    onSelect: function (title, index) {
		var href = $('#' + tab_id).tabs('getTab', title).panel('options').href;
		var id = href.replace(/\//g, '_').replace('.html', '');
		App[id] && App[id].focus && App[id].focus();
		App.store(tab_id, title);
                App.state[tab_id]=title;
                location="#"+App.module.current+"#"+$.param(App.state).replace(/\+/g, '%20');
	    },
	    onLoad:function(panel){
		var href = panel.panel('options').href;
		if( href ){
		    var id = href.replace(/\//g, '_').replace('.html', '');
		    if( App[id] ){
			App.require(App[id].require,function(){
			    App.initModule(id,{inline:true},null);
			});
		    }
		}
	    }
	});
        App.Topic('hashChange').subscribe(function(hash_object){
            if( hash_object[tab_id] ){
                $('#' + tab_id).tabs('select',decodeURI(hash_object[tab_id]));
            }
        });
    },
    isellTabs:function( query, tabdata ){
        let tabs_tpl=
            `<div class="isell-tabs-buttons">
                {{.}}<div data-index="{{#}}" {{if class}}class="{{class}}"{{/if}}>{{title}}</div>{{/.}}
            </div>
            <div class="isell-tabs-holders"></div>`;
        let clear_query=query.replace(/\W/g,'');
        let isellTabs={
            selectedIndex:0,
            init(){
                isellTabs.render();
                setTimeout(function(){
                    isellTabs.initEvents();
                },0);
            },
            initEvents(){
                $(query+" .isell-tabs-buttons").click(function(e){
                    let index=$(e.target).data('index');
                    isellTabs.select(index);
                });
                App.Topic('hashChange').subscribe(function(state){
                    let title=state[clear_query];
                    isellTabs.selectTitle(title);
                });
            },
            select(index){
                if( index>-1 ){
                    $(query+" .isell-tabs-buttons div").removeClass('isell-tabs-active');
                    $(query+` .isell-tabs-buttons div[data-index='${index}']`).addClass('isell-tabs-active');
                    isellTabs.loadContent( index );
                    App.state[clear_query]=tabdata[index].title;
                    location="#"+App.module.current+"#"+$.param(App.state).replace(/\+/g, '%20');
                    isellTabs.selectedIndex=index;
                }
            },
            selectTitle(title){
                for(var i=0;i<tabdata.length;i++){
                    if(tabdata[i].title===title){
                        isellTabs.select(i);
                        return;
                    }
                }
                isellTabs.select(0);
            },
            render(){
                var rendered=Mark.up(tabs_tpl,tabdata);
                $(query).html(rendered).addClass('isell-tabs');
            },
            loadContent( index ){
                $(query+" .isell-tabs-holders>div").css('left','-10000px');
                let holder=$(query+` .isell-tabs-holders div[data-index='${index}']`);
                if( holder.length<1 ){
                    $(query+" .isell-tabs-holders").append(`<div data-index="${index}">Загрузка ...</div>`);
                    holder=$(query+` .isell-tabs-holders div[data-index='${index}']`);
                    $.get(tabdata[index].url).done(function(html){
                        holder.html(html);
                        var id = tabdata[index].url.replace(/\//g, '_').replace(/.html/g, '');
                        App[id] && App[id].init && App[id].init();
                    });
                }
                holder.css('left',0);
            }
        };
        isellTabs.init();
        return isellTabs;
    },
    initModule: function(id,data,handler){
	App[id].data = data;
	App[id].handler = handler;
	App[id].node = $("#" + id);
	App[id].init ? App[id].init(data, handler) : '';
	if( !App[id].parsed ){
	    $.parser.parse("#" + id);//for easy ui
	    App[id].parsed=true;
	}
	App[id].initAfter ? App[id].initAfter(data, handler) : '';
	handler&&handler.notify('inited',App[id]);
    },
    loadModule: function ( path, data, id_new, id_search, id_replace ) {
	var id = id_new?id_new:path.replace(/\//g, '_');
	var handler = $.Deferred();
	if( App[id] ){
	    App.initModule(id,data,handler);
	} else {
	    App[id] = {};
	    $.get(path + '.html',function(html){
		html=id_search?html.replace(id_search,id_replace):html;
		App.setHTML("#"+id,html);
		if(App[id].require){
		    App.require(App[id].require,function(){
			App.initModule(id,data,handler);
		    });
		} else {
		    App.initModule(id,data,handler);
		}
	    });   
	}
	return handler.promise();	
    },
    loadWindow: function (path, data) {
	var id = path.replace(/\//g, '_');
	if (!$('#' + id).length) {
	    $('#appWindowContainer').append('<div id="' + id + '" class="app_window"></div>');
	}
	return App.loadModule(path, data || {});
    },
    sequence:[],
    seqWait:false,
    seqNext:function(){
	if( this.seqWait || this.sequence.length===0 ){
	    return;
	}
	this.seqSend( this.sequence.shift() );
    },
    seqSend:function( rq ){
	this.wait=true;
	$[rq.type].apply(null,rq.args).always(function(){
	    App.seqWait=false;
	    App.seqNext();
	});
    },
    get:function(){
	this.sequence.push({type:'get',args:arguments});
	this.seqNext();
    },
    post:function(){
	this.sequence.push({type:'post',args:arguments});
	this.seqNext();
    },
    loadedScripts:[],
    require:function(urls,callback){
	if(!urls){
	    callback&&callback();
	    return false;
	}
	var filesLeft=urls.length;
	function ok(){
	    if( --filesLeft<=0){
		callback&&callback();
	    }
	}
	for(var i in urls){
	    var url=urls[i];
	    if( Array.isArray(url) ){
		var original_callback=callback;
		callback=function(){
		    App.require(url,original_callback);
		};
		ok();
	    } else
	    if( url.indexOf('.css')>-1 ){
		$('head').append( $('<link rel="stylesheet" type="text/css" />').attr('href', url) );
		ok();
	    } else
	    if( App.loadedScripts.indexOf(url)>-1 ){
		ok();
	    } else {
		App.loadedScripts.push(url);
		$.ajax({url: url,dataType: "script",cache: true,async:true}).done(function(a,b,c){
		    ok();
		}).fail(function(a,b,c){
                    ok();
                    console.log('failed',b,c);
                });
	    }
	}
    },
    include:function(urls,callback){
	var filesLeft=urls.length;
	function ok(){
	    if( --filesLeft<=0){
		callback&&callback();
	    }
	}
	for(var i in urls){
	    var url=urls[i];
	    if( App.loadedScripts.indexOf(url)>-1 ){
		ok();
	    }
	    App.loadedScripts.push(url);
	    var script = document.createElement('script');
	    script.src = url;
	    script.onload = ok;
	    document.head.appendChild(script); //or something of the likes
	}	
    },
    getHolderId:function(){
        return document.scripts[document.scripts.length - 1].parentNode.id;
    },
    barcode:{
        eanCheckDigit: function(s){
            let result = 0;
            let i = 1;
            for (let counter = s.length-1; counter >=0; counter--){
                result = result + parseInt(s.charAt(counter)) * (1+(2*(i % 2)));
                i++;
            }
            return (10 - (result % 10)) % 10;
        },
        toEAN13:function(code){
            if( ( String(code).length===13 || String(code).length===14 ) && $.isNumeric(code) ){
                let barcode=String(code);
                let boxlevel=0;
                if( barcode.length===13 ){
                    let pcode=barcode.substring(0,12);
                    if( barcode !== (pcode+App.barcode.eanCheckDigit(pcode)) ){
                        return null;
                    }
                }
                if( barcode.length===14 ){
                    let pcode=barcode.substring(1,13);
                    boxlevel=barcode.substring(0,1);
                    barcode=pcode+App.barcode.eanCheckDigit(pcode);
                }
                return {ean13:barcode,boxlevel:boxlevel};
            }
            return null;
        }
    },
    search:{
        init:function(){
            $('.ui.search').search({
                apiSettings: {
                  url: 'Search/comboSearch/?q={query}'
                },
                onSelect:function(){
                    setTimeout(function(){
                        $('.ui.search').search('set value');
                    },100);
                },
                type: 'category'
            });
            $('body').keypress(function(e){
                let is_printable_char=String(e.key).match(/^\S/);
                let is_body_node=(e.target.nodeName==='BODY');
                if( is_printable_char && is_body_node ){
                    let current_value=$('.ui.search').search('get value');
                    $('.ui.search').search('set value',current_value+e.key);
                    $('.ui.search input').focus();
                }
            });
        },
        findPcomp:function(pcomp_id){
            App.user.pcompSelect({company_id:pcomp_id});
        },
        findDoc:function(doc_id,active_company_id,passive_company_id){
            App.user.acompSelect({company_id:active_company_id}).then(function(){
                App.user.pcompSelect({company_id:passive_company_id}).then(function(){
                    location.hash="#Trade#trade_main_tabs=Документы&doc_id="+doc_id;
                });
            });
        },
        findProd:function(product_id,product_code){
            location.hash="#Stock#stock_main_tabs=Остатки товара&product_code="+product_code;
        }
    }
};

$(App.init);
//////////////////////////////////////////////////
//UTILS
//////////////////////////////////////////////////
App.json=function( text ){
    try{
	return text===''?null:JSON.parse(text);
    }
    catch(e){
	console.log('isell-app-json-err: '+e+text);
	return null;
    }
};
App.uri = function () {
    var args = Array.prototype.slice.call(arguments);
    return args.map(function(text){
	return encodeURIComponent(String(text).replace(/\n/g," "));
    }).join('/');
};
App.toIso = function (dmY) {
    if (dmY instanceof Date) {
	return dmY.getFullYear() + '-' + String("0" + (dmY.getMonth() + 1)).slice(-2) + '-' + String("0" + dmY.getDate()).slice(-2);
    }
    return dmY ? dmY.replace(/[^\d]/g, '').replace(/^[\d]{4}(\d\d)$/, "20$1").replace(/^(\d\d)(\d\d)(\d\d\d\d)$/, "$3-$2-$1") : null;
};
App.toDmy = function (iso) {
    if (iso instanceof Date) {
	return String("0" + iso.getDate()).slice(-2) + '.' + String("0" + (iso.getMonth() + 1)).slice(-2) + '.' + iso.getFullYear();
    }
    return iso?iso.replace(/^(\d\d\d\d)-(\d\d)-(\d\d)T?(\d\d:\d\d:\d\d)?Z?$/, "$3.$2.$1"):null;
};
App.toDmyt = function (iso) {
    var date;
    if ( iso instanceof Date ) {
	date=iso;
    } else {
	date=new Date(iso);
    }
    if( date instanceof Date ){
	 return String("0" + date.getDate()).slice(-2) + '.' + String("0" + (date.getMonth() + 1)).slice(-2) + '.' + date.getFullYear()+' '+ String("0" + date.getHours()).slice(-2)+':'+ String("0" + date.getMinutes()).slice(-2);
    }
    return null;
};
App.today = function () {
    return App.toDmy(new Date());
};
App.calc=function( expression ){
    try{
        return eval(expression.toString().replace(/,/g,'.').match( /[\(\d\.\)\*\/\+-]*/ ).toString()) || 0;
    } catch(e){
        return 0;
    }
};
App.formatNum = function (num, mode) {
    if ( !num && num !== 0 || mode === 'clear' && num * 1 === 0) {
	return '';
    }
    return Number.parseFloat(num).toLocaleString('en-US',{ style: 'decimal',minimumFractionDigits:2 }).replace(/,/g,' ');
};
App.formElements=function( fquery ){
    return $(fquery + " input," + fquery + " textarea," + fquery + " select");
};
App.setupForm = function ( fquery, fvalue, mode ) {
    if (!fquery) {
	return false;
    }
    fvalue=fvalue||{};
    App.formElements(fquery).each(function (i, element) {
	var value=fvalue[element.name] || ( mode==='use_inp_values'&&$(element).val() ?$(element).val():'');//Support for document header
	$(element).val(value);
	if ($(element).attr('type') === 'hidden') {
	    return true;
	}
	if ($(element).attr('title') && !$(element).attr('data-skip')) {
	    $(element).wrap('<div class="inp_group"></div>');
	    $(element).before("<label><b>" + element.title + ": </b></label>");
	}
	if ($(element).attr('type') === 'checkbox' && fvalue[element.name] * 1) {
	    $(element).attr('checked', 'checked');
	}
	$(element).attr('data-skip', 1);
    });
    return App.formElements(fquery);
};
App.collectForm = function (fquery) {
    var fvalue = {};
    App.formElements(fquery).each(function (i, element) {
	if (element.name) {
	    fvalue[element.name] = App.val(element);
	}
    });
    return fvalue;
};
App.val = function (element) {
    if ($(element).attr('type') === 'checkbox') {
	return $(element).is(':checked') ? 1 : 0;
    }
    return $(element).val();
};
App.store=function(key,value){
    if(value===undefined){
	return localStorage.getItem(key);
    }
    localStorage.setItem(key,value);
};
App.cookie = function (cname, cvalue) {
    if (cvalue === undefined) {
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for (var i = 0; i < ca.length; i++) {
	    var c = ca[i];
	    while (c.charAt(0) === ' ')
		c = c.substring(1);
	    if (c.indexOf(name) === 0)
		return c.substring(name.length, c.length);
	}
	return "";
    }
    else {
	var d = new Date();
	d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));
	var expires = "expires=" + d.toUTCString();
	document.cookie = cname + "=" + cvalue + "; " + expires;
    }
};
App.getUrlParent=function(){
    return location.href.split('/')[3];
};
App.loadBg = function () {
    if (localStorage.getItem('isell_bg'+(App.acomp?App.acomp.company_id:0) )) {
	$("body").css('background', 'url("' + localStorage.getItem('isell_bg'+(App.acomp?App.acomp.company_id:0) ) + '") repeat fixed center top');
	$("body").css('background-size', '100%');
    } else {
	$("body").css('background', '');	
    }
};
App.setBg = function () {
    App.loadWindow('page/dialog/background_setter');
};
App.datagrid = {
    tooltip: function (value, row, callback) {
	if( !value ){
	    return '';
	}
	var parts = value.split(' ');
	var cmd = parts.shift();
	if (cmd){
	    var handler='';
	    var style='max-width:16px;height:auto';
	    if( callback ){
		handler='onclick="setTimeout(function(){'+callback+'},0)"';
		style='max-width:16px;height:auto;cursor:pointer';
	    }
	    return '<img src="img/' + cmd + '.png" style="'+style+'" title="' + parts.join(' ') + '" '+handler+'>';
	}
	else{
	    return '';
	}
    }
};
//App.renderTpl=function( id, data, mode ){
//    var query='#'+id;
//    if( id.match(/\W/) ){
//        query=id;
//    }
//    if( !$(query).length ){
//        console.log('not found: '+query);
//        return;
//    }
//    if( !this.tplcache[query] || mode==='nocache' ){
//        this.tplcache[query]=$(query).html().replace(/&gt;/,'>').replace(/<!--/g,'').replace(/-->/g,'');
//    }
//    $(query).html( Mark.up(App.tplcache[query], data) );
//    $(query).removeClass('covert');
//};
App.renderTpl=function (query, data, mode) {
    $node=$(query);
    if( $node.length===0 ){
        $node=$(`#${query}`);
    }
    if( $node.length===0 ){
        return;
    }
    if (!this.tplcache[query] || mode === 'nocache') {
        this.tplcache[query] = $node.html().replace(/&gt;/g, '>').replace(/<!--/g, '').replace(/-->/g, '');
    }
    $node.html(Mark.up(App.tplcache[query], data));
    $node.removeClass('covert');
};
App.setHTML=function( query, html ){
    $(query).html(html);
    $(query).find("script").each(function() { eval(this.text);} );
};
App.updaterCheck=function ( skip_release_check ){ 
    var handler=$.Deferred();
    $.get('Maintain/getCurrentVersionStamp',function(resp){
	var info=App.json(resp);
	$.getJSON('https://api.github.com/repos/baycik/isell/commits?since='+info.stamp+'&sha='+info.branch+'&callback=?',function(resp){
	    try{
		var is_release=false;
		var list=[];
		for(var i in resp.data){
		    var commit=resp.data[i].commit;
		    list.push({name:commit.committer.name,date:App.toDmy(commit.committer.date),message:commit.message});
		    if( commit.message.indexOf('release')>-1 || commit.message.indexOf('bugfix')>-1 ){
			is_release=true;
		    }
		}
		App.renderTpl('sync_panel',{updates:list,is_release:is_release});
                handler.notify('updatesChecked',list);
		if( !skip_release_check && is_release && App.flash("Поступили важные обновления!") ){
		    App.loadWindow('page/dialog/updater',{updates:list});
		}
	    } catch (e){
		console.log( e );
	    }
	});
    });
    return handler;
};
App.updaterInit=function(){
    if( App.user.signedIn ){
	App.renderTpl('sync_panel',{updates:[]});
	$('#sync_panel').click(function(){
	    App.updaterCheck( false ).progress(function(status,list){
		App.loadWindow('page/dialog/updater',{updates:list});
	    });
	});
	setTimeout(App.updaterCheck,1000*1);
    } else {
	setTimeout(App.updaterInit,1000*6);
    }
};
App.chatCheck=function(){
    if( App.user.signedIn ){
        var mode='';
        if( location.href.match(/(localhost)|(127.0.0.1)/) && !localStorage.getItem('executeTasks') ){
            mode='skip_tasks';//do not execute tasks on developing
            App.renderTpl('chat_panel',{count:0});
            return false;
        }
	$.get('Chat/checkNew',{mode:mode},function(resp){
	    var count=resp*1;
            if( App.chatPrevCount !== count ){
                App.renderTpl('chat_panel',{count:count});
                App.chatPrevCount = count;
            }
	    if( count ){
		App.flash("У вас новое сообщение!");
                new Audio('img/icq_message_sound.mp3').play();
	    }
	});
    }
    clearTimeout(App.chatclock);
    App.chatclock=setTimeout(App.chatCheck,1000*30);
};
App.chatInit=function(){
    setTimeout(App.chatCheck,1000*4);
};
//////////////////////////////////////////////////
//AJAX SETUP
//////////////////////////////////////////////////
$.ajaxSetup({
    cache: true
});
$(document).ajaxComplete(function (event, xhr, settings) {
    $("#app_busy").hide();
    if( xhr.statusText==='error' ){
        
    }
    else if( settings.crossDomain===false && settings.dataType!=='script' ){
	switch(xhr.status){
	    case 401:
		App.user.loginFormShow();
		break;
	    case 500:
		console.log('iSellServer error: ',xhr.responseText);
	}
	var msg = xhr.getResponseHeader('X-isell-msg');
	if (msg) {
	    var msg = decodeURIComponent(msg.replace(/\+/g, " "));
	    App.flash(msg);
	}
    }
});
$(document).ajaxSend(function () {
    $("#app_busy").show();
});
$.fn.pagination.defaults.layout=['list','sep','first','prev','sep','links','sep','next','sep'];
$.fn.pagination.defaults.displayMsg="{from}-{to}/{total}";

$.fn.datebox.defaults.formatter = function (date) {
    return App.toDmy(date);
};
$.fn.datebox.defaults.parser = function (input) {
    if (input instanceof Date) {
	return input;
    }
//    if( !input ){
//	return new Date();
//    }
    var parts=input.replace(/[^\d]/g, '').replace(/^(\d\d)(\d\d)(\d\d\d\d)$/, "$2/$1/$3").substr(0,10);
    var date=Date.parse(parts);
    if( parts.length===10 && !isNaN(Date.parse(parts)) ){
	var date=new Date(Date.parse(parts));
	$(this).datebox('setValue',date);
	return date;
    }
};


Mark.pipes.format = function (str) {
    return App.formatNum(str);
};


App.user = {
    props: {},
    signedIn:false,
    getData: function () {
        App.get("User/getUserData", function (resp) {
            App.user.setProps( App.json(resp) );
        });
    },
    setProps:function( userProps ){
        App.user.props=userProps;
        App.user.setActiveCompany(userProps.acomp,'notify_init');
        App.user.setPassiveCompany(userProps.pcomp,'notify_init');
        if( userProps && userProps.user_level>0 ){
            App.renderTpl('div_user_panel', App.user.props || '');
            this.signedIn=true;
            this.loginFormHide();
            App.module.init();
        } else {
            this.signedIn=false;
            this.loginFormShow();
        }
    },
    getLevel:function(){
        return App.user.props && App.user.props.user_level>0?App.user.props.user_level:0;
    },
    signIn: function () {
        var user_login=$("#user_login").val();
        var user_pass=$("#user_pass").val();
        App.post("User/SignIn",{login:user_login,pass:user_pass,mode:'get_user_data'},function(resp){
            var props=App.json(resp);
            if( props ){
                $("#SeqDialogMsg").html("");
                App.user.setProps( props );
            } else {
                $("#SeqDialogMsg").html("Логин или пароль не верны!").css('color','red').css('font-size','14px');
            }
        });
    },
    signOut: function () {
        App.get("User/SignOut");
        this.setProps({});
    },
    edit:function(){
        App.post("User/userFetch",function(resp){
            var props=App.json(resp);
            App.user.promptEditor(props);
        });
    },
    promptEditor:function( user ){
        App.loadWindow('page/dialog/user_edit',user).progress(function(status,user_data){
            if( status==='submit' ){
                App.post("User/save",user_data,function(ok){
                    if( ok*1 ){
                        App.user.getData();
                        App.flash("Свойства пользователя сохранены");
                    } else {
                        if( ok==='LAST_ADMIN' ){
                            alert("Должен остаться хотя бы один администратор.");
                        }
                        App.flash("Свойства пользователя не изменены");
                    }
                });
            }
        });
    },
    loginFormShow: function () {
        $("#loginScreen,#loginOverlay").show();
        $("#user_login").focus();
    },
    loginFormHide: function () {
        $("#loginScreen,#loginOverlay").hide();
    },
    acompSwitch:function(){
        return App.get("Company/switchActiveCompany",function(resp){
            var company=App.json(resp);
            App.user.setActiveCompany(company);
        });
    },
    pcompSelectionDialog:function(){
        return App.loadWindow('page/company/tree',{}).progress(function(status,comp){
            if( status==='select' ){
                App.user.pcompSelect(comp);
            }
            if( status==='reset' ){
                App.user.pcompSelect({});
            }
        });
    },
    pcompSelect: function ( company ) {
        var company_id=company.company_id||0;
        if( App.pcomp && App.pcomp.company_id==company_id ){
            return $.Deferred().resolve();
        }
        return $.post('Company/selectPassiveCompany/' + company_id, function (xhr) {
            App.user.setPassiveCompany(App.json(xhr));
        });
    },
    setPassiveCompany:function( company, mode ){
        var old_pcomp_id=App.pcomp?App.pcomp.company_id:0;
        App.pcomp=company;
        if( company ){
            if( company.company_id===old_pcomp_id ){
                App.handler.notify('passiveCompanyReloaded',company);
                App.Topic('passiveCompanyReloaded').publish(company);
                return;
            }
            App.Topic('passiveCompanySelected').publish(company);
            if( mode==='notify_init' ){
                App.handler.notify('passiveCompanyInited',company);
                return;
            }
            App.handler.notify('passiveCompanySelected',company);
        } else {
            App.handler.notify('passiveCompanyReset');
            App.Topic('passiveCompanyReset').publish();
            App.Topic('passiveCompanySelected').unsubscribe();
        }
    },
    acompSelect: function ( company ) {
        var company_id=company.company_id||0;
        if( App.acomp && App.acomp.company_id==company_id ){
            return $.Deferred().resolve();
        }
        return $.post('Company/selectActiveCompany/' + company_id, function (xhr) {
            App.user.setActiveCompany(App.json(xhr));
        });
    },
    setActiveCompany:function( company, mode ){
        var old_acomp_id=App.acomp?App.acomp.company_id:0;
        App.acomp=company;
        if( company ){
            if( company.company_id===old_acomp_id ){
                App.handler.notify('activeCompanyReloaded',company);
                return;
            }
            setTimeout(function(){
                App.loadBg();
            },0);
            App.Topic('activeCompanySelected').publish(company);
            if( mode==='notify_init' ){
                App.handler.notify('activeCompanyInited',company);
                return;
            }
            App.handler.notify('activeCompanySelected',company);

        } else {
            App.handler.notify('activeCompanyReset');
            App.Topic('activeCompanyReset').publish();
        }
    }
};
App.topics={};
App.Topic = function (id) {
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
};

App.module={
    init: function () {
        $(window).bind('hashchange', function (e) {
            App.module.hashchanged(location.hash.substring(1));
        });
        App.module.list=[];
        for(var i in App.user.props.module_list){
            App.module.list.push(App.user.props.module_list[i]);
        }
        if( !location.hash ){
            location.hash="#"+App.module.list[0].name;
        }
        this.render_icons();
    },
    render_icons:function(){
        App.renderTpl('div_module_list', {module_list:App.module.list});
        App.module.hashchanged(location.hash.substring(1));
    },
    hashchanged:function(hash){
        var url_chunks = hash.split("#");
        App.url_query = url_chunks[1];
        App.module.load(url_chunks[0]);
        App.module.parseState(App.url_query);
        this.load(url_chunks[0]);
        App.Topic('hashChange').publish(App.state,url_chunks[0]);
    },
    parseState: function (text) {
        var newstate = {};
        if (text) {
            var pairs = text.split('&');
            for (var i in pairs) {
                var keyval = pairs[i].split('=');
                newstate[keyval[0]] = decodeURIComponent(keyval[1]);
            }
        }
        App.state = newstate;
    },
    load:function(name){
        if( this.current === name ){
            return false;
        }
        //$("#holder"+this.current).hide();
        $("#holder"+this.current).addClass('hidden');
        this.current=name;
        var holder=$("#holder"+this.current);
        if( !holder.length ){
            $("#ModuleContainer").append('<div id="holder' + this.current + '"></div>');
            holder=$("#holder"+this.current);
        }
        //holder.show();
        holder.removeClass('hidden');
        this.loadHTML(holder);
        this.findTitle();
        this.selectButton();
    },
    loadHTML:function(holder){
        var url="page/"+this.current+"/"+this.current+"_main.html";
        if( !holder.html() ){
            holder.load(url,function(){
                App.module.initHTML();
            });
        }	
    },
    initHTML:function(){
        setTimeout(function(){
            App.module.parseHTML();
            $("#holder"+this.current).find("script").each(function() { eval(this.text);} );
            window[App.module.current + 'Js'] && window[App.module.current + 'Js'].init && window[App.module.current + 'Js'].init(); 		    
        },0);
    },
    parseHTML:function(){
        $.parser.parse("#holder"+App.module.current);//for easy ui   
    },
    findTitle:function(){		    
        App.setTitle(App.user.props.module_list[App.module.current].label);
    },
    selectButton:function(){
        $(".ModuleButtonSelected").removeClass("ModuleButtonSelected");
        $("#"+this.current+"Button").addClass("ModuleButtonSelected");
    }
};
App.onReady = function () {
    App.user.getData();
    App.handler.progress(function(status){
        if( status==='passiveCompanySelected' || status==='passiveCompanyReset' || status==='activeCompanySelected' ){
            App.setTitle();
        }
    });
};


















$.extend($.fn.datagrid.defaults, {
    ctrlSelect:true,
    onLoadSuccess: function (jq) {
	var grid = $(this);
	if( grid.data('initedEvents') ){
	    return;
	}
	grid.data('initedEvents',true);
	grid.datagrid('getPanel').panel('panel').attr('tabindex',0).on('keydown', function (e) {
	    var selected = grid.datagrid('getSelected');
	    var index = grid.datagrid('getRowIndex', selected);
	    switch (e.keyCode) {
		case 38:	// up
		    e.preventDefault();
		    grid.datagrid('unselectAll');
		    if (selected && index>0){
			grid.datagrid('selectRow', index-1);
		    } else {
			var rows = grid.datagrid('getRows');
			grid.datagrid('selectRow', rows.length - 1);
		    }
		    break;
		case 40:	// down
		    e.preventDefault();
		    grid.datagrid('unselectAll');
		    var rows = grid.datagrid('getRows');
		    if (selected && rows.length-1>index){
			grid.datagrid('selectRow', index+1);
		    } else {
			grid.datagrid('selectRow', 0);
		    }
		    break;
		case 13:
		    grid.trigger('rowEdit');
		    break;
		    
	    }
	});
	if( !grid.datagrid('options').singleSelect ){
	    grid.datagrid('getPanel').find('table tr').on('click',function(e){
		var currentIndex=$(this).attr('datagrid-row-index');
		if( grid.lastClickedRow!==undefined && e.shiftKey ){
		    var min=Math.min(currentIndex,grid.lastClickedRow);
		    var max=Math.max(currentIndex,grid.lastClickedRow);
		    $(this).parent().each(function(){
			var index=$(this).data('datagrid-row-index');
			if( index>min && index>max ){
			    $(this).addClass("datagrid-row-checked datagrid-row-selected");
			}
		    });
		    /*
		    for( var i=Math.min(currentIndex,grid.lastClickedRow);i<Math.max(currentIndex,grid.lastClickedRow);i++ ){
			//grid.datagrid('selectRow', i);
			$(this).parent().find("")
		    }*/
		}
		grid.lastClickedRow=currentIndex;
	    });
	}
    }
});
