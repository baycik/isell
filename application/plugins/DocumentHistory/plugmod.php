<?php
/*
 * Filename to change. Root is appdir
 */
$filename[]=<<<EOT
models/Data.php
EOT;
/*
 * Search for string
 */
$search[]=<<<'EOT'
$this->permited_tables = json_decode(file_get_contents('application/config/permited_tables.json', true));
EOT;
/*
 * Replace found with
 */
$replace[]=<<<EOT
EOT;
/*
 * Inserts before found
 */
$before[]=<<<EOT
EOT;
/*
 * Inserts after found
 */
$after[]=<<<'EOT'
        $this->permited_tables[]=json_decode('{"table_name":"plugin_doc_history_list","table_title":"История изменений документа","level":2,"editable":0}');
EOT;