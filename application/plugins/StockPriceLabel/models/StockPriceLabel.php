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
    public $out=['label'=>'string','field'=>'string','quantity'=>['string','1'], 'pcomp2_id'=>'int', 'out_type'=>'string'];
    public function out($label,$field,$quantity_field, $pcomp2_id, $out_type){
        $pcomp_id=$this->Hub->pcomp('company_id');
        $ratio=$this->Hub->pref("usd_ratio");
        $sql="SELECT
                product_code,
                product_barcode,
                ru product_name,
                REPLACE(GET_SELL_PRICE(product_code,'$pcomp_id','$ratio'),'.00','') product_price,
                #ROUND(GET_SELL_PRICE(product_code,'$pcomp2_id','$ratio'),2) product_price2,
                $quantity_field quantity,
                product_img
            FROM
                prod_list
                    JOIN
                imported_data ON product_code=$field
                    JOIN
                stock_entries USING(product_code)
            WHERE label='$label'";
        $products=$this->get_list($sql);
        
        
        $price_tags=[];
        foreach($products as $row){
            $tag_quantity=1;
            if($row->quantity>0){
                $tag_quantity=$row->quantity;
            }
            for( $i=0;$i<$tag_quantity;$i++){
               $price_tags[]= $row;
            }
        }
        $dump=[
	    'tpl_files_folder'=>"application/plugins/StockPriceLabel/",
	    'tpl_files'=>"template.html",
	    'title'=>"ценников",
	    'view'=>[
		'products'=>$price_tags,
		'date'=>date('d.m.Y')
	    ]
	];
	
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}
