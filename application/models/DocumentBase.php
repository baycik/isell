<?php
/*
 * This class is a base class for all v5+ document handling classes
 */
abstract class DocumentBase extends Catalog{
    protected $doc_id=null;
    protected $document_properties=null;
    protected function documentSelect( $doc_id ){
	$this->doc_id=$doc_id;
	unset($this->document_properties);
    }
    protected function documentFlush(){
	if( !$this->document_properties ){
	    throw new Exception("Can't flush properties because they are not loaded");
	}
	return $this->update('document_list',$this->document_properties,['doc_id'=>$this->document_properties->doc_id]);
    }
    protected function doc($field=null,$value=null){
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
    protected function documentCurrencyCorrectionGet(){
	$native_curr=$this->Hub->pcomp('curr_code') && ($this->Hub->pcomp('curr_code') != $this->Hub->acomp('curr_code'))?0:1;
	return $native_curr?1:1/$this->doc('doc_ratio');
    }
    protected function documentSetLevel($level){
	return false;
    }
    protected function headDefaultDataGet(){
	return [];
    }
    public function headGet( $doc_id ){
	if( $doc_id==0 ){
	    return $this->headDefaultDataGet();
	}
	//$this->documentSelect($doc_id);
	$sql="
	    SELECT
		doc_id,
		passive_company_id,
                (SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=passive_company_id) label,
		IF(is_reclamation,-doc_type,doc_type) doc_type,
		is_reclamation,
		is_commited,
		notcount,
		vat_rate,
		use_vatless_price,
		signs_after_dot,
		doc_ratio,
		doc_num,
		DATE_FORMAT(cstamp,'%d.%m.%Y') doc_date,
		doc_data,
		(SELECT last_name FROM user_list WHERE user_id=created_by) created_by,
		(SELECT last_name FROM user_list WHERE user_id=modified_by) modified_by
	    FROM
		document_list
	    WHERE doc_id=$doc_id"
	;
	$head=$this->get_row($sql);
	//$head->extra_expenses=$this->getExtraExpenses();
	return $head;
    }

    protected function documentAdd( $doc_type ){
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
	return $new_doc_id;
    }
    protected function documentGetPrevious($acomp_id,$pcomp_id){
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
    protected function documentNumNext($acomp_id,$doc_type){
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
    public $documentUpdate=['doc_id'=>'int','field'=>'string','value'=>'string'];
    public function documentUpdate($doc_id,$field,$value){
	$this->Hub->set_level(2);
	$this->documentSelect($doc_id);
	$ok=true;
	$this->query("START TRANSACTION");
	switch($field){
	    case 'is_commited':
		$ok=$this->documentChangeCommit( (bool) $value );
		break;
	    case 'notcount':
		$ok=$this->transChangeNotcount((bool) $value );
		break;
	    case 'use_vatless_price':
		break;
	    case 'doc_ratio':
		$ok=$this->transChangeCurrRatio( $value );
		break;
	    case 'vat_rate':
		$ok=$this->transChangeVatRate( $value );
		break;
	    case 'doc_num':
		if( !is_int($value) ){
		    return false;
		}
	}
	if( !$ok ){
	    return false;
	}
	$this->doc($field,$value);
	$this->documentFlush();
	$this->query("COMMIT");
	return true;
    }
    protected function documentUpdateRatio( $new_ratio ){
	
    }
    protected function documentDelete(){
	$doc_id=$this->doc('doc_id');
	$this->query("START TRANSACTION");
	$this->delete('document_entries',['doc_id'=>$doc_id]);
	$this->delete('document_view_list',['doc_id'=>$doc_id]);
	$this->delete('acc_trans',['doc_id'=>$doc_id]);
	$ok=$this->delete('document_list',['doc_id'=>$doc_id,'is_commited'=>0]);
	$this->query("COMMIT");
	return $ok;
    }
    /*
     * Commits and uncommit document
     */
    protected function documentChangeCommit( $make_commited=false ){
	if( $make_commited && $this->doc('is_commited') || !$make_commited && !$this->doc('is_commited') ){
	    return true;
	}
	return $this->documentChangeCommitEntries($make_commited);
    }
    /*
     * Commit or uncommit all entries in document
     */
    protected function documentChangeCommitEntries($make_commited){
	$doc_id=$this->doc('doc_id');
	$document_entries=$this->get_list("SELECT doc_entry_id FROM document_entries WHERE doc_id='$doc_id'");
	
	foreach($document_entries as $entry){
	    $this->Hub->msg("$make_commited $entry->doc_entry_id --".$this->entryCommit($entry->doc_entry_id));
	    if( $make_commited && !$this->entryCommit($entry->doc_entry_id) ){
		return false;//need to commit but it failed
	    }
	    if( !$make_commited && !$this->entryUncommit($entry->doc_entry_id) ){
		return false;//need to uncommit but it failed
	    }
	}
	return true;
    }
    protected function entryCommit($doc_entry_id,$new_product_quantity=NULL){
	return false;
    }
    protected function entryUncommit($doc_entry_id){
	return false;
    }
    
    
    public function entryDelete($doc_id,$doc_entry_ids){
	$this->documentSelect($doc_id);
	$this->query("START TRANSACTION");
	foreach($doc_entry_ids as $doc_entry_id){
	    $uncommit_ok=$this->entryUncommit($doc_entry_id);
	    $delete_ok=$this->delete('document_entries',['doc_id'=>$doc_id,'doc_entry_id'=>$doc_entry_id]);
	    if( !$uncommit_ok || !$delete_ok ){
		return false;
	    }
	}
	$this->query("COMMIT");
	return true;
    }    
    
    

    public $import=['doc_id'=>'int','label'=>'string'];
    public function entryImport( $doc_id,$label ){
	$this->documentSelect($doc_id);
	$this->entriesTruncate();
	$source = array_map('addslashes',$this->request('source','raw'));
	$target = array_map('addslashes',$this->request('target','raw'));
        $source[]=$this->doc('doc_id');
        $target[]='doc_id';
	$this->entryImportFromTable('document_entries', $source, $target, '/product_code/product_quantity/invoice_price/party_label/doc_id/', $label);
	$this->query("DELETE FROM imported_data WHERE {$source[0]} IN (SELECT product_code FROM document_entries WHERE doc_id={$doc_id})");
        return  $this->db->affected_rows();
    }
    private function entryImportTruncate(){
	$doc_id=$this->doc('doc_id');
	$doc_entry_ids=[];
	$document_entries=$this->get_list("SELECT doc_entry_id FROM document_entries WHERE doc_id='$doc_id'");
	foreach($document_entries as $entry){
	    $doc_entry_ids[]=$entry->doc_entry_id;
	}
	$this->entryDelete($doc_id,$doc_entry_ids);
    }
    private function entryImportFromTable( $table, $src, $trg, $filter, $label ){
	$target=[];
	$source=[];
	$doc_vat_ratio=1+$this->doc('vat_rate')/100;
	$curr_correction=$this->documentCurrencyCorrectionGet();
        $quantity_source_field='';
	for( $i=0;$i<count($trg);$i++ ){
            if( strpos($filter,"/{$trg[$i]}/")===false || empty($src[$i]) ){
		continue;
	    }
	    if( $trg[$i]=='product_code' ){
		$product_code_source=$src[$i];
	    }
	    if( $trg[$i]=='invoice_price' ){
		$src[$i]=round($src[$i]/$curr_correction/$doc_vat_ratio,2);
	    }
	    if( $trg[$i]=='product_quantity' ){
		$quantity_source_field=$src[$i];
	    }
            
	    $target[]=$trg[$i];
	    $source[]=$src[$i];
	}
	$target_list=  implode(',', $target);
	$source_list=  implode(',', $source);
	$this->query("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label='$label' AND $product_code_source IN (SELECT product_code FROM stock_entries) ON DUPLICATE KEY UPDATE product_quantity=product_quantity+$quantity_source_field");
	return $this->db->affected_rows();
    }
}