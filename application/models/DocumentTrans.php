<?php
require_once 'DocumentUtils.php';
class DocumentTrans extends DocumentUtils{
    public $documentAdd=['doc_type'=>'string'];
    public function documentAdd( $doc_type ){
	$this->Base->set_level(2);
	$user_id=$this->Base->svar('user_id');
	$acomp_id=$this->Base->acomp('company_id');
	$pcomp_id=$this->Base->pcomp('company_id');
	$vat_rate = $this->Base->acomp('company_vat_rate');
	$usd_ratio=$this->Base->pref('usd_ratio');
	$new_document=[
	    'doc_type'=>($doc_type?$doc_type:'sell'),
	    'cstamp'=>date('Y-m-d H:i:s'),
	    'active_company_id'=>$acomp_id,
	    'passive_company_id'=>$pcomp_id,
	    'signs_after_dot'=>2,
	    'doc_ratio'=>$usd_ratio,
	    'vat_rate'=>$vat_rate,
	    'created_by'=>$user_id,
	    'modified_by'=>$user_id,
	    'use_vatless_price'=>0,
	    'notcount'=>0,
	    'doc_num'=>0
	];
	$prev_document=$this->documentGetPrevious($acomp_id, $pcomp_id);
	if( $prev_document && !is_numeric($prev_document->doc_type) ){
	    $new_document['doc_type']=$prev_document->doc_type;
	    $new_document['notcount']=$prev_document->notcount;
	    $new_document['signs_after_dot']=$prev_document->signs_after_dot;
	    $new_document['use_vatless_price']=$prev_document->use_vatless_price;
	    $new_document['vat_rate']=$prev_document->vat_rate;
	}
	$new_document['doc_num']=$this->documentNumNext($acomp_id,$doc_type);
	$new_doc_id=$this->create('document_list', $new_document);
	do_action("document_{$new_document['doc_type']}_created",$new_doc_id);
	return $new_doc_id;
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
		AND active_company_id='$acomp_id' 
		AND cstamp>DATE_FORMAT(NOW(),'%Y')";
	
	$next_num = $this->get_value($sql);
	return $next_num ? $next_num : 1;
    }
}