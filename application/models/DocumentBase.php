<?php
/*
 * This class is a base class for all v5+ document handling classes
 */
abstract class DocumentBase extends Catalog{
    protected $doc_id=null;
    protected $document_properties=null;
    //////////////////////////////////////////
    // UTILS SECTION
    //////////////////////////////////////////
    protected function doc($field=null,$value=null){
	if( !isset($this->document_properties) ){
	    if( !$this->doc_id ){
		throw new Exception("Can't use properties because Document is not selected");
	    }
	    $this->document_properties=$this->get_row("SELECT * FROM document_list WHERE doc_id='$this->doc_id'");
	}
	if( isset($value) ){
	    return $this->document_properties->$field=$value;
	}
	return isset($this->document_properties->$field)?$this->document_properties->$field:null;
    }
    protected function isCommited(){
	return $this->doc('is_commited')=='1';
    }
    //////////////////////////////////////////
    // DOCUMENT SECTION
    //////////////////////////////////////////
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
    protected function documentCurrencyCorrectionGet(){
	$native_curr=$this->Hub->pcomp('curr_code') && ($this->Hub->pcomp('curr_code') != $this->Hub->acomp('curr_code'))?0:1;
	return $native_curr?1:1/$this->doc('doc_ratio');
    }
    protected function documentSetLevel($level){
	return false;
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
	$prev_document=$this->headerPreviousGet($acomp_id, $pcomp_id);
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
	$this->db_transaction_start();
	switch($field){
	    case 'is_commited':
		$ok=$this->documentChangeCommit( $value );
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
	$this->db_transaction_commit();
	return true;
    }
    protected function documentUpdateRatio( $new_ratio ){
	
    }
    protected function documentDelete( $doc_id ){
	$this->db_transaction_start();
	$this->delete('document_entries',['doc_id'=>$doc_id]);
	$this->delete('document_view_list',['doc_id'=>$doc_id]);
	$this->transClear();
	$ok=$this->delete('document_list',['doc_id'=>$doc_id,'is_commited'=>0]);
	$this->db_transaction_commit();
	return $ok;
    }
    //////////////////////////////////////////
    // COMMIT SECTION
    //////////////////////////////////////////
    protected function documentChangeCommit( $make_commited=false ){
	if( $make_commited && $this->isCommited() || !$make_commited && !$this->isCommited() ){
	    return true;
	}
        $trans_ok=$this->documentChangeCommitTransactions($make_commited);
	$entries_ok=$this->documentChangeCommitEntries($make_commited);
	if( !$trans_ok || !$entries_ok ){
	    $this->db_transaction_rollback();
	    return false;
	}
    }
    protected function documentChangeCommitEntries($make_commited){
	$doc_id=$this->doc('doc_id');
	$document_entries=$this->get_list("SELECT doc_entry_id FROM document_entries WHERE doc_id='$doc_id'");
	//$this->Hub->msg("make_commited $make_commited");
	
	foreach($document_entries as $entry){
	    if( $make_commited && !$this->entryCommit($entry->doc_entry_id) ){
		return false;//need to commit but it failed
	    }
	    if( !$make_commited && !$this->entryUncommit($entry->doc_entry_id) ){
		return false;//need to uncommit but it failed
	    }
	}
	return true;
    }
    protected function documentChangeCommitTransactions($make_commited){
        if( $make_commited ){
	    $footer=$this->footGet();
	    $this->transUpdate($footer);
            
        } else {
            $this->transDisable();
        }
        return false;
    }
    //////////////////////////////////////////
    // HEADER SECTION
    //////////////////////////////////////////
    public function headGet( $doc_id ){
	if( $doc_id==0 ){
	    return $this->headerDefaultGet();
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
    protected function headPreviousGet($acomp_id,$pcomp_id){
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
    protected function headDefaultGet(){
	return [];
    }
    //////////////////////////////////////////
    // BODY SECTION
    //////////////////////////////////////////
    protected function bodyGet($doc_id){
	$this->entriesTmpCreate( $doc_id );
	return $this->get_list("SELECT * FROM tmp_doc_entries");
    }
    protected function entriesTmpCreate( $doc_id ){
	//$this->documentSelect($doc_id);
	$doc_vat_ratio=1+$this->doc('vat_rate')/100;
	//$signs_after_dot=$this->doc('signs_after_dot');
	$curr_correction=$this->documentCurrencyCorrectionGet();

        $this->query("DROP TEMPORARY TABLE IF EXISTS tmp_doc_entries");
        $sql="CREATE TEMPORARY TABLE tmp_doc_entries ( INDEX(product_code) ) ENGINE=MyISAM AS (
                SELECT 
                    *,
                    ROUND(corrected_price, 2) AS product_price_vatless,
                    ROUND(corrected_price * $doc_vat_ratio, 2) AS product_price_total,
                    ROUND(corrected_price * product_quantity,2) product_sum_vatless,
                    ROUND(corrected_price * $doc_vat_ratio * product_quantity,2) product_sum_total
                FROM
                (SELECT
                    de.*,
                    ru product_name,
		    invoice_price * $curr_correction corrected_price,
		    invoice_price<(self_price-0.01) is_loss,
		    product_quantity*product_weight weight,
                    product_quantity*product_volume volume,
                    CHK_ENTRY(doc_entry_id) AS row_status,
                    product_unit,
                    analyse_origin
                FROM
                    document_list
                        JOIN
                    document_entries de USING(doc_id)
                        JOIN 
                    prod_list pl USING(product_code)
                WHERE
                    doc_id='$doc_id'
                ORDER BY pl.product_code) t)";
        $this->query($sql);
    }
    protected function entryAdd(){
	
    }
    protected function entryUpdate(){
	
    }
    public function entryDelete($doc_id,$doc_entry_ids){
	$this->documentSelect($doc_id);
	$this->db_transaction_start();
	foreach($doc_entry_ids as $doc_entry_id){
	    if( $this->isCommited() ){
		$uncommit_ok=$this->entryUncommit($doc_entry_id);
	    } else {
		$uncommit_ok=true;
	    }
	    $delete_ok=$this->delete('document_entries',['doc_id'=>$doc_id,'doc_entry_id'=>$doc_entry_id]);
	    if( !$uncommit_ok || !$delete_ok ){
		return false;
	    }
	}
	$this->db_transaction_commit();
	return true;
    }
    protected function entryCommit($doc_entry_id,$new_product_quantity=NULL){
	return false;
    }
    protected function entryUncommit($doc_entry_id){
	return false;
    }
    public $entryImport=['doc_id'=>'int','label'=>'string'];
    public function entryImport( $doc_id,$label ){
	$this->documentSelect($doc_id);
	$doc_was_commited=$this->doc('is_commited');
	$this->documentUpdate($doc_id,'is_commited',false);
	
	$this->entryImportTruncate();
	$source = array_map('addslashes',$this->request('source','raw'));
	$target = array_map('addslashes',$this->request('target','raw'));
        $source[]=$doc_id;
        $target[]='doc_id';
	$this->entryImportFromTable('document_entries', $source, $target, '/product_code/product_quantity/invoice_price/party_label/doc_id/', $label);
	$this->query("DELETE FROM imported_data WHERE {$source[0]} IN (SELECT product_code FROM document_entries WHERE doc_id={$doc_id})");
	$imported_count=$this->db->affected_rows();
	if( $doc_was_commited ){
	    $this->documentUpdate($doc_id,'is_commited',true);
	}
        return  $imported_count;
    }
    private function entryImportTruncate(){
	if( $this->doc('is_commited') ){
	    return false;
	}
	$doc_id=$this->doc('doc_id');
	return $this->delete('document_entries',['doc_id'=>$doc_id]);
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
		$src[$i]="ROUND($src[$i]/$curr_correction/$doc_vat_ratio,2)";
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
    //////////////////////////////////////////
    // FOOT SECTION
    //////////////////////////////////////////
    protected function footGet(){
	$curr_code=$this->Hub->pcomp('curr_code');
	$curr_symbol=$this->get_value("SELECT curr_symbol FROM curr_list WHERE curr_code='$curr_code'");
	$sql="SELECT
	    ROUND(SUM(weight),2) total_weight,
	    ROUND(SUM(volume),2) total_volume,
	    SUM(product_sum_vatless) vatless,
	    SUM(product_sum_total) total,
	    SUM(product_sum_total-product_sum_vatless) vat,
	    SUM(ROUND(product_quantity*self_price,2)) self,
	    '$curr_symbol' curr_symbol
	FROM tmp_doc_entries";
	return $this->get_row($sql);
    }
    //////////////////////////////////////////
    // TRANS SECTION
    //////////////////////////////////////////
    protected function transDisable(){
        $Trans=$this->Hub->load_model("AccountsCore");
        $this->db_transaction_start();
        $ok=$Trans->documentTransDisable( $this->doc('doc_id') );
        if( $ok ){
            $this->db_transaction_commit();
            return true;
        }
        $this->db_transaction_rollback();
        return false;
    }
    protected function transClear(){
        $Trans=$this->Hub->load_model("AccountsCore");
        $this->db_transaction_start();
        $ok=$Trans->documentTransClear( $this->doc('doc_id') );
        if( $ok ){
            $this->db_transaction_commit();
            return true;
        }
        $this->db_transaction_rollback();
        return false;
    }
    protected function transUpdate(){
	$foot=$this->footGet();
        if ($this->Hub->pcomp('curr_code') == $this->Hub->acomp('curr_code')) {
	    $doc_ratio=0;
	} else {
	    $doc_ratio=$this->doc('doc_ratio');
	}
        $Trans=$this->Hub->load_model("AccountsCore");
        $this->db_transaction_start();
        $ok=$Trans->documentTransUpdate( $this->doc('doc_id'), $foot,  $doc_ratio);
        if( $ok ){
            $this->db_transaction_commit();
            return true;
        }
        $this->db_transaction_rollback();
        return false;
    }
    protected function transCreate(){
        $this->db_transaction_start();
        
        $this->db_transaction_commit();
    }
    
    protected $document_transaction_scheme=[
        [
            'role'=>'total',
            'comment'=>'',
            'debit'=>'',
            'credit'=>''
        ]
    ];
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    

    /*
     * Suggestion
     */
    public $suggestFetch=['q'=>'string'];
    public function suggestFetch($q){
	if( $q=='' ){
	    return [];
	}
	$company_lang = $this->Hub->pcomp('language');
	if( !$company_lang ){
	    $company_lang='ru';
	}
	$where=['is_service=0'];
	$clues=  explode(' ', $q);
	foreach ($clues as $clue) {
            if ($clue == ''){
                continue;
	    }
            $where[]="(product_code LIKE '%$clue%' OR $company_lang LIKE '%$clue%')";
        }
	$sql="
	    SELECT
		product_code,
		$company_lang label,
		product_spack,
		product_quantity
	    FROM
		prod_list
		    JOIN
		stock_entries USING(product_code)
	    WHERE
		".( implode(' AND ',$where) )."
		    ORDER BY fetch_count-DATEDIFF(NOW(),fetch_stamp) DESC, product_code
	    LIMIT 15
	    ";
	return $this->get_list($sql);
    }
    
    public $pickerListFetch=['parent_id'=>'int','offset'=>['int',0],'limit'=>['int',10],'sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json'];
    public function pickerListFetch($parent_id,$offset,$limit,$sortby,$sortdir,$filter){
	$pcomp_id=$this->Hub->pcomp('company_id');
	$doc_ratio=$this->Hub->pref('usd_ratio');
	
	$having=$this->makeFilter($filter);
	$order='';
	$where='';
	if( $parent_id ){
	    $branch_ids=$this->treeGetSub('stock_tree',$parent_id);
	    $where="WHERE se.parent_id IN (".implode(',',$branch_ids).")";
	}
	if( $sortby ){
	    $order="ORDER BY $sortby $sortdir";
	}
	$sql="SELECT 
		pl.product_code,
		ru,
		product_quantity,
		ROUND(GET_PRICE(product_code,'$pcomp_id','$doc_ratio'),2) price,
		product_spack
	    FROM 
		stock_entries se
		    JOIN
		prod_list pl USING(product_code)
	    $where 
	    HAVING $having 
	    $order
	    LIMIT $limit OFFSET $offset";
	return $this->get_list($sql);
    }
}