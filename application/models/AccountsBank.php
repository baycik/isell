<?php
require_once 'AccountsData.php';
class AccountsBank extends AccountsData{
    public $min_level=3;
    public function clientBankGet( $main_acc_code=0, $page=1, $rows=30 ){
        $this->check($main_acc_code);
        $this->check($page,'int');
        $this->check($rows,'int');
	$active_company_id=$this->Base->acomp('company_id');
        
	$having=$this->decodeFilterRules();
	$offset=$page>0?($page-1)*$rows:0;
	$sql="SELECT *,
		    IF(trans_id,'ok Проведен','gray Непроведен') AS status,
		    IF(debit_amount,ROUND(debit_amount,2),'') AS debit,
		    IF(credit_amount,ROUND(credit_amount,2),'') AS credit,
		    DATE_FORMAT(transaction_date,'%d.%m.%Y') AS tdate,
		    DATE_FORMAT(date,'%d.%m.%Y') AS date
                FROM acc_check_list 
		WHERE main_acc_code='$main_acc_code' AND active_company_id='$active_company_id'
		HAVING $having
		ORDER BY transaction_date DESC 
		LIMIT $rows OFFSET $offset";
	$result_rows=$this->get_list($sql);
	$total_estimate=$offset+(count($result_rows)==$rows?$rows+1:count($result_rows));
	return ['rows'=>$result_rows,'total'=>$total_estimate,'sub_totals'=>$this->clientBankGetTotals( $result_rows )];
    }
    private function clientBankGetTotals( $rows ){
	$totals=['tdebit'=>0,'tcredit'=>0];
        foreach ($rows as $row) {
	    $totals['tdebit']+=$row->debit;
	    $totals['tcredit']+=$row->credit;
        }
	$totals['tdebit']=$totals['tdebit']?round($totals['tdebit'],2):'';
	$totals['tcredit']=$totals['tcredit']?round($totals['tcredit'],2):'';
	return $totals;
    }
    
    public function getCorrespondentStats(){
	$check_id=$this->request('check_id','int',0);
	$check=$this->getCheck($check_id);
	
        $Company=$this->Base->load_model("Company");
        $company_id=$Company->companyFindByCode( $check->correspondent_code, $check->correspondent_code );
	if( !$company_id ){
	    return null;
	}
	
	$pcomp=$Company->selectPassiveCompany($company_id);
	$favs=$this->accountFavoritesFetch(true);
	foreach($favs as $acc){
	    $this->appendSuggestions($acc,$check);
	}
	return [
	    'trans_id'=>$check->trans_id,
	    'pcomp'=>$pcomp,
	    'favs'=>$favs
	];
    }
    
    private function getCheck( $check_id ){
	return $this->get_row("SELECT * FROM acc_check_list WHERE check_id=$check_id");
    }
    
    private function appendSuggestions( &$acc, $check ){
	$active_company_id=$this->Base->acomp('company_id');
	$passive_company_id=$this->Base->pcomp('company_id');
	$sql="SELECT 
		    at.*,
		    DATE_FORMAT(tstamp,'%d.%m.%Y') date,
		    code,
		    descr
		FROM 
		    acc_trans  at
		        JOIN
		    acc_trans_status USING (trans_status)
			JOIN
		    acc_check_list acl ON debit_amount=amount OR credit_amount=amount
		WHERE 
		    at.active_company_id=$active_company_id
		    AND at.passive_company_id=$passive_company_id
                    AND IF(debit_amount>0,acc_credit_code='{$acc->acc_code}',acc_debit_code='{$acc->acc_code}')
		    AND trans_status IN(0,1,2,3)
		    AND acl.check_id={$check->check_id}";
	$acc->suggs=$this->get_list($sql);
	return $acc;
    }
    
    public function checkDelete( $check_id ){
	$this->check($check_id,'int');
	$check=$this->getCheck($check_id);
	if( $check->trans_id ){
	    $this->transDelete($check->trans_id);
	}
	return $this->delete('acc_check_list',['check_id'=>$check_id]);
    }
    
    /*
     * IMPORT OF FILE .csv
     */
    
    public function up( $main_acc_code ){
	if( $_FILES['upload_file'] && !$_FILES['upload_file']['error'] ){
	    if ( strrpos($_FILES['upload_file']['name'], '.csv') ){
		return $this->parseCSV( $_FILES['upload_file']['tmp_name'], $main_acc_code );
	    }
	}
        return 'error'.$_FILES['upload_file']['error'];
    }
    
    private function parseCSV( $UPLOADED_FILE, $main_acc_code ){
	$csv_raw = file_get_contents($UPLOADED_FILE);
	$csv = iconv('Windows-1251', 'UTF-8', $csv_raw);
	$csv_lines = explode("\n", $csv);
	array_shift($csv_lines);
	$csv_sequence=explode(',',str_replace( '-', '_', $this->Base->pref('clientbank_fields') ));
	foreach ($csv_lines as $line) {
	    if ( !$line ){
		continue;
	    }
	    $i=0;
	    $check = [];
	    $vals = str_getcsv($line, ';');
	    foreach($csv_sequence as $field){
		$check[trim($field)]=$vals[$i++];
	    }
	    $this->addCheckDocument($check, $main_acc_code);
	}
	return 'imported';
    }
    
    private function addCheckDocument($check, $main_acc_code) {
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	$active_company_id=$this->Base->acomp('company_id');
        $fields = ['number','date','value_date','debit_amount','credit_amount','assumption_date','currency','transaction_date','client_name','client_code','client_account','client_bank_name','client_bank_code','correspondent_name','correspondent_code','correspondent_account','correspondent_bank_name','correspondent_bank_code','assignment'];
        $set = ["active_company_id='$active_company_id'","main_acc_code='$main_acc_code'"];
        foreach ($fields as $field) {
	    $value = isset($check[$field])?$check[$field]:'';
            if ($field == 'debit_amount' || $field == 'credit_amount') {
                $value = str_replace(',', '.', $value);
            }
            if (strpos($field, 'date') !== false) {
                preg_match_all('/(\d{2})[^\d](\d{2})[^\d](\d{4})( \d\d:\d\d(:\d\d)?)?/i', $value, $matches);
                $value = "{$matches[3][0]}-{$matches[2][0]}-{$matches[1][0]}{$matches[4][0]}";
            }
	    $set[] = "$field='" . addslashes($value) . "' ";
        }
        $this->query("INSERT INTO acc_check_list SET " . implode(',', $set), false);
        return true;
    }
    
    /*
     * VIEW OUT
     */

    public function cbankViewGet(){
	$page=$this->request('page','int');
	$rows=$this->request('rows','int');
	$main_acc_code=$this->request('main_acc_code');
	$out_type=$this->request('out_type');
	
	$dump=$this->fillDump($main_acc_code, $page, $rows);
	
	$ViewManager=$this->Base->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);	
    }
    private function fillDump($main_acc_code, $page, $rows){
	$table=$this->clientBankGet($main_acc_code, $page, $rows);
	$dump=[
	    'tpl_files'=>$this->Base->acomp('language').'/CheckList.xlsx',
	    'title'=>"Платежные поручения",
	    'user_data'=>[
		'email'=>$this->Base->svar('pcomp')?$this->Base->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'view'=>[
		'a'=>$this->Base->svar('acomp'),
		'user_sign'=>$this->Base->svar('user_sign'),
		'table'=>$table
	    ]
	];
	return $dump;	
    }
}