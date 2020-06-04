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
                '3__'
            ],
            'passive'=>[
                '63_',
                '60',
                '66'
            ]
        ];
    }
    
    private function getAccountLeftover($acc_code,$sign=1){
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
                        $active_filter),'')*$sign leftover$i";
        }
        $leftover_queries= implode(',', $leftovers);
        $lvl3_sql="SELECT 
                acc_code,CONCAT(REPEAT('  ',LENGTH(acc_code)),label) acc_name,$leftover_queries
            FROM
                acc_tree
            WHERE
                acc_code LIKE '{$acc_code}'
            HAVING leftover0
            ORDER BY acc_code
                ";
        return $this->get_list($lvl3_sql);;
    }
    
    
    private function getAccountTree($table_rows){
        $grandtotal=0;
        $subtotals=[];
        foreach($table_rows as &$row){
            $acc_code=$row->acc_code;
            for($i=1;$i<strlen($acc_code);$i++){
                $group_code=substr($acc_code,0,$i);
                $subtotals[$group_code]=($subtotals[$group_code]??0)+$row->leftover0;
            }
            $grandtotal+=$row->leftover0;
        }
        foreach($subtotals as $group_code=>$subtotal){
            $sql="
                SELECT
                    acc_code,CONCAT(REPEAT('  ',LENGTH(acc_code)),label) acc_name, '$subtotal' leftover0
                FROM
                    acc_tree
                WHERE
                    acc_code='$group_code'
                ";
            $table_rows[]=$this->get_row($sql);
        }
        $codes  = array_column($table_rows, 'acc_code');
        array_multisort($codes, SORT_STRING, $table_rows);
        
        return (object)[
            'total'=>$grandtotal,
            'rows'=>$table_rows
                ];
    }
    
    private function iso2dmy( $iso ){
	$chunks=  explode('-', $iso);
	return "$chunks[2].$chunks[1].$chunks[0]";
    }
    
    public function viewGet(){
        $this->Hub->set_level(4);
        
        $active=[];
        $passive=[];
        foreach( $this->accounts['active'] as $acc_code ){
            $active= array_merge($active,$this->getAccountLeftover($acc_code));
        }
        foreach( $this->accounts['passive'] as $acc_code ){
            $passive= array_merge($passive,$this->getAccountLeftover($acc_code,-1));
        }
        $view=[
            'input'=>[
                'all_active'=>$this->all_active,
                'fdate0'=>$this->iso2dmy($this->fdate)
            ],
            'active'=>$this->getAccountTree($active),
            'passive'=>$this->getAccountTree($passive)
        ];
	return $view;
    }
}