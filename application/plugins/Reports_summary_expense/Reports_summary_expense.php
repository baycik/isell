<?php
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
        $acc_debit_filter="acc_debit_code LIKE '9%' OR acc_debit_code LIKE '8%'";
        $acc_credit_filter="acc_credit_code LIKE '9%' OR acc_credit_code LIKE '8%'";
        
        $this->query("DROP TEMPORARY TABLE IF EXISTS tmp_expenses;");
        $main_table_sql="CREATE TEMPORARY TABLE tmp_expenses ENGINE=MyISAM AS (
            SELECT 
                active_company_id,
                acc_debit_code,
                ct.label pname,
                description,
                DATE_FORMAT(cstamp,'%d.%m.%Y') trans_date,
                IF($acc_debit_filter,1,- 1)*ROUND(amount, 2) amount
            FROM
                acc_trans atr
                    JOIN
                companies_list cl ON atr.passive_company_id = company_id
                    JOIN
                companies_tree ct ON ct.branch_id=cl.branch_id
            WHERE
                cstamp>'$this->idate' AND cstamp<'$this->fdate'
                AND ($acc_debit_filter OR $acc_credit_filter)
                $active_filter
            ORDER BY tstamp)";
        $this->query($main_table_sql);
	$rows=$this->get_list("SELECT * FROM tmp_expenses");
        
        $totals_table_sql="
	    SELECT 
                acc_debit_code,
                (SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=active_company_id) aname,
		(SELECT label FROM acc_tree WHERE acc_code=acc_debit_code) acc_name,
		SUM(amount) amount_sum
	    FROM 
		tmp_expenses
	    GROUP BY active_company_id,acc_debit_code";
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