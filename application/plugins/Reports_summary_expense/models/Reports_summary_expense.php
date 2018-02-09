<?php
/* Group Name: Результаты деятельности
 * User Level: 1
 * Plugin Name: Анализ Затрат
 * Plugin URI: 
 * Version: 0.1
 * Description: Выводит развернутую информацию о затратах
 * Author: baycik 2017
 * Author URI: 
 * Trigger before: Reports_summary_expense
 */



/*
SELECT 
    expense_label,
    trans_name,
    DATE_FORMAT(cstamp,'%Y.%m.%d') dt,
    company_name,
    description,
    amount
FROM
    acc_trans
        JOIN
    companies_list ON company_id = passive_company_id
		LEFT JOIN
	acc_trans_names USING(acc_debit_code,acc_credit_code)
WHERE
    acc_credit_code LIKE '3%'
    AND cstamp>'2017-12-01'
    AND expense_label<>''
 */


class Reports_summary_expense extends Catalog{
    private $idate;
    private $fdate;
    private $all_active;
    private $account_classes=[8,9];
    public function __construct() {
	$this->idate=$this->dmy2iso( $this->request('idate','\d\d.\d\d.\d\d\d\d') ).' 00:00:00';
	$this->fdate=$this->dmy2iso( $this->request('fdate','\d\d.\d\d.\d\d\d\d') ).' 23:59:59';
	$this->all_active=$this->request('all_active','bool');
	parent::__construct();
    }
    private function dmy2iso( $dmy ){
	$chunks=  explode('.', $dmy);
	return "$chunks[2]-$chunks[1]-$chunks[0]";
    }
    public function viewGet(){
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Hub->acomp('company_id');
        $acc_debit_filter="acc_debit_code NOT LIKE '3%'";
        $acc_credit_filter="acc_credit_code LIKE '3%'";
        
        $this->query("DROP TEMPORARY TABLE IF EXISTS tmp_expenses;");
        $main_table_sql="CREATE TEMPORARY TABLE tmp_expenses AS (
            SELECT 
                is_active,
                IF(expense_label,expense_label,trans_label) label,
                trans_name,
                DATE_FORMAT(cstamp,'%Y.%m.%d') trans_date,
                acc_debit_code,
                company_name,
                description,
                amount
            FROM
                acc_trans
                    JOIN
                companies_list ON company_id = passive_company_id
                    LEFT JOIN
                acc_trans_names USING(acc_debit_code,acc_credit_code)
            WHERE
                cstamp>'$this->idate' AND cstamp<'$this->fdate'
                AND $acc_debit_filter 
                AND $acc_credit_filter
                $active_filter
            ORDER BY tstamp)";
        $this->query($main_table_sql);
	$rows=$this->get_list("SELECT * FROM tmp_expenses");
        
        $totals_table_sql="
	    SELECT 
                label expense_label,
                trans_name,
                acc_debit_code,
		SUM(amount) amount_sum
	    FROM 
		tmp_expenses
	    GROUP BY is_active,label";
	$totals=$this->get_list($totals_table_sql);
        $total_amount=0;
        foreach( $totals as $row ){
            $total_amount+=$row->amount_sum;
        }
        
	$view=[
                'total_amount'=>$total_amount,
		'rows'=>count($rows)?$rows:[[]],
		'totals'=>count($totals)?$totals:[[]]
		];
	return $view;	
    }
}