<?php
require_once 'AccountsCore.php';
class AccountsView extends AccountsCore{
    public $ledgerPaymentViewGet=[
	'acc_code'=>'string',
	'idate'=>'\d\d\d\d-\d\d-\d\d',
	'fdate'=>'\d\d\d\d-\d\d-\d\d',
	'out_type'=>'string',
	'use_passive_filter'=>'bool',
	'page'=>'int'
	];
    public function ledgerPaymentViewGet($acc_code,$idate,$fdate,$out_type,$use_passive_filter,$page){
	$this->ledgerViewGet($acc_code,$idate,$fdate,$out_type,$use_passive_filter,$page);
    }
    
    public $ledgerViewGet=[
	'acc_code'=>'string',
	'idate'=>'\d\d\d\d-\d\d-\d\d',
	'fdate'=>'\d\d\d\d-\d\d-\d\d',
	'out_type'=>'string',
	'use_passive_filter'=>'bool',
	'page'=>'int'
	];
    public function ledgerViewGet($acc_code,$idate,$fdate,$out_type,$use_passive_filter,$page){
	$rows=10000;
	$dump=$this->fillDump($acc_code, $idate, $fdate, $page, $rows, $use_passive_filter);
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
    
    private function fillDump($acc_code, $idate, $fdate, $page, $rows, $use_passive_filter){
        $Utils=$this->Hub->load_model('Utils');
	$table=$this->ledgerFetch($acc_code, $idate, $fdate, $page, $rows, $use_passive_filter);
        foreach ($table['rows'] as $row) {
            $arr = explode(' ', $row->trans_status);
            $row->trans_status = $arr[1];
            $row->debit==0?$row->debit='':'';
            $row->credit==0?$row->credit='':'';
        }
        
        $blank_set=$this->Hub->pref('blank_set');
        if ( $use_passive_filter ){
	    $tpl_files=$blank_set.'/LedgerPayments.xlsx';
	    $title="Акт Сверки на".date('d.m.Y', strtotime($fdate));
        } else {
	    $tpl_files=$blank_set.'/LedgerTransactions2.xlsx';
	    $title="Выписка Счета на".date('d.m.Y', strtotime($fdate));
        }
	
	$dump=[
	    'tpl_files'=>$tpl_files,
	    'title'=>$title,
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'view'=>[
		'a'=>$this->Hub->svar('acomp'),
		'p'=>$this->Hub->svar('pcomp'),
		'user_sign'=>$this->Hub->svar('user_sign'),
		'idate_dmy'=>date('d.m.Y', strtotime($idate)),
		'fdate_dmy'=>date('d.m.Y', strtotime($fdate)),
		'spell'=>$Utils->spellAmount( abs($table['sub_totals']->fbal) ),
		'ilocalDate'=>$Utils->getLocalDate($idate),
		'localDate'=>$Utils->getLocalDate($fdate),
                'director_name'=>$this->Hub->pref('director_name'),
                'accountant_name'=>$this->Hub->pref('accountant_name'),
		'ledger'=>$table
	    ]
	];
	return $dump;
    }
}