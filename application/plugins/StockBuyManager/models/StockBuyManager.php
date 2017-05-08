<?php
/* Group Name: Склад
 * User Level: 2
 * Plugin Name: Менеджер закупок
 * Plugin URI: http://isellsoft.com
 * Version: 0.1
 * Description: Tool for managing income buyes
 * Author: baycik 2011
 * Author URI: http://isellsoft.com
 */
class StockBuyManager extends Catalog{
    
    public function install(){
	$install_file=__DIR__."/install.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($install_file);
    }
    public function uninstall(){
	$uninstall_file=__DIR__."/uninstall.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($uninstall_file);
    }
    public $views=['string'];
    public function views($path){
	header("X-isell-type:OK");
	$this->load->view($path);
    }
    
    public $listFetch=['offset'=>'int','limit'=>'int','sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json'];
    public function listFetch($offset,$limit,$sortby,$sortdir,$filter=null){
	if( empty($sortby) ){
	    $sortby='supply_modified';
	}
	$where='1';
	
	$having=$this->makeFilter($filter);
	$sql="
	    SELECT 
		*
	    FROM 
		supply_list
	    WHERE $where
	    HAVING $having
	    ORDER BY $sortby $sortdir
	    LIMIT $limit OFFSET $offset";
	return $this->get_list($sql);
    }
    
    public $entryImport=['supplier_company_id'=>'int','label'=>'string'];
    public function entryImport( $supplier_company_id,$label ){
	$source = array_map('addslashes',$this->request('source','raw'));
	$target = array_map('addslashes',$this->request('target','raw'));
        $source[]=$supplier_company_id;
        $target[]='supplier_company_id';
	$this->entryImportFromTable('supply_list', $source, $target, '/supplier_company_id/product_code/supply_code/supply_name/supply_buy/supply_self/supply_delivery/supply_comment/supply_spack/supply_bpack/supply_volume/supply_weight/supply_unit', $label);
	$this->query("DELETE FROM imported_data WHERE {$source[0]} IN (SELECT supply_code FROM supply_list WHERE supplier_company_id={$supplier_company_id})");
	$imported_count=$this->db->affected_rows();
        return  $imported_count;
    }
    private function entryImportFromTable( $table, $src, $trg, $filter, $label ){
	$set=[];
	$target=[];
	$source=[];
	$supply_code_source='';
	for( $i=0;$i<count($trg);$i++ ){
            if( strpos($filter,"/{$trg[$i]}/")===false || empty($src[$i]) ){
		continue;
	    }
	    if( $trg[$i]=='supply_code' ){
		$supply_code_source=$src[$i];
	    }
	    $target[]=$trg[$i];
	    $source[]=$src[$i];
	    $set[]="{$trg[$i]}=$src[$i]";
	}
	$target_list=  implode(',', $target);
	$source_list=  implode(',', $source);
	$set_list=  implode(',', $set);
	//die("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label='$label' AND $supply_code_source<>''");
	$this->query("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label='$label' AND $supply_code_source<>'' ON DUPLICATE KEY UPDATE $set_list");
	return $this->db->affected_rows();
    }
    
    
    public $supplierListFetch=['offset'=>'int','limit'=>'int','sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json'];
    public function supplierListFetch($offset,$limit,$sortby,$sortdir,$filter=null){
	if( empty($sortby) ){
	    $sortby='supply_modified';
	}
	$where='1';
	
	$having=$this->makeFilter($filter);
	$sql="
	    SELECT 
		*
	    FROM 
		supply_list
	    WHERE $where
	    HAVING $having
	    ORDER BY $sortby $sortdir
	    LIMIT $limit OFFSET $offset";
	return $this->get_list($sql);
    }

}
