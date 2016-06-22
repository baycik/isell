<?php
/* 
 * Plugin Name: Price list creator
 * Plugin URI: isellsoft.ru
 * Version: 0.1
 * Description: Tool for creating price list 
 * Author: baycik 2016
 * Author URI: isellsoft.ru
 */
include 'models/Catalog.php';
class Stock_price_list extends Catalog{
    function __construct(){
	add_action( 'stock_add_tab', function(){
	    return $this->get_tab();
	});
    }
    private function get_tab(){
	return [
	    'title'=>'Прайс лист',
	    'href'=>'page/plugins/stock_price_list/stock_price_list.html'
	];
    }
    public function save(){
	$deployment_id=$this->input->post('deployment_id');
	$deployment_data=$this->input->post('deployment_data');
	$this->load->model('Storage');
	return $this->Storage->file_store('stock_price_list/deployments/'.$deployment_id.'.json',$deployment_data);
    }
    public function open(){
	$deployment_id=$this->input->get_post('deployment_id');
	$this->load->model('Storage');
	return $this->Storage->file_restore('stock_price_list/deployments/'.$deployment_id.'.json');	
    }
    public function remove(){
	$deployment_id=$this->input->get_post('deployment_id');
	$this->load->model('Storage');
	return $this->Storage->file_remove('stock_price_list/deployments/'.$deployment_id.'.json');	
    }
    public function listFetch(){
	$this->load->model('Storage');
	$dep_files=$this->Storage->file_list('stock_price_list/deployments/');
	$dep_list=[];
	foreach ($dep_files as $dep_file){
	    $deployment=$this->Storage->json_restore('stock_price_list/deployments/'.$dep_file);
	    $dep_list[]=['id'=>$deployment->id,'date'=>date('d.m.Y',substr($deployment->id,0,10)),'name'=>$deployment->name];
	}
	return $dep_list;
    }
    public function getDeployment( $deployment_id=null ){
	if( !$deployment_id ){
	    $deployment_id=$this->input->get_post('deployment_id');
	}
	
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
	$pcomp_id=$this->Base->pcomp('company_id');
	$dollar_ratio=$this->Base->pref('usd_ratio');
	$main_curr_code=$this->Base->acomp('curr_code');
	$sql="SELECT
		product_code,
		ru product_name,
		product_quantity<>0 in_stock,
		product_img,
		ROUND(
		    sell
			*IF(curr_code AND curr_code<>'$main_curr_code',$dollar_ratio,1)
			*IF(discount,discount,1)
		,2) product_price
	    FROM
		stock_entries se
		    JOIN
		price_list USING(product_code)
		    JOIN
		prod_list USING(product_code)
		    LEFT JOIN
		stock_tree st ON se.parent_id=st.branch_id
		    LEFT JOIN
		companies_discounts cd ON cd.branch_id=st.top_id AND company_id='$pcomp_id'
	    WHERE
		se.parent_id='{$block->id}'
	    ORDER BY $this->sort_by";
	$block->rows=$this->get_list($sql);
        
        
        

	$block->imgs=$this->getBlockImg($block->id,count($block->rows));
	return $block;
    }
    
    private function getBlockImg( $block_id,$block_rows ){
        $limit=floor($block_rows/4);
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
    
    public function printout(){
	$deployment_id=$this->input->get_post('deployment_id');
	$out_type=$this->input->get_post('out_type');
	$deployment=$this->getDeployment($deployment_id);
	if( !$deployment['deployment'] ){
	    return 'price is empty';
	}
	if( $deployment['deployment']->sort_by ){
	    $this->sort_by=$deployment['deployment']->sort_by;
	    if( $this->sort_by=='fetch_count' ){
		$this->sort_by.=" DESC";
	    }
	}
	$this->Base->load_model('Storage');
	$price_blocks=[];
	foreach( $deployment['deployment']->items as $block ){
	    $price_blocks[]=$this->fillPriceBlocks($block);
	}
	$dump=[
	    'tpl_files_folder'=>"../plugins/stock_price_list/",
	    'tpl_files'=>"template.html",
	    'title'=>"Прайс-лист",
	    'view'=>[
		'price_blocks'=>$price_blocks
	    ]
	];
	
	$ViewManager=$this->Base->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}


