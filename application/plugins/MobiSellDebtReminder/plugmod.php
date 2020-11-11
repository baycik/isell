<?php


$filename[]=<<<EOT
plugins/MobiSell/views/document.html
EOT;
$search[]=<<<EOT
	    set: function (head) {
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<'EOT'
        
    $("#debt_stats").addClass('covert');
    $.post("../MobiSellDebtReminder/debtStatsGet",{company_id:head.passive_company_id},function(resp){
        var stats=App.json(resp);
        if( stats ){
            App.renderTpl('debt_stats',stats);
        }
    });
EOT;


$filename[]=<<<EOT
plugins/MobiSell/views/document.html
EOT;
$search[]=<<<EOT
    <div id="document_header_segment"
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
    <div id="debt_stats" class="covert">
        <div class="ui warning message">
        {{label}}<br>
        {{if expired_debt|more>0}}
        Просроченный долг: <b style="color:red">{{expired_debt}}</b> (Просрочено на {{m}}мес {{d}}дн) <br>
        {{/if}}
        Общий долг: {{total_debt}}
        </div>
    </div>
EOT;
$after[]=<<<EOT
EOT;
