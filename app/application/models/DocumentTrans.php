<?php
require_once 'DocumentUtils.php';
class DocumentTrans extends DocumentUtils{
    ///////////////////////////////////////
    //EXPERIMENTAL
    ///////////////////////////////////////
    private $docTypeConfig=[
	1=>[
	    'name'	=>"Расходный документ",
	    'total'	=>'361_702',
	    'vat'	=>'702_641',
	    'vatless'	=>'702_791',
	    'self'	=>'791_281',
	    'profit'	=>'791_441'
	],
	2=>[
	    'name'	=>"Приходный документ",
	    'total'	=>'63_631',
	    'vat'	=>'641_63',
	    'vatless'	=>'',
	    'self'	=>'281_63',
	    'profit'	=>''
	]
    ];
    ///////////////////////////////////////
    //EXPERIMENTAL
    ///////////////////////////////////////
    private function docTotalsGet(){
	$doc_id=$this->doc('doc_id');
	$doc_vat_rate=$this->doc('vat_rate');
	$sql="
	    SELECT
		total,
		vatless,
		self,
		total-vatless vat,
		vatless-self profit
	    FROM
		(SELECT
		    SUM(ROUND(product_quantity*invoice_price*(1+$doc_vat_rate/100),2)) total,
		    SUM(ROUND(product_quantity*invoice_price,2)) vatless,
		    SUM(ROUND(product_quantity*self_price,2)) self
		FROM
		    document_entries
		WHERE doc_id='$doc_id') t";
	return $this->get_row($sql);
    }
    ///////////////////////////////////////
    //EXPERIMENTAL
    ///////////////////////////////////////
    private function docTransGet(){
	$doc_id=$this->doc('doc_id');
	$sql="
	    SELECT
		trans_id,
		type
	    FROM
		document_trans";
	return $this->get_row($sql);	
    }
    ///////////////////////////////////////
    //EXPERIMENTAL
    ///////////////////////////////////////
    public function docTransUpdate( $doc_id ){
	$this->selectDoc($doc_id);
	if( !$this->isCommited() ){
	    return false;
	}
	$totals=$this->docTotalsGet();
	$docTrans=$this->docTransGet();
    }

}
