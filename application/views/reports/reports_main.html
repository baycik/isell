<script>
    /*global App*/
    ReportsJs={
	init:function(){
	    this.tile.load();
	},
	tile:{
	    report_list:[],
	    load:function(){
		$.get("ReportManager/listFetch/",function(resp){
		    ReportsJs.tile.report_list=App.json(resp);
		    var group_name='';
		    for(var i=0;i<ReportsJs.tile.report_list.length;i++){
			if( ReportsJs.tile.report_list[i].group_name!==group_name ){
			    group_name=ReportsJs.tile.report_list[i].group_name||'';
			    ReportsJs.tile.report_list.splice(i,0,{header:group_name});
			}
		    }
		    App.renderTpl('reports_tile',{reports:ReportsJs.tile.report_list});
		    var last_index=App.store('last_report_i')||1;
		    ReportsJs.report.load(ReportsJs.tile.report_list[last_index]);
		});
	    },
	    click:function(node){
		var index=$(node).data('report-index');
		App.store('last_report_i',index);
		ReportsJs.report.load(ReportsJs.tile.report_list[index]);
	    }
	},
	report:{
	    current_report_info:{},
	    load:function(rpt_info){
		if( rpt_info ){
		    this.current_report_info=rpt_info;
		    this.formLoad();
		    App.renderTpl('report_info',this.current_report_info);
		}
	    },
	    formLoad:function(){
		var report_id=this.current_report_info.report_id;
		$.get("ReportManager/formGet/"+report_id,function(resp){
		    $("#report_form_wrapper").html(resp);
		    ReportsJs.report.formRender();
		});
	    },
	    formRender:function(){
		var report_id=this.current_report_info.report_id;
		var standart_data=this.formGetInitials();
		App.renderTpl("report_form_wrapper",standart_data,'nocache');
		App.setupForm("#report_form_wrapper form",{},'use_inp_values');
		$("#report_form_wrapper form").attr('action',"ReportManager/formSubmit/"+report_id);
		$("#report_form_wrapper form").attr('target','report_viewport');
		$("#report_form_wrapper form").attr('method','post');
		$("#report_form_wrapper form").submit(function(){
		    $('#report_throbber').show();
		});
		$.parser.parse("#report_form_wrapper");//for easy ui
		$("#report_viewport").height(0);
	    },
	    formGetInitials:function(){
		var now=new Date();
		return {
		    today:App.today(),
		    first_day:App.toDmy(new Date(now.getFullYear(), now.getMonth(), 1))
		};
	    }
	}
    };
</script>
<style>
    .left_label{
	padding: 3px;
	margin-bottom: 5px;
	border: 1px solid #999;
	border-right: none;
	background: linear-gradient(90deg,rgba(255,255,255,1),rgba(255,255,255,0.5));
    }
    .left_label:hover{
	background: linear-gradient(90deg,rgba(255,255,200,1),rgba(255,255,255,0.5));;
	-border-color: #afa;
	cursor: pointer;
    }
    .left_label_selected,.left_label_selected:hover{
	background: #FF9;
    }
    #reports_tile{
	width:210px;
	float:left;
	border-right: 1px solid #ccc;
	padding-right: 2px;
    }
    #reports_tile .left_label{
	min-height: 30px;
	width:200px;
	float:left;
	border: 1px solid #999;
	margin: 2px;
    }
    #report_info{
	padding: 2px;
	margin: 2px;
	background-color: rgba(255,255,255,0.4);
    }
    #report_form_wrapper{
	border-top:1px solid #fff;
	margin-top: 4px;
    }
    #report_form_wrapper .inp_group b{
	width:120px;
    }
    #report_throbber img{
        position: absolute;
        z-index:10;
        top: 20px;
        left: 50%;
    }
</style>
</style>
<div id="reports_tile" class="covert">
    {{reports}}
	{{if header}}
	<div><b>{{header}}</b></div>
	{{else}}
	    <div class="left_label" data-report-index="{{#}}" onclick="ReportsJs.tile.click(this);">
		<img src="img/report24.png"> {{title}}
	    </div>	
	{{/if}}
    {{/reports}}
</div>
<div style="width:1000px;float:left;overflow: auto">
    <div id="report_info" class="covert">
	<div id="report_throbber" style="display: none;position: relative;background-color: rgba(0,255,0,0.4);" >
	    <img src="img/throbber_1.gif">
	</div>
	<img src="img/reportbig.png" style="float:left">
	<div style="float: right">
	    <b>Автор:</b> {{author}}<br>
	    <b>Версия:</b> {{version}}<br>
	    {{if uri}}<b>Сайт:</b> <a href="{{uri}}" target="_blank">{{uri}}</a><br>{{/if}}
	</div>
	<div>
	    <h2>{{title}}</h2>
	    {{description}}
	</div>
	<div id="report_form_wrapper"></div>
    </div>
    
    <div style="margin:2px;">
	<iframe id="report_viewport" name="report_viewport" onload="this.style.height=this.contentDocument.body.scrollHeight +'px';$('#report_throbber').hide()" style="border:none;width: 100%;background: none transparent"  allowtransparency="true"></iframe>
    </div>
    
</div>