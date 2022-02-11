<?php
/* Group Name: Работа с клиентами
 * User Level: 2
 * Plugin Name: Оплаты и отгрузки клиентам
 * Plugin URI: 
 * Version: 1
 * Description: Выводит информацию о продажах и оплатах клиентов
 * Author: baycik 2017
 * Author URI: 
 * Trigger before: Reports_client_payment_delivery
 */
class Reports_client_payment_delivery extends Catalog{
    private $idate;
    private $fdate;
    private $all_active;
    private $deliveries;
    private $payments;
    public function check( &$var, $type=null ){
        $type= str_replace('?', '', $type);
	switch( $type ){
	    case 'raw':
		break;
	    case 'int':
		$var=(int) $var;
		break;
	    case 'float':
	    case 'double':
		$var=(float) $var;
		break;
	    case 'bool':
		$var=$var?1:0;
		break;
	    case 'escape':
	    case 'string':
                $var=  addslashes( $var );
                break;
	    case 'json':
	    case 'array':
	    case '?array':
                if( is_array($var) ){
                    break;//native post array
                }
                $var= trim($var, "\"");
                $result= json_decode( $var,true );
                if( json_last_error()!=JSON_ERROR_NONE ){
                    $var=stripslashes($var);
                    $result= json_decode( $var,true );
                }
                if( json_last_error()!=JSON_ERROR_NONE ){
                    throw new Exception('JSON error: '.json_last_error_msg(),500);
                }
                $var=$result;
                break;
            case 'object':
            case '?object':
                $var= trim($var, "\"");
                $result= json_decode( $var,false ); 
                if( json_last_error()!=JSON_ERROR_NONE ){
                    $var=stripslashes($var);
                    $result= json_decode( $var,false );
                }
                if( json_last_error()!=JSON_ERROR_NONE ){
                    throw new Exception('JSON error: '.json_last_error_msg(),500);
                }
                $var=$result;
                break;
	    default:
		if( $type ){
		    $matches=[];
		    preg_match('/'.$type.'/u', $var, $matches);
		    $var=  isset($matches[0])?$matches[0]:null;
		} else {
		    $var=  addslashes( $var );
		}
	}
        return $var;
    }
    public function request( $name, $type=null, $default=null ){
	$value=$this->input->get_post($name);
	if( !is_array($value) && strlen($value)==0 ){
	    return $default;
	}
        return $this->check($value,$type);
    }
    public function __construct() {
	$this->idate=$this->dmy2iso( $this->request('idate','\d\d.\d\d.\d\d\d\d') );
	$this->fdate=$this->dmy2iso( $this->request('fdate','\d\d.\d\d.\d\d\d\d') );
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
    private function iso2dmy( $iso ){
	$chunks=  explode('-', $iso);
	return "$chunks[2].$chunks[1].$chunks[0]";
    }
    private function or_like($field,$value){
	$cases=explode(",",$value);
	$filter="";
	foreach($cases as $case){
	    if($case){
		$filter.=" OR $field LIKE '%$case%'";
	    }
	}
	return $filter;
    }
    private function getAssignedPathWhere(){
        $assigned_path=$this->Hub->svar('user_assigned_path');
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
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Hub->acomp('company_id');
        $user_level=$this->Hub->svar('user_level');
        $path_filter=$this->getAssignedPathWhere();
        $having=$this->filter_value?"HAVING 0 ".$this->or_like($this->filter_by,$this->filter_value):"";
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
		AND cstamp>'$this->idate 00:00:00' 
		AND cstamp<'$this->fdate 23:59:59' 
		AND level<='$user_level'
                $path_filter
		$active_filter
	    $having
	    ORDER BY cstamp DESC";
	$rows=$this->get_list($sql);
	$total_debit=0;
        $total_credit=0;
        foreach( $rows as $row ){
            $total_debit+=(float) $row->debit*1;
            $total_credit+=(float) $row->credit;
        }
	$total_debit=round($total_debit,2);
	$total_credit=round($total_credit,2);
	return [
	    'total_debit'=>$total_debit,
	    'total_credit'=>$total_credit,
	    'rows'=>count($rows)?$rows:[[]],
	    'input'=>[
		'idate'=>$this->iso2dmy($this->idate),
		'fdate'=>$this->iso2dmy($this->fdate),
		'all_active'=>$this->all_active,
		'deliveries'=>$this->deliveries,
		'payments'=>$this->payments,
		'filter_by'=>$this->filter_by,
		'filter_value'=>$this->filter_value
	    ]
	];
    }
}