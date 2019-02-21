<?php
/* Group Name: Работа с клиентами
 * User Level: 2
 * Plugin Name: Годовые Продажи менеджера
 * Plugin URI: http://isellsoft.com
 * Version: 0.1
 * Description: Анализ продаж менеджера по месяцам
 * Author: baycik 2019
 * Author URI: http://isellsoft.com
 * Trigger before: Reports_manager_annual_sells
 */
class Reports_manager_annual_sells extends Catalog{
    private $idate;
    private $fdate;
    private $all_active;
    public function __construct() {
        $this->idate=$this->request('idate').' 00:00:00';
	$this->fdate=$this->request('fdate').' 23:59:59';
	$this->all_active=$this->request('all_active','bool');
	$this->count_sells=$this->request('count_sells','bool',0);
	$this->count_reclamations=$this->request('count_reclamations','bool',0);
        $this->include_vat=$this->request('include_vat','bool',0);
	$this->group_by_filter=$this->request('group_by_filter');
	$this->group_by=$this->request('group_by','\w+');
	$this->group_by2=$this->request('group_by2','\w+');
	$this->group_by_manager=$this->request('group_by_manager','bool',0);
	$this->group_by_client=$this->request('group_by_client','bool',0);

        $this->path_include=$this->request('path_include');
        $this->path_exclude=$this->request('path_exclude');
        $this->manager_id=$this->request('manager_id');
	parent::__construct();
    }
    private function iso2dmy( $iso ){
	$chunks=  explode('-', substr ($iso,0,10) );
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
        
        $having=$this->group_by_filter?"HAVING group_by LIKE '%".str_replace(",", "%' OR group_by LIKE '%", $this->group_by_filter)."%'":"";
        
        $manager_filter='';
        if( $this->manager_id ){
            $manager_filter=" AND manager_id='{$this->manager_id}'";
        }
        $path_filter='';
        if( $this->path_exclude || $this->path_include ){
	    $path_filter.=$this->or_like('path ', $this->path_include);
	    $path_filter.=$this->and_like('path NOT ', $this->path_exclude);
        }
	
        $group_by_fields=[];
        $group_by_fields[]=$this->group_by;
        $this->group_by2 && $group_by_fields[]=$this->group_by2;
        $this->group_by_manager && $group_by_fields[]='manager_name';
        $this->group_by_client && $group_by_fields[]='pcomp_name';
        $group_by_concat="CONCAT(".implode(",'/ ',",$group_by_fields).")";

        
        $sql="
            SELECT 
                $group_by_concat AS group_by,
                SUM(row_qty) qty,
                SUM(row_sum) sum,
                
                SUM(IF( MONTH(cstamp)=1, row_qty, 0)) qty1,
                SUM(IF( MONTH(cstamp)=1, row_sum, 0)) sum1,
                
                SUM(IF( MONTH(cstamp)=2, row_qty, 0)) qty2,
                SUM(IF( MONTH(cstamp)=2, row_sum, 0)) sum2,
                
                SUM(IF( MONTH(cstamp)=3, row_qty, 0)) qty3,
                SUM(IF( MONTH(cstamp)=3, row_sum, 0)) sum3,
                
                SUM(IF( MONTH(cstamp)=4, row_qty, 0)) qty4,
                SUM(IF( MONTH(cstamp)=4, row_sum, 0)) sum4,
                
                SUM(IF( MONTH(cstamp)=5, row_qty, 0)) qty5,
                SUM(IF( MONTH(cstamp)=5, row_sum, 0)) sum5,
                
                SUM(IF( MONTH(cstamp)=6, row_qty, 0)) qty6,
                SUM(IF( MONTH(cstamp)=6, row_sum, 0)) sum6,
                
                SUM(IF( MONTH(cstamp)=7, row_qty, 0)) qty7,
                SUM(IF( MONTH(cstamp)=7, row_sum, 0)) sum7,
                
                SUM(IF( MONTH(cstamp)=8, row_qty, 0)) qty8,
                SUM(IF( MONTH(cstamp)=8, row_sum, 0)) sum8,
                
                SUM(IF( MONTH(cstamp)=9, row_qty, 0)) qty9,
                SUM(IF( MONTH(cstamp)=9, row_sum, 0)) sum9,
                
                SUM(IF( MONTH(cstamp)=10, row_qty, 0)) qty10,
                SUM(IF( MONTH(cstamp)=10, row_sum, 0)) sum10,
                
                SUM(IF( MONTH(cstamp)=11, row_qty, 0)) qty11,
                SUM(IF( MONTH(cstamp)=11, row_sum, 0)) sum11,
                
                SUM(IF( MONTH(cstamp)=12, row_qty, 0)) qty12,
                SUM(IF( MONTH(cstamp)=12, row_sum, 0)) sum12
                
            FROM 
                (SELECT 
                    product_code,
                    analyse_type,
                    analyse_brand,
                    analyse_class,
                    product_article,
                    st.label AS category_name,
                    dl.cstamp,
                    active_company_id,
                    is_reclamation,
                    ct.path,
                    manager_id,
                    COALESCE(CONCAT(first_name, ' ' ,last_name),'') AS manager_name,
                    ct.label AS pcomp_name,
                    de.product_quantity row_qty,
                    de.product_quantity * de.invoice_price * IF($this->include_vat, dl.vat_rate / 100 + 1, 1) row_sum
                FROM
                    document_entries de
                        JOIN
                    document_list dl USING (doc_id)
                        JOIN
                    companies_list cl ON company_id = passive_company_id
                        JOIN
                    companies_tree ct USING (branch_id)
                        JOIN
                    prod_list pl USING (product_code)
                        LEFT JOIN
                    user_list ul ON user_id = manager_id
                        LEFT JOIN
                    stock_entries se USING(product_code)
                        LEFT JOIN
                    stock_tree st ON se.parent_id=st.branch_id
                WHERE
                    doc_type = 1
                    AND cstamp>'$this->idate' 
                    AND cstamp<'$this->fdate' 
                    AND is_commited = 1
                    AND notcount = 0) t
            WHERE
                1
                $active_filter 
                $reclamation_filter 
                $manager_filter
                $path_filter
            GROUP BY $group_by_concat
            $having
            ORDER BY sum DESC
            ";
        $rows=$this->get_list($sql);
	$view=[
		'rows'=>count($rows)?$rows:[[]],
		'input'=>[
		    'idate'=>$this->iso2dmy($this->idate),
		    'fdate'=>$this->iso2dmy($this->fdate),
		    'all_active'=>$this->all_active,
		    'count_reclamations'=>$this->count_reclamations,
		    'count_sells'=>$this->count_sells,
                    'include_vat'=>$this->include_vat,
		    'group_by_filter'=>$this->group_by_filter,
		    'group_by'=>$this->group_by,
		    'group_by_manager'=>$this->group_by_manager,
		    'group_by_client'=>$this->group_by_client,
                    'include_vat'=>$this->include_vat
		]
        ];
        foreach($rows as $row){
            for($i=0;$i<=12;$i++){
                $id=$i>0?$i:'';
                if( !isset($view['total_qty'.$id]) ){
                    $view['total_qty'.$id]=0;
                }
                if( !isset($view['total_sum'.$id]) ){
                    $view['total_sum'.$id]=0;
                }
                $view['total_qty'.$id]+=$row->{'qty'.$id};
                $view['total_sum'.$id]+=$row->{'sum'.$id};
                !$row->{'qty'.$id} && $row->{'qty'.$id}='';
                !$row->{'sum'.$id} && $row->{'sum'.$id}='';
            }
        }
	return $view;	        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
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
