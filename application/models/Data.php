<?php

require_once 'Catalog.php';
class Data extends Catalog {
    private $current_table_orderby=null;
    function __construct(){
	$this->permited_tables = json_decode(file_get_contents('application/config/permited_tables.json', true));
    }
    
    public $import=['string'];
    public function import($table_name){
	if( !$this->checkTable($table_name) ){
	    return false;
	}
	$label=$this->request('label');
	$source = array_map('addslashes',$this->request('source','raw'));
	$target = array_map('addslashes',$this->request('target','raw'));
	if( $table_name=='prod_list' ){
	    $this->importInTable('prod_list', $source, $target, '/product_code/ru/ua/en/product_spack/product_bpack/product_weight/product_volume/product_unit/analyse_origin/product_barcode/analyse_type/analyse_brand/analyse_class/product_article/', $label);
	} else if( $table_name=='price_list' ){
	    $this->importInTable('prod_list', $source, $target, '/product_code/', $label);
	    $this->importInTable('price_list', $source, $target, '/product_code/sell/buy/curr_code/label/', $label);
	}
	$this->query("DELETE FROM imported_data WHERE label LIKE '%$label%' AND {$source[0]} IN (SELECT product_code FROM $table_name)");
        return  $this->db->affected_rows();
    }
    private function importInTable( $table, $src, $trg, $filter, $label ){
	$set=[];
	$target=[];
	$source=[];
	for( $i=0;$i<count($trg);$i++ ){
            if( strpos($filter,"/{$trg[$i]}/")!==false && !empty($src[$i]) ){
		$target[]=$trg[$i];
		$source[]=$src[$i];
		$set[]="{$trg[$i]}=$src[$i]";
	    }
	}
	$target_list=  implode(',', $target);
	$source_list=  implode(',', $source);
	$set_list=  implode(',', $set);
	$this->query("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label LIKE '%$label%' ON DUPLICATE KEY UPDATE $set_list");
	return $this->db->affected_rows();
    }

    private function checkTable($table_name) {
	foreach ($this->permited_tables as $table) {
	    if ( isset($table->level) && $this->Hub->svar('user_level') < $table->level){
		continue;
            }
	    if ($table_name == $table->table_name){
		if( isset($table->orderby) ){
		    $this->current_table_orderby=$table->orderby;
		}
		return true;
            }
	}
	return false;
    }

    public $permitedTableList=[];
    public function permitedTableList() {
	$table_list = [];
	foreach ($this->permited_tables as $table) {
	    if (isset($table->level) && $this->Hub->svar('user_level') < $table->level || isset($table->hidden) && $table->hidden){
		continue;
            }
	    $table_list[] = $table;
	}
	return $table_list;
    }
    
    public $tableStructure=['string'];
    public function tableStructure($table_name){
	if( !$this->checkTable($table_name) ){
	    return false;
	}
	return $this->get_list("SHOW FULL COLUMNS FROM $table_name");
    }
    
    public $tableData=['string'];
    public function tableData($table_name,$having=null){
	if( !$this->checkTable($table_name) ){
	    return false;
	}
	$page=$this->request('page','int',1);
	$rows=$this->request('rows','int',1000);
	if( !$having ){
	    $having=$this->decodeFilterRules();
	}
	$order='';
	if( $this->current_table_orderby ){
	    $order="ORDER BY ".$this->current_table_orderby;
	}
	$offset=($page-1)*$rows;
	if( $offset<0 ){
	    $offset=0;
	}
	return [
		    'rows'=>$this->get_list("SELECT * FROM $table_name WHERE $having $order LIMIT $rows OFFSET $offset"),
		    'total'=>$this->get_value("SELECT COUNT(*) FROM $table_name WHERE $having")
		];
    }
    
    public $tableRowsDelete=['string'];
    public function tableRowsDelete($table_name){
	$this->Hub->set_level(3);
	if( !$this->checkTable($table_name) ){
	    return false;
	}
	$deleted=0;
	$rowKeys=$this->request('rowKey','json');
	foreach( $rowKeys as $key ){
	    $deleted+=$this->delete($table_name,$key);
	}
	return $deleted;
    }
    
    public $tableRowCreateUpdate=['string'];
    public function tableRowCreateUpdate($table_name){
	$this->Hub->set_level(3);
	if( !$this->checkTable($table_name) ){
	    return false;
	}
	$rowKey=$this->request('rowKey','json');
	$data=$this->request('data','json');
	$create=$this->request('create','bool');
	if( !$rowKey ){
	    return false;
	}
	if( $create ){
	    $ok_created=$this->create($table_name, $rowKey);
	}
	return $this->update($table_name, $data, $rowKey) || $ok_created;
    }
    
    public $tableViewGet=['string'];
    public function tableViewGet($table_name){
	$out_type=$this->request('out_type');
	
	$table=$this->tableData($table_name);
	//print_r($table['rows']);exit;
	
	$dump=[
	    'tpl_files'=>'/GridTpl.xlsx',
	    'title'=>"Экспорт таблицы",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'struct'=>$this->tableStructure($table_name),
	    'view'=>[
		'rows'=>$table['rows']
	    ]
	];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}