<?php
require_once 'Catalog.php';
class DocumentUtils extends Catalog{
    public $min_level=1;
    protected $_doc;
    public function selectDoc( $doc_id ){
	if( $doc_id!=$this->Hub->svar('doc_id') ){
            $this->Hub->svar('doc_id',$doc_id);
            unset( $this->_doc );
            /*
             * Compability for isell2 calls
             */
            if( isset($this->bridge) ){
                $this->bridge->LoadClass('Document')->selectDoc($doc_id);
            }
        }
    }
    private function checkPassiveLoad(){
	if( $this->Hub->pcomp('company_id')!==$this->_doc->passive_company_id  ){
	    $this->Hub->load_model('Company');
	    $this->Hub->Company->selectPassiveCompany( $this->_doc->passive_company_id );	
	}
    }
    protected function loadDoc($doc_id){//MUST INCLUDE SECURITY CHECK!!! user_level & path
        if( $doc_id ){
            $sql="SELECT 
                    dl.*,
                    dsl.status_code,
                    DATE_FORMAT(cstamp,'%d.%m.%Y') AS doc_date 
                FROM 
                    document_list dl
                        LEFT JOIN
                    document_status_list dsl USING(doc_status_id)
                WHERE doc_id='$doc_id'";
            $document_head=$this->get_row($sql);
            if($document_head){ 
                $passive_company=$this->Hub->load_model('Company')->companyGet($document_head->passive_company_id);
            } else { 
                return false;
            }
            /*
             * IF cant get company than it is not permitted to user
             */
            if( !$passive_company ){
                http_response_code(403);
                die();
            }
            $this->_doc = $document_head;
            if( !$this->_doc ){
                $this->Hub->msg("Doc $doc_id not found");
                $this->Hub->response(0);
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
	$this->loadDoc($doc_id);
    }
    protected function doc($name) {
	if ( !isset($this->_doc) ) {
	    $doc_id = $this->Hub->svar('doc_id');
	    $this->loadDoc($doc_id);
	}
	return isset($this->_doc->$name)?$this->_doc->$name:NULL;
    }
    protected function isServiceDoc(){
	return $this->doc('doc_type')==3 || $this->doc('doc_type')==4;
    }
    protected function isCommited() {
	return $this->doc('is_commited');
    }
    protected function isReserved(){
        return $this->doc('status_code')=='reserved';
    }
    
    protected function setDocumentModifyingUser() {
	$user_id = $this->Hub->svar('user_id');
	$this->rowUpdateField( 'document_list', 'doc_id', $this->doc('doc_id'), 'modified_by', $user_id );
    }
    protected function getNextDocNum($doc_type, $creation_mode=false) {//Util
        $Pref=$this->Hub->load_model('Pref');
        
        $counter_increase=$creation_mode=='not_increase_number'?0:1;
        $counter_name="counterDocNum_".$doc_type;
        $nextNum=$Pref->counterNumGet($counter_name,null,$counter_increase);
        if( !$nextNum ){
            $doc_type_row=$this->get_row("SELECT * FROM document_types WHERE doc_type='$doc_type'");
            $counter_title=$doc_type_row->doc_type_name??'???';
            $Pref->counterCreate($counter_name,null,$counter_title);
            $nextNum=$Pref->counterNumGet($counter_name,null,$counter_increase);
        }
        return $nextNum;
    }
    protected function calcCorrections( $skip_vat_correction=false, $skip_curr_correction=false ) {
	$doc_id=$this->doc('doc_id');
	$curr_code=$this->Hub->pcomp('curr_code');
	$native_curr=($this->Hub->pcomp('curr_code') == $this->Hub->acomp('curr_code'))?1:0;
	$sql="SELECT 
		@vat_ratio:=1+vat_rate/100,
		@vat_correction:=IF(use_vatless_price OR '$skip_vat_correction',1,@vat_ratio),
		@curr_correction:=IF($native_curr OR '$skip_curr_correction',1,1/doc_ratio),
		@curr_symbol:=(SELECT curr_symbol FROM curr_list WHERE curr_code='$curr_code'),
                @signs_after_dot:=signs_after_dot
	    FROM
		document_list
	    WHERE
		doc_id='$doc_id'";
	$this->query($sql);
    }
}