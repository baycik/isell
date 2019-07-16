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
            <div id="DebtManager_dashboard_holder" class="panel" style="margin: 10px;"></div>
            <script>
                $("#DebtManager_dashboard_holder").load("DebtManager/dashboard");
            </script>
        </div>
EOT;


