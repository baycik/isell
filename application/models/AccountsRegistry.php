<?php
require_once 'AccountsCore.php';
class AccountsRegistry extends AccountsCore{
    public $min_level=3;
    public $xml_filter = false;
    
    public $registryFetch=[
	'period'=>'string',
	'mode'=>'string',
	'direction'=>'string',
	'page'=>['int',1],
	'rows'=>['int',1000]
        ];
    public function registryFetch($period='',$mode='', $direction='sell',$page=1, $rows=1000){
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
        $invalid_condition = "       
                total <= 0 
                OR doc_view_id IS NULL
                OR LENGTH(company_tax_id) = 10 AND company_tax_id2 = ''
                OR LENGTH(company_tax_id) = 12  AND company_tax_id2 != ''
                OR LENGTH(company_tax_id) NOT IN (10,12)
                ";
        if($this->xml_filter){
           $having .= " AND invalid = 0 ";
        }
	$active_company_id=$this->Hub->acomp('company_id');
	$this->query("DROP TEMPORARY TABLE IF EXISTS tax_bill_reg");
        $this->query("SET SESSION group_concat_max_len = 1024*5;");
	$tmp_sql="CREATE TEMPORARY TABLE tax_bill_reg ( INDEX(doc_view_id) ) ENGINE=MyISAM AS ( 
            SELECT
                *,
                ($invalid_condition) AS invalid
            FROM(    
            SELECT
		dl.doc_id,
		doc_view_id,
		doc_type_name,
		CONCAT(icon_name,' ',doc_type_name) doc_type,
		view_num tax_bill_num,
		DATE_FORMAT(dl.cstamp,'%d.%m.%Y') cdate,
		DATE_FORMAT(dvl.tstamp,'%d.%m.%Y') tax_bill_date,
		IF(company_tax_id,company_name,CONCAT(company_name,' (НЕПЛАТЕЛЬЩИК НАЛОГА)')) company_name,
		company_tax_id,
		company_tax_id2,
                dl.cstamp,
		(SELECT ROUND(amount,2) FROM acc_trans JOIN document_trans dt USING(trans_id) WHERE dt.doc_id=dl.doc_id AND dt.trans_role='total') total,
		(SELECT ROUND(amount,2) FROM acc_trans JOIN document_trans dt USING(trans_id) WHERE dt.doc_id=dl.doc_id AND dt.trans_role='vat') vat,
		(SELECT ROUND(amount,2) FROM acc_trans JOIN document_trans dt USING(trans_id) WHERE dt.doc_id=dl.doc_id AND dt.trans_role='vatless') vatless,
                (SELECT GROUP_CONCAT(DISTINCT se.party_label) party_label FROM stock_entries se JOIN document_entries de ON se.product_code = de.product_code WHERE de.doc_id = dl.doc_id) as party_labels
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
		AND $direction_filter)t
	    HAVING $having
            ORDER BY invalid DESC, SUBSTRING(cstamp,1,10))";
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
    
    
    public $registryXmlGet=['type'=>'string','period'=>'string', 'fields'=> 'object'];
    
    public function registryXmlGet($type, $period, $fields){
        $this->xml_filter = true;
        if($type == 'difference'){
            foreach(['buy', 'sell'] as $item){
                $data[$item] = $this->registryFetch($period, '',$item)['sub_totals'];
            }
            $data['difference'] = new stdClass();
            $data['difference']->sum_total = $data['sell']->sum_total - $data['buy']->sum_total;
            $data['difference']->sum_vatless = $data['sell']->sum_vatless - $data['buy']->sum_vatless;
            $data['difference']->sum_vat = $data['sell']->sum_vat - $data['buy']->sum_vat;
        } else {
            $data = $this->registryFetch($period, '',$type);
        }
	$blank = "./application/views/rpt/xml/registry_$type.xml.php";
        $handled_period = $this->handleQuarter($period);
        $acomp_info = $this->Hub->svar('acomp');
        $document_name = $this->generateUniqueFileName($acomp_info, $type, $period);
        ob_start();
        header('Content-disposition: attachment; filename="'.$document_name.'.XML"');
        header('Content-type: "text/xml"; charset="utf-8"');
        include $blank;
        $xml = ob_get_contents();
        ob_clean();
        echo iconv('utf-8','windows-1251',$xml);
        
    }
    
    private function generateUniqueFileName($acomp_info, $type, $period){
        $prefix = 'NO_NDS';
        if($type == 'sell'){
            $prefix = 'NO_NDS.9';
        } 
        if($type == 'buy'){
            $prefix = 'NO_NDS.8';
        } 
        $hash = strtoupper(md5($acomp_info->company_tax_id.$acomp_info->company_tax_id2.$period));
        foreach([8,13,18,23] as $index){
            $hash = substr_replace($hash, '-', $index, 0);
        }
        $document_name_array = [
            $prefix,
            '9102',
            '9102',
            $acomp_info->company_tax_id.$acomp_info->company_tax_id2,
            date('Ymd'),
            $hash
        ];
        return implode('_', $document_name_array);
    }
    
    
    private function handleQuarter($period){
        $result = [];
        $exploded_period = explode('-', $period);
        $result['year'] = $exploded_period[0];
        $result['date'] = $exploded_period[1];
        switch($exploded_period[1]){
            case "I":
                $result['date'] = '21';
                break;
            case "II":
                $result['date'] = '22';
                break;
            case "III":
                $result['date'] = '23';
                break;
            case "IV":
                $result['date'] = '24';
                break;
        }
        return $result;
    }
}
