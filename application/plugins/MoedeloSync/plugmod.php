<?php
$filename[]=<<<EOT
views/rpt/FileEngineWrapper.php
EOT;
$search[]=<<<EOT
<div class="no_print appbar" align="center">
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<'EOT'
    <?php
    $supported_types=['1_136','1_133','1_140','1_143'];
    $current_key="{$view->head->doc_type}_{$view->doc_view->view_type_id}";
    if( isset($view->doc_view->view_type_id) && in_array($current_key,$supported_types) ):
    $file_name=urlencode("{$view->doc_view->view_name} №{$view->doc_view->view_num} от {$view->doc_view->loc_date}");
    ?>
    <div class="gray_grad" style="display:inline-block;padding:3px;font-size:12px;">
        <img src="../../MoedeloSync/moedelologo.png" style="width:24px;height:auto;">
        <a style="color:#333;" target="_blank" href="../../MoedeloSync/downloadFile/?doc_type=<?php echo $view->head->doc_type?>&doc_view_id=<?php echo $view->doc_view->doc_view_id?>&view_type_id=<?php echo $view->doc_view->view_type_id?>&file_name=<?php echo $file_name?>&file_type=pdf">Pdf</a>
        <a style="color:#333;" target="_blank" href="../../MoedeloSync/downloadFile/?doc_type=<?php echo $view->head->doc_type?>&doc_view_id=<?php echo $view->doc_view->doc_view_id?>&view_type_id=<?php echo $view->doc_view->view_type_id?>&file_name=<?php echo $file_name?>&file_type=doc">Doc</a>
        <a style="color:#333;" target="_blank" href="../../MoedeloSync/downloadFile/?doc_type=<?php echo $view->head->doc_type?>&doc_view_id=<?php echo $view->doc_view->doc_view_id?>&view_type_id=<?php echo $view->doc_view->view_type_id?>&file_name=<?php echo $file_name?>&file_type=xls">Xls</a>
    </div>
    <?php
    endif;
    ?>
EOT;

$filename[]=<<<EOT
views/company/details.html
EOT;
$search[]=<<<EOT
</textarea>
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<'EOT'
    <div style="padding:5px;">
    <button onclick="$.get('MoedeloSync/remotePush',{handler:'MoedeloSyncCompanies',local_id:App.page_company_details.data.company_id}).done(function(){App.flash('Реквизиты сохранены!')}).fail(function(){App.flash('Возникла ошибка!')});"><img src="img/big_rightarrow.png" style="width:24px;height:auto;"><img src="MoedeloSync/moedelologo.png" style="width:24px;height:auto;"> Форсировать сохранение</button>
    </div>
EOT;



$filename[]=<<<EOT
views/stock/product_card.html
EOT;
$search[]=<<<EOT
<!--PLUGINS-->
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<'EOT'
    <div class="easyui-panel" title="Моёдело" data-options="collapsible:true,collapsed:true">
    <button onclick="var moedelo_local_id=App.page_stock_product_card.data.product_id;App.page_stock_product_card.submit(event).then(function(){$.get('MoedeloSync/remotePush',{handler:'MoedeloSyncProduct',local_id:moedelo_local_id}).done(function(){App.flash('Товар сохранен в Моёдело!')}).fail(function(){App.flash('Возникла ошибка!')}) });"><img src="img/big_rightarrow.png" style="width:24px;height:auto;"><img src="MoedeloSync/moedelologo.png" style="width:24px;height:auto;"> Форсировать сохранение</button>
    </div>
EOT;
