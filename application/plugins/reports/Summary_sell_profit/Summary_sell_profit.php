<?php
class Summary_sell_profit extends Catalog{
    private $idate;
    private $fdate;
    private $all_active;
    public function __construct() {
	$this->idate=$this->dmy2iso( $this->request('idate','\d\d.\d\d.\d\d\d\d') ).' 00:00:00';
	$this->fdate=$this->dmy2iso( $this->request('fdate','\d\d.\d\d.\d\d\d\d') ).' 23:59:59';
	$this->all_active=$this->request('all_active','bool');
	$this->count_sells=$this->request('count_sells','bool',0);
	$this->count_reclamations=$this->request('count_reclamations','bool',0);
	$this->in_alt_currency=$this->request('in_alt_currency','bool',0);
	$this->group_by_client=$this->request('group_by_client','bool',0);
	$this->language=$this->request('language','\w+','ru');
	$this->group_by_filter=$this->request('group_by_filter');
	$this->group_by=$this->request('group_by','\w+');
	if( !in_array($this->group_by, ['label','product_code','analyse_type','analyse_group','analyse_class','analyse_section']) ){
	    $this->group_by='label';
	}
	parent::__construct();
    }
    private function dmy2iso( $dmy ){
	$chunks=  explode('.', $dmy);
	return "$chunks[2]-$chunks[1]-$chunks[0]";
    }
    public function viewGet(){
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Base->acomp('company_id');
        
        $reclamation_filter='';
        if( $this->count_sells && !$this->count_reclamations ){
            $reclamation_filter=' AND is_reclamation=0';
        }
        if( !$this->count_sells && $this->count_reclamations ){
            $reclamation_filter=' AND is_reclamation=1';
        }
        if( !$this->count_sells && !$this->count_reclamations ){
            $reclamation_filter=' AND 0';
        }
	$passive_groupper=$this->group_by_client?',passive_company_id':'';
	$group_by_label=$this->group_by_client?"CONCAT($this->group_by,' / ',(SELECT company_name FROM companies_list WHERE company_id=passive_company_id))":"$this->group_by";
        $having=$this->group_by_filter?"HAVING group_by LIKE '%$this->group_by_filter%'":"";
	
	$this->query("DROP TEMPORARY TABLE IF EXISTS tmp_sell_profit;");
	$main_table_sql="CREATE TEMPORARY TABLE tmp_sell_profit ( INDEX(product_code) ) ENGINE=MyISAM AS (
	    SELECT 
		$group_by_label group_by,
		product_code $passive_groupper,
		product_name,
		sell_qty,
		self_prod_sum/sell_qty self_avg,
		self_prod_sum,
		sell_prod_sum/sell_qty sell_avg,
		sell_prod_sum,
		sell_prod_sum-self_prod_sum net_prod_sum
	    FROM 	    
		(SELECT
		    product_code $passive_groupper,
		    $this->language product_name,
		    (SELECT label FROM stock_tree WHERE branch_id=se.parent_id) label,
		    analyse_type,
		    analyse_group,
		    analyse_class,
		    analyse_section,
		    SUM( de.product_quantity ) sell_qty,
		    SUM( de.self_price/IF($this->in_alt_currency,doc_ratio,1)*de.product_quantity ) self_prod_sum,
		    SUM( de.invoice_price/IF($this->in_alt_currency,doc_ratio,1)*de.product_quantity ) sell_prod_sum
		FROM
		    document_entries de
			JOIN
		    document_list dl USING(doc_id)
			JOIN
		    stock_entries se USING(product_code)
			JOIN
		    prod_list pl USING(product_code)
		WHERE
		    doc_type=1 AND cstamp>'$this->idate' AND cstamp<'$this->fdate' AND is_commited=1 AND notcount=0 $active_filter $reclamation_filter
		GROUP BY product_code $passive_groupper) entries
		$having)";
	$this->query($main_table_sql);
	$rows=$this->get_list("SELECT * FROM tmp_sell_profit");
	
	$totals_table_sql="
	    SELECT 
		group_by,
		SUM(sell_qty) sell_qty_sum,
		SUM(self_prod_sum) self_sum,
		SUM(sell_prod_sum) sell_sum,
		SUM(sell_prod_sum)-SUM(self_prod_sum) net_sum
	    FROM 
		tmp_sell_profit
	    GROUP BY group_by $passive_groupper";
	$totals=$this->get_list($totals_table_sql);
        $total_sell=0;
        $total_self=0;
        $total_net=0;
        $total_qty=0;
        foreach( $totals as $row ){
            $total_sell+=$row->sell_sum;
            $total_self+=$row->self_sum;
            $total_net+=$row->net_sum;
            $total_qty+=$row->sell_qty_sum;
        }
	function sort_bysell($a,$b){
	    if( $a->net_sum==$b->net_sum ){
		return 0;
	    }
	    return ($a->net_sum>$b->net_sum)?-1:1;
	}
	usort($totals,'sort_bysell');
	$view=[
                'total_sell'=>$total_sell,
                'total_self'=>$total_self,
                'total_net'=>$total_net,
                'total_qty'=>$total_qty,
		'rows'=>count($rows)?$rows:[[]],
		'totals'=>count($totals)?$totals:[[]]
		];
	return $view;	
    }
}