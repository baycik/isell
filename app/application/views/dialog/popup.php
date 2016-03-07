<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<title>Popup Editor</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="css/app.css" rel="stylesheet" type="text/css" />
	<link href="css/main.css" rel="stylesheet" type="text/css" />
	<link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css" />
	<script type="text/javascript" src="js/dojo/dojo.js" djConfig=" parseOnLoad: true,locale: 'ru'"></script>
	<script type="text/javascript">
	    function init() {
		if (!window.fvalue && opener && opener.popupfvalue)
		    fvalue = opener.popupfvalue;
		if (window['beforeInit'])
		    beforeInit();
		if (document.getElementById("EditorForm")) {
		    var EditorForm = dijit.byId("EditorForm");
		    EditorForm.attr("value", fvalue);
		    dojo.connect(EditorForm, "onSubmit", function (e) {
			if (e)
			    e.preventDefault();
			submit(EditorForm.attr("value"))
		    });
		}
		if (window['afterInit'])
		    afterInit();
		if (!window.callback && opener && opener.popupcallback) {
		    window.callback = opener.popupcallback;
		}
		if (!window.callback)
		    alert('Callback is not set');
	    }
	    function unload() {
		if (window['beforeUnload'])
		    beforeUnload();
	    }
	    function submit(_fvalue) {
		fvalue = _fvalue;
		if (window['beforeSubmit'] && !beforeSubmit())
		    return false;
		window.callback(fvalue);
		window.close();
		return false;
	    }
	//function fillForm( form_id, fvalue ){
	//	var form=dojo.byId(form_id);
	//	for( i in form.elements ){
	//		if( form.elements[i].type=='checkbox' )
	//			form.elements[i].checked=fvalue[form.elements[i].name]?'checked':'';
	//		else
	//			form.elements[i].value=fvalue[form.elements[i].name];
	//	}
	//}
	//function collectForm( form_id ){
	//	var form=dojo.byId(form_id);
	//	for( i in form.elements ){
	//		if( form.elements[i].type=='checkbox' )
	//			fvalue[form.elements[i].name]=form.elements[i].checked?'1':'0';
	//		else
	//			fvalue[form.elements[i].name]=form.elements[i].value;
	//	}
	//	return fvalue;
	//}
	    dojo.addOnLoad(init);
	</script>
    </head>

    <body class=" claro " onBeforeUnload="unload()">
	<table id="cont" cellpadding="0" cellspacing="0" width="100%">
	    <tr><td>
		    <?php
		    if (isset($_REQUEST['tpl'])) {
			include 'application/views/dialog/' . $_REQUEST['tpl'];
		    } else {
			echo 'TPL is not specified';
		    }
		    ?>
		</td></tr>
	</table>
    </body>
</html>