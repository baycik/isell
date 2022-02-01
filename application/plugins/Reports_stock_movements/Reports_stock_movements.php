<?php
/* Group Name: Результаты деятельности
 * User Level: 3
 * Plugin Name: Движения товара
 * Plugin URI: 
 * Version: 1.0
 * Description: Выводит информацию о движениям по складу
 * Author: baycik 2018
 * Author URI: 
 * Trigger before: Reports_stock_movements
 */
class Reports_stock_movements extends Catalog{
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
	$this->idate=$this->request('idate').' 00:00:00';
	$this->fdate=$this->request('fdate').' 23:59:59';
	$this->all_active=$this->request('all_active','bool');
	$this->count_reclamations=$this->request('count_reclamations','bool',0);
	$this->include_vat=$this->request('include_vat','bool',0);
	$this->in_alt_currency=$this->request('in_alt_currency','bool',0);
	$this->show_entries=$this->request('show_entries','bool',0);
	$this->group_by_filter=$this->request('group_by_filter');
	$this->group_by=$this->request('group_by','\w+');
	if( !in_array($this->group_by, ['parent_id','product_code','analyse_type','analyse_brand','analyse_class','product_article']) ){
	    $this->group_by='parent_id';
	}
        
	$this->group_by2=$this->request('group_by2','\w+');
        $this->group_by2_comma=$this->group_by2?','.$this->group_by2:'';
        $this->group_by2_slash=$this->group_by2?",'/',$this->group_by2":'';
	parent::__construct();
    }
    private function iso2dmy( $iso ){
	$chunks=  explode(' ', $iso);
	$chunks=  explode('-', $chunks[0]);
	return "$chunks[2].$chunks[1].$chunks[0]";
    }
    public function viewGet(){
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Hub->acomp('company_id');
	$reclamation_filter=$this->count_reclamations?'':' AND is_reclamation=0';
        
        $having=$this->group_by_filter?"HAVING group_by LIKE '%".str_replace(",", "%' OR group_by LIKE '%", $this->group_by_filter)."%'":"";
        
        if( $this->include_vat ){
            $leftover_calc_mode='selfprice include_vat';
        } else {
            $leftover_calc_mode='selfprice';
        }
        
        
        $sql_tmp_drop="DROP TEMPORARY TABLE IF EXISTS tmp_stock_movements;";
        $sell_buy_table="
            SELECT
                product_code,
                
                SUM( IF(cstamp<'$this->idate',IF(doc_type=2,product_quantity,-product_quantity),0) ) stock_iqty,
                LEFTOVER_CALC(product_code,'$this->idate',0,'$leftover_calc_mode')/IF($this->in_alt_currency,doc_ratio,1) buy_iavg,
                
                SUM( IF(doc_type=2 AND cstamp>'$this->idate',self_price*IF($this->include_vat,dl.vat_rate/100+1,1)/IF($this->in_alt_currency,doc_ratio,1)*product_quantity,0) ) buy_sum,
                SUM( IF(doc_type=2 AND cstamp>'$this->idate',product_quantity,0) ) buy_qty,
                    
                SUM( IF(doc_type=1 AND cstamp>'$this->idate',self_price*IF($this->include_vat,dl.vat_rate/100+1,1)/IF($this->in_alt_currency,doc_ratio,1)*product_quantity,0) ) sell_sum,
                SUM( IF(doc_type=1 AND cstamp>'$this->idate',product_quantity,0) ) sell_qty,
                    
                SUM( IF(doc_type=2,product_quantity,-product_quantity) ) stock_fqty,
                LEFTOVER_CALC(product_code,'$this->fdate',0,'$leftover_calc_mode')/IF($this->in_alt_currency,doc_ratio,1) buy_favg
            FROM
                document_entries de
                    JOIN
                document_list dl USING(doc_id)
            WHERE
                (doc_type=1 OR doc_type=2) AND cstamp<'$this->fdate' AND is_commited=1 AND notcount=0 $active_filter $reclamation_filter
            GROUP BY product_code";
        $sql_tmp_create="CREATE TEMPORARY TABLE tmp_stock_movements AS(
            SELECT 
                product_code _product_code,
                ru,
                
                stock_iqty,
                stock_iqty*buy_iavg stock_isum,
                
                buy_qty,
                buy_sum,
                
                sell_qty,
                sell_sum,
                
                stock_fqty,
                stock_fqty*buy_favg stock_fsum,

                CONCAT(IF('$this->group_by'='parent_id',(SELECT label FROM stock_tree WHERE branch_id=se.parent_id),$this->group_by) $this->group_by2_slash) group_by,
                $this->group_by $this->group_by2_comma
            FROM
                stock_entries se
                    JOIN
                prod_list pl USING(product_code)
                    LEFT JOIN
                ($sell_buy_table) sellbuy USING(product_code)
            $having
            )";
        $sql_summary="
            SELECT 
                group_by,
                
                SUM(stock_iqty) total_stock_iqty,
                SUM(stock_isum) total_stock_isum,
                
                SUM(buy_qty) total_buy_qty,
                SUM(buy_sum) total_buy_sum,
                
                SUM(sell_qty) total_sell_qty,
                SUM(sell_sum) total_sell_sum,
                
                SUM(stock_fqty) total_stock_fqty,
                SUM(stock_fsum) total_stock_fsum
            FROM
                tmp_stock_movements
            GROUP BY
		$this->group_by $this->group_by2_comma
            ORDER BY
                total_sell_sum DESC,total_stock_fsum DESC
            ";
        //die($sql);
        
        $this->query($sql_tmp_drop);
        $this->query($sql_tmp_create);
        
        
        $summary_rows=$this->get_list($sql_summary);
        
        $grand_total_stock_isum=0;
        $grand_total_stock_iqty=0;

        $grand_total_buy_sum=0;
        $grand_total_buy_qty=0;        
        
        $grand_total_sell_sum=0;
        $grand_total_sell_qty=0;
        
        $grand_total_stock_fsum=0;
        $grand_total_stock_fqty=0;
        
        foreach( $summary_rows as $row ){
            $grand_total_stock_isum+=$row->total_stock_isum;
            $grand_total_stock_iqty+=$row->total_stock_iqty;
            
            $grand_total_buy_sum+=$row->total_buy_sum;
            $grand_total_buy_qty+=$row->total_buy_qty;
            
            $grand_total_sell_sum+=$row->total_sell_sum;
            $grand_total_sell_qty+=$row->total_sell_qty;
            
            $grand_total_stock_fsum+=$row->total_stock_fsum;
            $grand_total_stock_fqty+=$row->total_stock_fqty;
        }
        
        if( $this->show_entries ){
            $rows=$this->get_list("SELECT * FROM tmp_stock_movements WHERE GREATEST(stock_isum,buy_sum,sell_sum,stock_fsum)>0 ORDER BY group_by,_product_code");
        }
	$view=[
                'grand_total_stock_isum'=>round($grand_total_stock_isum,2),
                'grand_total_stock_iqty'=>round($grand_total_stock_iqty,2),
            
                'grand_total_buy_sum'=>round($grand_total_buy_sum,2),
                'grand_total_buy_qty'=>round($grand_total_buy_qty,2),
            
                'grand_total_sell_sum'=>round($grand_total_sell_sum,2),
                'grand_total_sell_qty'=>round($grand_total_sell_qty,2),
            
                'grand_total_stock_fsum'=>round($grand_total_stock_fsum,2),
                'grand_total_stock_fqty'=>round($grand_total_stock_fqty,2),
            
		'summary_rows'=>count($summary_rows)?$summary_rows:[[]],
		'rows'=>count($rows)?$rows:[[]],
		'input'=>[
		    'idate'=>$this->iso2dmy($this->idate),
		    'fdate'=>$this->iso2dmy($this->fdate),
		    'all_active'=>$this->all_active,
		    'count_reclamations'=>$this->count_reclamations,
		    'count_sells'=>$this->count_sells,
		    'in_alt_currency'=>$this->in_alt_currency,
                    'include_vat'=>$this->include_vat,
		    'group_by_client'=>$this->group_by_client,
		    'language'=>$this->language,
		    'group_by_filter'=>$this->group_by_filter,
		    'group_by'=>$this->group_by
		]
		];
	return $view;	
    }
}