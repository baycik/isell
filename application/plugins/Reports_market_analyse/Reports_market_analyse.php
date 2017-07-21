<?php
class Reports_market_analyse extends Catalog{
    public function __construct() {
	parent::__construct();
    }

    public function viewGet(){

	$sql="
	    SELECT
                B article,
                product_code,
                ru product_name,
                analyse_type,
                D sold,
                D*GET_PRICE(product_code,222,65) sold_sum,
                E leftover,
                E*GET_PRICE(product_code,222,65) leftover_sum
            FROM
                imported_data
                    LEFT JOIN
                prod_list ON analyse_section=B
            WHERE
                B<>'' 
                AND label='маркет'
            ";
	
	//echo "<pre>$sql";
	//die();
	
	$rows=$this->get_list($sql);

	return [
	    'rows'=>count($rows)?$rows:[[]]
	];
    }
}