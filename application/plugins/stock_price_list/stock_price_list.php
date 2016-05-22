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
    public function getDeployment(){
	$deployment_id=$this->input->get_post('deployment_id');
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
		//unset( $availables[$i] );
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
}


