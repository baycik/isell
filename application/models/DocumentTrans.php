<?php
require_once 'DocumentUtils.php';
class DocumentTrans extends DocumentUtils{
    public $documentAdd=['doc_type'=>'string'];
    public function documentAdd( $doc_type ){
	$this->Base->set_level(2);
	$created_by=$this->Base->svar('user_id');
	$acomp_id=$this->Base->acomp('company_id');
	$pcomp_id=$this->Base->pcomp('company_id');
	$vat_rate = $this->Base->acomp('company_vat_rate');
	$usd_ratio=$this->Base->pref('usd_ratio');
	$prev_document=$this->documentGetPrevious($acomp_id, $pcomp_id);
	
	if( !$doc_type ){
	    $doc_type=$prev_document->doc_type?$prev_document->doc_type:'sell';
	}
	$doc_num=$this->documentNumNext($doc_type);
	
	$sql="";
    }
    private function documentGetPrevious($acomp_id,$pcomp_id){
	$sql="SELECT 
	    * 
	    FROM 
		document_list 
	    WHERE 
		active_company_id='$acomp_id' 
		AND passive_company_id='$pcomp_id' 
		AND doc_type<10 
		AND is_commited=1 
	    ORDER BY cstamp DESC LIMIT 1";
	return 	$this->get_row($sql);
    }
    private function documentNumNext($acomp_id,$doc_type){
	$sql="SELECT 
		MAX(doc_num)+1 
	    FROM 
		document_list 
	    WHERE 
		doc_type='$doc_type' 
		AND active_company_id='$active_company_id' 
		AND cstamp>DATE_FORMAT(NOW(),'%Y')";
	
	$next_num = $this->get_value($sql);
	return $next_num ? $next_num : 1;
    }
}