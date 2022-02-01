<?php
/* Group Name: Результаты деятельности
 * User Level: 2
 * Plugin Name: Анализ отчетов маркетов
 * Plugin URI: http://isellsoft.com
 * Version: 0.1
 * Description: Анализ отчетов маркетов
 * Author: baycik 2017
 * Author URI: http://isellsoft.com
 * Trigger before: Reports_market_analyse
 */
class Reports_market_analyse extends Catalog{
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
        $this->group_by_filter=$this->request('group_by_filter');
	$this->group_by=$this->request('group_by','\w+');
	parent::__construct();
    }
    public function install(){
	$install_file=__DIR__."/install.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($install_file);
    }
    public function uninstall(){
	$uninstall_file=__DIR__."/uninstall.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($uninstall_file);
    }
    public $views=['string'];
    public function views($path){
	header("X-isell-type:OK");
	$this->load->view($path);
    }
    private function dmy2iso( $dmy ){
	$chunks=  explode('.', $dmy);
	return "$chunks[2]-$chunks[1]-$chunks[0]";
    }
    
    public $reportImport=[
        'pcomp_id'=>'int',
        'idate'=>'string',
        'fdate'=>'string',
        'comment'=>'string',
        'label'=>'string',
        'affiliate'=>'string',
        'article'=>'string',
        'left'=>'string',
        'sold'=>'string'];
    public function reportImport($pcomp_id,$idate,$fdate,$comment,$label,$affiliate,$article,$left,$sold){
        $Company=$this->Hub->load_model("Company");
        $Company->selectPassiveCompany($pcomp_id);
        
        $usd_ratio=$this->Hub->pref('usd_ratio');
        $acomp_id=$this->Hub->acomp('company_id');
        $pcomp_label=$this->Hub->pcomp('label');
        $sql_clear="DROP TEMPORARY TABLE IF EXISTS tmp_market_report";#TEMPORARY
        $sql_prepare="CREATE TEMPORARY TABLE tmp_market_report ( INDEX(product_code) ) ENGINE=MyISAM AS (
            SELECT
                $article article,
                product_code,
                ru product_name,
                $affiliate store_code,
                $sold sold,
                $left leftover
            FROM
                imported_data
                    LEFT JOIN
                prod_list pl ON product_article=$article
            WHERE
                $article<>'' 
                AND label='$label')";
        
        
        $sql_price_setup="SET @_product_code:='',@_acomp_id:=$acomp_id,@_pcomp_id:=$pcomp_id,@_to_cstamp:='{$fdate}';";
        $sql_price_clear="DROP TEMPORARY TABLE IF EXISTS tmp_market_report_price";#TEMPORARY
        $sql_price_prepare="CREATE TEMPORARY TABLE tmp_market_report_price ( INDEX(product_code) ) ENGINE=MyISAM AS (
            SELECT 
		product_code,ROUND(SUM(qty*invoice_price)/SUM(qty),2) avg_price 
	    FROM
		(SELECT 
		    product_code,
		    invoice_price,
		    @_quantity:=IF(product_code<>@_product_code AND @_product_code:=product_code OR 1,total,@_quantity)-product_quantity q,
		    product_quantity+LEAST(0,@_quantity) qty
		FROM
		    (SELECT 
			product_code,
			product_quantity,
			invoice_price*(1+dl.vat_rate/100) invoice_price,
			total
		    FROM
			(SELECT product_code,SUM(sold)+SUM(leftover) total FROM tmp_market_report GROUP BY product_code) tmr
			    JOIN
			document_entries de USING(product_code)
			    JOIN
			document_list dl USING (doc_id)
		    WHERE
			cstamp < @_to_cstamp
			AND active_company_id=@_acomp_id
			AND passive_company_id=@_pcomp_id
		    ORDER BY product_code,cstamp DESC) sub
		) sub2
            WHERE qty>0
            GROUP BY product_code)";
        
        $sql_price_missing_clear="DROP TEMPORARY TABLE IF EXISTS tmp_market_report_missing";#TEMPORARY
        $sql_price_missing_fill="CREATE TEMPORARY TABLE tmp_market_report_missing ( INDEX(product_code) ) AS 
            (SELECT product_code,GET_PRICE(product_code,$pcomp_id,$usd_ratio) avg_price
                FROM tmp_market_report
                WHERE product_code NOT IN (SELECT product_code FROM tmp_market_report_price)
                GROUP BY product_code)";
        $sql_price_complete="INSERT INTO tmp_market_report_price SELECT * FROM tmp_market_report_missing";
        
        $this->query($sql_clear);
        $this->query($sql_prepare);
        $this->query($sql_price_setup);
        $this->query($sql_price_clear);
        $this->query($sql_price_prepare);
        $this->query($sql_price_missing_clear);
        $this->query($sql_price_missing_fill);
        $this->query($sql_price_complete);
        
        $this->query("START TRANSACTION");
        $report_id=$this->reportCreate($idate,$fdate,$comment,$pcomp_id,$pcomp_label);
        $this->reportSave($report_id);
        $this->reportFillSummaries($report_id);
        $this->importerClear($label);
        $this->query("COMMIT");
        return true;
    }
    
    private function importerClear($label){
        $this->query("DELETE FROM imported_data WHERE label='$label'");
    }
    
    private function reportCreate($idate,$fdate,$comment,$pcomp_id,$pcomp_label){
        $this->query("INSERT INTO plugin_market_rpt_list SET idate='$idate',fdate='$fdate',comment='$comment',pcomp_id='$pcomp_id',pcomp_label='$pcomp_label'");
        return $this->db->insert_id();
    }
    
    private function reportFillSummaries($report_id){
        $sql_summary_update="UPDATE 
                plugin_market_rpt_list ml
            JOIN
                (SELECT ROUND(SUM(sold_sum),2) sold_sum, ROUND(SUM(leftover_sum),2) leftover_sum FROM plugin_market_rpt_entries WHERE report_id='$report_id') summary
            SET ml.sold_sum=summary.sold_sum,ml.leftover_sum=summary.leftover_sum
            WHERE report_id='$report_id'";
        $this->query($sql_summary_update);
    }

    private function reportSave($report_id){
        $sql_prepare="INSERT INTO plugin_market_rpt_entries (
	    SELECT
                $report_id AS report_id,
                product_code,
                article,
                product_name,
                store_code,
                sold,
                leftover,
                avg_price,
                '' group_by,
                avg_price*sold sold_sum,
                avg_price*leftover leftover_sum
            FROM
                tmp_market_report
                    LEFT JOIN
                tmp_market_report_price USING(product_code)
            ORDER BY sold_sum<>0,sold_sum DESC,leftover_sum DESC
	    )";
        $this->query($sql_prepare);        
    }
    
    public $reportDelete=['report_id'=>'int'];
    public function reportDelete( $report_id ){
        $this->query("START TRANSACTION");
        $this->query("DELETE FROM plugin_market_rpt_entries WHERE report_id='$report_id'");
        $this->query("DELETE FROM plugin_market_rpt_list WHERE report_id='$report_id'");
        $this->query("COMMIT");
        return true;
    }
    

    public function viewGet(){
        $report_id=$this->request('report_id','int');
        if( !$report_id ){
            return [];
        }
        $sql_fetch="
            SELECT
		*
            FROM
                plugin_market_rpt_entries
		    JOIN
		prod_list USING(product_code)
            WHERE
                report_id='$report_id'";
	$having=$this->group_by_filter?"HAVING group_by LIKE '%$this->group_by_filter%'":"";
        $sql_summary_type_fetch="
            SELECT
		$this->group_by group_by,
                SUM(sold) sold,
                SUM(avg_price*sold) sold_sum,
                SUM(leftover) leftover,
                SUM(avg_price*leftover) leftover_sum
            FROM
                plugin_market_rpt_entries
		    JOIN
		prod_list USING(product_code)
            WHERE
                report_id='$report_id'
            GROUP BY $this->group_by 
	    $having
            ORDER BY sold_sum DESC";

	$rows=$this->get_list($sql_fetch);
	$sum_rows=$this->get_list($sql_summary_type_fetch);
	
	
	$report_header=$this->get_row("SELECT * FROM plugin_market_rpt_list WHERE report_id='$report_id'");
	$report_header->group_by=$this->group_by;
	$report_header->group_by_filter=$this->group_by_filter;
	
	return [
	    'rows'=>count($rows)?$rows:[[]],
	    'sum_rows'=>count($sum_rows)?$sum_rows:[[]],
	    'sum'=>$this->calc_sum($sum_rows),
	    'input'=>$report_header
	];
    }
    private function calc_sum($sum_rows){
	$sum=[
	    'sum_sold'=>0,
	    'sum_leftover'=>0,
	    'sum_sold_sum'=>0,
	    'sum_leftover_sum'=>0
	];
	foreach($sum_rows as $row){
	    $sum['sum_sold']+=$row->sold;
	    $sum['sum_leftover']+=$row->leftover;
	    $sum['sum_sold_sum']+=$row->sold_sum;
	    $sum['sum_leftover_sum']+=$row->leftover_sum;
	}
	return $sum;
    }
    
    
    public $listFetch=['offset'=>'int','limit'=>'int','sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json'];
    public function listFetch($offset,$limit,$sortby,$sortdir,$filter=null){
	if( empty($sortby) ){
	    $sortby="idate";
	}
	$having=$this->makeFilter($filter);
        $sql="SELECT
                *
            FROM
                plugin_market_rpt_list
            HAVING $having
	    ORDER BY $sortby $sortdir
	    LIMIT $limit OFFSET $offset";
	return $this->get_list($sql);
    }
}