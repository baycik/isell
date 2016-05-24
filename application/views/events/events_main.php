<script>
    /*global App*/
EventsJs={
    activeDates:[],
    selectedDay:App.toIso(App.today()),
    init:function(){
	this.activeDatesGet();
    },
    activeDatesGet:function(){
	$.get("Events/activeDatesGet",function(resp){
	    EventsJs.activeDates=App.json(resp);
	    $("#event_calendar").calendar();
	    EventsJs.tile.load();
	});
    },
    show_dialog:function(fvalue){
	fvalue.event_user_id = App.user.props.user_id;
	fvalue.event_date=App.toDmy(EventsJs.selectedDay);
	App.loadWindow('page/events/event', fvalue).progress(function (status, fvalue) {
	    if (status === 'update' || status === 'create' || status === 'delete') {
		if( fvalue && fvalue.event_date ){
		    EventsJs.selectedDay = fvalue.event_date;
		}
		EventsJs.activeDatesGet();
	    }
	});
    },
    calendar:{
	formatter:function (date) {
	    var iso = App.toIso(date);
	    for (var i in EventsJs.activeDates) {
		if ( iso === EventsJs.activeDates[i]['event_date'] ) {
		    return '<div style="border:2px #0f0 solid;border-radius:18px;font-size:14px;">' + date.getDate() + '</div>';
		}
	    }
	    return '<div style="font-size:14px;">' + date.getDate() + '</div>';
	},
	onChange: function(date){
	    EventsJs.selectedDay = App.toIso(date);
	    EventsJs.tile.load();
	}
    },
    tile:{
	event_list:[],
	load:function(){
	    $.get("Events/listFetch/"+EventsJs.selectedDay,function(resp){
		EventsJs.tile.event_list=App.json(resp);
                var event_list_tmp=[];
		var event_label='---';
		for(var i=0;i<EventsJs.tile.event_list.length;i++){
		    if( EventsJs.tile.event_list[i].event_label!==event_label ){
			event_label=EventsJs.tile.event_list[i].event_label||'-';
                        event_list_tmp.push( {header:event_label} );
		    }
                    event_list_tmp.push( EventsJs.tile.event_list[i] );
		}
                EventsJs.tile.event_list=event_list_tmp;
		App.renderTpl('events_tile',{events:EventsJs.tile.event_list});
	    });
	},
	click:function( node ){
	    $(".event_tile_item_row_selected").removeClass("event_tile_item_row_selected");
	    $(node).addClass("event_tile_item_row_selected");
	    //var index=$(node).data('event-index');
	    //EventsJs.show_dialog(EventsJs.tile.event_list[index]);
	},
	create:function(node){
	    var header=$(node).parent().data('header');
	    var fvalue = {
		event_label: header,
		event_date: EventsJs.selectedDay
	    };
	    EventsJs.show_dialog(fvalue);
	},
	edit:function(node){
	    var index=node?$(node).data('event-index'):$(".event_tile_item_row_selected").data('event-index');
	    EventsJs.show_dialog(EventsJs.tile.event_list[index]);
	},
	delete:function(node){
	    var index=node?$(node).data('event-index'):$(".event_tile_item_row_selected").data('event-index');
            if (confirm('Удалить запись?') && EventsJs.tile.event_list[index].event_id) {
                $.post('Events/eventDelete/'+EventsJs.tile.event_list[index].event_id,function(ok){
		    if( ok*1 ){
			App.flash("Запись удалена");
			EventsJs.tile.load();
		    }else {
			App.flash("Запись неудалена");
		    }
                });
            }	    
	},
	move:function(node){
	    var label=$(node).parent().data('header');
	    var index=$(".event_tile_item_row_selected").data('event-index');
	    var event_id=index?EventsJs.tile.event_list[index].event_id:0;
	    App.loadWindow("page/events/move").progress(function(status,newdate,mode){
		if( status==='move' ){
		    $.post("Events/eventMove/"+App.uri(EventsJs.selectedDay,newdate,event_id,label,mode),function(ok){
			if( ok*1 ){
			    App.flash("Запись перенесена");
			    EventsJs.activeDatesGet();
			    $("#event_calendar").calendar('moveTo',new Date(newdate));
			} else {
			    App.flash("Запись не перенесена");
			}
		    });
		}
	    });
	},
	print:function(node){
	    var label=$(node).parent().data('header');
	    EventsJs.selectedDay
	    
	},
	out:function( label, out_type ){
	    var params={
		label:label,
		event_date:EventsJs.selectedDay,
		out_type:out_type
	    };
	    var url='Events/eventViewGet/?'+$.param( params );
	    if( out_type==='.print' ){
		window.open(url,'print_tab');
	    } else {
		location.href=url;
	    }
	}
    }
};    
</script>
<style>
    .event_tile_label{
	text-align: center;
	font-size: 18px;
	margin: 5px;
	margin-bottom: 0px;
	padding: 5px;
    }
    .event_tile_item_row{
	margin:0px 5px 0px 5px;
	background-color: rgba(255,255,255,0.4);
	border-bottom: 1px #999 solid;
    }
    .event_tile_item_row:hover{
	background-color: rgba(255,255,255,0.7);
	cursor: pointer;
    }
    .event_tile_item_row div{
	display: table-cell;
	vertical-align: middle;
	border-left: 1px solid #def;
	padding: 1px;
	height: 25px;
	overflow: hidden;
	text-overflow: ellipsis;
    }
    .event_tile_item_row_selected,.event_tile_item_row_selected:hover{
	margin:-2px 3px -1px 3px;
	border: 2px solid #08f;
	background-color: rgba(255,255,255,0.7);
    }
    .event_tile_header,.event_tile_header:hover{
	background: linear-gradient(0deg, rgba(220,255,255,0.9), rgba(255,255,255,0.6));
	cursor: default;
    }
    .event_tile_header div{
	text-align: center;
	font-weight: bold;
    }
    
    
    .event_tile_item_1critical{
	background-color: rgba(255,0,0,0.4);
    }
    .event_tile_item_2high{
	background-color: rgba(255,255,0,0.4);
    }
    .event_tile_item_3medium{
	
    }
    .event_tile_item_4low{
	background-color: rgba(200,200,200,0.4);	
    }
    .event_tile_item_5future{
	background-color: rgba(150,255,150,0.4);
    }
</style>
<div id="event_calendar" class="easyui-calendar" style="width:220px;height:220px;display: inline-block" data-options="
		 formatter:EventsJs.calendar.formatter,
		 onChange:EventsJs.calendar.onChange"></div>
<div style="display: inline-block;vertical-align: top;background-color: rgba(255,255,255,0.4);width:950px;padding: 10px;">
    <div id="events_tile" class="covert">
	{{if events|empty}}
	<div style="text-align: center">
	    <h1>Нет заданий на этот день</h1>
	    <button onclick="EventsJs.tile.create(this);"><img src="img/edit_add.png"> Добавть задание</button>
	</div>
	{{/if}}
	{{events}}
	    {{if header}}
	    <div style="padding: 5px;padding-top: 20px;">
		<div style="text-align: right;padding-right: 5px;float: right;" data-header="{{header}}">
		    <span class="icon-24 icon-create" title="Добавить" onclick="EventsJs.tile.create(this);"> </span>
		    <span class="icon-24 icon-change" title="Изменить" onclick="EventsJs.tile.edit();"> </span>
		    <span class="icon-24 icon-delete" title="Удалить" onclick="EventsJs.tile.delete();"> </span>
		    <span class="icon-24" style="background-image: url(img/big_rightarrow.png);background-repeat: no-repeat;background-size: 24px 24px;" title="Перенести на другую дату" onclick="EventsJs.tile.move(this);"> </span>
		    <span class="icon-24 icon-reload" title="Обновить" onclick="EventsJs.tile.load();"> </span>
		    <span class="icon-24 icon-print" title="Печать" onclick="EventsJs.tile.out($(this).parent().data('header'),'.print');"> </span>
		</div>
		<span style="font-size:18px;font-weight: bold">
		    {{header}}
		</span>
	    </div>
	    <div class="event_tile_item_row event_tile_header">
		<div style="width:25px">!</div>
		<div style="width:60px">Дата</div>
		<div style="width:150px">Задание</div>
		<div style="width:200px">Место</div>
		<div style="width:150px">Цель</div>
		<div style="width:100px">Контакт</div>
		<div>Комментарий</div>
	    </div>
	    {{else}}
		<div class="event_tile_item_row event_tile_item_{{event_priority}}" data-event-index="{{#}}" onclick="EventsJs.tile.click(this);" ondblclick="EventsJs.tile.edit(this);">
		    <div style="max-width:26px;width:25px;"></div>
		    <div style="max-width:60px;width:60px;text-align: center">{{date_dmy}}</div>
		    <div style="max-width:150px;width:150px">{{event_name}}</div>
		    <div style="max-width:200px;width:200px">{{event_place}}</div>
		    <div style="max-width:150px;width:150px">{{event_target}}</div>
		    <div style="max-width:100px;width:100px">{{event_note}}</div>
		    <div>{{event_descr}}</div>
		</div>	
	    {{/if}}
	{{/events}}
    </div>
</div>
