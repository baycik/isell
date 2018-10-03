<?php
/* Group Name: Работа с клиентами
 * User Level: 2
 * Plugin Name: Продажи менеджера
 * Plugin URI: http://isellsoft.com
 * Version: 0.1
 * Description: Анализ продаж менеджера с учетом скидок клиента
 * Author: baycik 2017
 * Author URI: http://isellsoft.com
 * Trigger before: Reports_manager_sells
 */
class Reports_manager_sells extends Catalog{
    private $idate;
    private $fdate;
    private $all_active;
    public function __construct() {
	$this->idate=$this->dmy2iso( $this->request('idate','\d\d.\d\d.\d\d\d\d') );
	$this->fdate=$this->dmy2iso( $this->request('fdate','\d\d.\d\d.\d\d\d\d') );
	$this->all_active=$this->request('all_active','bool');
	$this->count_sells=$this->request('count_sells','bool',0);
	$this->count_reclamations=$this->request('count_reclamations','bool',0);
	$this->group_by=$this->request('group_by','\w+');
        $this->path_include=$this->request('path_include');
        $this->path_exclude=$this->request('path_exclude');
        $this->manager_id=$this->request('manager_id');
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
    private function or_like($field,$value){
	$cases=explode(",",$value);
	$filter=" AND (0";
	foreach($cases as $case){
	    if($case){
		$filter.=" OR $field LIKE '%$case%'";
	    }
	}
	return $filter.")";
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
        
        $manager_filter='';
        if( $this->manager_id ){
            $manager_filter=" AND manager_id='{$this->manager_id}'";
        }
        $path_filter='';
        if( $this->path_exclude || $this->path_include ){
	    $path_filter.=$this->or_like('ct.path ', $this->path_include);
	    $path_filter.=$this->and_like('ct.path NOT ', $this->path_exclude);
        }
	
	$this->query("DROP TEMPORARY TABLE IF EXISTS tmp_manager_sells;");#TEMPORARY TEMPORARY 
	$main_table_sql="CREATE TEMPORARY TABLE tmp_manager_sells ENGINE=MyISAM AS (
	    SELECT 
		DATE_FORMAT(cstamp, '%d.%m.%Y') date,
		doc_num,
		ct.label client_name,
		(SELECT st2.label FROM stock_tree st2 WHERE branch_id=st.top_id) cat,
		ROUND(SUM(de.product_quantity * invoice_price * (1+dl.vat_rate/100) )) sum,
		ROUND(COALESCE(JSON_EXTRACT(doc_settings,
					CONCAT('$.discounts.b', st.top_id)),
				discount,
				1) * 100 - 100,
			2) discount_overall
	    FROM 	    
		document_entries de
		    JOIN
		document_list dl USING (doc_id)
		    JOIN
		companies_list cl ON company_id = passive_company_id
		    JOIN
		companies_tree ct USING (branch_id)
		    JOIN
		stock_entries se USING (product_code)
		    JOIN
		stock_tree st ON se.parent_id = st.branch_id
		    LEFT JOIN
		companies_discounts cd ON cd.company_id = passive_company_id
		    AND st.top_id = cd.branch_id
	    WHERE
		doc_type=1 
                AND cstamp>'$this->idate 00:00:00' 
                AND cstamp<'$this->fdate 23:59:59' 
                AND is_commited=1 
                AND notcount=0 
                $active_filter 
                $reclamation_filter 
                $manager_filter
                $path_filter
	    GROUP BY discount_overall ,st.top_id,  doc_id
	    ORDER BY ct.label)";
        //die($main_table_sql);
	$this->query($main_table_sql);
	
	$sum_fields='';
	$discount_list=$this->get_value("SELECT GROUP_CONCAT(DISTINCT(discount_overall)) FROM tmp_manager_sells;");
        $discounts= explode( ',',$discount_list);
		
        if( count($discounts)>1 ){
            rsort($discounts);
            foreach( $discounts as $i=>$dsc ){
                $sum_fields.=",IF(discount_overall=$dsc,sum,'') s$i";
            }
            $rows=$this->get_list("SELECT *$sum_fields FROM tmp_manager_sells");  
        }
	$client_chart=$this->get_list("SELECT client_name,SUM(sum) total FROM tmp_manager_sells GROUP BY client_name ORDER BY SUM(sum) DESC");
	$cat_chart=$this->get_list("SELECT cat,SUM(sum) total FROM tmp_manager_sells GROUP BY cat ORDER BY SUM(sum) DESC");
        return [
		    'd'=>$discounts,
		    'rows'=>count($rows)?$rows:[[]],
		    'input'=>[
			'idate'=>$this->iso2dmy($this->idate),
			'fdate'=>$this->iso2dmy($this->fdate),
			'all_active'=>$this->all_active,
			'count_sells'=>$this->count_sells,
			'count_reclamations'=>$this->count_reclamations,
			'path_include'=>$this->path_include,
			'path_exclude'=>$this->path_exclude
		    ],
		    'client_chart'=>$client_chart,
		    'cat_chart'=>$cat_chart
                ];
    }
}
