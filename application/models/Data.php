<?php

require_once 'Catalog.php';
class Data extends Catalog {
    function __construct(){
	$this->permited_tables = json_decode(file_get_contents('application/config/permited_tables.json', true));
    }
    public function import($table_name){
	if( !$this->checkTable($table_name) ){
	    return false;
	}
	$label=$this->request('label');
	$source = array_map('addslashes',$this->request('source','raw'));
	$target = array_map('addslashes',$this->request('target','raw'));
	if( $table_name=='prod_list' ){
	    $this->importInTable('prod_list', $source, $target, '/product_code/ru/ua/en/product_spack/product_bpack/product_weight/product_volume/product_unit/product_uktzet/barcode/analyse_type/analyse_group/analyse_class/analyse_section/', $label);
	} else if( $table_name=='price_list' ){
	    $this->importInTable('prod_list', $source, $target, '/product_code/', $label);
	    $this->importInTable('price_list', $source, $target, '/product_code/sell/buy/curr_code/', $label);
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
	    if ( isset($table->level) && $this->Base->svar('user_level') < $table->level){
		continue;
            }
	    if ($table_name == $table->table_name){
		return true;
            }
	}
	return false;
    }

    public function permitedTableList() {
	$table_list = [];
	foreach ($this->permited_tables as $table) {
	    if (isset($table->level) && $this->Base->svar('user_level') < $table->level || isset($table->hidden) && $table->hidden){
		continue;
            }
	    $table_list[] = $table;
	}
	return $table_list;
    }
    
    public function tableStructure($table_name){
	if( !$this->checkTable($table_name) ){
	    return false;
	}
	return $this->get_list("SHOW FULL COLUMNS FROM $table_name");
    }
    public function tableData($table_name,$having=null){
	if( !$this->checkTable($table_name) ){
	    return false;
	}
	$page=$this->request('page','int',1);
	$rows=$this->request('rows','int',1000);
	if( !$having ){
	    $having=$this->decodeFilterRules();
	}
	$offset=($page-1)*$rows;
	if( $offset<0 ){
	    $offset=0;
	}
	return [
		    'rows'=>$this->get_list("SELECT * FROM $table_name WHERE $having LIMIT $rows OFFSET $offset"),
		    'total'=>$this->get_value("SELECT COUNT(*) FROM $table_name WHERE $having")
		];
    }
    public function tableRowsDelete($table_name){
	$this->Base->set_level(3);
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
    public function tableRowCreateUpdate($table_name){
	$this->Base->set_level(3);
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
	    $this->create($table_name, $rowKey);
	}
	return $this->update($table_name, $data, $rowKey);
    }
    public function tableViewGet($table_name){
	$out_type=$this->request('out_type');
	
	$table=$this->tableData($table_name);
	//print_r($table['rows']);exit;
	
	$dump=[
	    'tpl_files'=>'/GridTpl.xlsx',
	    'title'=>"Экспорт таблицы",
	    'user_data'=>[
		'email'=>$this->Base->svar('pcomp')?$this->Base->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'struct'=>$this->tableStructure($table_name),
	    'view'=>[
		'rows'=>$table['rows']
	    ]
	];
	$ViewManager=$this->Base->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}