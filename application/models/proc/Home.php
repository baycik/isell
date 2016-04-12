<?php
require_once 'Data.php';
class Home extends Data{
    private function getAssignedPathWhere(){
        $assigned_path=$this->Base->svar('user_assigned_stat')?$this->Base->svar('user_assigned_stat'):$this->Base->svar('user_assigned_path');
        return $assigned_path?"AND (path LIKE '".str_replace(',',"%' OR path LIKE '",$assigned_path.'')."%')":"";    
    }
    public function fetchExpiredDebts( $table_query ){
        $user_id=$this->Base->svar('user_id');
	$active_company_id=$this->Base->acomp('company_id');
        $user_level=$this->Base->svar('user_level');
        $table="(SELECT 
                        level,
                        path,
                        company_name,
                        label AS company_short,
                        deferment,
                        ROUND(SUM(IF(acc_debit_code=361,amount,IF(acc_credit_code=361,-amount,0))),2) sell,
                        ROUND(SUM(IF(acc_debit_code=631,amount,IF(acc_credit_code=631,-amount,0))),2) buy,
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
			WHERE active_company_id='$active_company_id'
                GROUP BY companies_list.company_id) AS expired";
        $where_path=$this->getAssignedPathWhere();
        $where="(sell>allow OR buy<>0) AND level<='$user_level' $where_path";
        $order='ORDER BY expday DESC';
        $this->Base->query("DROP TEMPORARY TABLE IF EXISTS exp_debts_temp;");
        $this->Base->query("
                CREATE TEMPORARY TABLE exp_debts_temp(
                company_short VARCHAR(45),
                credit_sum VARCHAR(10),
                debt_sum VARCHAR(10),
                debt_exp VARCHAR(10),
                deferment INT,
                m INT,
                d INT,
                path VARCHAR(45)
                ) ENGINE=Memory;
        ");
        $this->Base->query("
            INSERT INTO exp_debts_temp SELECT 
                company_short,
                buy,
                ROUND(sell),
                ROUND(IF(sell > allow, sell - allow, 0)) AS exp,
                deferment,
                FLOOR(expday / 30.417) m,
                ROUND(expday - FLOOR(expday / 30.417) * 30.417) d,
                REPLACE(path,'>','> ') path
             FROM $table WHERE $where"
        );
        $this->Base->query("SET @sexp=0.0,@sdebt=0.0");
        return array(
            'grid'=>$this->getGridData( 'exp_debts_temp', $table_query, '*,@sexp:=@sexp+debt_exp sx,@sdebt:=@sdebt+debt_sum sd', 'debt_sum>5', 'ORDER BY m DESC,d DESC' ),
            'summary'=>$this->Base->get_row("SELECT ROUND(@sexp/2,2) sexp,ROUND(@sdebt/2,2) sdebt")
        );
    }
    public function fetchManagerPayments($grid_query,$period){
        $user_level=$this->Base->svar('user_level');
        //$user_id=$this->Base->svar('user_id');
        $where_path=$this->getAssignedPathWhere();
        $select="
                DATE_FORMAT(cstamp,'%d.%m.%Y') cdate,
                cstamp,
		IF(company_vat_id,'','НП') not_tax_payer,
                label,
                description,
                IF(acc_debit_code=361,ROUND(amount,2),0) AS debit,
                IF(acc_credit_code=361,ROUND(amount,2),0) AS credit,
		REPLACE(path,'>','> ') path
                ";
        $table="
                companies_list
                JOIN companies_tree USING(branch_id)
                JOIN acc_trans ON company_id=passive_company_id";
        $where="
                (acc_credit_code=361) 
                $where_path
                AND SUBSTRING(cstamp,1,7)='$period'
                AND level<='$user_level'";
        $order="ORDER BY cstamp DESC";//
	$table_filter=$this->makeGridFilter($table,$grid_query);
	$table_filter=count($table_filter)?"WHERE ".implode(' AND ',$table_filter):'';	
	$this->Base->query("DROP TEMPORARY TABLE IF EXISTS mng_paym_temp;");
        $this->Base->query("
                CREATE TEMPORARY TABLE mng_paym_temp(
                cdate VARCHAR(10),
                cstamp VARCHAR(10),
		not_tax_payer VARCHAR(2),
                label VARCHAR(100),
                description VARCHAR(255),
                debit VARCHAR(10),
		credit VARCHAR(10),
                path VARCHAR(45)
                ) ENGINE=Memory;
        ");
        $this->Base->query("
            INSERT INTO mng_paym_temp SELECT * FROM (SELECT $select FROM $table WHERE $where $order) AS t $table_filter"
        );
        $paymets=array(
            'grid'=>$this->getGridData('mng_paym_temp','','*'),
            'summary'=>$this->Base->get_row("SELECT ROUND(SUM(credit),2) scredit,DATE_FORMAT(MIN(cstamp),'%d.%m.%Y') sdate,DATE_FORMAT(MAX(cstamp),'%d.%m.%Y') fdate FROM mng_paym_temp")
        );
        return $paymets;
    }
    public function fetchAvgRate($month,$year){
	$this->Base->set_level(3);
	$idate="{$year}-{$month}-01";
	$fdate=date("Y-m-t", strtotime($idate));
	$this->Base->LoadClass('Accounts');
	$ledger_361=$this->Base->Accounts->fetchAccountLedgerGrid(null,$idate,$fdate,361,false);
	
	$final_debt_sum=  str_replace(' ', '', $ledger_361['entries']['items'][0]['debit']);
	$period_payment_sum=  str_replace(' ', '', $ledger_361['entries']['items'][1]['credit']);
	$active_company_id=$this->Base->acomp('company_id');
	if( !$final_debt_sum || !$period_payment_sum ){
	    return false;
	}
	$sql_set="SET
		@period_start='$idate',
		@period_finish='$fdate',
		@period_payment_sum={$period_payment_sum},
		@final_debt_sum={$final_debt_sum},
		@first_closed='',
		@last_closed='',
		@avg_ratio=0,
		@total=0,
		@total_alt=0;";
	$this->Base->query($sql_set);
	$sql_calculate=" 
	    SELECT 
		DATE_FORMAT(dl.cstamp, '%Y-%m-%d') date1,
		doc_num,
		amount,
		doc_ratio,
		@final_debt_sum:=ROUND(@final_debt_sum - amount),
		IF(@final_debt_sum < 0,
		    @total_alt:=@total_alt + amount / doc_ratio,
		    ''),
		IF(@final_debt_sum < 0,
		    @total:=@total + amount,
		    ''),
		IF(@final_debt_sum < 0 AND @last_closed='', @last_closed:=dl.cstamp,''),
		@first_closed:=dl.cstamp
	    FROM
		document_list dl
		    JOIN
		document_trans dt USING (doc_id)
		    JOIN
		acc_trans USING (trans_id)
	    WHERE
		dl.active_company_id='$active_company_id'
		AND dt.type = '361_702' AND doc_type = 1 
		AND is_commited = 1 
		AND dl.cstamp < @period_finish 
		AND @final_debt_sum + @period_payment_sum > 0
	    ORDER BY dl.cstamp desc;";
	$this->Base->query($sql_calculate);
	$sql_select1=" 
	    SELECT 
		'$idate' idate,
		'$fdate' fdate,
		@period_payment_sum period_payment_sum,
		$final_debt_sum final_debt_sum,
		@first_closed first_closed,
		@last_closed last_closed,
		ROUND(@total,2) total,
		ROUND(@total_alt,2) total_alt,
		ROUND(@total / @total_alt, 2) avg_ratio;";
	return  $this->Base->get_row($sql_select1);
    }
    public $fns=array(
	
	'fetchAvgRate'=>'(string) month,(string) year'
    );
}
