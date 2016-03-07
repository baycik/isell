<?php
class Summary_sell_stock extends Catalog{
    private $idate;
    private $fdate;
    private $all_active;
    public function __construct() {
	$this->idate=$this->dmy2iso( $this->request('idate','\d\d.\d\d.\d\d\d\d') ).' 00:00:00';
	$this->fdate=$this->dmy2iso( $this->request('fdate','\d\d.\d\d.\d\d\d\d') ).' 23:59:59';
	$this->all_active=$this->request('all_active','bool');
	$this->count_reclamations=$this->request('count_reclamations','bool',0);
	$this->in_alt_currency=$this->request('in_alt_currency','bool',0);
	$this->group_by_filter=$this->request('group_by_filter');
	$this->group_by=$this->request('group_by','\w+');
	if( !in_array($this->group_by, ['parent_id','product_code','analyse_type','analyse_group','analyse_class','analyse_section']) ){
	    $this->group_by='parent_id';
	}
	parent::__construct();
    }
    private function dmy2iso( $dmy ){
	$chunks=  explode('.', $dmy);
	return "$chunks[2]-$chunks[1]-$chunks[0]";
    }
    public function viewGet(){
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Base->acomp('company_id');
	$reclamation_filter=$this->count_reclamations?'':' AND is_reclamation=0';
        $having=$this->group_by_filter?"HAVING group_by LIKE '%$this->group_by_filter%'":"";
        $sell_buy_table="
            SELECT
                product_code,
                SUM( IF(doc_type=2,product_quantity,-product_quantity) ) stock_qty,
                SUM( IF(doc_type=2,invoice_price/IF($this->in_alt_currency,doc_ratio,1)*product_quantity,0) )/SUM( IF(doc_type=2,product_quantity,0) ) buy_avg,
                SUM( IF(doc_type=1 AND cstamp>'$this->idate',invoice_price/IF($this->in_alt_currency,doc_ratio,1)*product_quantity,0) ) sell_prod_sum,
                SUM( IF(doc_type=1 AND cstamp>'$this->idate',product_quantity,0) ) sell_qty
            FROM
                document_entries de
                    JOIN
                document_list dl USING(doc_id)
            WHERE
                (doc_type=1 OR doc_type=2) AND cstamp<'$this->fdate' AND is_commited=1 AND notcount=0 $active_filter $reclamation_filter
            GROUP BY product_code";
        $sql="
            SELECT 
                IF('$this->group_by'='parent_id',(SELECT label FROM stock_tree WHERE branch_id=se.parent_id),$this->group_by) group_by,
                SUM(sell_prod_sum) sell_sum,
                SUM(buy_avg*stock_qty) stock_sum,
                SUM(stock_qty) stock_sum_qty,
                SUM(sell_qty) sell_sum_qty
            FROM
                stock_entries se
                    JOIN
                prod_list pl USING(product_code)
                    LEFT JOIN
                ($sell_buy_table) sellbuy USING(product_code)
            GROUP BY
		$this->group_by
            $having";
        //die($sql);
        
        $rows=$this->get_list($sql);
        $total_sell=0;
        $total_sell_qty=0;
        $total_stock=0;
        $total_stock_qty=0;
        foreach( $rows as $row ){
            $total_sell+=$row->sell_sum;
            $total_sell_qty+=$row->sell_sum_qty;
            $total_stock+=$row->stock_sum;
            $total_stock_qty+=$row->stock_sum_qty;
        }
        foreach( $rows as $row ){
            $row->sell_proc=    $total_sell?round( $row->sell_sum/$total_sell, 4):'';
            $row->stock_proc=   $total_stock?round( $row->stock_sum/$total_stock, 4):'';
	    $this->clear_zero($row);
        }
	function sort_bysell($a,$b){
	    if( $a->sell_sum==$b->sell_sum ){
		return 0;
	    }
	    return ($a->sell_sum>$b->sell_sum)?-1:1;
	}
	usort($rows,'sort_bysell');
	$view=[
                'total_sell'=>round($total_sell,2),
                'total_stock'=>round($total_stock,2),
                'total_sell_qty'=>round($total_sell_qty,2),
                'total_stock_qty'=>round($total_stock_qty,2),
		'rows'=>count($rows)?$rows:[[]]
		];
	return $view;	
    }
    private function clear_zero(&$row){
	foreach($row as &$field){
	    if(is_numeric($field) && $field==0){
		$field='';
	    }
	}
    }
}