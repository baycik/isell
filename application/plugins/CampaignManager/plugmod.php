<?php

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
\n  <div title="Менеджер Мотиваций" href="CampaignManager/index" style="min-height: 500px;"></div>
EOT;

$filename[]=<<<EOT
plugins/MobiSell/views/home.html
EOT;
$search[]=<<<EOT
<!--PLUGIN-PANELS-->
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
<div id="CampaignManager_dashboard_holder" class="ui blue segment raised" style="margin: 10px;"></div>
<script>
    $("#CampaignManager_dashboard_holder").load("../CampaignManager/dashboardMobiSell");
</script>
EOT;

