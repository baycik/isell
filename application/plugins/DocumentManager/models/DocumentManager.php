<?php
/* Group Name: Документ
 * User Level: 2
 * Plugin Name: Менеджер накладных
 * Plugin URI: http://isellsoft.ru
 * Version: 1.0
 * Description: Инструмент для манипуляций с документами
 * Author: baycik 2017
 * Author URI: http://isellsoft.ru
 */
class DocumentManager extends Catalog{
    public $result=['config'=>'json'];
    public function result($config){
	$add=[0];
	$extract=[0];
	foreach($config as $doc_id=>$action){
	    if($action == 'add'){
		$add[]=$doc_id;
	    }
	    if($action == 'extract'){
		$extract[]=$doc_id;
	    }
	}
	$add_in=  implode(',', $add);
	$extract_in=  implode(',', $extract);
	$sql="SELECT
		    product_code,
		    ru product_name,
		    SUM( IF( doc_id IN ($add_in),+1,-1)*product_quantity ) product_quantity
		FROM
		    document_entries
			JOIN
		    prod_list USING(product_code)
		WHERE
		    doc_id IN ($add_in)
		    OR doc_id IN ($extract_in)
		GROUP BY product_code";
	return $this->get_list($sql);
    }
    
    public $export=['config'=>'json','label'=>'string'];
    public function export($config,$label=''){
	$result_table=$this->result($config);
	foreach ($result_table as $row){
	    $this->create('imported_data', ['label'=>$label,'A'=>$row->product_code,'B'=>$row->product_name,'C'=>$row->product_quantity]);
	}
	return count($result_table);
    }
}
