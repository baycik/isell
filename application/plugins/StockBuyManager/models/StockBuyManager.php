<?php
/* Group Name: Склад
 * User Level: 2
 * Plugin Name: Менеджер закупок
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: Tool for managing income buyes
 * Author: baycik 2017
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
	    $sortby="product_code IS NULL,product_code";
	    //$sortdir="DESC";
	}
	$where='1';
	
	$having=$this->makeFilter($filter);
	$sql="
	    SELECT 
		supply_id,
		supplier_company_id,
		product_code,
		supply_code,
		supply_name,
		ROUND(supply_buy,2) supply_buy,
		supply_sell_ratio,
		supply_comment,
		supply_spack,
		supply_bpack,
		supply_volume,
		supply_weight,
		supply_unit,
		supply_modified,
		IF(supplier_name IS NULL,'*',supplier_name) supplier_name,
		supplier_delivery,
		ROUND(supply_buy*(1-supplier_buy_discount/100)*(1+supplier_buy_expense/100),2) supply_self,
		ROUND(
		    IF(supplier_sell_gain,
		    supply_buy*(1-supplier_buy_discount/100)*(1+supplier_buy_expense/100)*(1+supplier_sell_gain/100),
		    supply_buy*(1-supplier_sell_discount/100))
                    *(1+supply_sell_ratio/100)
		,2) supply_sell,
                (SELECT path FROM stock_tree JOIN stock_entries se ON se.parent_id=branch_id WHERE se.product_code=sl.product_code) supply_stock_path
	    FROM 
		supply_list sl
		    LEFT JOIN
		supplier_list USING(supplier_company_id)
	    HAVING $having
	    ORDER BY $sortby $sortdir
	    LIMIT $limit OFFSET $offset";
	return $this->get_list($sql);
    }
    
    public $viewGet=['sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json','out_type'=>'string'];
    public function viewGet($sortby,$sortdir,$filter,$out_type){
	$table=$this->listFetch(0,10000,$sortby,$sortdir,$filter);
	$dump=[
	    'tpl_files_folder'=>__DIR__.'/../views/',
	    'tpl_files'=>'StockBuyExport.xlsx',
	    'title'=>"Товары поставщиков",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'view'=>[
		'date'=>date('d.m.Y'),
		'filter'=>$filter,
		'rows'=>$table
	    ]
	];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
    
    public $entryImport=['supplier_company_id'=>'int','label'=>'string'];
    public function entryImport( $supplier_company_id,$label ){
	$source = array_map('addslashes',$this->request('source','raw'));
	$target = array_map('addslashes',$this->request('target','raw'));
        $source[]=$supplier_company_id;
        $target[]='supplier_company_id';
	$this->entryImportFromTable('supply_list', $source, $target, '/supplier_company_id/product_code/supply_code/supply_name/supply_buy/supply_sell_ratio/supply_comment/supply_spack/supply_bpack/supply_volume/supply_weight/supply_unit/', $label);
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
	//die("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label='$label' AND $supply_code_source<>'' ON DUPLICATE KEY UPDATE $set_list");
	$this->query("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label='$label' AND $supply_code_source<>'' ON DUPLICATE KEY UPDATE $set_list");
	return $this->db->affected_rows();
    }
    
    public $supplyCreate=['supplier_company_id'=>'int'];
    public function supplyCreate($supplier_company_id){
	$insert_id=$this->create('supply_list',['supplier_company_id'=>$supplier_company_id]);
	$this->update('supply_list',['supply_code'=>$insert_id],['supply_id'=>$insert_id]);
	return $insert_id;
    }
    
    public $supplyUpdate=['supply_id'=>'int','field'=>'string','value'=>'string'];
    public function supplyUpdate($supply_id,$field,$value){
	if( $field=='supplier_name' ){
	    $field='supplier_company_id';
	    $value=$this->get_value("SELECT supplier_company_id FROM supplier_list WHERE supplier_name='$value'");
	}
	if( $field=='product_code' ){
	    $value=$this->get_value("SELECT product_code FROM prod_list WHERE product_code='$value'");
	}
	$data=[$field=>$value];
	return $this->update('supply_list',$data,['supply_id'=>$supply_id]);
    }
 
    public $supplyDelete=['supply_ids'=>'raw'];
    public function supplyDelete($supply_ids){
	return $this->delete('supply_list','supply_id',$supply_ids);
    }
    
    public $supplyExport=['supply_ids'=>'raw'];
    public function supplyExport($supply_ids){
	if( empty($supply_ids) ){
	    return 0;
	}
	$ids=implode(',',$supply_ids);
	$sql="INSERT INTO
		imported_data (label,A,B,C,D,E,F,G,H,I)
	    (SELECT 
		'склад' label,
		IF(product_code IS NOT NULL,product_code,supply_code) A,
		supply_name B,
		ROUND(
		    IF(supplier_sell_gain,
		    supply_buy*(1-supplier_buy_discount/100)*(1+supplier_buy_expense/100)*(1+supplier_sell_gain/100),
		    supply_buy*(1-supplier_sell_discount/100))
                    *(1+supply_sell_ratio/100)
		,2) C,
		supply_spack D,
		supply_bpack E,
		supply_weight F,
		supply_volume G,
		supply_unit H,
		supply_comment I
	    FROM
		supply_list sl
		    LEFT JOIN
		supplier_list USING(supplier_company_id)
	    WHERE
		supply_id IN ($ids))";
	$this->query($sql);
	return count($supply_ids);
    }

    
    
    
    
    
    
    
    
    
    
    
    
    
    public $supplierListFetch=['offset'=>'int','limit'=>'int','sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json'];
    public function supplierListFetch($offset,$limit,$sortby,$sortdir,$filter=null){
	if( empty($sortby) ){
	    $sortby='supplier_name';
	}
	
	$sql="
	    SELECT 
		*,
		(SELECT COUNT(*) FROM supply_list WHERE supply_list.supplier_company_id=supplier_list.supplier_company_id) supplier_product_count
		
	    FROM 
		supplier_list
	    ORDER BY $sortby $sortdir
	    LIMIT $limit OFFSET $offset";
	$all_count=$this->get_value("SELECT COUNT(*) FROM supply_list");
	$all=[['supplier_name'=>'* Все поставщики','supplier_company_id'=>0,'supplier_product_count'=>$all_count]];
	$suppliers=array_merge($all,$this->get_list($sql));
	return $suppliers;
    }
    
    public $supplierCreate=['supplier_company_id'=>'int','label'=>'string'];
    public function supplierCreate($supplier_company_id,$label){
	return $this->create('supplier_list',['supplier_company_id'=>$supplier_company_id,'supplier_name'=>$label]);
    }
    
    public $supplierUpdate=['supplier_company_id'=>'int','field'=>'string','value'=>'string'];
    public function supplierUpdate($supplier_company_id,$field,$value){
	$data=[$field=>$value];
	if( $field =='supplier_sell_discount' ){
	    $data['supplier_sell_gain']=0;
	} else
	if( $field =='supplier_sell_gain' ){
	    $data['supplier_sell_discount']=0;
	}
	
	
	return $this->update('supplier_list',$data,['supplier_company_id'=>$supplier_company_id]);
    }

    public $supplierDelete=['supplier_company_id'=>'int','also_products'=>'bool'];
    public function supplierDelete($supplier_company_id,$also_products){
	if( $also_products ){
	    $this->delete('supply_list',['supplier_company_id'=>$supplier_company_id]);
	} else {
	    $this->update('supply_list',['supplier_company_id'=>0],['supplier_company_id'=>$supplier_company_id]);
	}
	return $this->delete('supplier_list',['supplier_company_id'=>$supplier_company_id]);
    }

}
