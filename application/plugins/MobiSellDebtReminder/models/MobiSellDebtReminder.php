<?php
/* User Level: 1
 * Group Name: Мобильное
 * Plugin Name: MobiSell Debt Reminder
 * Version: 2020-11-11
 * Description: Мобильное приложение
 * Author: baycik 2020
 * Author URI: isellsoft.net
 * Trigger before: MobiSellDebtReminder
 */

class MobiSellDebtReminder extends Catalog{
    
    private function getAssignedPathWhere(){
        $assigned_path=$this->Hub->svar('user_assigned_path');
        return $assigned_path?"AND (path LIKE '".str_replace(',',"%' OR path LIKE '",$assigned_path.'')."%')":"";    
    }
    public function debtStatsGet( int $company_id ) {
        $user_level=$this->Hub->svar('user_level');
        $path_filter=$this->getAssignedPathWhere();
        $sql="
	    SELECT
		label,
                deferment,
                sell total_debt,
                ROUND(IF(sell > allow, sell - allow, 0)) AS expired_debt,
                FLOOR(expday / 30.417) m,
                ROUND(expday - FLOOR(expday / 30.417) * 30.417) d
	    FROM
		(SELECT 
		    path,
		    label,
		    deferment,
		    ROUND(SUM(IF(acc_debit_code=361,amount,IF(acc_credit_code=361,-amount,0))),2) sell,
		    ROUND(SUM(
		    IF(
		    DATEDIFF(NOW(),acc_trans.cstamp)<=deferment AND (trans_status=1 OR trans_status=2),IF(acc_debit_code=361,amount,0),0)
		    ),2) allow,
		    MAX(IF(DATEDIFF(NOW(),acc_trans.cstamp)>deferment AND (trans_status=1 OR trans_status=2),DATEDIFF(NOW(),acc_trans.cstamp),0)) AS expday
                FROM
		    companies_list
			LEFT JOIN 
		    acc_trans ON company_id=passive_company_id
			LEFT JOIN
		    companies_tree USING(branch_id)
		WHERE 
                    company_id='$company_id'
		    AND level<='$user_level'
		    $path_filter) expired";
        
        
        
        return $this->get_row($sql);//['expired_debt'=>256.32,'total_debt'=>963.21];
    }
    
}