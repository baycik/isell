<?php
/* Group Name: Статистика
 * User Level: 4
 * Plugin Name: Баланс
 * Plugin URI: 
 * Version: 1
 * Description: 
 * Author: baycik 2020
 * Author URI: 
 * Trigger before: Reports_balance
 */
class Reports_balance extends Catalog{
    private $all_active;
    public function __construct() {
	parent::__construct();
	$this->all_active=$this->request('all_active','bool');
        $this->fdate=$this->request('fdate','\d\d\d\d.\d\d.\d\d');
        $this->accounts=[
            'active'=>[
                '3'
            ],
            'passive'=>[
                '6'
            ]
        ];
    }
    private function getAssignedPathWhere(){
        $assigned_path=$this->Hub->svar('user_assigned_path');
        return $assigned_path?"AND (path LIKE '".str_replace(',',"%' OR path LIKE '",$assigned_path.'')."%')":"";    
    }
    private function getAccountLeftover($acc_code){
        $fdate_list=[$this->fdate];
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Hub->acomp('company_id');
        $leftovers=[];
        foreach($fdate_list as $i=>$fdate){
            $leftovers[]="
                COALESCE((SELECT 
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
                        $active_filter),'') leftover$i";
        }
        $leftover_queries= implode(',', $leftovers);
        $lvl3_sql="SELECT 
                acc_code,label acc_name,$leftover_queries
            FROM
                acc_tree
            WHERE
                acc_code LIKE '{$acc_code}__'
            HAVING leftover0
            ORDER BY acc_code
                ";
        return $this->get_list($lvl3_sql);;
    }
    
    
    private function getAccountTree($leftovers){
        $subtotals=[];
        foreach($leftovers as $leftover){
            $acc_code=$leftover['acc_code'];
            for($i=0;$i<count($acc_code);$i++){
                $group_code=substr($acc_code,0,$i);
                $subtotals[$group_code]=$subtotals[$group_code]??0+$leftover['leftover0'];
            }
        }
        
        
        
        
        
        
        $codes  = array_column($group_rows, 'acc_code');
        array_multisort($codes, SORT_STRING, $group_rows);
        
        
        
        
    }
        
        
        
        
        
        
        

        
        
    
    
    
    
    
    
    
    
    
    
    
    private function iso2dmy( $iso ){
	$chunks=  explode('-', $iso);
	return "$chunks[2].$chunks[1].$chunks[0]";
    }
    public function viewGet(){
        $this->Hub->set_level(4);
        
        $v=[
            'active'=>[],
            'passive'=>[]
        ];
        foreach( $this->accounts['active'] as $acc_code ){
            $v['active']= array_merge($v['active'],$this->getAccountLeftover($acc_code));
        }
        foreach( $this->accounts['passive'] as $acc_code ){
            //$v['passive']= array_merge($v['passive'],$this->getAccountLeftover($acc_code, $fdate_list));
        }
        
        foreach( $fdate_list as $i=>$fdate ){
            //$v['active_total_'.$i]='';
        }
        
        
        
        $v['input']=[
            'all_active'=>$this->all_active,
            'fdate0'=>$this->iso2dmy($this->fdate)
        ];
        
        //header("Content-type:text/plain");print_r($v);
        
	return $v;
    }
}