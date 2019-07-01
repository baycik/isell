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
    public $min_level=2;
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
	    $sortby="sl.product_code IS NULL,sl.product_code";
	    //$sortdir="DESC";
	}
	$having=$this->makeFilter($filter);
	$sql="
	    SELECT 
		supply_id,
		supplier_id,
		sl.product_code,
		supply_code,
		supply_name,
		supply_leftover,
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
                path
	    FROM 
		supply_list sl
		    LEFT JOIN
		supplier_list USING(supplier_id)
                    LEFT JOIN
                stock_entries se  ON se.product_code=IF(sl.product_code IS NULL,sl.supply_code,sl.product_code)
                    LEFT JOIN 
                stock_tree ON se.parent_id=branch_id 
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
    
    public $entryImport=['supplier_id'=>'int','label'=>'string'];
    public function entryImport( $supplier_id,$label ){
	$source = array_map('addslashes',$this->request('source','raw'));
	$target = array_map('addslashes',$this->request('target','raw'));
        $source[]=$supplier_id;
        $target[]='supplier_id';
	$this->entryImportFromTable('supply_list', $source, $target, '/supplier_id/product_code/supply_code/supply_name/supply_leftover/supply_buy/supply_sell_ratio/supply_comment/supply_spack/supply_bpack/supply_volume/supply_weight/supply_unit/', $label);
	$this->query("DELETE FROM imported_data WHERE {$source[0]} IN (SELECT supply_code FROM supply_list WHERE supplier_id={$supplier_id})");
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
	$this->query("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label LIKE '%$label%' AND $supply_code_source<>'' ON DUPLICATE KEY UPDATE $set_list");
	return $this->db->affected_rows();
    }
    
    public $supplyCreate=['supplier_id'=>'int'];
    public function supplyCreate($supplier_id){
	$insert_id=$this->create('supply_list',['supplier_id'=>$supplier_id]);
	$this->update('supply_list',['supply_code'=>$insert_id],['supply_id'=>$insert_id]);
	return $insert_id;
    }
    
    public $supplyUpdate=['supply_id'=>'int','field'=>'string','value'=>'string'];
    public function supplyUpdate($supply_id,$field,$value){
	if( $field=='supplier_name' ){
	    $field='supplier_id';
	    $value=$this->get_value("SELECT supplier_id FROM supplier_list WHERE supplier_name='$value'");
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
		imported_data (label,A,B,C,D,E,F,G,H,I,J)
	    (SELECT 
		'склад' label,
		IF(product_code IS NOT NULL,product_code,supply_code) A,
		supply_name B,
		ROUND(
		    supply_buy*(1-supplier_buy_discount/100)
		,2) C,
		ROUND(
		    IF(supplier_sell_gain,
		    supply_buy*(1-supplier_buy_discount/100)*(1+supplier_buy_expense/100)*(1+supplier_sell_gain/100),
		    supply_buy*(1-supplier_sell_discount/100))
                    *(1+supply_sell_ratio/100)
		,2) D,
		supply_spack E,
		supply_bpack F,
		supply_weight G,
		supply_volume H,
		supply_unit I,
		supply_comment J
	    FROM
		supply_list sl
		    LEFT JOIN
		supplier_list USING(supplier_id)
	    WHERE
		supply_id IN ($ids))";
	$this->query($sql);
	return count($supply_ids);
    }

    
    
    
    
    
    
    
    
    
    
    
    
    
    public $supplierListFetch=[];
    public function supplierListFetch(){
	$all_count=$this->get_value("SELECT COUNT(*) FROM supply_list");
	$all=[['supplier_name'=>'* Все поставщики','supplier_id'=>0,'supplier_product_count'=>$all_count]];
	$sql="
	    SELECT 
		*,
                (SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=supplier_company_id) real_name,
		(SELECT COUNT(*) FROM supply_list WHERE supply_list.supplier_id=supplier_list.supplier_id) supplier_product_count
	    FROM 
		supplier_list
	    ORDER BY supplier_name";
	return array_merge($all,$this->get_list($sql));
    }
    
    public $supplierCreate=['supplier_company_id'=>'int','label'=>'string'];
    public function supplierCreate($supplier_company_id,$label){
	return $this->create('supplier_list',['supplier_company_id'=>$supplier_company_id,'supplier_name'=>$label]);
    }
    
    public $supplierUpdate=['supplier_id'=>'int','field'=>'string','value'=>'string'];
    public function supplierUpdate($supplier_id,$field,$value){
	$data=[$field=>$value];
	if( $field =='supplier_sell_discount' ){
	    $data['supplier_sell_gain']=0;
	} else
	if( $field =='supplier_sell_gain' ){
	    $data['supplier_sell_discount']=0;
	}
	return $this->update('supplier_list',$data,['supplier_id'=>$supplier_id]);
    }
    
    public $supplierUpdatePrices=['supplier_id'=>'int'];
    public function supplierUpdatePrices($supplier_id){
        $sql="UPDATE 
                price_list pl
                    JOIN
                stock_entries USING(product_code)
                    JOIN
                supply_list sl ON pl.product_code=IF(sl.product_code IS NOT NULL,sl.product_code,sl.supply_code)
		    JOIN
		supplier_list USING(supplier_id)
            SET 
                buy=ROUND(
                    supply_buy*(1-supplier_buy_discount/100)
                ,2),
                sell=ROUND(
		    IF(supplier_sell_gain,
		    supply_buy*(1-supplier_buy_discount/100)*(1+supplier_buy_expense/100)*(1+supplier_sell_gain/100),
		    supply_buy*(1-supplier_sell_discount/100))
                    *(1+supply_sell_ratio/100)
                ,2)
            WHERE 
                supplier_id='$supplier_id'
                AND label=''
                ";
	$this->query($sql);
        return  $this->db->affected_rows();
    }

    public $supplierDelete=['supplier_id'=>'int','also_products'=>'bool'];
    public function supplierDelete($supplier_id,$also_products){
	if( $also_products ){
	    $this->delete('supply_list',['supplier_id'=>$supplier_id]);
	} else {
	    $this->update('supply_list',['supplier_id'=>0],['supplier_id'=>$supplier_id]);
	}
	return $this->delete('supplier_list',['supplier_id'=>$supplier_id]);
    }

    
    private function orderTmpCreate(){
	$this->orderChartTmpCreate();
        $sql_clear="DROP TEMPORARY TABLE IF EXISTS tmp_supply_order;";# TEMPORARY
        $sql_prepare="CREATE TEMPORARY TABLE tmp_supply_order AS (SELECT
	    entry_id,
	    t.product_code,
	    product_quantity,
	    ROUND(supply_buy*(1-supplier_buy_discount/100),2) supply_buy,
	    supplier_company_id,
	    (SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=supplier_company_id) supplier_company_label,
	    product_volume,
	    product_weight
	FROM
	    (SELECT
		entry_id,
		product_code,
		product_quantity,
		supply_id selected_supply_id,
		(SELECT supply_id FROM tmp_supply_order_chart soc WHERE soc.product_code=so.product_code ORDER BY self LIMIT 1) cheapest_supply_id
	    FROM 
		supply_order so
	    ) t
	    JOIN
	    supply_list ON supply_id=IF(selected_supply_id,selected_supply_id,cheapest_supply_id)
		JOIN
	    supplier_list USING(supplier_id)
		JOIN
	    prod_list pl ON pl.product_code=t.product_code);";
	$this->query($sql_clear);
        $this->query($sql_prepare);
    }
    private function orderChartTmpCreate(){
        $sql_clear="DROP TEMPORARY TABLE IF EXISTS tmp_supply_order_chart;";# TEMPORARY
        $sql_prepare="CREATE TEMPORARY TABLE tmp_supply_order_chart AS (SELECT 
                        product_code,
                        supplier_id,
                        (SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=spl.supplier_company_id) supplier_company_label,
                        supply_id,
                        ROUND(supply_buy*(1-supplier_buy_discount/100)*(1+supplier_buy_expense/100),2) self,
			ROUND(supply_buy*(1-supplier_buy_discount/100),2) supply_buy
                    FROM 
                        supplier_list spl
                            JOIN
                        supply_list sl USING(supplier_id)
                    WHERE 
                        product_code IN (SELECT product_code FROM supply_order) );";
        $this->query($sql_clear);
        $this->query($sql_prepare);
    }
    
    
    public $orderFetch=['offset'=>'int','limit'=>'int','sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json'];
    public function orderFetch($offset,$limit,$sortby,$sortdir,$filter=null){
	if( empty($sortby) ){
	    $sortby="entry_id";
	}
	$having=$this->makeFilter($filter);
	$this->orderChartTmpCreate();
        $sql_fetch="
	    SELECT 
                entry_id,
                product_code,
                ru product_name,
                product_quantity,
                product_comment,
                supply_id,
                (SELECT 
                    CONCAT(IF(so.supply_id IS NULL,'#',''),GROUP_CONCAT(CONCAT_WS(':',CONCAT(IF(soc.supply_id=so.supply_id,'#',''),supplier_company_label),supply_id,self) ORDER BY self SEPARATOR '|')) 
                FROM 
                    tmp_supply_order_chart soc 
                WHERE soc.product_code=so.product_code
		) suggestion
                FROM 
                    supply_order so
                        LEFT JOIN
                    prod_list USING(product_code)
            HAVING $having
	    ORDER BY $sortby $sortdir
	    LIMIT $limit OFFSET $offset";
	return $this->get_list($sql_fetch);
    }

    public $orderSummaryFetch=[];
    public function orderSummaryFetch(){
    	$this->orderTmpCreate();
	
	$all_count=$this->get_value("SELECT COUNT(*) FROM tmp_supply_order");
	$all=[['supplier_company_label'=>'* Все товары','supplier_company_id'=>0,'summary_count'=>$all_count]];
        $sql_fetch="
	    SELECT 
		supplier_company_id,
		supplier_company_label,
		COUNT(*) summary_count,
		ROUND(SUM(product_volume*product_quantity),2) summary_volume,
		ROUND(SUM(product_weight*product_quantity),2) summary_weight,
		MAX(IF(product_volume>0,0,1)) volume_more,
		MAX(IF(product_weight>0,0,1)) weight_more,
		ROUND(SUM(supply_buy*product_quantity),2) summary_sum
	    FROM
		tmp_supply_order
	    GROUP BY supplier_company_id";
	return array_merge($all,$this->get_list($sql_fetch));
    }
    
    public $orderCreate=[];
    public function orderCreate(){
	return $this->create('supply_order',['product_code'=>'']);
    }
    
    public $orderUpdate=['entry_id'=>'int','field'=>'string','value'=>'string'];
    public function orderUpdate($entry_id,$field,$value){
	return $this->update('supply_order',[$field=>$value],['entry_id'=>$entry_id]);
    }
 
    public $orderDelete=['entry_ids'=>'raw'];
    public function orderDelete($entry_ids){
	return $this->delete('supply_order','entry_id',$entry_ids);
    }
    
    public $orderFromStock=['parent_id'=>'int'];
    public function orderFromStock( $parent_id ){
        $stock_cat=$this->get_value("SELECT label FROM stock_tree WHERE branch_id='$parent_id'");
	$where="1";
	if( $parent_id ){
	    $branch_ids=$this->treeGetSub('stock_tree',$parent_id);
	    $where="parent_id IN (".implode(',',$branch_ids).")";
	}
        $sql="INSERT supply_order (product_code,product_quantity,product_comment) 
            SELECT 
                product_code,
                product_wrn_quantity-product_quantity,
                'Склад [$stock_cat]'
            FROM
                stock_entries
            WHERE 
                product_wrn_quantity>product_quantity
                AND $where";
        return $this->query($sql);
    }
    
    public $viewOrderGet=['sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json','out_type'=>'string'];
    public function viewOrderGet($sortby,$sortdir,$filter,$out_type){
	$table=$this->orderFetch(0,10000,$sortby,$sortdir,$filter);
	foreach($table as $row){
	    $first='';
	    $other='';
	    $options=explode('|',$row->suggestion);
	    foreach($options as $option){
		$option_arr=explode(':',$option);
		if($option_arr[0][0]=='#'){
		    $first=$option_arr[2]." $option_arr[0] | ";
		    $row->product_price=$option_arr[2];
		} else {
		    $other.=$option_arr[2]." $option_arr[0] | ";
		}
	    }
	    $row->suggestion=$first.$other;
	}
	$dump=[
	    'tpl_files_folder'=>__DIR__.'/../views/',
	    'tpl_files'=>'StockBuyOrderExport.xlsx',
	    'title'=>"Заказ поставщикам",
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

    public $orderSubmit=['supplier_company_id'=>'int'];
    public function orderSubmit($supplier_company_id){
	$this->orderTmpCreate();
	$buy_order=$this->get_list("SELECT 
	    GROUP_CONCAT(entry_id) entry_ids,product_code,SUM(product_quantity) product_quantity,MIN(supply_buy)  supply_buy
	    FROM 
		tmp_supply_order 
	    WHERE 
		supplier_company_id='$supplier_company_id'
	    GROUP BY product_code");
	
	
	$Company=$this->Hub->load_model('Company');
	$Company->selectPassiveCompany($supplier_company_id);
	$DocumentItems=$this->Hub->load_model("DocumentItems");
	$doc_id=$DocumentItems->createDocument(2);//create buy document
	$vat_correction=$DocumentItems->headGet( $doc_id )->vat_rate/100+1;
	foreach($buy_order as $row){
	   $DocumentItems->entryAdd($row->product_code,$row->product_quantity,$row->supply_buy/$vat_correction);
	   $this->query("DELETE FROM supply_order WHERE entry_id IN ($row->entry_ids)");
	}
	return $doc_id;
    }
    public function ordersAbsentCreate(){
        $list=$this->get_list("SELECT 
                    de.*
                FROM
                    isell_db.document_list dl
                        JOIN
                    document_entries de USING (doc_id)
                        JOIN
                    stock_entries se USING (product_code)
                        JOIN
                    isell_db.supply_list sl USING (product_code)
                WHERE
                    dl.is_commited = 0 AND se.product_quantity = 0;");
        print_r($list);
        die;
	
    }

}
