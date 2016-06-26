<?php
class Reports_summary_sell_profit extends Catalog{
    private $idate;
    private $fdate;
    private $all_active;
    public function __construct() {
	$this->idate=$this->dmy2iso( $this->request('idate','\d\d.\d\d.\d\d\d\d') ).' 00:00:00';
	$this->fdate=$this->dmy2iso( $this->request('fdate','\d\d.\d\d.\d\d\d\d') ).' 23:59:59';
	$this->all_active=$this->request('all_active','bool');
	parent::__construct();
    }
    private function dmy2iso( $dmy ){
	$chunks=  explode('.', $dmy);
	return "$chunks[2]-$chunks[1]-$chunks[0]";
    }
    public function viewGet(){
	$active_filter=$this->all_active?'':' AND active_company_id='.$this->Base->acomp('company_id');
        
        
        
        
        
	$view=[
                'total_sell'=>$total_sell,
                'total_self'=>$total_self,
                'total_net'=>$total_net,
                'total_qty'=>$total_qty,
		'rows'=>count($rows)?$rows:[[]],
		'totals'=>count($totals)?$totals:[[]]
		];
	return $view;	
    }
}