<?php
/* Group Name: Склад
 * User Level: 2
 * Plugin Name: Прайс-лист менеджер
 * Plugin URI: http://isellsoft.com
 * Version: 1.1
 * Description: Tool for creating price list 
 * Author: baycik 2016
 * Author URI: http://isellsoft.com
 * Trigger before: Stock_price_list
 */
class Stock_price_list extends Catalog{
    function __construct(){

    }
    
    public $save=['deployment_id'=>'string','deployment_data'=>'raw'];
    public function save($deployment_id,$deployment_data){
	$this->load->model('Storage');
	return $this->Storage->file_store('stock_price_list/deployments/'.$deployment_id.'.json',$deployment_data);
    }
    
    public $open=['deployment_id'=>'string'];
    public function open($deployment_id){
	$this->load->model('Storage');
	return $this->Storage->file_restore('stock_price_list/deployments/'.$deployment_id.'.json');	
    }
    
    public $remove=['deployment_id'=>'string'];
    public function remove($deployment_id){
	$this->load->model('Storage');
	return $this->Storage->file_remove('stock_price_list/deployments/'.$deployment_id.'.json');	
    }
    
    public $listFetch=[];
    public function listFetch(){
	$this->load->model('Storage');
	$dep_files=$this->Storage->file_list('stock_price_list/deployments/');
	$dep_list=[];
	foreach ($dep_files as $dep_file){
	    $deployment=$this->Storage->json_restore('stock_price_list/deployments/'.$dep_file);
	    if(!$deployment){
		continue;
	    }
	    $dep_list[]=['id'=>$deployment->id,'date'=>date('d.m.Y',substr($deployment->id,0,10)),'name'=>$deployment->name];
	}
	return $dep_list;
    }
    
    public $getDeployment=['deployment_id'=>'string'];
    public function getDeployment( $deployment_id){
	$this->load->model('Storage');
	$availables=$this->getAvailables();
	$deployment=$this->Storage->json_restore('stock_price_list/deployments/'.$deployment_id.'.json');
	if( $deployment ){
	    $deployment->items=$this->updateDeployment($deployment->items,$availables);
	}
	return [
	    'deployment'=>$deployment,
	    'availables'=>$availables
	];
    }
    private function updateDeployment($items,&$availables){
	$updated_items=[];
	foreach($items as $key=>$value){
	    if( $value->type=='category'){
		$updated_value=$this->removeFromAvailables($availables, $value->id);
		if( $updated_value ){
		    $updated_value->type='category';
		    $updated_items[]=$updated_value;
		    continue;
		}
	    }
	    $updated_items[]=$value;
	}
	return $updated_items;
    }
    
    
    private function removeFromAvailables( &$availables, $id ){
	for($i=0;$i<count($availables);$i++){
	    $item=$availables[$i];
	    if( $item->id==$id ){
		array_splice($availables, $i, 1);
		return $item;
	    }
	}
	return null;
    }
    private function getAvailables(){
	$sql="
	    SELECT
		branch_id id,
		label text,
		path,
		(SELECT COUNT(*) FROM stock_entries WHERE parent_id=branch_id) product_count
	    FROM
		stock_tree
	    HAVING
		product_count<>0
	    ORDER BY path";
	return $this->get_list($sql);
    }
    
    private function fillPriceBlocks( $block ){
	if( $block->type!='category' ){
	    return $block;
	}
	$pcomp_id=$this->Hub->pcomp('company_id');
	$dollar_ratio=$this->Hub->pref('usd_ratio');
	$main_curr_code=$this->Hub->acomp('curr_code');
        $sql="SELECT
		se.product_code,
		ru product_name,
                CEIL(CHAR_LENGTH(ru)/50) rows_occupied, 
		product_quantity<>0 in_stock,
		product_img,
		ROUND(
		    sell
			*IF(curr_code<>'' AND curr_code<>'$main_curr_code',$dollar_ratio,1)
			*IF(discount,discount,1)
		,2) product_price
	    FROM
		stock_entries se
		    JOIN
		price_list pl ON pl.product_code=se.product_code AND pl.label='{$this->price_label}'
		    JOIN
		prod_list pdl ON pdl.product_code=se.product_code 
		    LEFT JOIN
		stock_tree st ON se.parent_id=st.branch_id
		    LEFT JOIN
		companies_discounts cd ON cd.branch_id=st.top_id AND company_id='$pcomp_id'
	    WHERE
		se.parent_id='{$block->id}'
	    ORDER BY $this->sort_by";
	$block->rows=$this->get_list($sql);
        $rows_count=$this->countImageOnBlock($block->rows);
	$block->imgs=$this->getBlockImg($block->id,$rows_count);
        $block->img_height=count($block->imgs)>0?($rows_count*25/$rows_count):0;
	return $block;
    }
    
    private function countImageOnBlock($rows){
        $real_rows_count=0;
        foreach($rows as $row){
            $real_rows_count+=$row->rows_occupied;
        }
        return $real_rows_count;
    }
    
    private function getBlockImg( $block_id,$block_rows ){
        $limit=round($block_rows/4);
        $img_sql="SELECT
		product_code,
		product_img
	    FROM
		stock_entries se
		    JOIN
		stock_tree st ON se.parent_id=st.branch_id
	    WHERE
		se.parent_id='$block_id'
                AND product_img
	    ORDER BY fetch_count DESC
            LIMIT $limit";
        return $this->get_list($img_sql);
    }
    
    public $printout=['deployment_id'=>'string','out_type'=>'string'];
    public function printout($deployment_id,$out_type){
	$deployment=$this->getDeployment($deployment_id);
	if( !$deployment['deployment'] ){
	    return 'price is empty';
	}
	if( isset($deployment['deployment']->sort_by) ){
	    $this->sort_by=$deployment['deployment']->sort_by;
	    if( $this->sort_by=='fetch_count' ){
		$this->sort_by.=" DESC";
	    }
	} else {
	    $this->sort_by.="product_code";
	}
	if( isset($deployment['deployment']->price_label) ){
	    $this->price_label=$deployment['deployment']->price_label;
	} else {
	    $this->price_label='';
	}
	
	$this->Hub->load_model('Storage');
	$price_blocks=[];
	foreach( $deployment['deployment']->items as $block ){
	    $price_blocks[]=$this->fillPriceBlocks($block);
	}
	$dump=[
	    'tpl_files_folder'=>"../plugins/stock_price_list/",
	    'tpl_files'=>"template.html",
	    'title'=>"Прайс Лист",
	    'view'=>[
		'price_blocks'=>$price_blocks,
		'pcomp_label'=>$this->Hub->pcomp('label'),
		'dollar_ratio'=>$this->Hub->pref('usd_ratio'),
		'date'=>date('d.m.Y')
	    ]
	];
	
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}


