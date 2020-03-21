<?php
 
$filename[]=<<<EOT
views/home/home_main.html
EOT;
$search[]=<<<EOT
<!--PLUGIN-WIDGETS-->
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
\n  <div class="widget-block"> 
        <div id="DebtManager_dashboard_holder" class="DebtManager_dashboard_holder  panel" style="margin: 10px;">
            <script>
                $("#DebtManager_dashboard_holder").load("DebtManager/views/?path=dashboard.html");
            </script>
        </div>
    </div>
EOT;


$filename[]=<<<EOT
views/marketing/marketing_main.html
EOT;
$search[]=<<<EOT
<!--PLUGIN-TABS-->
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
\n  <div title="⏲ Задолженности" href="DebtManager/index" style="min-height: 500px;"></div>
EOT;



