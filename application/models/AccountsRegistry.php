<?php
require_once 'AccountsCore.php';
class AccountsRegistry extends AccountsCore{
    public $min_level=3;
    
    public $registryFetch=[
	'period'=>'string',
	'mode'=>'string',
	'direction'=>'string',
	'page'=>['int',1],
	'rows'=>['int',1000]];
    public function registryFetch($period='',$mode='', $direction='sell',$page=1,$rows=1000){
	$offset=($page-1)*$rows;
	if( $offset<0 ){
	    $offset=0;
	}
	$having=$this->decodeFilterRules();
	if( $direction=='sell' ){
	    $direction_filter="(doc_type=1 OR doc_type=3)";
	} else {
	    $direction_filter="(doc_type=2 OR doc_type=4)";
	}
        
        $period_parts= explode('-', $period);
        if( is_numeric($period_parts[1]) ){
            $period_filter = "dl.cstamp LIKE '{$period}%'";
        } else {
            switch( $period_parts[1] ){
                case 'I':
                    $period_filter = "dl.cstamp LIKE '{$period_parts[0]}-01%' OR dl.cstamp LIKE '{$period_parts[0]}-02%' OR dl.cstamp LIKE '{$period_parts[0]}-03%'";
                    break;
                case 'II':
                    $period_filter = "dl.cstamp LIKE '{$period_parts[0]}-04%' OR dl.cstamp LIKE '{$period_parts[0]}-05%' OR dl.cstamp LIKE '{$period_parts[0]}-06%'";
                    break;
                case 'III':
                    $period_filter = "dl.cstamp LIKE '{$period_parts[0]}-07%' OR dl.cstamp LIKE '{$period_parts[0]}-08%' OR dl.cstamp LIKE '{$period_parts[0]}-09%'";
                    break;
                case 'IV':
                    $period_filter = "dl.cstamp LIKE '{$period_parts[0]}-10%' OR dl.cstamp LIKE '{$period_parts[0]}-11%' OR dl.cstamp LIKE '{$period_parts[0]}-12%'";
                    break;
            }
        }
        
        
	$active_company_id=$this->Hub->acomp('company_id');
	$this->query("DROP TEMPORARY TABLE IF EXISTS tax_bill_reg");
	$tmp_sql="CREATE TEMPORARY TABLE tax_bill_reg ( INDEX(doc_view_id) ) ENGINE=MyISAM AS ( SELECT
		dl.doc_id,
		doc_view_id,
		doc_type_name,
		CONCAT(icon_name,' ',doc_type_name) doc_type,
		view_num tax_bill_num,
		DATE_FORMAT(dl.cstamp,'%d.%m.%Y') cdate,
		DATE_FORMAT(dvl.tstamp,'%d.%m.%Y') tax_bill_date,
		IF(company_tax_id,company_name,CONCAT(company_name,' (НЕПЛАТЕЛЬЩИК НАЛОГА)')) company_name,
		company_tax_id company_tax_id,
		(SELECT ROUND(amount,2) FROM acc_trans JOIN document_trans dt USING(trans_id) WHERE dt.doc_id=dl.doc_id AND dt.trans_role='total') total,
		(SELECT ROUND(amount,2) FROM acc_trans JOIN document_trans dt USING(trans_id) WHERE dt.doc_id=dl.doc_id AND dt.trans_role='vat') vat,
		(SELECT ROUND(amount,2) FROM acc_trans JOIN document_trans dt USING(trans_id) WHERE dt.doc_id=dl.doc_id AND dt.trans_role='vatless') vatless
	    FROM
		document_list dl
		    JOIN
		document_types USING(doc_type)
		    JOIN
		companies_list ON company_id=passive_company_id
		    LEFT JOIN
		document_view_list dvl ON dl.doc_id=dvl.doc_id AND view_role='tax_bill'
	    WHERE
		active_company_id='$active_company_id'
                AND ($period_filter)
		AND is_commited=1
		AND $direction_filter
	    HAVING $having
            ORDER BY SUBSTRING(dl.cstamp,1,10),doc_view_id )";
	$this->query($tmp_sql);
	
	if( $mode=='group_by_comp' ){
	    $sql="SELECT 
		    company_name,
		    company_tax_id,
		    SUM(total) total,
		    SUM(vatless) vatless,
		    SUM(vat) vat 
		FROM 
		    tax_bill_reg 
		GROUP BY company_tax_id
		LIMIT $rows OFFSET $offset";
            $sql_sub="SELECT 
                    *,
                    COUNT(*) count
                FROM
                    (SELECT
                        SUM(total) sum_total,
                        SUM(vatless) sum_vatless,
                        SUM(vat) sum_vat
                    FROM tax_bill_reg
                    GROUP BY company_tax_id) t";
        } else {
	    $sql="SELECT * FROM tax_bill_reg LIMIT $rows OFFSET $offset";
            $sql_sub="SELECT 
                    COUNT(*) count,
                    SUM(total) sum_total,
                    SUM(vatless) sum_vatless,
                    SUM(vat) sum_vat
                FROM tax_bill_reg";
	}
	$rows=$this->get_list($sql);
        $sub_totals=$this->get_row($sql_sub);
	if( !count($rows) ){
	    $rows=[[]];
	}
	return [
	    'rows'=>$rows,
	    'sub_totals'=>$sub_totals,
	    'total'=>$sub_totals->count
	];
    }
    
    public $registryViewGet=['period'=>'string','mode'=>'string','out_type'=>['string','.print']];
    public function registryViewGet($period,$mode,$out_type){
	$blank_set=$this->Hub->pref('blank_set');
	$dump=[
	    'tpl_files'=>$blank_set.'/AccDocumentRegistry.xlsx',
	    'title'=>"Реестр документов",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'view'=>[
		'period'=>$period,
		'buy'=>$this->registryFetch($period, $mode,'buy'),
		'sell'=>$this->registryFetch($period, $mode,'sell')
	    ]
	];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}
