<?php

/*
 * This class is a base class for all v5+ document handling classes
 */

class DocumentBase extends Catalog {
    protected $doc_id = null;
    protected $doc_type_name="Basic Document";
    protected $document_properties = null;
    
    use DocumentBaseEntries;
    use DocumentBaseTransactions;
    use DocumentBaseSuggestion;

    public function init() {
        $this->Topic("documentBeforeChangeDocStatusId")->subscribe('DocumentBase', 'documentBeforeChangeDocStatusId');
        $this->Topic("documentBeforeChangeIsCommited")->subscribe('DocumentBase', 'transCommitedChangeRefresh');
    }

    /**
     * Getter/setter for document_list table for selected document
     * @param string $field
     * @param string $value
     * @param bool $flush is needed to save changes immediately
     * @return string
     * @throws Exception
     */
    public function doc(string $field = null, string $value = null, bool $flush = true) {
        if (!isset($this->document_properties)) {
            throw new Exception("Can't use properties because Document is not selected");
        }
        if (isset($value)) {
            $this->Hub->set_level(2);
//            if( $this->document_properties->$field==$value ){
//                return false;//should it be $value?
//            }
            $this->document_properties->$field = $value;
            $flush && $this->documentFlush();
        }
        return $this->document_properties->$field ?? null;
    }

    /**
     * Loads document head to memory and checks permissions
     * @param int $doc_id
     * @return boolean
     * @throws Exception
     */
    public function documentSelect(int $doc_id, bool $skip_if_same_doc = true) {
        if ($skip_if_same_doc && $this->doc_id == $doc_id) {
            return true;
        }
        unset($this->document_properties);
        $document_properties = $this->get_row("SELECT * FROM document_list WHERE doc_id='$doc_id'");
        if(!$document_properties){
            throw new Exception("Document #$doc_id not found", 404);
        }
        $Company = $this->Hub->load_model("Company");
        $document_properties->pcomp = $Company->companyGet($document_properties->passive_company_id);
        $is_access_granted = $document_properties->pcomp ? 1 : 0;
        if ($is_access_granted) {
            $this->doc_id = $doc_id;
            $this->document_properties = $document_properties;
            return true;
        }
        throw new Exception("Access denied for selection of this document", 403);
    }

    /**
     * Saves document head from memory
     * @return bool
     * @throws Exception
     */
    private function documentFlush(): bool {
        if (!$this->document_properties) {
            throw new Exception("Can't flush properties because they are not loaded");
        }
        return $this->update('document_list', $this->document_properties, ['doc_id' => $this->document_properties->doc_id]);
    }

    public function isCommited() {
        return $this->doc('is_commited') == 1 ? true : false;
    }

    public function documentNumNext($doc_type, $creation_mode = null) {
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

    protected function documentCurrCorrectionGet() {
        $is_curr_native = $this->doc('pcomp')->curr_code == $this->Hub->acomp('curr_code') ? 1 : 0;
        return $is_curr_native ? 1 : 1 / $this->doc('doc_ratio');
    }
    //////////////////////////////////////////
    // DOCUMENT CRUD SECTION
    //////////////////////////////////////////
    public function documentGet(int $doc_id, array $parts_to_load) {
        $this->documentSelect($doc_id);
    }

    protected function documentCreate(int $doc_type, string $doc_handler = null) {
        $this->Hub->set_level(1);
        $user_id = $this->Hub->svar('user_id');
        $acomp_id = $this->Hub->acomp('company_id');
        $pcomp_id = $this->Hub->pcomp('company_id');
        $vat_rate = $this->Hub->acomp('company_vat_rate');
        $usd_ratio = $this->Hub->pref('usd_ratio');


        $Company = $this->Hub->load_model("Company");
        $is_access_granted = $Company->companyCheckUserPermission($pcomp_id);
        if (!$is_access_granted) {
            throw new Exception("Access denied for creation document for this company");
        }

        $new_document = [
            'doc_type' => ($doc_type ? $doc_type : '1'),
            'doc_handler' => $doc_handler,
            'cstamp' => date('Y-m-d H:i:s'),
            'active_company_id' => $acomp_id,
            'passive_company_id' => $pcomp_id,
            'signs_after_dot' => 2,
            'doc_ratio' => $usd_ratio,
            'vat_rate' => $vat_rate,
            'created_by' => $user_id,
            'modified_by' => $user_id,
            'use_vatless_price' => 0,
            'notcount' => 0,
            'doc_num' => $this->documentNumNext($doc_type),
            'doc_status_id' => 1
        ];
        $prev_document = $this->headPreviousGet($acomp_id, $pcomp_id);
        if ($prev_document) {
            $new_document['doc_type'] = $prev_document->doc_type;
            $new_document['notcount'] = $prev_document->notcount;
            $new_document['signs_after_dot'] = $prev_document->signs_after_dot;
            $new_document['use_vatless_price'] = $prev_document->use_vatless_price;
            $new_document['vat_rate'] = $prev_document->vat_rate;
        }
        $new_doc_id = $this->create('document_list', $new_document);
        return $new_doc_id;
    }

    public function documentUpdate(int $doc_id, object $document) {
        $this->documentSelect($doc_id);
        if($document->head ?? null){
            $this->headUpdate($doc_id, $document->head );
        }
        if($document->entries ?? null){
            $this->entryListUpdate($doc_id, $document->entries);
        }
        if($document->views ?? null){
            $this->viewListUpdate($doc_id, $document->views );
        }
        if($document->trans ?? null){
            $this->transListUpdate($doc_id, $document->trans );
        }
    }

    public function documentDelete(int $doc_id) {
        $this->documentSelect($doc_id);
        if ($this->isCommited()) {
            return false;
        }
        $this->db_transaction_start();
        $this->delete('document_entries', ['doc_id' => $doc_id]);
        $this->delete('document_view_list', ['doc_id' => $doc_id]);
        $this->transSchemeDelete();
        $ok = $this->delete('document_list', ['doc_id' => $doc_id, 'is_commited' => 0]);
        $this->db_transaction_commit();
        return $ok;
    }

    public function documentNameGet() {
        return "?????";
    }

    //DOCUMENT EVENT LISTENERS SECTION
    protected function documentBeforeChangeDocStatusId($field, $new_status_id, $document_properties) {
        if (!isset($new_status_id)) {
            return false;
        }
        $commited_only = $this->get_value("SELECT commited_only FROM document_status_list WHERE doc_status_id='$new_status_id'");
        if ($commited_only != $this->isCommited()) {
            return false;
        }
        return true;
    }

    //////////////////////////////////////////
    // HEAD SECTION
    //////////////////////////////////////////
    public function headGet(int $doc_id = 0) {
        if ($doc_id == 0) {
            return $this->headCreate();
        }
        $this->documentSelect($doc_id);
        $this->document_properties->active_company_label = $this->get_value("SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id={$this->document_properties->active_company_id}");
        $this->document_properties->passive_company_label = $this->get_value("SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id={$this->document_properties->passive_company_id}");
        $this->document_properties->created_by = $this->get_value("SELECT last_name FROM user_list WHERE user_id={$this->document_properties->created_by}");
        $this->document_properties->modified_by = $this->get_value("SELECT last_name FROM user_list WHERE user_id={$this->document_properties->modified_by}");
        $this->document_properties->child_documents = $this->get_list("SELECT doc_id,cstamp,doc_num FROM document_list WHERE parent_doc_id={$doc_id}");
        $this->document_properties->status = $this->get_row("SELECT * FROM document_status_list WHERE doc_status_id={$this->document_properties->doc_status_id}");
        $this->document_properties->type = $this->get_row("SELECT * FROM document_types WHERE doc_type={$this->document_properties->doc_type}");
        $this->document_properties->extra_expenses = $this->headGetExtraExpenses();
        $checkout = $this->get_row("SELECT * FROM checkout_list WHERE parent_doc_id={$doc_id}");
        if ($checkout) {
            $this->document_properties->checkout_id = $checkout->checkout_id;
            $this->document_properties->checkout_status = $checkout->checkout_status;
            $this->document_properties->checkout_modified_by = $this->get_value("SELECT last_name FROM user_list WHERE user_id={$checkout->modified_by}");
        }
        return $this->document_properties;
    }

    private function headCreate() {
        $active_company_id = $this->Hub->acomp('company_id');
        $passive_company_id = $this->Hub->pcomp('company_id');
        if (!$active_company_id || !$passive_company_id) {
            throw new Exception("Passive or active company is not selected");
        }
        $prevHead = $this->get_row("SELECT 
		doc_type
	    FROM 
		document_list 
	    WHERE 
		passive_company_id='$passive_company_id' 
		AND active_company_id='$active_company_id' 
		AND doc_type<10 
		AND is_commited=1 
	    ORDER BY cstamp DESC LIMIT 1");
        $defHead = (object) [];
        $defHead->doc_id = 0;
        $defHead->is_commited = 0;
        $defHead->notcount = 0;
        $defHead->cstamp = date("Y-m-d H:i:s");
        //$defHead->doc_date = date("Y-m-d");
        $defHead->doc_data = '';
        $defHead->type = $this->get_row("SELECT * FROM document_types WHERE doc_type=".($prevHead->doc_type ?? 1));
        $defHead->status=$this->get_row("SELECT * FROM document_status_list WHERE doc_status_id=1");;
        $defHead->doc_num = $this->documentNumNext($defHead->type->doc_type, 'not_increase_number');
        $defHead->doc_ratio = $this->Hub->pref('usd_ratio');
        $defHead->curr_code = $this->Hub->pcomp('curr_code');
        $defHead->vat_rate = $this->Hub->pcomp('vat_rate');
        return $defHead;
    }

    public function headUpdate(int $doc_id, object $head) {
        foreach ($head as $field => $value) {
            $this->headFieldUpdate($doc_id, $field, $value);
        }
    }

    public function headFieldUpdate(int $doc_id, string $field, string $value = null) {
        if($field=='doc_type'){
            return false;
        }
        $fieldCamelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        $this->db_transaction_start();
        $this->documentSelect($doc_id);
        $ok = $this->Topic('documentBeforeChange' . $fieldCamelCase)->publish($field, $value, $this->document_properties);
        if ($ok === false) {
            $this->db_transaction_rollback();
            return false;
        }
        $this->doc($field, $value);
        if ($this->isCommited()) {
            $this->transSchemeUpdate();
        }
        $this->db_transaction_commit();
        return true;
    }

    public function headDelete() {
        throw new Exception("Function Not exists");
    }

    //HEAD UTILS
    private function headPreviousGet($acomp_id, $pcomp_id) {
        $sql = "SELECT 
		* 
	    FROM 
		document_list 
	    WHERE 
		active_company_id='$acomp_id' 
		AND passive_company_id='$pcomp_id' 
		AND doc_type<10 
		AND is_commited=1 
	    ORDER BY cstamp DESC LIMIT 1";
        return $this->get_row($sql);
    }

    private function headGetExtraExpenses() {
        $doc_type = $this->doc('doc_type');
        $doc_id = $this->doc('doc_id');
        if ($doc_id && $doc_type == 2) {//only for buy documents
            return $this->get_value("SELECT ROUND(SUM(self_price-invoice_price),2) FROM document_entries WHERE doc_id=$doc_id LIMIT 1");
        }
        return 0;
    }
    //////////////////////////////////////////
    // FOOTER SECTION
    //////////////////////////////////////////
    public function footGet($doc_id) {
        $this->entryListCreate($doc_id);
        $curr_code = $this->Hub->pcomp('curr_code');
        $curr_symbol = $this->get_value("SELECT curr_symbol FROM curr_list WHERE curr_code='$curr_code'");
        $sql = "SELECT
	    ROUND(SUM(entry_weight_total),2) total_weight,
	    ROUND(SUM(entry_volume_total),2) total_volume,
	    SUM(entry_sum_vatless) vatless,
	    SUM(entry_sum_total) total,
	    SUM(entry_sum_total-entry_sum_vatless) vat,
	    SUM(ROUND(product_quantity*self_price,2)) self,
	    '$curr_symbol' curr_symbol
	FROM 
            tmp_entry_list";
        return $this->get_row($sql);
    }

    //////////////////////////////////////////
    // VIEWS SECTION
    //////////////////////////////////////////
    public function viewGet(int $doc_view_id) {
        return null;
    }

    public function viewCreate(int $doc_id, object $view) {
        return null;
    }

    public function viewUpdate(int $doc_view_id, object $view) {
        return null;
    }

    public function viewDelete(int $doc_view_id) {
        return null;
    }

    public function viewListGet(int $doc_id) {
        return null;
    }

    public function viewListCreate(int $doc_id, array $view_list) {
        return null;
    }

    public function viewListUpdate(int $doc_id, array $view_list) {
        return null;
    }

    public function viewListDelete(int $doc_id, array $view_id_list) {
        return null;
    }
    
    //////////////////////////////////////////
    // UTILS SECTION
    //////////////////////////////////////////
    private function duplicateEntries($new_doc_id, $old_doc_id) {
        $old_entries = $this->get_list("SELECT product_code,product_quantity,self_price,party_label,invoice_price FROM document_entries WHERE doc_id='$old_doc_id'");
        foreach ($old_entries as $entry) {
            $entry->doc_id = $new_doc_id;
            $this->create("document_entries", $entry);
        }
    }

    private function duplicateHead($new_doc_id, $old_doc_id) {
        $old_head = $this->get_row("SELECT cstamp,doc_data,doc_ratio,notcount,use_vatless_price FROM document_list WHERE doc_id='$old_doc_id'");
        $this->update("document_list", $old_head, ['doc_id' => $new_doc_id]);
    }
    public function documentDuplicate($old_doc_id) {
        $this->check($old_doc_id, 'int');
        $this->Hub->set_level(2);
        $this->documentSelect($old_doc_id);
        $old_doc_type = $this->doc('doc_type');
        $new_doc_id = $this->documentCreate($old_doc_type);
        $this->duplicateEntries($new_doc_id, $old_doc_id);
        $this->duplicateHead($new_doc_id, $old_doc_id);
        return $new_doc_id;
    }

    protected function documentCurrencyCorrectionGet() {
        $native_curr = $this->Hub->pcomp('curr_code') && ($this->Hub->pcomp('curr_code') != $this->Hub->acomp('curr_code')) ? 0 : 1;
        return $native_curr ? 1 : 1 / $this->doc('doc_ratio');
    }

    private function documentSetExtraExpenses($expense) {//not beautifull function at all
        $doc_type = $this->doc('doc_type');
        $doc_id = $this->doc('doc_id');
        if ($doc_id && $doc_type == 2) {
            //only for buy documents
            $footer = $this->footerGet();
            $expense_ratio = $expense / $footer->vatless + 1;
            return $this->query("UPDATE document_entries SET self_price=invoice_price*$expense_ratio WHERE doc_id=$doc_id");
        }
    }

}