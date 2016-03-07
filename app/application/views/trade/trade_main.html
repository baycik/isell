<script type="text/javascript">
    /* global App */
    App.pcomp={};
    TradeJs = {
        init: function () {
	    this.ctreeInlineInit();
            //setTimeout(function(){TradeJs.initTabsOnce()},3000);
	},
        initTabsOnce:function(){
            if( this.tabsInited ){
                return;
            }
            this.tabsInited=true;
            App.initTabs('trade_main_tabs');
	    App.handler.progress(function(status){
		if( status==='passiveCompanySelected' ){
		    TradeJs.showTabs(true);
		}
	    });
        },
	showTabs:function( show ){
	    if( show ){
		$("#trade_main_tabs_info").hide();
		$("#trade_main_tabs_content").css('visibility','visible');		
	    } else {
		$("#trade_main_tabs_info").show();
                $("#trade_main_tabs_content").css('visibility','hidden');
	    }
	    TradeJs.toggleView(true);
	},
        selectPassiveCompany: function ( company ) {
            if ( company.company_id && company.company_id!==App.pcomp.company_id ) {
                $.post('Company/selectPassiveCompany/' + company.company_id, function (xhr) {
		    App.user.setPassiveCompany(App.json(xhr));
                });
            }
        },
	ctreeInlineInit:function(){
	    App.loadModule('page/company/tree',{inline:true,clickselect:true},'CtreeInline',/(Ctree|page_company_tree)([^\w]|_)/g,"CtreeInline$2").progress(function(status,company){
                if( status==='select' ){
		    TradeJs.showTabs(true);
                    TradeJs.selectPassiveCompany(company);
                }
                if( status==='treeLoaded' ){
                    TradeJs.initTabsOnce();
                }
                if( status==='deleted' && company==='company' ){
                    TradeJs.showTabs(false);
                    App.pcomp={};
                }
	    });
	},
	toggleView:function( hide_doc_list_full ){
	    if( App.page_trade_document_list_full || hide_doc_list_full ){
		$("#trade_main_show_full_btn").html('Показать все накладные');
		$("#trade_main_tabs_holder").show();
		TradeJs.destroyDocListFull();
	    } else {
		$("#trade_main_show_full_btn").html('Скрыть все накладные');
		$("#trade_main_tabs_holder").hide();
		App.loadModule('page/trade/document_list_full');
	    }
	},
	destroyDocListFull:function(){
	    $("#page_trade_document_list_full").html('');
	    delete App.page_trade_document_list_full;
	}
    };
</script>
<table style="border-spacing: 0px;border-collapse: separate;">
    <tr>
        <td style="vertical-align: top;padding: 0px;">
	    <div class="transp60" style="margin-right: 1px;min-width:180px;" id="CtreeInline"></div>
	    <button id="trade_main_show_full_btn" style="width:100%" onclick="TradeJs.toggleView()">Показать все накладные</button>
        </td>
        <td style="vertical-align: top;padding: 0px;">
	    <div id="page_trade_document_list_full"></div>
	    <div id="trade_main_tabs_holder">
		<div id="trade_main_tabs_info">
		    <h1 style="text-shadow: 0px 0px 2px #fff;color:#333"><<< <i>Выберите клиента для начала работы</i></h1>
		</div>
		<div id="trade_main_tabs_content" style="visibility: hidden">
		    <div id="trade_main_tabs" class="slim-tab">
			<div title="Документы" href="page/trade/document_list.html" style="min-height: 500px;"></div>
			<div title="Взаиморасчет" href="page/trade/payments.html" style="min-height: 500px;"></div>
			<div title="Настройки" href="page/company/prefs.html" style="min-height: 500px;"></div>
			<div title="Реквизиты" href="page/trade/details.html" style="min-height: 500px;"></div>
			<div title="Бланки" href="page/trade/blank.html" style="min-height: 500px;"></div>
		    </div>
		</div>		
	    </div>
        </td>
    </tr>
</table>
