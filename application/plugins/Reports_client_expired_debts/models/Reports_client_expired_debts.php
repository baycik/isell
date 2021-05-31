<?php
/* Group Name: Работа с клиентами
 * User Level: 1
 * Plugin Name: Задолженность клиентов
 * Plugin URI: 
 * Version: 1.9
 * Description: Выводит информацию о просроченной и общей задолженности клиентов
 * Author: baycik 2019
 * Author URI: 
 * Trigger before: Reports_client_expired_debts
 */
class Reports_client_expired_debts extends Catalog{
    private $all_active;
    private $our_debts;
    private $their_debts;
    public function __construct() {
	$this->all_active=$this->request('all_active','bool');
	$this->our_debts=$this->request('our_debts','bool');
	$this->their_debts=$this->request('their_debts','bool');
	$this->filter_by=$this->request('filter_by','\w+');
	$this->filter_value=$this->request('filter_value');
        $this->fdate=$this->request('fdate');
        $this->threshold=$this->request('threshold','int',0);
	parent::__construct();
    }
    private function or_like($field,$value){
	$cases=explode(",",$value);
	$filter=[];
	foreach($cases as $case){
	    if($case){
		$filter[]="$field LIKE '%$case%'";
	    }
	}
	return implode(" OR ",$filter);
    }
    private function dmy2iso( $dmy ){
	$chunks=  explode('.', $dmy);
	return "$chunks[2]-$chunks[1]-$chunks[0]";
    }
    private function iso2dmy( $iso ){
	$chunks=  explode('-', $iso);
	return "$chunks[2].$chunks[1].$chunks[0]";
    }
    private function getAssignedPathWhere(){
        $assigned_path=$this->Hub->svar('user_assigned_path');
        return $assigned_path?"AND (path LIKE '".str_replace(',',"%' OR path LIKE '",$assigned_path.'')."%')":"";    
    }
    private function getDirectionFilter(){
	$direction_filter=[];
	if($this->our_debts){
	    $direction_filter[]="buy<>0";
	}
	if($this->their_debts){
	    $direction_filter[]="sell<>0";
	}
	return $direction_filter?'HAVING ('.implode(' OR ', $direction_filter).')':'HAVING 0';
    }
    public function viewGet(){
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Hub->acomp('company_id');
        $date_filter='';
        if( $this->fdate ){
            $date_filter="AND acc_trans.cstamp<'$this->fdate 23:59:59' ";
        }
        $user_level=$this->Hub->svar('user_level');
        $path_filter=$this->getAssignedPathWhere();
	$having =$this->getDirectionFilter();
        $having.=$this->filter_value?"AND (".$this->or_like($this->filter_by,$this->filter_value).")":"";
        $having.=" AND ( sell>$this->threshold OR buy>$this->threshold OR exp>$this->threshold )";

	$sql="
	    SELECT
		label,
		REPLACE(path,'/','/ ') path,
                deferment,
		phone,
                buy,
                sell,
                ROUND(IF(sell > allow, sell - allow, 0)) AS exp,
                FLOOR(expday / 30.417) m,
                ROUND(expday - FLOOR(expday / 30.417) * 30.417) d
	    FROM
		(SELECT 
		    path,
		    label,
		    deferment,
		    ROUND(SUM(IF(acc_debit_code=361,amount,IF(acc_credit_code=361,-amount,0))),2) sell,
		    ROUND(SUM(IF(acc_debit_code=631,-amount,IF(acc_credit_code=631,amount,0))),2) buy,
		    ROUND(SUM(
		    IF(
		    DATEDIFF(NOW(),acc_trans.cstamp)<=IF(doc_deferment,doc_deferment,deferment) AND (trans_status=1 OR trans_status=2),IF(acc_debit_code=361,amount,0),0)
		    ),2) allow,
		    MAX(IF(DATEDIFF(NOW(),acc_trans.cstamp)>IF(doc_deferment,doc_deferment,deferment) AND (trans_status=1 OR trans_status=2),DATEDIFF(NOW(),acc_trans.cstamp),0)) AS expday,
		    CONCAT(company_mobile,' ',company_phone) phone
                FROM
		    companies_list
			LEFT JOIN 
		    acc_trans ON company_id=passive_company_id
                        LEFT JOIN
                    document_list USING(doc_id)
			LEFT JOIN
		    companies_tree USING(branch_id)
		WHERE 
		    level<='$user_level'
                    $date_filter
		    $active_filter
		    $path_filter
                GROUP BY companies_list.company_id
		
		ORDER BY expday DESC) expired
		$having";
	
	//echo "<pre>$sql";
	//die();
	
	$rows=$this->get_list($sql);

	$total_our_debt=0;
        $total_their_debt=0;
        $total_their_exp=0;
        foreach( $rows as $row ){
            $total_our_debt+=$row->buy;
            $total_their_debt+=$row->sell;
            $total_their_exp+=$row->exp;
	    $row->buy=$row->buy!=0?$row->buy:'';
	    $row->sell=$row->sell!=0?$row->sell:'';
	    $row->exp=$row->exp!=0?$row->exp:'';
	    $row->m=$row->m!=0?$row->m:'';
	    $row->d=$row->d!=0?$row->d:'';
	    $row->deferment=$row->deferment!=0?$row->deferment:'';
        }
	return [
	    'total_our_debt'=>$total_our_debt,
	    'total_their_debt'=>$total_their_debt,
	    'total_their_exp'=>$total_their_exp,
	    'rows'=>count($rows)?$rows:[[]],
	    'input'=>[
		'all_active'=>$this->all_active,
                'fdate'=>$this->iso2dmy($this->fdate),
		'our_debts'=>$this->our_debts,
		'their_debts'=>$this->their_debts,
		'filter_by'=>$this->filter_by,
		'filter_value'=>$this->filter_value
	    ]
	];
    }
}