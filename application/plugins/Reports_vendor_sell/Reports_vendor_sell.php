<?php
/* Group Name: Результаты деятельности
 * User Level: 3
 * Plugin Name: Отчет реализации
 * Plugin URI: 
 * Version: 1.0
 * Description: Выводит информацию о реализованной продукции
 * Author: baycik 2018
 * Author URI: 
 * Trigger before: Reports_vendor_sell
 */
class Reports_vendor_sell extends Catalog{
    private $idate;
    private $fdate;
    private $all_active;
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
	$this->count_reclamations=$this->request('count_reclamations','bool',0);
	$this->count_buy_price=$this->request('count_buy_price','bool',0);
	$this->in_alt_currency=$this->request('in_alt_currency','bool',0);
	$this->group_by_filter=$this->request('group_by_filter');
	$this->group_by=$this->request('group_by','\w+');
        $this->path_include=$this->request('path_include');
        $this->path_exclude=$this->request('path_exclude');
	if( !in_array($this->group_by, ['parent_id','product_code','analyse_type','analyse_brand','analyse_class','product_article']) ){
	    $this->group_by='parent_id';
	}
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
    private function and_like($field,$value){
	$cases=explode(",",$value);
	$filter="";
	foreach($cases as $case){
	    if($case){
		$filter.=" AND $field LIKE '%$case%'";
	    }
	}
	return $filter;
    }
    private function or_like($field,$value){
	$cases=explode(",",$value);
	$filter=" AND (0";
	foreach($cases as $case){
	    if($case){
		$filter.=" OR $field LIKE '%$case%'";
	    }
	}
	return $filter.")";
    }
    public function viewGet(){
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Hub->acomp('company_id');
	$reclamation_filter=$this->count_reclamations?'':' AND is_reclamation=0';
	$leftover_price=$this->count_buy_price?'invoice_price':'self_price';
        $having=$this->group_by_filter?"HAVING group_by LIKE '%$this->group_by_filter%'":"";
        $vendor_code_filter='';
        if( $this->path_exclude || $this->path_include ){
            $or_like=$this->or_like('ct.path ', $this->path_include);
	    $and_like=$this->and_like('ct.path NOT ', $this->path_exclude);
            $this->query("DROP TEMPORARY TABLE IF EXISTS tmp_vendor_codes;");
            $this->query("CREATE TEMPORARY TABLE tmp_vendor_codes AS (
                    SELECT 
                        product_code
                    FROM
                        document_entries
                    JOIN document_list USING (doc_id)
                    JOIN companies_list ON doc_type = 2 AND passive_company_id = company_id
                    JOIN companies_tree ct USING(branch_id)
                    WHERE 1 $and_like $or_like
                );");
	    $vendor_code_filter="AND de.product_code IN (SELECT product_code FROM tmp_vendor_codes)";
        }
        $this->query("DROP TEMPORARY TABLE IF EXISTS tmp_vendor_sells;");
        $this->query("CREATE TEMPORARY TABLE tmp_vendor_sells AS (
                SELECT *,
                    ROUND(sell_qty*buy_avg,2) self_sum 
                FROM(
                    SELECT 
                        product_code,
                        ru,
                        SUM( IF(doc_type=2,product_quantity,-product_quantity) ) stock_qty,
                        SUM( IF(doc_type=2,$leftover_price/IF($this->in_alt_currency,doc_ratio,1)*product_quantity,0) )/SUM( IF(doc_type=2,product_quantity,0) ) buy_avg,
                        SUM( IF(doc_type=1 AND cstamp>'$this->idate 00:00:00',product_quantity,0) ) sell_qty
                    FROM
                        document_entries de
                            JOIN 
                        document_list dl USING (doc_id)
                            JOIN
                        prod_list pl USING (product_code)
                    WHERE
                        (doc_type=1 OR doc_type=2) AND cstamp<'$this->fdate 23:59:59' AND is_commited=1 AND notcount=0 $vendor_code_filter $active_filter $reclamation_filter
                    GROUP BY product_code) t
            );");
        $sql="
            SELECT 
                IF('$this->group_by'='parent_id',(SELECT label FROM stock_tree WHERE branch_id=se.parent_id),$this->group_by) group_by,
                SUM(self_sum) self_sum,
                SUM(buy_avg*stock_qty) stock_sum,
                SUM(stock_qty) stock_sum_qty,
                SUM(sell_qty) sell_sum_qty
            FROM
                tmp_vendor_sells
                    JOIN
                stock_entries se USING (product_code)
                    JOIN
                prod_list pl USING (product_code)
            GROUP BY
		$this->group_by
            $having
            ORDER BY
                self_sum DESC,stock_sum DESC";
        
        $rows=$this->get_list($sql);
        $total_sell=0;
        $total_sell_qty=0;
        $total_stock=0;
        $total_stock_qty=0;
        foreach( $rows as $row ){
            $total_sell+=$row->self_sum;
            $total_sell_qty+=$row->sell_sum_qty;
            $total_stock+=$row->stock_sum;
            $total_stock_qty+=$row->stock_sum_qty;
        }
        foreach( $rows as $row ){
            $row->sell_proc=    $total_sell?round( $row->self_sum/$total_sell, 4):'';
            $row->stock_proc=   $total_stock?round( $row->stock_sum/$total_stock, 4):'';
	    $this->clear_zero($row);
        }

        
        
        $entries=$this->get_list("SELECT *,ROUND(buy_avg*stock_qty,2) stock_sum FROM tmp_vendor_sells HAVING stock_sum<>0 OR self_sum<>0 ORDER BY stock_sum DESC,self_sum DESC");
	$view=[
                'total_sell'=>round($total_sell,2),
                'total_stock'=>round($total_stock,2),
                'total_sell_qty'=>round($total_sell_qty,2),
                'total_stock_qty'=>round($total_stock_qty,2),
		'rows'=>count($rows)?$rows:[[]],
                'entries'=>count($entries)?$entries:[],
                'input'=>[
		    'idate'=>$this->iso2dmy($this->idate),
		    'fdate'=>$this->iso2dmy($this->fdate),
		    'all_active'=>$this->all_active,
		    'count_sells'=>$this->count_sells,
		    'count_reclamations'=>$this->count_reclamations,
		    'count_sells'=>$this->count_sells,
		    'in_alt_currency'=>$this->in_alt_currency,
		    'group_by_client'=>$this->group_by_client,
		    'language'=>$this->language,
		    'group_by_filter'=>$this->group_by_filter,
		    'group_by'=>$this->group_by,
		    'path_include'=>$this->path_include,
		    'path_exclude'=>$this->path_exclude
		]
		];
	return $view;	
    }
    private function clear_zero(&$row){
	foreach($row as &$field){
	    if(is_numeric($field) && $field==0){
		$field='';
	    }
	}
    }
}