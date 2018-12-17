<?php
/* Group Name: Статистика
 * User Level: 1
 * Plugin Name: Баланс
 * Plugin URI: 
 * Version: 0.1
 * Description: 
 * Author: baycik 2018
 * Author URI: 
 * Trigger before: Reports_balance
 */
class Reports_balance extends Catalog{
    private $all_active;
    public function __construct() {
	$this->all_active=$this->request('all_active','bool');
        $this->idate=$this->dmy2iso( $this->request('idate','\d\d.\d\d.\d\d\d\d') );
        $this->fdate=$this->dmy2iso( $this->request('fdate','\d\d.\d\d.\d\d\d\d') );
	parent::__construct();
    }
    private function getAssignedPathWhere(){
        $assigned_path=$this->Hub->svar('user_assigned_path');
        return $assigned_path?"AND (path LIKE '".str_replace(',',"%' OR path LIKE '",$assigned_path.'')."%')":"";    
    }
    public function viewGet(){
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Hub->acomp('company_id');
        $date_filter='';
        if( $this->fdate ){
            $date_filter="AND cstamp<'$this->fdate 23:59:59' ";
        }
        $user_level=$this->Hub->svar('user_level');
        $path_filter=$this->getAssignedPathWhere();
	$having =$this->getDirectionFilter();
        $having.=$this->filter_value?"AND (".$this->or_like($this->filter_by,$this->filter_value).")":"";
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
		    DATEDIFF(NOW(),acc_trans.cstamp)<=deferment AND (trans_status=1 OR trans_status=2),IF(acc_debit_code=361,amount,0),0)
		    ),2) allow,
		    MAX(IF(DATEDIFF(NOW(),acc_trans.cstamp)>deferment AND (trans_status=1 OR trans_status=2),DATEDIFF(NOW(),acc_trans.cstamp),0)) AS expday,
		    CONCAT(company_mobile,' ',company_phone) phone
                FROM
		    companies_list
			LEFT JOIN 
		    acc_trans ON company_id=passive_company_id
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