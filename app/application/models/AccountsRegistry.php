<?php
require_once 'AccountsCore.php';
class AccountsRegistry extends AccountsCore{
    public function registryFetch($period='',$mode='', $direction='sell'){
	$page=$this->request('page','int',1);
	$rows=$this->request('rows','int',1000);
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
	$active_company_id=$this->Base->acomp('company_id');
	$this->query("DROP TEMPORARY TABLE IF EXISTS tax_bill_reg");
	$tmp_sql="CREATE TEMPORARY TABLE tax_bill_reg ( INDEX(doc_view_id) ) ENGINE=MyISAM AS ( SELECT
		dl.doc_id,
		doc_view_id,
		doc_type_name,
		CONCAT(icon_name,' ',doc_type_name) doc_type,
		view_num tax_bill_num,
		DATE_FORMAT(dl.cstamp,'%d.%m.%Y') cdate,
		DATE_FORMAT(dvl.tstamp,'%d.%m.%Y') tax_bill_date,
		IF(company_vat_id,company_name,'НЕПЛАТЕЛЬЩИК НАЛОГА') company_name,
		company_vat_id company_tax_id,
		(SELECT ROUND(amount,2) FROM acc_trans JOIN document_trans dt USING(trans_id) WHERE dt.doc_id=dl.doc_id AND trans_role='total') total,
		(SELECT ROUND(amount,2) FROM acc_trans JOIN document_trans dt USING(trans_id) WHERE dt.doc_id=dl.doc_id AND trans_role='vat') vat,
		(SELECT ROUND(amount,2) FROM acc_trans JOIN document_trans dt USING(trans_id) WHERE dt.doc_id=dl.doc_id AND trans_role='vatless') vatless
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
		AND dl.cstamp LIKE '$period%'
		AND is_commited=1
		AND $direction_filter
	    HAVING $having)";
	$this->query($tmp_sql);
	
	$sql_sub="SELECT 
		COUNT(*) count,
		SUM(total) sum_total,
		SUM(vatless) sum_vatless,
		SUM(vat) sum_vat
	    FROM tax_bill_reg";
	$sub_totals=$this->get_row($sql_sub);
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
	} else {
	    $sql="SELECT * FROM tax_bill_reg LIMIT $rows OFFSET $offset";
	}
	$rows=$this->get_list($sql);
	if( !count($rows) ){
	    $rows=[[]];
	}
	return [
	    'rows'=>$rows,
	    'sub_totals'=>$sub_totals,
	    'total'=>$sub_totals->count
	];
    }
    public function registryViewGet(){
	$period=$this->request('period');
	$mode=$this->request('mode');
	$out_type=$this->request('out_type','string','.print');
	$blank_set=$this->Base->pref('blank_set');
	$dump=[
	    'tpl_files'=>$blank_set.'/AccDocumentRegistry.xlsx',
	    'title'=>"Реестр документов",
	    'user_data'=>[
		'email'=>$this->Base->svar('pcomp')?$this->Base->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'view'=>[
		'period'=>$period,
		'buy'=>$this->registryFetch($period, $mode,'buy'),
		'sell'=>$this->registryFetch($period, $mode,'sell')
	    ]
	];
	$ViewManager=$this->Base->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}
