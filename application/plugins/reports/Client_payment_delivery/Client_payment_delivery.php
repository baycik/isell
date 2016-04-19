<?php
class Client_payment_delivery extends Catalog{
    private $idate;
    private $fdate;
    private $all_active;
    private $deliveries;
    private $payments;
    public function __construct() {
	$this->idate=$this->dmy2iso( $this->request('idate','\d\d.\d\d.\d\d\d\d') ).' 00:00:00';
	$this->fdate=$this->dmy2iso( $this->request('fdate','\d\d.\d\d.\d\d\d\d') ).' 23:59:59';
	$this->all_active=$this->request('all_active','bool');
	$this->deliveries=$this->request('deliveries','bool');
	$this->payments=$this->request('payments','bool');
	$this->filter_by=$this->request('filter_by','\w+');
	$this->filter_value=$this->request('filter_value');
	parent::__construct();
    }
    private function dmy2iso( $dmy ){
	$chunks=  explode('.', $dmy);
	return "$chunks[2]-$chunks[1]-$chunks[0]";
    }
    private function getAssignedPathWhere(){
        $assigned_path=$this->Base->svar('user_assigned_path');
        return $assigned_path?"AND (path LIKE '".str_replace(',',"%' OR path LIKE '",$assigned_path.'')."%')":"";    
    }
    private function getDirectionFilter(){
	$direction_filter=[];
	if($this->deliveries){
	    $direction_filter[]="acc_debit_code=361";
	}
	if($this->payments){
	    $direction_filter[]="acc_credit_code=361";
	}
	return $direction_filter?'('.implode(' OR ', $direction_filter).')':'0';
    }
    public function viewGet(){
	$direction_filter=$this->getDirectionFilter();
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Base->acomp('company_id');
        $user_level=$this->Base->svar('user_level');
        $path_filter=$this->getAssignedPathWhere();
        $having=$this->filter_value?"HAVING $this->filter_by LIKE '%$this->filter_value%'":"";
	$sql="
	    SELECT
		DATE_FORMAT(cstamp,'%d.%m.%Y') cdate,
                cstamp,
                label,
                description,
                IF(acc_debit_code=361,ROUND(amount,2),'') AS debit,
                IF(acc_credit_code=361,ROUND(amount,2),'') AS credit,
		path
	    FROM
		companies_list
		    JOIN 
		companies_tree USING(branch_id)
		    JOIN 
		acc_trans ON company_id=passive_company_id
	    WHERE
		$direction_filter
		AND cstamp>'$this->idate' 
		AND cstamp<'$this->fdate' 
		AND level<='$user_level'
                $path_filter
		$active_filter
	    $having
	    ORDER BY cstamp DESC";
	$rows=$this->get_list($sql);
	$total_debit=0;
        $total_credit=0;
        foreach( $rows as $row ){
            $total_debit+=$row->debit*1;
            $total_credit+=$row->credit*1;
        }
	$total_debit=round($total_debit,2);
	$total_credit=round($total_credit,2);
	return [
	    'total_debit'=>$total_debit,
	    'total_credit'=>$total_credit,
	    'rows'=>count($rows)?$rows:[[]]
	];
    }
}