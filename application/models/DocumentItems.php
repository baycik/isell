<?php
require_once 'DocumentCore.php';
class DocumentItems extends DocumentCore{
    public function suggestFetch(){
	$q=$this->input->get('q');
	$clues=  explode(' ', $q);
	$company_lang = $this->Base->pcomp('language');
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
		product_quantity
	    FROM
		prod_list
		    JOIN
		stock_entries USING(product_code)
	    WHERE
		".( implode(' AND ',$where) )."
		    ORDER BY fetch_count DESC, product_code
	    LIMIT 15
	    ";
	return $this->get_list($sql);
    }
    protected function footerGet(){
	$doc_id=$this->doc('doc_id');
	//$curr_symbol=$this->Base->pcomp('curr_symbol');
	$use_total_as_base=$this->Base->pref('use_total_as_base');
	$this->calcCorrections();
	$sql = "
	    SELECT
		total_weight,
		total_volume,
		vatless,
		total - vatless vat,
		total,
		@curr_symbol curr_symbol,
		self
	    FROM
		(SELECT
		    ROUND(SUM(product_quantity*product_weight),2) total_weight,
		    ROUND(SUM(product_quantity*product_volume),2) total_volume,
		    ROUND(IF('$use_total_as_base',
			SUM(ROUND(invoice_price * @vat_ratio * @curr_correction,@signs_after_dot) * product_quantity),
			SUM(ROUND(invoice_price * @curr_correction * product_quantity,2)) * @vat_ratio
			),2) total,
		    ROUND(IF('$use_total_as_base',
			SUM(ROUND(invoice_price * @vat_ratio * @curr_correction,@signs_after_dot) * product_quantity)/ @vat_ratio,
			SUM(ROUND(invoice_price * @curr_correction * product_quantity,2))
			),2) vatless,
		    SUM(ROUND(product_quantity*self_price,2)) self
		FROM
		    document_entries JOIN prod_list USING(product_code)
		WHERE doc_id='$doc_id') t";
	return $this->get_row($sql);
    }
    protected function entriesFetch( $skip_vat_correction=false ){
	$doc_id=$this->doc('doc_id');
	$this->calcCorrections( $skip_vat_correction );
        $curr_code=$this->Base->acomp('curr_code');
	$company_lang = $this->Base->pcomp('language');
        $use_total_as_base=$this->Base->pref('use_total_as_base');
	$sql = "SELECT
                doc_entry_id,
                pl.product_code,
                $company_lang product_name,
                product_quantity,
                product_unit,
		analyse_section,
                ROUND(invoice_price * @vat_correction * @curr_correction, @signs_after_dot) AS product_price,
                ROUND(IF('$use_total_as_base',
                    ROUND(invoice_price * @vat_correction * @curr_correction, @signs_after_dot) * product_quantity,
                    invoice_price * @vat_correction * @curr_correction * product_quantity),2) product_sum,
                CHK_ENTRY(doc_entry_id) AS row_status,
                party_label,
                product_uktzet,
                self_price,
                @buy:=IF(prl.curr_code='$curr_code' OR prl.curr_code='',buy,buy*doc_ratio) buy,
                IF(doc_type=1,invoice_price<@buy,invoice_price>@buy) is_loss
            FROM
                document_list
		    JOIN
		document_entries de USING(doc_id)
		    JOIN 
		prod_list pl USING(product_code)
                    LEFT JOIN
                price_list prl USING(product_code)
            WHERE
                doc_id='$doc_id'
            ORDER BY pl.product_code";
	return $this->get_list($sql);
    }
    public function entryAdd( $doc_id, $code, $quantity ){
	$this->selectDoc($doc_id);
	$Document2=$this->Base->bridgeLoad('Document');
	return $Document2->addEntry( $code, $quantity );
    }
    public function entryPostAdd(){
	$doc_id=$this->request('doc_id','int');
	$code=$this->request('code');
	$quantity=$this->request('quantity','int');
	return $this->entryAdd($doc_id, $code, $quantity);
    }
    public function entryUpdate( $doc_id, $doc_entry_id, $name, $value='' ){
	$this->check($doc_id,'int');
	$this->selectDoc($doc_id);
	$Document2=$this->Base->bridgeLoad('Document');
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
    public function entryDelete( $doc_id, $ids ){
	$this->check($doc_id,'int');
	$this->selectDoc($doc_id);
	$ids_arr=  json_decode('[['.str_replace(',', '],[', rawurldecode($ids)).']]');
	$Document2=$this->Base->bridgeLoad('Document');
	return $Document2->deleteEntry($ids_arr);
    }
    public function entryStatsGet( $doc_id, $product_code ){
	$this->check($doc_id,'int');
	$this->selectDoc($doc_id);
	$curr=$this->get_row("SELECT curr_symbol FROM curr_list WHERE curr_code='".$this->Base->pcomp('curr_code')."'");
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
	$Document2=$this->Base->bridgeLoad('Document');
	$invoice=$Document2->getProductInvoicePrice($product_code);
        $this->calcCorrections();
        return $this->get_value("SELECT REPLACE(FORMAT('$invoice' * @vat_correction * @curr_correction,".$this->doc('signs_after_dot')."),',','') AS product_price");
//	$invoice=round($invoice,$this->doc('signs_after_dot'));
//	if( !$this->doc('use_vatless_price') ){
//	    $invoice*=1+$this->doc('vat_rate')/100;
//	}
//	return round($invoice,$this->doc('signs_after_dot'));
    }
    public function entryDocumentGet( $doc_id ){
	$this->check($doc_id,'int');
	$this->selectDoc($doc_id);
	$document=array();
	$document['entries']=$this->entriesFetch();
	$document['footer']=$this->footerGet();
	return $document;
    }
    public function entryDocumentCommit( $doc_id ){
	$this->check($doc_id,'int');
	$this->selectDoc($doc_id);
	$Document2=$this->Base->bridgeLoad('Document');
	return $Document2->commit();
    }
    public function entryDocumentUncommit( $doc_id ){
	$this->check($doc_id,'int');
	$this->selectDoc($doc_id);
	$Document2=$this->Base->bridgeLoad('Document');
	return $Document2->uncommit();
    }
    public function recalc( $doc_id, $proc=0 ){
	$this->check($doc_id,'int');
	$this->selectDoc($doc_id);
	$Document2=$this->Base->bridgeLoad('Document');
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
	$old_head=$this->get_row("SELECT cstamp,reg_stamp,doc_data,doc_ratio,notcount,inernn,use_vatless_price FROM document_list WHERE doc_id='$old_doc_id'");
	$this->update("document_list", $old_head, ['doc_id'=>$new_doc_id]);
    }
    public function duplicate( $old_doc_id ){
	$this->check($old_doc_id,'int');
	$this->Base->set_level(2);
	$this->selectDoc($old_doc_id);
	$old_doc_type = $this->doc('doc_type');
	$new_doc_id=$this->createDocument($old_doc_type);
	$this->duplicateEntries($new_doc_id, $old_doc_id);
	$this->duplicateHead($new_doc_id, $old_doc_id);
	return $new_doc_id;
    }
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
	    $target[]=$trg[$i];
	    $source[]=$src[$i];
	    $set[]="{$trg[$i]}=$src[$i]";
	}
	$target_list=  implode(',', $target);
	$source_list=  implode(',', $source);
	$set_list=  implode(',', $set);
	$this->query("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label='$label' AND $product_code_source IN (SELECT product_code FROM stock_entries) ON DUPLICATE KEY UPDATE $set_list");
	return $this->db->affected_rows();
    }
}
