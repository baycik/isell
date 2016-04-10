<?php
if ( isset($word_header) ) {
?>
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
    <head>
    <title><?php echo $user_data['title'] ?></title>
    <!--[if gte mso 9]>
    <xml>
    <w:WordDocument>
    <w:View>Print</w:View>
    <w:Zoom>100</w:Zoom>
    <w:DoNotOptimizeForBrowser/>
    </w:WordDocument>
    </xml>
    <![endif]-->
    <style><!-- 
	@page{
	    size:<?php echo ($this->landscape_orientation?'29.7cm 21cm':'21cm 29.7cm ')?>;  /* A4 */
	    margin:0.5cm 0.5cm 0.5cm 0.5cm; /* Margins: 2.5 cm on each side */
	    mso-page-orientation: <?php echo ($this->landscape_orientation?'landscape':'portrait')?>;  
	}
	@page WordSection1 { }
	div.WordSection1 { page:WordSection1; }
	--></style>
	    <?php
	} else {?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<link rel="icon" type="image/png" href="../../img/Printer.png">
		<?php
	    }
	    ?>
	    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	    <style type="text/css">
		body, html, td{
		    font-family:Arial;
		}
		.table_border{
		    border-collapse: collapse;
		}
		.table_border td{
		    border:#000 1px solid;
		    padding:1px;
		    margin-top:1px;
		}
		.noborder{
		    border:none;
		}
		.tiny{
		    font-size:8px;
		}
		.small{
		    font-size:9px;
		}
		.medium{
		    font-size:11px;
		}
		.big{
		    font-size:14px;
		}
		.bigest{
		    font-size:16px;
		}
		.large{
		    font-size:17px;
		}
		.cell{
		    width:16px;
		    height:21px;
		    text-align:center;
		    font-size:14px;
		    font-family:"Courier New", Courier, monospace;
		    line-height:14px;
		}
		.page-break{
		    height:100px;
		    page-break-after: always;
		}
		.gridlines td{
		    padding:3px;
		}
	    </style>

	    <title>Печать <?php echo $user_data['title'] ?></title>
    </head>

    <body>
	<?php if (isset($show_controls) && $show_controls && !isset($word_header) ) { ?>
	<link rel="stylesheet" type="text/css" href="../../css/app.css" />
	<style type="text/css">
	    @media all{
		.appbar{
		    background: url(../../img/topbar.png)  repeat-x;
		    border-bottom:solid 1px #aaf;
		    box-shadow:1px 1px 3px 3px #aaf;
		    padding:3px;
		}
		body{
		    background-color:#ddd;
		    background-image:none;
		}
		.page-break{
		    height:10px;
		}
		.page{
		    box-shadow:1px 1px 3px 3px #aaa;
		    padding:30px;
		    margin:10px;
		    background-color:#FFFFFF;
		    display:inline-block;
		    min-height:1110px;
		    background: url(../../img/2xLine.jpg);
		}
		.page table.sheet0 td{
		    padding: 2px;
		}
	    }
	    @media print{
		.page-break{
		    page-break-after: always;
		}
		.page{
		    box-shadow:         none;
		    padding:0px;
		    margin:0px;
		    background-color:#FFFFFF;
		    display:block;
		    height:auto;
		}
		.subpage{
		    page-break-inside:avoid;
		}
		body{
		    background-color:#fff;
		}
		.no_print{
		    display:none;
		}
	    }
	</style>
	<script type="text/javascript">
	    function sendemail(fext) {
		var params={
		    subject:'<?php echo addslashes($user_data['title']) ?>',
		    body:'<?php echo addslashes($user_data['msg']) ?>'.replace(/\\n/img, '\n'),
		    to:'<?php echo addslashes($user_data['email']) ?>',
		    fgenerator:'<?php echo $user_data['fgenerator']; ?>',
                    out_type:fext,
		    dump_id:'<?php echo isset($user_data['dump_id'])?$user_data['dump_id'] : isset($_GET['dump_id'])?$_GET['dump_id']:0; ?>',
		    send_file:1
		};
		var main=opener||parent;
		if (main && main.App) {
		    main.App.loadWindow('page/dialog/send_email',params);
		    main.alert("Файл '"+params.subject+fext+"' прикреплен к письму");
		}
	    }
	</script>
	<div class="no_print appbar" align="center">
		<?php
		if ($export_types):
		    foreach ($export_types as $ext => $name):?>
			<div class="gray_grad" style="display:inline-block;padding:3px;font-size:12px;">
                            <a href="javascript:location.href+='&out_type=<?php echo $ext ?>'" style="color:#333;" title="Скачать">
                                <?php echo $name ?><img src="../../img/down.png" width="24" height="24" border="0" align="absmiddle" /></a>
                            <a href="javascript:sendemail('<?php echo $ext ?>')" style="color:black;font-size:12px;" title="Отправить по Email">
                                <img src="../../img/email.png" width="24" height="24" border="0" align="absmiddle" />
                            </a>
                        </div>
			<?php
		    endforeach;
		endif;
		?>
	    <div style="display:inline-block;width:30px;">&nbsp;</div>
	    <div class="gray_grad" style="display:inline-block;padding:3px;">
		<a href="javascript:window.print()"style="color:black;font-size:12px;">Напечатать <img src="../../img/print.png" border="0" align="absmiddle" /></a>
	    </div>
	</div>
	<?php } echo "<div align='center' class='WordSection1'>$html</div>" ?>
    </body>
</html>
