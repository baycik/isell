<?php
/* Group Name: Статистика
 * User Level: 4
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
	parent::__construct();
	$this->all_active=$this->request('all_active','bool');
        $this->idate= $this->request('idate','\d\d\d\d.\d\d.\d\d');
        $this->fdate=$this->request('fdate','\d\d\d\d.\d\d.\d\d');
        $this->accounts=[
            'active'=>[
                '301'
            ],
            'passive'=>[
                '63'
            ]
        ];
    }
    private function getAssignedPathWhere(){
        $assigned_path=$this->Hub->svar('user_assigned_path');
        return $assigned_path?"AND (path LIKE '".str_replace(',',"%' OR path LIKE '",$assigned_path.'')."%')":"";    
    }
    private function getAccountLeftover($acc_code,$fdate_list){
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Hub->acomp('company_id');
        $leftovers=[];
        foreach($fdate_list as $i=>$fdate){
            $leftovers[]="
                (SELECT 
                        ROUND(SUM(amount) / 1000, 0)
                    FROM
                        acc_trans
                    WHERE
                        cstamp<'$fdate'
                        AND acc_debit_code LIKE CONCAT(acc_code,'%')
                        $active_filter) 
                -
                
                (SELECT 
                        ROUND(SUM(amount) / 1000, 0)
                    FROM
                        acc_trans
                    WHERE
                        cstamp<'$fdate'
                        AND acc_credit_code LIKE CONCAT(acc_code,'%')
                        $active_filter) leftover$i";
        }
        $leftover_queries= implode(',', $leftovers);
        $sql="SELECT 
                acc_code,label acc_name,$leftover_queries
            FROM
                acc_tree
            WHERE
                acc_code LIKE '{$acc_code}_'";
        return $this->get_list($sql);
    }
    private function iso2dmy( $iso ){
	$chunks=  explode('-', $iso);
	return "$chunks[2].$chunks[1].$chunks[0]";
    }
    public function viewGet(){
        
        $fdate_list=[$this->fdate];
        
        $v=[
            'active'=>[],
            'passive'=>[]
        ];
        foreach( $this->accounts['active'] as $acc_code ){
            $v['active']= array_merge($v['active'],$this->getAccountLeftover($acc_code, $fdate_list));
        }
        foreach( $this->accounts['passive'] as $acc_code ){
            $v['passive']= array_merge($v['passive'],$this->getAccountLeftover($acc_code, $fdate_list));
        }
        
        foreach( $fdate_list as $i=>$fdate ){
            $v['active_total_'.$i]=
        }
        
        
        
        $v['input']=[
            'all_active'=>$this->all_active,
            'fdate0'=>$this->iso2dmy($this->fdate)
        ];
        
        print_r($v);
        
	return $v;
    }
}