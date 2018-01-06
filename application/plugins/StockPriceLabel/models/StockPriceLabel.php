<?php
/* Group Name: Склад
 * User Level: 2
 * Plugin Name: Распечатка ценников
 * Plugin URI: http://isellsoft.ru
 * Version: 0.1
 * Description: Tool for creating price labels
 * Author: baycik 2018
 * Author URI: http://isellsoft.ru
 */
class StockPriceLabel extends Catalog{
    public $out=['label'=>'string','field'=>'string','out_type'=>'string'];
    public function out($label,$field,$out_type){
        $pcomp_id=$this->Hub->pcomp('company_id');
        $ratio=$this->Hub->pref("usd_ratio");
        $sql="SELECT
                product_code,
                ru product_name,
                GET_PRICE(product_code,'$pcomp_id','$ratio') product_price,
                product_img
            FROM
                prod_list
                    JOIN
                imported_data ON product_code=$field
                    JOIN
                stock_entries USING(product_code)
            WHERE label='$label'";
        $products=$this->get_list($sql);
        $dump=[
	    'tpl_files_folder'=>"application/plugins/StockPriceLabel/",
	    'tpl_files'=>"template.html",
	    'title'=>"Печать ценников",
	    'view'=>[
		'products'=>$products,
		'date'=>date('d.m.Y')
	    ]
	];
	
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}
