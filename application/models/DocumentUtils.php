<?php
require_once 'Catalog.php';
class DocumentUtils extends Catalog{
    public $min_level=1;
    protected function selectDoc( $doc_id ){
	if( $doc_id!=$this->Base->svar('doc_id') ){
	    $this->Base->svar('doc_id',$doc_id);
	    unset( $this->_doc );
	}
    }
    private function checkPassiveLoad(){
	if( $this->Base->pcomp('company_id')!==$this->_doc->passive_company_id  ){
	    $this->Base->load_model('Company');
	    $this->Base->Company->selectPassiveCompany( $this->_doc->passive_company_id );	
	}
    }
    private function loadDoc($doc_id) {
        if( $doc_id ){
            $this->_doc = $this->get_row("SELECT *, DATE_FORMAT(cstamp,'%d.%m.%Y') AS doc_date FROM document_list WHERE doc_id='$doc_id'");
            if( !$this->_doc ){
                $this->Base->msg("Doc $doc_id not found");
                $this->Base->response(0);
            }
            $this->_doc->vat_ratio=1 + $this->_doc->vat_rate / 100;
            $this->checkPassiveLoad();
        } else {
            $this->_doc=$this->headDefGet();
        }
    }
    protected function updateProps( $props ){
	$doc_id = $this->doc('doc_id');
	$this->rowUpdate( 'document_list', $props, array('doc_id'=>$doc_id) );
	$this->selectDoc($doc_id);
    }
    protected function doc($name) {
	if ( !isset($this->_doc) ) {
	    $doc_id = $this->Base->svar('doc_id');
	    $this->loadDoc($doc_id);
	}
	return isset($this->_doc->$name)?$this->_doc->$name:NULL;
    }
    protected function isServiceDoc(){
	return $this->doc('doc_type')==3 OR $this->doc('doc_type')==4;
    }
    protected function isCommited() {
	return $this->doc('is_commited');
    }
    protected function setDocumentModifyingUser() {
	$user_id = $this->Base->svar('user_id');
	$this->rowUpdateField( 'document_list', 'doc_id', $this->doc('doc_id'), 'modified_by', $user_id );
    }
    protected function getNextDocNum($doc_type) {//Util
	$active_company_id = $this->Base->acomp('company_id');
	$next_num = $this->get_value("SELECT MAX(doc_num)+1 FROM document_list WHERE doc_type='$doc_type' AND active_company_id='$active_company_id' AND cstamp>DATE_FORMAT(NOW(),'%Y')");
	return $next_num ? $next_num : 1;
    }
    protected function calcCorrections($skip_vat_correction=false) {
	$doc_id=$this->doc('doc_id');
	$curr_code=$this->Base->pcomp('curr_code');
	$native_curr=($this->Base->pcomp('curr_code') == $this->Base->acomp('curr_code'))?1:0;
	$sql="SELECT 
		@vat_ratio:=1+vat_rate/100,
		@vat_correction:=IF(use_vatless_price OR '$skip_vat_correction',1,@vat_ratio),
		@curr_correction:=IF($native_curr,1,1/doc_ratio),
		@curr_symbol:=(SELECT curr_symbol FROM curr_list WHERE curr_code='$curr_code'),
                @signs_after_dot:=signs_after_dot
	    FROM
		document_list
	    WHERE
		doc_id='$doc_id'";
	$this->query($sql);
    }
}