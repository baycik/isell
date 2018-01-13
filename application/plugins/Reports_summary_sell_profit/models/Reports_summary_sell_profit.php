<?php
/* Group Name: Результаты деятельности
 * User Level: 3
 * Plugin Name: Анализ продаж и прибыли
 * Plugin URI: 
 * Version: 0.1
 * Description: Выводит развернутую информацию о продажах и прибыле, с возможностью группировать по аналитикам и клиентам
 * Author: baycik 2017
 * Author URI: 
 * Trigger before: Reports_summary_sell_profit
 */
class Reports_summary_sell_profit extends Catalog{
    private $idate;
    private $fdate;
    private $all_active;
    public function __construct() {
	$this->idate=$this->dmy2iso( $this->request('idate','\d\d.\d\d.\d\d\d\d') );
	$this->fdate=$this->dmy2iso( $this->request('fdate','\d\d.\d\d.\d\d\d\d') );
	$this->all_active=$this->request('all_active','bool');
	$this->count_sells=$this->request('count_sells','bool',0);
	$this->count_reclamations=$this->request('count_reclamations','bool',0);
	$this->in_alt_currency=$this->request('in_alt_currency','bool',0);
	$this->group_by_client=$this->request('group_by_client','bool',0);
	$this->language=$this->request('language','\w+','ru');
	$this->group_by_filter=$this->request('group_by_filter');
	$this->group_by=$this->request('group_by','\w+');
        $this->path_include=$this->request('path_include');
        $this->path_exclude=$this->request('path_exclude');
	if( !in_array($this->group_by, ['label','product_code','analyse_type','analyse_brand','analyse_class','product_article']) ){
	    $this->group_by='label';
	}
	parent::__construct();
    }
    private function dmy2iso( $dmy ){
	$chunks=  explode('.', $dmy);
	return "$chunks[2]-$chunks[1]-$chunks[0]";
    }
    private function iso2dmy( $iso ){
	$chunks=  explode('-', $iso);
	return "$chunks[2].$chunks[1].$chunks[0]";
    }
    private function and_like($field,$value){
	$cases=explode(",",$value);
	$filter="";
	foreach($cases as $case){
	    if($case){
		$filter.=" AND $field LIKE '%$case%'";
	    }
	}
	return $filter;
    }
    public function viewGet(){
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Hub->acomp('company_id');
        
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
        $path_filter='';
        if( $this->path_exclude || $this->path_include ){
	    $path_filter="JOIN companies_list cl ON dl.passive_company_id=company_id JOIN companies_tree ct ON cl.branch_id=ct.branch_id ";
	    $path_filter.=$this->and_like('ct.path ', $this->path_include);
	    $path_filter.=$this->and_like('ct.path NOT ', $this->path_exclude);
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
		    analyse_brand,
		    analyse_class,
		    product_article,
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
                    $path_filter
		WHERE
		    doc_type=1 AND cstamp>'$this->idate 00:00:00' AND cstamp<'$this->fdate 23:59:59' AND is_commited=1 AND notcount=0 $active_filter $reclamation_filter
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
		'totals'=>count($totals)?$totals:[[]],
		'input'=>[
		    'idate'=>$this->iso2dmy($this->idate),
		    'fdate'=>$this->iso2dmy($this->fdate),
		    'all_active'=>$this->all_active,
		    'count_sells'=>$this->count_sells,
		    'count_reclamations'=>$this->count_reclamations,
		    'count_sells'=>$this->count_sells,
		    'in_alt_currency'=>$this->in_alt_currency,
		    'group_by_client'=>$this->group_by_client,
		    'language'=>$this->language,
		    'group_by_filter'=>$this->group_by_filter,
		    'group_by'=>$this->group_by,
		    'path_include'=>$this->path_include,
		    'path_exclude'=>$this->path_exclude
		]
		];
	return $view;	
    }
}