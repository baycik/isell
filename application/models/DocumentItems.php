<?php
class DocumentItems extends DocumentCore{
    public function suggestFetch( string $q='', int $offset=0,int $limit=10, int $doc_id=0, int $category_id=0 ){
        session_write_close();
        $matches=$this->suggestResultFetch($q, $offset, $limit, $doc_id, $category_id);
        if( !$matches ){
            $matches=$this->suggestResultFetch($this->transliterate($q,'fromlatin'), $offset, $limit, $doc_id, $category_id);
        }
        if( !$matches ){
            $matches=$this->suggestResultFetch($this->transliterate($q,'fromcyrilic'), $offset, $limit, $doc_id, $category_id);
        }
        return $matches;
    }
    
    private function suggestResultFetch( string $q, int $offset=0,int $limit=10, int $doc_id=0, int $category_id=0 ){
        $pcomp_id=$this->Hub->pcomp('company_id');
        $usd_ratio=$this->Hub->pref('usd_ratio');
	if( $doc_id ){
	    $this->selectDoc($doc_id);
	    $pcomp_id=$this->doc('passive_company_id');
	    $usd_ratio=$this->doc('doc_ratio');
	}
	$where="1";
	if( strlen($q)==13 && is_numeric($q) ){
	    $where="product_barcode=$q";
	} else if( $q ){
	    $cases=[];
	    $clues=  explode(' ', $q);
	    foreach ($clues as $clue) {
		if ($clue == ''){
		    continue;
		}
		$cases[]="(pl.product_code LIKE '%$clue%' OR ru LIKE '%$clue%')";
	    }
	    if( count($cases)>0 ){
		$where=implode(' AND ',$cases);
	    }
	}
        if( $category_id ){
            $branch_ids = $this->treeGetSub('stock_tree', $category_id);
            $where .= " AND parent_id IN (" . implode(',', $branch_ids) . ")";
        }
        if( $this->doc('doc_type')==3 || $this->doc('doc_type')==4 ){
            $where .= " AND is_service=1";
        }
        $this->query("SET @promo_limit:=3;");
	$sql="
            SELECT
                *,
		ROUND(GET_SELL_PRICE(product_code,'$pcomp_id','$usd_ratio'),2) product_price_total,
                ROUND(GET_PRICE(product_code,'$pcomp_id','$usd_ratio'),2) product_price_total_raw
            FROM (
                SELECT
                    product_id,
                    pl.product_code,
                    pl.analyse_class,
                    ru product_name,
                    product_spack,
                    product_quantity leftover,
                    product_img,
                    product_unit,
                    product_reserved,
                    product_awaiting,
                    is_service,
                    CONCAT( 
                        product_quantity<>0,
                        IF( prl.product_code IS NOT NULL AND (@promo_limit:=@promo_limit-1)>=0,1,0),
                        LPAD(fetch_count-DATEDIFF(NOW(),COALESCE(se.fetch_stamp,se.modified_at)),6,'0')
                    ) popularity
                FROM
                    stock_entries se
                        JOIN
                    prod_list pl USING(product_code)
                        LEFT JOIN
                    price_list prl ON se.product_code=prl.product_code AND label='PROMO'
                WHERE $where
                ORDER BY 
                    popularity DESC,
                    pl.product_code
                LIMIT $limit OFFSET $offset) inner_table";
        $suggested=$this->get_list($sql);//for plugin modifications
        return $suggested;
    }
    
    protected function footerGet(){
        $this->entriesTmpCreate();
	//$use_total_as_base=(bool) $this->Hub->pref('use_total_as_base');
	//if($use_total_as_base){
	    $sql="SELECT
		    ROUND(SUM(weight),2) total_weight,
		    ROUND(SUM(volume),2) total_volume,
		    SUM(product_sum_vatless) vatless,
		    SUM(product_sum_total) total,
		    SUM(product_sum_total-product_sum_vatless) vat,
		    SUM(ROUND(product_quantity*self_price,2)) self,
		    @curr_symbol curr_symbol
		FROM tmp_doc_entries";
	//} else {
	    
	//}

	return $this->get_row($sql);
    }
    
    private function entriesTmpCreate( $skip_vat_correction=false, $skip_curr_correction=false ){
	$doc_id=$this->doc('doc_id');
	$this->calcCorrections( $skip_vat_correction, $skip_curr_correction );
        $curr_code=$this->Hub->acomp('curr_code');
	$company_lang = $this->Hub->pcomp('language')??'ru';
        $pcomp_price_label=$this->Hub->pcomp('price_label');
        $this->query("DROP TEMPORARY TABLE IF EXISTS tmp_doc_entries");
        $sql="CREATE TEMPORARY TABLE tmp_doc_entries ( INDEX(product_code) ) AS (
                SELECT
                    *,
                    IF(doc_type=1,product_price_total-buy<0.01,product_price_total-buy>0.01) is_loss
                FROM
                (SELECT
                    doc_entry_id,
                    ROUND(invoice_price * @curr_correction, 2) AS product_price_vatless,
                    ROUND(invoice_price * @curr_correction * product_quantity,2) product_sum_vatless,
                    ROUND(invoice_price * @curr_correction * @vat_ratio, 2) AS product_price_total,
		    ROUND(invoice_price * @curr_correction * @vat_ratio * product_quantity,2) product_sum_total,
                    ROUND(breakeven_price,2) breakeven_price,
                    product_quantity*product_weight weight,
                    product_quantity*product_volume volume,
                    pl.product_code,
                    pl.product_id,
                    $company_lang product_name,
                    (product_quantity+0) product_quantity,
                    CHK_ENTRY(doc_entry_id) AS row_status,
                    product_unit,
                    party_label,
                    product_article,
                    product_barcode,
                    analyse_origin,
                    analyse_class,
                    self_price,
                    buy*IF(curr_code IS NULL OR curr_code='' OR '$curr_code'=ppl.curr_code,1,doc_ratio*@curr_correction) buy,
                    curr_code,
                    doc_type
                FROM
                    document_list
                        JOIN
                    document_entries de USING(doc_id)
                        JOIN 
                    prod_list pl USING(product_code)
                        LEFT JOIN
                    price_list ppl ON de.product_code=ppl.product_code AND label='$pcomp_price_label'
                WHERE
                    doc_id='$doc_id'
                ORDER BY pl.product_code) t
                )";
        //die($sql);
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
    
    

    public function entryAdd( int $doc_id, string $code, float $quantity, float $price=NULL , string $mode=NULL){
        if($doc_id){
            $this->selectDoc($doc_id);
        }
	$Document2=$this->Hub->bridgeLoad('Document');
	$add_duplicate_rows=(bool) $this->Hub->pref('add_duplicate_rows');
        if( $mode==='add_duplicate_rows' ){
            $add_duplicate_rows=1;
        }
	$doc_entry_id=$Document2->addEntry( $code, $quantity, $price, $add_duplicate_rows );
        if( is_numeric($doc_entry_id) ){
            $this->entryBreakevenPriceUpdate($doc_entry_id);
        }
        $Events=$this->Hub->load_model("Events");
        $Events->Topic('documentEntryChanged')->publish($doc_entry_id,$this->_doc);
        return $doc_entry_id;
    }
    public function entryBreakevenPriceUpdate( $doc_entry_id=null, $doc_id=null ){
        if( !$doc_entry_id&&!$doc_id ){
            return;
        }
        $pcomp_id=$this->doc('passive_company_id');
        $usd_ratio=$this->doc('doc_ratio');
        $doc_type=$this->doc('doc_type');
        
        $skip_breakeven_check=$this->Hub->pcomp('skip_breakeven_check');
        if( abs($doc_type)!=1 ){
            $skip_breakeven_check=true;
        }
        if( $doc_entry_id ){
            $where="doc_entry_id=$doc_entry_id";
        } else {
            $where="doc_id=$doc_id";
        }
        if( $skip_breakeven_check ){
            $sql="UPDATE 
                    document_entries 
                SET 
                    breakeven_price = 0
                WHERE 
                    $where";
        } else {
            $sql="UPDATE 
                    document_entries 
                SET 
                    breakeven_price = ROUND(GET_BREAKEVEN_PRICE(product_code,'$pcomp_id','$usd_ratio',self_price),2)
                WHERE 
                    $where";
        }
        $this->query($sql);
    }
/*    public $entryPostAdd=[];
    public function entryPostAdd(){
	$doc_id=$this->request('doc_id','int');
	$code=$this->request('code');
	$quantity=$this->request('quantity','int');
	$this->selectDoc($doc_id);
	return $this->entryAdd($code, $quantity);
    }*/
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
                $this->Hub->set_level(1);
                $ok=$Document2->updateEntry($doc_entry_id, $value, NULL);
//                if( $this->isReserved() ){
//                    $this->reservedCountUpdate();
//                }
                $Document2->updateTrans();
                $this->Topic('documentEntryChanged')->publish($doc_entry_id,$this->_doc, func_get_args());
		return $ok;
	    case 'product_price':
                $this->Hub->set_level(2);
		$ok=$Document2->updateEntry($doc_entry_id, NULL, $value);
                $Document2->updateTrans();
                $this->Topic('documentEntryChanged')->publish($doc_entry_id,$this->_doc);
                return $ok;
	    case 'party_label':
                $this->Hub->set_level(2);
                $this->query("UPDATE document_entries SET party_label='$value' WHERE doc_entry_id='$doc_entry_id'");
                $this->Topic('documentEntryChanged')->publish($doc_entry_id,$this->_doc);
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
        $this->loadDoc($doc_id);
	$Document2=$this->Hub->bridgeLoad('Document');
	$delete_ok=$Document2->deleteEntry($ids_arr);
        $Events=$this->Hub->load_model("Events");
        foreach($ids_arr[0] as $entry_id){
            $Events->Topic('documentEntryChanged')->publish($entry_id,$this->_doc);
        }
        return $delete_ok;
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
        return $this->get_value("SELECT REPLACE(FORMAT('$invoice' * @vat_correction * @curr_correction,2 ),',','') AS product_price");
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
    protected function documentSettingSet( $key, $value ){
	$doc_id=$this->doc('doc_id');
	$this->query("UPDATE document_list SET doc_settings=JSON_SET(COALESCE(doc_settings,JSON_OBJECT()),'$key',CAST('$value' AS JSON)) WHERE doc_id='$doc_id'");	
    }
    protected function documentSettingGet($key){
	$doc_id=$this->doc('doc_id');
	return $this->get_value("SELECT JSON_EXTRACT(doc_settings,'$key') FROM document_list WHERE doc_id='$doc_id'");	
    }
    
    public $entryDocumentCommit=['int'];
    public function entryDocumentCommit( $doc_id ){
	$this->selectDoc($doc_id);
        $passive_company_id=$this->doc('passive_company_id');
        $Company=$this->Hub->load_model("Company");
        $Company->selectPassiveCompany($passive_company_id);
	$this->documentDiscountsSave();
	$Document2=$this->Hub->bridgeLoad('Document');
        $commit_ok=$Document2->commit();
        if( $commit_ok ){
            $this->loadDoc($doc_id);//very very bad engine handling of doc_head updates
            $this->setStatusByCode($doc_id,'processed');
        }
	return $commit_ok;
    }
    
    public $entryDocumentUncommit=['int'];
    public function entryDocumentUncommit( $doc_id ){
	$this->selectDoc($doc_id);
        $is_commited=$this->isCommited();
	$Document2=$this->Hub->bridgeLoad('Document');
        $uncommit_ok=$Document2->uncommit();
        if( $is_commited && $uncommit_ok ){
            $this->loadDoc($doc_id);//very very bad engine handling of doc_head updates
            $this->setStatusByCode($doc_id,'created');
        }
	return $uncommit_ok;
    }
    public $recalc=['int','double'];
    public function recalc( $doc_id, $proc=0 ){
	$this->selectDoc($doc_id);
        $this->entryBreakevenPriceUpdate(null,$doc_id);
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
    
    public $absentToNewdoc=['doc_id'=>'int','new_doc_comment'=>'string'];
    public function absentToNewdoc($old_doc_id,$new_doc_comment){
	$this->Hub->set_level(2);
	$this->selectDoc($old_doc_id);
        if( $this->isCommited() ||  $this->doc('doc_type')!=1 ){
            return false;
        }
        $new_doc_id=$this->createDocument(1);
        $this->absentEntriesMove($new_doc_id, $old_doc_id);
        $this->duplicateHead($new_doc_id, $old_doc_id);
        $this->headUpdate('doc_data',$new_doc_comment);
        return $new_doc_id;
    }
    
    private function absentEntriesMove($new_doc_id,$old_doc_id){
        $sql="SELECT 
            doc_entry_id,
            de.product_code,
            GREATEST(se.product_quantity - se.product_reserved,0) old_product_quantity,
            GREATEST(de.product_quantity - se.product_quantity + se.product_reserved,0) new_product_quantity,
            de.self_price,
            de.party_label,
            de.invoice_price
        FROM 
            document_entries de
                JOIN 
            stock_entries se USING(product_code)
        WHERE 
            doc_id='$old_doc_id'
            AND de.product_quantity > (se.product_quantity-se.product_reserved)";
	$old_entries=$this->get_list($sql);
	foreach($old_entries as $entry){
            $old_entry=[
                'product_quantity'=>$entry->old_product_quantity
            ];
            if($entry->old_product_quantity>0){
                $this->update("document_entries",$old_entry,['doc_entry_id'=>$entry->doc_entry_id]);
            } else {
                $this->delete("document_entries",['doc_entry_id'=>$entry->doc_entry_id]);
            }
            if($entry->new_product_quantity>0){
                $new_entry=[
                    'doc_id'=>$new_doc_id,
                    'product_code'=>$entry->product_code,
                    'product_quantity'=>$entry->new_product_quantity,
                    'self_price'=>$entry->self_price,
                    'party_label'=>$entry->party_label,
                    'invoice_price'=>$entry->invoice_price
                ];
                $this->create("document_entries",$new_entry);
            }
	}
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
        $set_list="";
        $set_list_delimiter="";
	for( $i=0;$i<count($trg);$i++ ){
            if( strpos($filter,"/{$trg[$i]}/")===false || empty($src[$i]) ){
		continue;
	    }
	    if( $trg[$i]=='product_code' ){
		$product_code_source=$src[$i];
	    }
	    if( $trg[$i]=='invoice_price' ){
		$src[$i]=$src[$i].'/@curr_correction/@vat_correction';
                $set_list.="$set_list_delimiter invoice_price={$src[$i]}";
                $set_list_delimiter=",";
	    }
	    if( $trg[$i]=='product_quantity' ){
		$set_list.="$set_list_delimiter product_quantity=product_quantity+{$src[$i]}";
                $set_list_delimiter=",";
	    }
	    if( $trg[$i]=='party_label' ){
                $set_list.="$set_list_delimiter party_label={$src[$i]}";
                $set_list_delimiter=",";
	    }
            
	    $target[]=$trg[$i];
	    $source[]=$src[$i];
	    $set[]="{$trg[$i]}=$src[$i]";
	}
	$target_list=  implode(',', $target);
	$source_list=  implode(',', $source);
        $sql="INSERT INTO $table ($target_list) 
            SELECT $source_list 
                FROM imported_data 
                WHERE label='$label' AND $product_code_source 
                    IN (SELECT product_code FROM stock_entries) 
                    ON DUPLICATE KEY UPDATE 
                    $set_list";
	$this->query($sql);
	return $this->db->affected_rows();
    }
    
    public $documentOut=["doc_id"=>"int","out_type"=>"string"];
    public function documentOut($doc_id,$out_type){
        $dump=[
            'tpl_files'=>'DocumentOut.xlsx',
	    'title'=>"Document-".$this->doc('doc_num'),
            'view'=>[
                'head'=>$this->headGet($doc_id),
                'rows'=>$this->entriesFetch(),
                'footer'=>$this->footerGet()
            ]
        ];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    } 
}
