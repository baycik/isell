<script>
    /* global App,DetAcomp */
    PrefJs={
	init:function(){
	    this.initDetails();
	    this.initPrefs();
            this.initFormOnce();
            App.handler.progress(function(status,data){
                if( status==="activeCompanySelected" ){
                    PrefJs.initPrefs();
                }
            });
	},
	initDetails:function(){
	    var data={
		company_id:App.acomp.company_id,
		inline:true,
		is_active:true
	    };
	    this.user.init();
	    App.loadModule('page/company/details',data,'DetAcomp',/(Cdet|page_company_details)([^\w]|_)/g,"DetAcomp$2");
	},
	initPrefs:function(){
	    $.get("Pref/getPrefs/",function(text){
		PrefJs.prefData=App.json(text);
		App.setupForm('#AcompPrefs',PrefJs.prefData);
	    });
	},
        initFormOnce:function(){
            if( this.formInited ){
                return;
            }
            this.formInited=true;
            App.formElements("#AcompPrefs").change(function () {
		var node=this;
		PrefJs.updateField(node.name,App.val(node),node.title);
	    });
        },
	initClientbankFields:function(){
	    
	},
	updateField:function(field,value,title){
	    $.post('Pref/setPrefs/'+App.uri(field,value),function(ok){
		if( ok*1 ){
		    PrefJs.prefData[field]=value;
		    App.flash("Сохранено: " + title);
		} else {
		    App.flash("Сохраненние не удалось: " + title);
		}
	    });
	},
	user:{
	    init:function(){
		//this.getList();
	    },
	    getList:function(){
		$.get("User/getList",function(text){
		    var users=App.json(text);
		});
	    },
	    formatter:function(value){
		return ["Нет доступа","Ограниченный","Менеджер","Бухгалтер","Администратор"][value];
	    },
	    delete:function(){
		var user=$('#pref_user_dg').datagrid('getSelected');
                if( user && confirm("Удалить пользователя "+user.user_login+"?") ){
                    $.post("User/remove/"+user.user_id,function(ok){
                       if( ok*1 ){
                           App.flash("Пользователь "+user.user_login+" удален");
                       } else {
			   if( ok==='LAST_ADMIN' ){
			       alert("Должен остаться хотя бы один администратор.");
			   }
                           App.flash("Пользователь не удален");
                       }
                       $('#pref_user_dg').datagrid('reload');
                    });
                }
	    },
	    edit:function(){
		var user=$('#pref_user_dg').datagrid('getSelected');
		PrefJs.user.promptEditor(user);
	    },
	    create:function(){
		PrefJs.user.promptEditor({});
	    },
	    promptEditor:function( user ){
		if( App.user.getLevel()<3 ){
		    App.flash("Доступ ограничен");
		    return;
		}
		App.loadWindow('page/dialog/user_edit',user).progress(function(status,user_data){
		    if( status==='submit' ){
			$.post("User/save",user_data,function(ok){
			    if( ok*1 ){
				$('#pref_user_dg').datagrid('reload');
				App.flash("Свойства пользователя сохранены");
				PrefJs.user.checkUserChanged(user_data);
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
	    checkUserChanged:function(user_data){
		if( App.user.props.user_id===user_data.user_id && (App.user.props.user_level!==user_data.user_level || App.user.props.user_login!==user_data.user_login) ){
		    if(confirm("Нужно заново авторизироваться чтобы изменения вступили в силу.\n\nВыйти из программы?")){
			App.user.signOut();
		    }
		}
	    }
	},
        treeRecalculate:function(){
            $.get("Utils/treeRecalculate",function(resp){
                alert("Пересчет таблиц завершен");
            });
        }
    };
</script>
<div class="easyui-panel" title="Настройки" style="width:920px;padding: 3px;margin-bottom: 2px">
    <form id="AcompPrefs">
	<table>
	    <tr>
		<td style="vertical-align: top;width:340px;">
		    <input name="usd_ratio" title="Курс доллара">
		    <input name="default_debt_limit" title="Лимит долга по умолчанию">
		</td>
		<td style="vertical-align: top;width:340px;">
		    <div class="easyui-panel" title="Подписи в документах" data-options="collapsible:true,collapsed:true" style="width:340px;float:left">
			<input name="director_name" title="Руководитель ФИО">
			<input name="director_tin" title="Руководитель ИНН">
			<input name="accountant_name" title="Бухгалтер ФИО">
			<input name="accountant_tin" title="Бухгалтер ИНН">
			<input name="digital_signature" type="hidden" title="Цифровая подпись">	    
		    </div>
		    <div class="easyui-panel" title="Настройки бланков" data-options="collapsible:true,collapsed:true" style="width:340px;float:left">
			<input type="checkbox" title="Округлять до цены с НДС в накладных" name="use_total_as_base">
                        <select name="blank_set" title="Комплект бланков">
                            <option value="ua">Україна</option>
                            <option value="ru">Россия</option>
                        </select>
		    </div>
		    <div class="easyui-panel" title="Настройки Емаил SMTP" data-options="collapsible:true,collapsed:true" style="width:340px;float:left">
			<input name="SMTP_SERVER" title="SMTP Сервер">
			<input name="SMTP_USER" title="Пользователь">
			<input name="SMTP_PASS" title="Пароль">
                        <select name="SMTP_CRYPTO" title="Шифрование">
                            <option value="ssl">SSL</option>
                            <option value="">нет</option>
                        </select>
			<input name="SMTP_PORT" title="Порт">
			<input name="SMTP_SENDER_MAIL" title="Емаил отправителя">
			<input name="SMTP_SENDER_NAME" title="Имя отправителя">
			<input type="checkbox" title="Дублировать на Емаил отправителя" name="SMTP_SEND_COPY">
		    </div>
		    <div class="easyui-panel" title="Настройки ДевиноСМС" data-options="collapsible:true,collapsed:true" style="width:340px;float:left">
			<input name="SMS_SENDER" title="Отправитель">
			<input name="SMS_USER" title="Пользователь">
			<input name="SMS_PASS" title="Пароль">
		    </div>
		    <div class="easyui-panel" title="Настройки клиент-банка" data-options="collapsible:true,collapsed:true" style="width:340px;float:left">
                        <input type="checkbox" title="Идентификатор в платежных поручениях ИНН" name="tax_id_in_checks"> <br>
                        <textarea title="Порядок полей в файле .csv клиент банка" name="clientbank_fields" style="height: 200px"></textarea>
		    </div>
		</td>
	    </tr>
	</table>
    </form>
</div>
<div id="DetAcomp" style="width:920px;padding: 3px;margin-bottom: 2px"></div>
<div class="easyui-panel" title="Пользователи и сотрудники" style="width:920px;padding: 3px;margin-bottom: 2px">
    <div style="">
	<div style="text-align: right;padding-right: 5px;">
	    <span class="icon-24 icon-create" title="Добавить пользователя" onclick="PrefJs.user.create();"> </span>
	    <span class="icon-24 icon-change" title="Изменить пользователя" onclick="PrefJs.user.edit();"> </span>
	    <span class="icon-24 icon-delete" title="Удалить пользователя" onclick="PrefJs.user.delete();"> </span>
	    <span class="icon-24 icon-refresh" title="Обновить" onclick="$('#pref_user_dg').datagrid('reload')"> </span>
	</div>
	<table class="easyui-datagrid" id="pref_user_dg" data-options="
		singleSelect:true,
		onDblClickRow:PrefJs.user.edit,
		url:'User/listFetch'">
	    <thead data-options="frozen:true">
		<tr>
		    <th data-options="field:'user_login',width:80">Логин</th>
		</tr>
	    </thead>
	    <thead>
		<tr>
		    <th data-options="field:'user_level',width:100,formatter:PrefJs.user.formatter">Доступ</th>
		    <th data-options="field:'last_name',width:80">Фамилия</th>
		    <th data-options="field:'first_name',width:80">Имя</th>
		    <th data-options="field:'middle_name'">Отчество</th>
		    <th data-options="field:'user_position'">Должность</th>
		    <th data-options="field:'user_sign'">Подпись</th>
		    <th data-options="field:'nick'">Ник</th>
		    <th data-options="field:'id_type'">Документ</th>
		    <th data-options="field:'id_serial'">Серия</th>
		    <th data-options="field:'id_number'">Номер</th>
		    <th data-options="field:'id_given_by'">Выдан</th>
		    <th data-options="field:'id_date'">Дата выдачи</th>
		</tr>
	    </thead>
	</table>
    </div>
</div>
<div class="easyui-panel" title="Инструменты" style="width:920px;min-height: 200px;padding: 3px;margin-bottom: 2px">
    <button onclick="App.loadWindow('page/dialog/backup');"><img src="img/database.png" style="width:24px;height:24px"> Запустить менеджер резевного копирования</button>
    <button onclick="PrefJs.treeRecalculate()"><img src="img/utils.png" style="width:24px;height:24px"> Исправить ошибки в дереве склада,компаний,бух.счетов</button>
</div>
<style>
    #AcompPrefs b{
	width:120px;
    }
</style>