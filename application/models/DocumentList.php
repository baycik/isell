<?php
require_once 'Catalog.php';
class DocumentList extends Catalog{
    private $doc_id=null;
    private $document_properties=null;
    private function documentSelect( $doc_id ){
	$this->doc_id=$doc_id;
	unset($this->document_properties);
    }
    private function documentFlush(){
	if( !$this->document_properties ){
	    throw new Exception("Can't flush properties because they are not loaded");
	}
	return $this->update('document_list',$this->document_properties,['doc_id'=>$this->document_properties->doc_id]);
    }
    private function doc($field=null,$value=null){
	if( !isset($this->document_properties) ){
	    if( !$this->doc_id ){
		throw new Exception("Can't use properties because Document is not selected");
	    }
	    $this->document_properties=$this->get_row("SELECT * FROM document_list WHERE doc_id='$this->doc_id'");
	}
	if( $value!=null ){
	    return $this->document_properties->$field=$value;
	}
	return isset($this->document_properties->$field)?$this->document_properties->$field:null;
    }
    
    
    public $documentAdd=['doc_type'=>'string'];
    public function documentAdd( $doc_type ){
	$this->Hub->set_level(1);
	$user_id=$this->Hub->svar('user_id');
	$acomp_id=$this->Hub->acomp('company_id');
	$pcomp_id=$this->Hub->pcomp('company_id');
	$vat_rate = $this->Hub->acomp('company_vat_rate');
	$usd_ratio=$this->Hub->pref('usd_ratio');
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
    public $documentUpdate=['doc_id'=>'int','field'=>'escape','value'=>'string'];
    public function documentUpdate($doc_id,$field,$value){
	$this->Hub->set_level(2);
	$this->documentSelect($doc_id);
	$this->query("START TRANSACTION");
	switch($field){
	    case 'is_commited':
		$this->documentChangeCommit( (bool) $value );
	    case 'notcount':
		$this->transChangeNotcount((bool) $value );
	    case 'use_vatless_price':
	    case 'doc_ratio':
		$this->transChangeCurrRatio( $value );
	    case 'vat_rate':
		$this->transChangeVatRate( $value );
	    case 'doc_num':
		if( !is_int($value) ){
		    return false;
		}
	}
	$this->doc($field,$value);
	$this->documentFlush();
	$this->query("COMMIT");
    }
    private function documentChangeCommit( $make_commited=false ){
	if( $make_commited==false && !$this->doc('is_commited') ){
	    $this->documentDelete();//if doc is uncommited then delete it
	}
	
    }
    private function documentCommitEntries(){
	$doc_id=$this->doc('doc_id');
	$document_entries=$this->get_list("SELECT * FROM document_entries WHERE doc_id='$doc_id'");
	foreach($document_entries as $entry){
	    $ok=$this->Hub->plugin_do("document".$this->doc('doc_type'),'commitEntry',[$this,$entry]);
	    if(!$ok){
		return false;
	    }
	}
	return true;
    }
    
    
    
    
    private function documentUpdateRatio( $new_ratio ){
	
    }
    private function documentDelete(){
	//do_action("document".$this->doc('doc_type')."_before_delete",$doc_id);
	$doc_id=$this->doc('doc_id');
	$this->query("START TRANSACTION");
	$this->delete('document_entries',['doc_id'=>$doc_id]);
	$this->delete('document_view_list',['doc_id'=>$doc_id]);
	$this->delete('acc_trans',['doc_id'=>$doc_id]);
	$ok=$this->delete('document_list',['doc_id'=>$doc_id,'is_commited'=>0]);
	$this->query("COMMIT");
	return $ok;
    }
}