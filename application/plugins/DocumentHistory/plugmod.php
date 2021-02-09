<?php
$filename[]=<<<EOT
models/Data.php
EOT;
$search[]=<<<'EOT'
$this->permited_tables = json_decode(file_get_contents('application/config/permited_tables.json', true));
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<'EOT'
        $this->permited_tables[]=json_decode('{"table_name":"plugin_doc_history_list","table_title":"История изменений документа","level":2,"editable":0}');
EOT;
