<?php

trait DocumentBaseEntries{
    
    public function entryGet(int $doc_entry_id) {
        $entry_light = $this->get_row("SELECT * FROM document_entries JOIN prod_list USING(product_code) WHERE doc_entry_id=$doc_entry_id");
        if( !$entry_light ){
            throw new Exception("Document entry #$doc_entry_id not found"."SELECT * FROM document_entries JOIN prod_list USING(product_code) WHERE doc_entry_id=$doc_entry_id",404);
        }
        $this->documentSelect($entry_light->doc_id);
        return $entry_light;
    }

    public function entryCreate(int $doc_id, object $entry) {
        $this->documentSelect($doc_id);
        $this->db_transaction_start();
        $doc_entry_id = $this->create('document_entries', ['doc_id' => $doc_id]);
        $update_ok = $this->entryUpdate($doc_entry_id, $entry);
        if ($update_ok) {
            $this->db_transaction_commit();
            return true;
        }
        $this->db_transaction_rollback();
        return false;
    }

    /**
     * Makes changes to entry depend on commitment status. 
     * Must be called within transaction
     * 
     * @param int $doc_entry_id
     * @param object $new_entry_data
     * @param object $current_entry_data
     */
    protected function entrySave(int $doc_entry_id, object $new_entry_data, object $current_entry_data) {
        return false;
    }

    /**
     * 
     * @param int $doc_entry_id
     * @param object $new_entry_data
     * @return boolean
     */
    public function entryUpdate(int $doc_entry_id, object $new_entry_data) {
        if (!$doc_entry_id) {
            return false;
        }
        $current_entry_data = $this->entryGet($doc_entry_id);
        if (!$this->doc_id) {//document must be selected
            return false;
        }
        $this->db_transaction_start();
        $this->Topic('documentEntryChanged')->publish($doc_entry_id);
        $modify_stock = $this->isCommited() ? true : false;
        $update_ok = $this->entrySave($doc_entry_id, $new_entry_data, $current_entry_data, $modify_stock);
        if (!$update_ok) {
            $this->db_transaction_rollback();
            return false;
        }
        if ($this->isCommited()) {
            $this->transSchemeUpdate();
        }
        $this->db_transaction_commit();
        return true;
    }

    /**
     * Deletes entry by id
     * @param int $doc_entry_id
     * @return boolean
     */
    public function entryDelete(int $doc_id, int $doc_entry_id) {
        $this->documentSelect($doc_id);
        if (!$this->doc_id) {//document must be selected
            return false;
        }
        $this->db_transaction_start();
        $update_ok = true;
        if ($this->isCommited()) {
            $entry = (object) [
                        'product_quantity' => 0
            ];
            $update_ok = $this->entryUpdate($doc_entry_id, $entry);
        }
        $delete_ok = $this->delete('document_entries', ['doc_entry_id' => $doc_entry_id]);
        if ($update_ok && $delete_ok) {
            $this->db_transaction_commit();
            return true;
        }
        $this->db_transaction_rollback();
        return false;
    }

    protected function entryErrorGet($doc_entry_id) {
        $check_entry = $this->get_value("SELECT CHK_ENTRY($doc_entry_id)");
        $error_text = substr($check_entry, strpos($check_entry, " "));
        $entry = $this->get_row("SELECT product_code,ru product_name FROM document_entries JOIN prod_list USING(product_code) WHERE doc_entry_id=$doc_entry_id");
        $entry->error = $error_text;
        return "$error_text ($entry->product_name)";
    }
    
    protected function entryCommit($doc_entry_id, $new_product_quantity = NULL) {
        return false;
    }

    protected function entryUncommit($doc_entry_id) {
        return false;
    }

    /**
     * 
     * @param int $doc_id
     * @param int $doc_entry_id
     * @return type
     */
    public function entryListGet(int $doc_id, int $doc_entry_id = 0) {
        if( $doc_id==0 ){
            return [];
        }
        $this->entryListCreate($doc_id, $doc_entry_id);
        return $this->get_list("SELECT * FROM tmp_entry_list ORDER BY product_code");
    }

    protected function entryListClearCache(){
        return null;
    }
    
    protected function entryListCreate(int $doc_id, int $doc_entry_id = 0) {
        return null;
    }

    public function entryListUpdate(int $doc_id, array $entry_list) {
        return null;
    }

    public function entryListDelete(int $doc_id, array $doc_entry_ids) {
        $this->documentSelect($doc_id);
        $ok = true;
        foreach ($doc_entry_ids as $doc_entry_id) {
            $ok = $ok && $this->entryDelete($doc_id, $doc_entry_id);
        }
        return $ok;
    }
    
    private function entryListRecalculateNetTotal(int $doc_id,$pcomp_id,$usd_ratio){
        $vat_rate=$this->doc('vat_rate');
        $nettotal_sql="
                    SELECT
                        SUM(
                            ROUND(GET_SELL_PRICE(product_code,'{$pcomp_id}','{$usd_ratio}')*(1+$vat_rate/100),2)*product_quantity
                        ) nettotal
                    FROM
                        document_entries
                    WHERE
                        doc_id='$doc_id'
                    ";
        return $this->get_value($nettotal_sql);
    }
    
    public function entryListRecalculate( int $doc_id, float $amount=0, string $action='discount', string $source='pricelist'){
        $this->documentSelect($doc_id);
        $pcomp_id=$this->doc('passive_company_id');
        $pcomp=$this->Hub->load_model('Company')->companyGet($pcomp_id);
        $usd_ratio=$this->doc('doc_ratio');
        $skip_breakeven_check=$pcomp->skip_breakeven_check;
        if($action=='discount'){
            $ratio=1-abs($amount)/100;
        } else 
        if($action=='increase'){
            $ratio=1+abs($amount)/100;
        } else 
        if($action=='add'){
            if( $source=='pricelist' ){
                $total=$this->entryListRecalculateNetTotal($doc_id,$pcomp_id,$usd_ratio);
            } else {
                $total=$this->footGet($doc_id)->total;
            }
            $ratio=1+abs($amount)/$total;
        } else 
        if($action=='substract'){
            if( $source=='pricelist' ){
                $total=$this->entryListRecalculateNetTotal($doc_id,$pcomp_id,$usd_ratio);
            } else {
                $total=$this->footGet($doc_id)->total;
            }
            $ratio=1-abs($amount)/$total;
            if($ratio<0){
                return false;
            }
        }
        $entry_recalc_sql="
            UPDATE
                document_entries de
            SET
                invoice_price=      IF( '$source'='pricelist',ROUND(GET_SELL_PRICE(de.product_code,'{$pcomp_id}','{$usd_ratio}'),2),invoice_price)*$ratio,
                breakeven_price =   IF( '$skip_breakeven_check',0,ROUND(GET_BREAKEVEN_PRICE(product_code,'{$pcomp_id}','{$usd_ratio}',self_price),2))
            WHERE
                doc_id=$doc_id";
        return $this->query($entry_recalc_sql);
    }

    public function entryImport(int $doc_id, string $label) {
        $this->documentSelect($doc_id);
        $doc_was_commited = $this->doc('is_commited');
        $this->db_transaction_start();
        if ($doc_was_commited) {
            $this->documentUpdate($doc_id, (object) ['head'=>(object) ['is_commited'=>false]]);
        }

        $this->entryImportTruncate();
        $source = array_map('addslashes', $this->request('source', 'raw'));
        $target = array_map('addslashes', $this->request('target', 'raw'));
        $source[] = $doc_id;
        $target[] = 'doc_id';
        $this->entryImportFromTable('document_entries', $source, $target, '/product_code/product_quantity/invoice_price/party_label/doc_id/', $label);
        $this->query("DELETE FROM imported_data WHERE {$source[0]} IN (SELECT product_code FROM document_entries WHERE doc_id={$doc_id})");
        $imported_count = $this->db->affected_rows();
         if ($doc_was_commited) {
            $this->documentUpdate($doc_id, (object) ['head'=>(object) ['is_commited'=>true]]);
        }
        $this->db_transaction_commit();
        return $imported_count;
    }

    private function entryImportTruncate() {
        if ($this->doc('is_commited')) {
            return false;
        }
        $this->entryListClearCache();
        $doc_id = $this->doc('doc_id');
        return $this->delete('document_entries', ['doc_id' => $doc_id]);
    }

    private function entryImportFromTable($table, $src, $trg, $filter, $label) {
        $target = [];
        $source = [];
        $doc_vat_ratio = 1 + $this->doc('vat_rate') / 100;
        $curr_correction = $this->documentCurrencyCorrectionGet();
        $quantity_source_field = '';
        for ($i = 0; $i < count($trg); $i++) {
            if (strpos($filter, "/{$trg[$i]}/") === false || empty($src[$i])) {
                continue;
            }
            if ($trg[$i] == 'product_code') {
                $product_code_source = $src[$i];
            }
            if ($trg[$i] == 'invoice_price') {
                $src[$i] = "ROUND($src[$i]/$curr_correction/$doc_vat_ratio,2)";
            }
            if ($trg[$i] == 'product_quantity') {
                $quantity_source_field = $src[$i];
            }
            $target[] = $trg[$i];
            $source[] = $src[$i];
        }
        $target_list = implode(',', $target);
        $source_list = implode(',', $source);
        $this->query("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label='$label' AND $product_code_source IN (SELECT product_code FROM stock_entries) ON DUPLICATE KEY UPDATE product_quantity=product_quantity+$quantity_source_field");
        return $this->db->affected_rows();
    }
    
    
    
    
    
}