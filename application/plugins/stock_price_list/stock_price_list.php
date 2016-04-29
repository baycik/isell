<?php
/* 
 * Plugin Name: Price list creator
 * Plugin URI: isellsoft.ru
 * Version: 0.1
 * Description: Tool for creating price list 
 * Author: baycik 2016
 * Author URI: isellsoft.ru
 */
class Stock_price_list extends CI_Model{
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
}


