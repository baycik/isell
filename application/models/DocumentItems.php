<?php
require_once 'DocumentCore.php';
class DocumentItems extends DocumentCore{
    public $suggestFetch=['q'=>'string'];
    public function suggestFetch($q){
	$clues=  explode(' ', $q);
	$company_lang = $this->Hub->pcomp('language');
	$where=array();
	foreach ($clues as $clue) {
            if ($clue == ''){
                continue;
	    }
            $where[]="(product_code LIKE '%$clue%' OR $company_lang LIKE '%$clue%')";
        }
	if( $this->isServiceDoc() ){
	    $where[]='is_service=1';
	}
	$sql="
	    SELECT
		product_code,
		$company_lang label,
		product_spack,
		product_quantity,
                product_img
	    FROM
		stock_entries
		    JOIN
		prod_list USING(product_code)
	    WHERE
		".( implode(' AND ',$where) )."
		    ORDER BY fetch_count-DATEDIFF(NOW(),fetch_stamp) DESC, product_code
	    LIMIT 15
	    ";
	return $this->get_list($sql);
    }
    protected function footerGet(){
        $this->entriesTmpCreate();
        $sql="SELECT
                ROUND(SUM(weight),2) total_weight,
                ROUND(SUM(volume),2) total_volume,
                SUM(product_sum_vatless) vatless,
                SUM(product_sum_total) total,
                SUM(product_sum_total-product_sum_vatless) vat,
                SUM(ROUND(product_quantity*self_price,2)) self,
                @curr_symbol curr_symbol
            FROM tmp_doc_entries";
	return $this->get_row($sql);
    }
    
    
    private function entriesTmpCreate( $skip_vat_correction=false ){
	$doc_id=$this->doc('doc_id');
	$this->calcCorrections( $skip_vat_correction );
        $curr_code=$this->Hub->acomp('curr_code');
	$company_lang = $this->Hub->pcomp('language');
        $use_total_as_base=$this->Hub->pref('use_total_as_base');

        $this->query("DROP TEMPORARY TABLE IF EXISTS tmp_doc_entries");
        $sql="CREATE TEMPORARY TABLE tmp_doc_entries ( INDEX(product_code) ) ENGINE=MyISAM AS (
                SELECT 
                    *,
                    ROUND(product_price_vatless*product_quantity,2) product_sum_vatless,
                    ROUND(product_price_total*product_quantity,2) product_sum_total
                FROM
                (SELECT
                    doc_entry_id,
                    pl.product_code,
                    $company_lang product_name,
                    product_quantity,
                    ROUND(invoice_price * @curr_correction, @signs_after_dot) AS product_price_vatless,
                    ROUND(invoice_price * @curr_correction * @vat_ratio, @signs_after_dot) AS product_price_total,
                    product_quantity*product_weight weight,
                    product_quantity*product_volume volume,
                    CHK_ENTRY(doc_entry_id) AS row_status,
                    product_unit,
                    party_label,
                    analyse_section,
                    product_uktzet,
                    self_price,
                    IF(doc_type=1,invoice_price<self_price-0.01,invoice_price-0.01>self_price) is_loss
                FROM
                    document_list
                        JOIN
                    document_entries de USING(doc_id)
                        JOIN 
                    prod_list pl USING(product_code)
                WHERE
                    doc_id='$doc_id'
                ORDER BY pl.product_code) t
                )";
        $this->query($sql);
    }
    
    
    
    protected function entriesFetch( $skip_vat_correction=false ){
        $this->entriesTmpCreate();
        if( $this->doc('use_vatless_price') ){
            $sql="SELECT *, product_price_vatless product_price, product_sum_vatless product_sum FROM tmp_doc_entries";
        } else {
            $sql="SELECT *, product_price_total product_price, product_sum_total product_sum FROM tmp_doc_entries";
        }
        return $this->get_list($sql);
    }
    public function entryAdd( $code, $quantity, $price=NULL ){
	$Document2=$this->Hub->bridgeLoad('Document');
	return $Document2->addEntry( $code, $quantity, $price );
    }
    public $entryPostAdd=[];
    public function entryPostAdd(){
	$doc_id=$this->request('doc_id','int');
	$code=$this->request('code');
	$quantity=$this->request('quantity','int');
	$this->selectDoc($doc_id);
	return $this->entryAdd($code, $quantity);
    }
    public $entryUpdate=['int','int','string','string'];
    public function entryUpdate( $doc_id, $doc_entry_id, $name=null, $value=null ){
        if( $name==null && $value==null ){
            $name=$this->request('name');
            $value=$this->request('value');
        }
	$this->selectDoc($doc_id);
	$Document2=$this->Hub->bridgeLoad('Document');
	switch( $name ){
	    case 'product_quantity':
                $ok=$Document2->updateEntry($doc_entry_id, $value, NULL);
                $Document2->updateTrans();
		return $ok;
	    case 'product_price':
		$ok=$Document2->updateEntry($doc_entry_id, NULL, $value);
                $Document2->updateTrans();
                return $ok;
	    case 'party_label':
                $this->query("UPDATE document_entries SET party_label='$value' WHERE doc_entry_id='$doc_entry_id'");
		return true;
	}
    }
    public $entryDelete=['int','string'];
    public function entryDelete( $doc_id, $ids ){
	$ids_arr=  json_decode('[['.str_replace(',', '],[', rawurldecode($ids)).']]');
	
	return $this->entryDeleteArray($doc_id, $ids_arr);
    }
    
    public function entryDeleteArray($doc_id,$ids_arr){
	$this->selectDoc($doc_id);
	$Document2=$this->Hub->bridgeLoad('Document');
	return $Document2->deleteEntry($ids_arr);
    }
    
    public $entryStatsGet=['int','string'];
    public function entryStatsGet( $doc_id, $product_code ){
	$this->check($doc_id,'int');
	$this->selectDoc($doc_id);
	$curr=$this->get_row("SELECT curr_symbol FROM curr_list WHERE curr_code='".$this->Hub->pcomp('curr_code')."'");
	$sql="SELECT 
	    product_quantity,
	    product_spack
	FROM 
	    stock_entries
		JOIN
	    prod_list USING(product_code)
	WHERE
	    product_code='$product_code'";
	$stats=$this->get_row($sql);
	$stats->curr_symbol=$curr->curr_symbol;
	$stats->price=$this->entryPriceGet($product_code);
	return $stats;
    }
    private function entryPriceGet( $product_code ){
	$Document2=$this->Hub->bridgeLoad('Document');
	$invoice=$Document2->getProductInvoicePrice($product_code);
        $this->calcCorrections();
        return $this->get_value("SELECT REPLACE(FORMAT('$invoice' * @vat_correction * @curr_correction,".$this->doc('signs_after_dot')."),',','') AS product_price");
//	$invoice=round($invoice,$this->doc('signs_after_dot'));
//	if( !$this->doc('use_vatless_price') ){
//	    $invoice*=1+$this->doc('vat_rate')/100;
//	}
//	return round($invoice,$this->doc('signs_after_dot'));
    }
    public $entryDocumentGet=['int'];
    public function entryDocumentGet( $doc_id ){
	$this->selectDoc($doc_id);
	$document=array();
	$document['entries']=$this->entriesFetch();
	$document['footer']=$this->footerGet();
	return $document;
    }
    
    private function documentDiscountsSave(){
	$pcomp_id=$this->Hub->pcomp("company_id");
	$discount_list=$this->get_list("SELECT branch_id,discount FROM companies_discounts WHERE company_id='$pcomp_id'");
	$discount_obj=new stdClass();
	foreach($discount_list as $dsc ){
	    $discount_obj->{"b".$dsc->branch_id}=$dsc->discount;
	}
	$discounts_json=json_encode($discount_obj, JSON_NUMERIC_CHECK);
	$this->documentSettingSet( '$.discounts', $discounts_json );
    }
    private function documentSettingSet( $key, $value ){
	$doc_id=$this->doc('doc_id');
	$this->query("UPDATE document_list SET doc_settings=JSON_SET(COALESCE(doc_settings,JSON_OBJECT()),'$key',CAST('$value' AS JSON)) WHERE doc_id='$doc_id'");	
    }
    private function documentSettingGet($key){
	$doc_id=$this->doc('doc_id');
	return $this->get_value("SELECT JSON_EXTRACT(doc_settings,'$key') FROM document_list WHERE doc_id='$doc_id'");	
    }
    public $entryDocumentCommit=['int'];
    public function entryDocumentCommit( $doc_id ){
	$this->check($doc_id,'int');
	$this->selectDoc($doc_id);
	$this->documentDiscountsSave();
	$Document2=$this->Hub->bridgeLoad('Document');
	return $Document2->commit();
    }
    public $entryDocumentUncommit=['int'];
    public function entryDocumentUncommit( $doc_id ){
	$this->check($doc_id,'int');
	$this->selectDoc($doc_id);
	$Document2=$this->Hub->bridgeLoad('Document');
	return $Document2->uncommit();
    }
    public $recalc=['int','double'];
    public function recalc( $doc_id, $proc=0 ){
	$this->check($doc_id,'int');
	$this->selectDoc($doc_id);
	$Document2=$this->Hub->bridgeLoad('Document');
	$Document2->selectDoc($doc_id);
	$Document2->recalc($proc);
    }
    private function duplicateEntries($new_doc_id,$old_doc_id){
	$old_entries=$this->get_list("SELECT product_code,product_quantity,self_price,party_label,invoice_price FROM document_entries WHERE doc_id='$old_doc_id'");
	foreach($old_entries as $entry){
	    $entry->doc_id=$new_doc_id;
	    $this->create("document_entries",$entry);
	}
    }
    private function duplicateHead($new_doc_id,$old_doc_id){
	$old_head=$this->get_row("SELECT cstamp,doc_data,doc_ratio,notcount,use_vatless_price FROM document_list WHERE doc_id='$old_doc_id'");
	$this->update("document_list", $old_head, ['doc_id'=>$new_doc_id]);
    }
    public $duplicate=['int'];
    public function duplicate( $old_doc_id ){
	$this->check($old_doc_id,'int');
	$this->Hub->set_level(2);
	$this->selectDoc($old_doc_id);
	$old_doc_type = $this->doc('doc_type');
	$new_doc_id=$this->createDocument($old_doc_type);
	$this->duplicateEntries($new_doc_id, $old_doc_id);
	$this->duplicateHead($new_doc_id, $old_doc_id);
	return $new_doc_id;
    }
    public $import=['int'];
    public function import( $doc_id ){
	$this->check($doc_id,'int');
	$this->selectDoc($doc_id);
	if( $this->isCommited() ){
	    return false;
	}
	$label=$this->request('label');
	$source = array_map('addslashes',$this->request('source','raw'));
	$target = array_map('addslashes',$this->request('target','raw'));
	
        $source[]=$this->doc('doc_id');
        $target[]='doc_id';
	$this->importInTable('document_entries', $source, $target, '/product_code/product_quantity/invoice_price/party_label/doc_id/', $label);
	$this->query("DELETE FROM imported_data WHERE {$source[0]} IN (SELECT product_code FROM document_entries WHERE doc_id={$doc_id})");
        return  $this->db->affected_rows();
    }
    private function importInTable( $table, $src, $trg, $filter, $label ){
	$set=[];
	$target=[];
	$source=[];
	$this->calcCorrections();
        $quantity_source_field='';
	for( $i=0;$i<count($trg);$i++ ){
            if( strpos($filter,"/{$trg[$i]}/")===false || empty($src[$i]) ){
		continue;
	    }
	    if( $trg[$i]=='product_code' ){
		$product_code_source=$src[$i];
	    }
	    if( $trg[$i]=='invoice_price' ){
		$src[$i]=$src[$i].'/@curr_correction/@vat_correction';
	    }
	    if( $trg[$i]=='product_quantity' ){
		$quantity_source_field=$src[$i];
	    }
            
	    $target[]=$trg[$i];
	    $source[]=$src[$i];
	    $set[]="{$trg[$i]}=$src[$i]";
	}
	$target_list=  implode(',', $target);
	$source_list=  implode(',', $source);
	$set_list=  implode(',', $set);
	$this->query("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label='$label' AND $product_code_source IN (SELECT product_code FROM stock_entries) ON DUPLICATE KEY UPDATE product_quantity=product_quantity+$quantity_source_field");
	return $this->db->affected_rows();
    }
}
