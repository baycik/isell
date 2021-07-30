<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<link rel="icon" type="image/png" href="../../img/Printer.png">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="../../css/app.css" />
        <?php if( $page_orientation =='landscape' ): ?>
        <style type="text/css">
            @media screen{
                .page,table.sheet0{
                    width:297mm;
                    min-height:210mm;
                }
            }
            @media print { 
                @page{
                    size: A4 landscape;
                    margin: 5mm;
                }
            }
        </style>
        <?php else: ?>
        <style type="text/css">
            @media screen{
                .page,table.sheet0{
                    width:min-content;
                    min-width:210mm;
                    min-height:297mm;
                }
            }
            @media print { 
                @page{
                    size: A4 portrait;
                    margin: 5mm;
                }
            }
        </style>
        <?php endif;?>
        <title>Печать <?php echo $user_data['title'] ?></title>
    </head>

    <body>
        <?php if (isset($show_controls) && $show_controls ) { ?>
        <style type="text/css">
	    @media all{
		.appbar{
		    background: url(../../img/topbar.png)  repeat-x;
		    border-bottom:solid 1px #aaf;
		    box-shadow:1px 1px 3px 3px #aaf;
		    padding:3px;
		}
                td{
		    padding: 2px !important;
		}
		body{
		    background-color:#ddd;
		    background-image:none;
                    font-size: 9px;
		}
		.page-break{
		    height:10px;
		}
		.page,table.sheet0{
		    box-shadow:1px 1px 3px 3px #aaa;
		    padding:30px;
		    margin:10px;
		    background-color:#FFFFFF;
		    display:inline-block;
		    background: url(../../img/2xLine.jpg);
		}
		.page table.sheet0 td{
		    padding: 2px;
		}
	    }
            @media print {
		.page-break{
		    page-break-after: always;
		}
                .page{
                    display:block;
                }
		.page,table.sheet0{
		    box-shadow:none;
		    padding:0px;
		    margin:0px;
                    background: none;
		    background-color:#FFFFFF;
                    page-break-inside:avoid;
		}
		.subpage{
		    page-break-inside:avoid;
		}
		body{
		    background-color:#fff;
                    -webkit-print-color-adjust: exact;  /* Chrome/Safari/Edge/Opera */
                    color-adjust: exact;  /* Firefox */
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
		    dump_id:'<?php echo isset($user_data['dump_id'])?$user_data['dump_id'] : (isset($_GET['dump_id'])?$_GET['dump_id']:0); ?>',
		    send_file:1
		};
		var main=opener||parent;
                if( main && main.App && main.App.utils && main.App.utils.sendmail ){
                    main.App.utils.sendmail(params);
                    window.close();
                }
                if( main && main.App && main.App.loadWindow ){
                    main.App.loadWindow('page/dialog/send_email',params);
                    main.alert("Файл '"+params.subject+fext+"' прикреплен к письму");
                }
	    }
	</script>
        <div class="no_print appbar" style="text-align: center;">
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
                <a href="javascript:window.print()"style="color:black;font-size:12px;">Напечатать <img style="width:24px;height: 24px" src="../../img/print.png" border="0" align="absmiddle" /></a>
	    </div>
	</div>
        <?php } ?>
        <div style="text-align: center;page-break-before: unset">
            <?php echo "$html" ?>
        </div>
    </body>
</html>
