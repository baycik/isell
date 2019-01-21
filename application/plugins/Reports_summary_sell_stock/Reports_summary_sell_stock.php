<?php
/* Group Name: Результаты деятельности
 * User Level: 3
 * Plugin Name: Эффективность запасов
 * Plugin URI: 
 * Version: 1.0
 * Description: Выводит информацию о соотношении продаж к складским остаткам
 * Author: baycik 2018
 * Author URI: 
 * Trigger before: Reports_summary_sell_profit
 */
class Reports_summary_sell_stock extends Catalog{
    private $idate;
    private $fdate;
    private $all_active;
    public function __construct() {
	$this->idate=$this->dmy2iso( $this->request('idate','\d\d.\d\d.\d\d\d\d') ).' 00:00:00';
	$this->fdate=$this->dmy2iso( $this->request('fdate','\d\d.\d\d.\d\d\d\d') ).' 23:59:59';
	$this->all_active=$this->request('all_active','bool');
	$this->count_reclamations=$this->request('count_reclamations','bool',0);
	$this->include_vat=$this->request('include_vat','bool',0);
	$this->in_alt_currency=$this->request('in_alt_currency','bool',0);
	$this->show_entries=$this->request('show_entries','bool',0);
	$this->group_by_filter=$this->request('group_by_filter');
	$this->group_by=$this->request('group_by','\w+');
	if( !in_array($this->group_by, ['parent_id','product_code','analyse_type','analyse_brand','analyse_class','product_article']) ){
	    $this->group_by='parent_id';
	}
        
	$this->group_by2=$this->request('group_by2','\w+');
        $this->group_by2_comma=$this->group_by2?','.$this->group_by2:'';
        $this->group_by2_slash=$this->group_by2?",'/',$this->group_by2":'';
	parent::__construct();
    }
    private function dmy2iso( $dmy ){
	$chunks=  explode('.', $dmy);
	return "$chunks[2]-$chunks[1]-$chunks[0]";
    }
    public function viewGet(){
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Hub->acomp('company_id');
	$reclamation_filter=$this->count_reclamations?'':' AND is_reclamation=0';
        $having=$this->group_by_filter?"HAVING group_by LIKE '%".str_replace(",", "%' OR group_by LIKE '%", $this->group_by_filter)."%'":"";
        
        
        if( $this->include_vat ){
            $leftover_calc_mode='selfprice include_vat';
        } else {
            $leftover_calc_mode='selfprice';
        }
        
        
        $sql_tmp_drop="DROP TEMPORARY TABLE IF EXISTS tmp_summary_sell_stock;";
        $sell_buy_table="
            SELECT
                product_code,
                SUM( IF(doc_type=2,product_quantity,-product_quantity) ) stock_qty,
                LEFTOVER_CALC(product_code,'$this->fdate',0,'$leftover_calc_mode')/IF($this->in_alt_currency,doc_ratio,1) buy_avg,
                SUM( IF(doc_type=1 AND cstamp>'$this->idate',invoice_price*IF($this->include_vat,dl.vat_rate/100+1,1)/IF($this->in_alt_currency,doc_ratio,1)*product_quantity,0) ) sell_prod_sum,
                SUM( IF(doc_type=1 AND cstamp>'$this->idate',product_quantity,0) ) sell_qty
            FROM
                document_entries de
                    JOIN
                document_list dl USING(doc_id)
            WHERE
                (doc_type=1 OR doc_type=2) AND cstamp<'$this->fdate' AND is_commited=1 AND notcount=0 $active_filter $reclamation_filter
            GROUP BY product_code";
        $sql_tmp_create="CREATE TEMPORARY TABLE tmp_summary_sell_stock AS(
            SELECT 
                product_code _product_code,
                ru,
                sell_prod_sum,
                buy_avg*stock_qty stock_entry_sum,
                stock_qty,
                sell_qty,
                CONCAT(IF('$this->group_by'='parent_id',(SELECT label FROM stock_tree WHERE branch_id=se.parent_id),$this->group_by) $this->group_by2_slash) group_by,
                $this->group_by $this->group_by2_comma
            FROM
                stock_entries se
                    JOIN
                prod_list pl USING(product_code)
                    LEFT JOIN
                ($sell_buy_table) sellbuy USING(product_code)
            $having
            )";
        $sql_summary="
            SELECT 
                group_by,
                SUM(sell_prod_sum) sell_sum,
                SUM(stock_entry_sum) stock_sum,
                SUM(stock_qty) stock_sum_qty,
                SUM(sell_qty) sell_sum_qty
            FROM
                tmp_summary_sell_stock
            GROUP BY
		$this->group_by $this->group_by2_comma
            ORDER BY
                sell_sum DESC,stock_sum DESC
            ";
        //die($sql);
        
        $this->query($sql_tmp_drop);
        $this->query($sql_tmp_create);
        
        
        $summary_rows=$this->get_list($sql_summary);
        $total_sell=0;
        $total_sell_qty=0;
        $total_stock=0;
        $total_stock_qty=0;
        foreach( $summary_rows as $row ){
            $total_sell+=$row->sell_sum;
            $total_sell_qty+=$row->sell_sum_qty;
            $total_stock+=$row->stock_sum;
            $total_stock_qty+=$row->stock_sum_qty;
        }
        foreach( $summary_rows as $row ){
            $row->sell_proc=    $total_sell?round( $row->sell_sum/$total_sell, 4):'';
            $row->stock_proc=   $total_stock?round( $row->stock_sum/$total_stock, 4):'';
	    $this->clear_zero($row);
        }
        
        if( $this->show_entries ){
            $rows=$this->get_list("SELECT * FROM tmp_summary_sell_stock WHERE stock_entry_sum>0 OR sell_prod_sum>0 ORDER BY group_by,_product_code");
        }
	$view=[
                'total_sell'=>round($total_sell,2),
                'total_stock'=>round($total_stock,2),
                'total_sell_qty'=>round($total_sell_qty,2),
                'total_stock_qty'=>round($total_stock_qty,2),
		'summary_rows'=>count($summary_rows)?$summary_rows:[[]],
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