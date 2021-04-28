<?php

require_once 'Data.php';

class Document extends Data {

    protected $vat_rate = 1;
    public $use_vatless_price = 0;

    public function selectDoc($doc_id) {
        $this->Base->svar('doc_id', $doc_id);
	if ($doc_id == 0) {
	    $this->_doc['doc_id'] = 0;
	} else {
	    $this->loadDoc($doc_id);
	}
    }

    public function doc($name) {
	if ( !isset($this->_doc) ){
	    $doc_id = $this->Base->svar('doc_id');
	    $this->selectDoc($doc_id);
	}
	if ( isset($this->_doc[$name]) ){
	    return $this->_doc[$name];
	}
	else if ( isset($this->_doc->$name) ) {
	    return $this->_doc->$name;
	} else {
	    $this->Base->msg("Trying to access DOCUMENT property '$name', but it's not loaded\n");
	    return NULL;
	}
    }

    protected function isCommited() {
	return $this->doc('is_commited');
    }

    protected function loadDoc($doc_id) {
	$this->_doc = $this->Base->get_row("SELECT 
                dl.*,
                dsl.status_code,
                DATE_FORMAT(cstamp,'%d.%m.%Y') AS doc_date 
                FROM 
                    document_list dl
                        LEFT JOIN
                    document_status_list dsl USING(doc_status_id)
                WHERE doc_id='$doc_id'");
	if (!$this->_doc) {
	    $this->Base->response_error("Невозможно выбрать документ");
	}
	$this->use_vatless_price = $this->_doc['use_vatless_price'];
	$this->vat_rate = 1 + $this->_doc['vat_rate'] / 100;
    }

    protected function getCurrCorrection($mode='') {
	if ($this->Base->pcomp('curr_code') == $this->Base->acomp('curr_code') || $mode == 'total_in_uah') {
	    return 1;
	} else {
	    return 1 / $this->doc('doc_ratio');
	}
    }

    protected function getNextDocNum($doc_type,$creation_mode) {//Util
        $this->Base->LoadClass('PrefOld');
        
        $counter_increase=$creation_mode=='not_increase_number'?0:1;
        $counter_name="counterDocNum_".$doc_type;
        $nextNum=$this->Base->PrefOld->counterNumGet($counter_name,null,$counter_increase);
        if( !$nextNum ){
            $doc_type_row=$this->Base->get_row("SELECT * FROM document_types WHERE doc_type='$doc_type'");
            $counter_title=$doc_type_row['doc_type_name']??'???';
            $this->Base->PrefOld->counterCreate($counter_name,null,$counter_title);
            $nextNum=$this->Base->PrefOld->counterNumGet($counter_name,null,$counter_increase);
        }
        return $nextNum;
    }
    

    

    public function moveDoc($passive_company_id) {
	if (!$this->isCommited()) {
	    $doc_id = $this->doc('doc_id');
	    $this->Base->query("UPDATE document_list SET passive_company_id=$passive_company_id WHERE doc_id=$doc_id");
	    $company_name = $this->Base->get_row("SELECT company_name FROM companies_list WHERE company_id=$passive_company_id", 0);
	    $this->Base->msg("Документ перемещен к $company_name");

	    $user_id = $this->Base->svar('user_id');
	    $this->Base->query("UPDATE document_list SET modified_by='$user_id' WHERE doc_id='$doc_id'");
	} else {
	    $this->Base->msg("Проведенный документ не может быть перенесен!");
	}
    }

    public function duplicateDoc() {
	$doc_id = $this->doc('doc_id');
	$this->loadDoc($doc_id);
	$old_doc = $this->_doc;
	$new_doc_id = $this->add($old_doc['doc_type']);
	$res = $this->Base->query("SELECT * FROM document_entries WHERE doc_id=$old_doc[doc_id]");
	while ($row = mysqli_fetch_assoc($res)) {
	    $copy = array();
	    $copy[] = "product_code='$row[product_code]'";
	    $copy[] = "product_quantity=$row[product_quantity]";
	    $copy[] = "self_price=$row[self_price]";
	    $copy[] = "party_label='$row[party_label]'";
	    $copy[] = "invoice_price=$row[invoice_price]";
	    $copy = implode(',', $copy);
	    $this->Base->query("INSERT INTO document_entries SET doc_id=$new_doc_id, $copy");
	}
	mysqli_free_result($res);
	$copy = array();
	$copy[] = "cstamp='$old_doc[cstamp]'";
	$copy[] = "doc_data='$old_doc[doc_data]'";
	$copy[] = "doc_ratio=$old_doc[doc_ratio]";
	$copy[] = "notcount=$old_doc[notcount]";
	$copy[] = "inernn=$old_doc[inernn]";
	$copy[] = "use_vatless_price=$old_doc[use_vatless_price]";
	$copy = implode(',', $copy);
	$this->Base->query("UPDATE document_list SET $copy WHERE doc_id=$new_doc_id");
	$this->selectDoc($new_doc_id);
	$this->Base->msg("Документ скопирован");
    }

    public function recalc($perc = 0) {
	$rate = (1 + $perc / 100);
	$doc_id = $this->doc('doc_id');
	$res = $this->Base->query("SELECT doc_entry_id,product_code FROM document_entries WHERE doc_id='$doc_id'");
	while ($row = mysqli_fetch_assoc($res)) {
	    $invoice = $this->getProductInvoicePrice($row['product_code']);
	    $this->alterEntry('update', $row['doc_entry_id'], NULL, $invoice * $rate);
	}
	mysqli_free_result($res);
	$this->updateTrans();
    }

//    public function checkInErnn() {
//	$doc_id = $this->doc('doc_id');
//	$has_uktzed = $this->Base->get_row("SELECT 1 FROM document_entries JOIN prod_list USING(product_code) WHERE doc_id=$doc_id AND analyse_origin");
//	if (!$has_uktzed && $this->doc('inernn')) {
//	    $this->updateHead(NULL, 'inernn');
//	} else
//	if ($has_uktzed && !$this->doc('inernn')) {
//	    $this->updateHead(NULL, 'inernn');
//	    $this->Base->msg("Документ СОДЕРЖИТ кода УКТ ЗЕД. \nНалоговая накладная будет включена в ЕРНН!");
//	}
//    }

    protected function setDocumentModifyingUser() {
	$user_id = $this->Base->svar('user_id');
	$this->Base->query("UPDATE document_list SET modified_by='$user_id' WHERE doc_id='" . $this->doc('doc_id') . "'");
    }

    protected function isDebtLimitExceeded() {
	if ($this->doc('doc_type') != 1) {
	    /* only for sell docs */
	    return false;
	}
	$debt_limit = $this->Base->pcomp('debt_limit');
	$deferment = $this->Base->pcomp('deferment');
	if ($debt_limit == 0) {
	    $this->Base->LoadClass('PrefOld');
	    $prefs = $this->Base->PrefOld->prefGet();
	    $debt_limit = isset($prefs['default_debt_limit'])?$prefs['default_debt_limit']:0;
	    if ( $debt_limit==0 && $deferment==0 ) {
		return false;
	    }
	}
	$pcomp_id = $this->Base->pcomp('company_id');
	$this->Base->LoadClass('Accounts');
	$debt_account = $this->Base->Accounts->getAccountBalance(361, $pcomp_id,$deferment);
	$footer = $this->fetchFooter();
	$off_limit = $footer['total'] + $debt_account['balance'] - $debt_limit;
	if( $debt_limit>0 && $off_limit > 0 ){
	    $this->Base->msg("Лимит долга в $debt_limit превышен на " . round($off_limit, 2) . "{$footer['curr_symbol']}!\n");
	    return false;
	}
        if( $deferment>0 && $debt_account['expired_balance']>0 ){
	    $this->Base->msg("Имеется просроченная задолженность " . round($debt_account['expired_balance'], 2) . "{$footer['curr_symbol']}!\n");
	    return false;
        }
	return false;
    }

    protected function normalizeQuantitySign() {
	$doc_id = $this->doc('doc_id');
	$quantity_sign = $this->doc('is_reclamation') ? -1 : 1;
	$this->Base->query("UPDATE document_entries SET product_quantity=ABS(product_quantity)*$quantity_sign WHERE doc_id=$doc_id");
    }

    protected function getProductDiscount($company_id, $product_code) {
	$sql = "SELECT 
                    discount 
                FROM 
                    companies_discounts cd 
                        JOIN 
                    stock_tree st ON(cd.branch_id=st.top_id) 
                        JOIN 
                    stock_entries se ON(st.branch_id=se.parent_id)
                WHERE 
                    se.product_code='$product_code' 
                    AND cd.company_id='$company_id'";
	$discount = $this->Base->get_row($sql, 0);
	return $discount !== NULL ? $discount : 1;
    }

/////////////////////////////////////////////////////////////////
//DOCUMENT CORE
/////////////////////////////////////////////////////////////////

    public function commit() {
	$this->Base->set_level(2);
	if ( $this->isCommited() ) {
	    $this->Base->msg("Документ уже проведен!\n");
	    return false;
	}
	if ( $this->isDebtLimitExceeded() ) {
	    return false;
	}
	$doc_id = $this->doc('doc_id');

	$company_lang = $this->Base->pcomp('language');
	$sql = $this->getEntriesSqlParts($doc_id);
        
        $skip_breakeven_check=$this->Base->pcomp('skip_breakeven_check');
        $err = $this->Base->get_row("SELECT row_status,ru,ua FROM (SELECT CHK_ENTRY(de.doc_entry_id) AS row_status,ru,ua FROM $sql[table] WHERE $sql[where]) AS t WHERE row_status LIKE 'err%' ".($skip_breakeven_check?"AND row_status NOT LIKE 'err_breakeven%'":'')." LIMIT 1");
	if ($err) {
	    $status_msg = substr($err['row_status'], strpos($err['row_status'], ' '));
	    $this->Base->msg("- $status_msg \"$err[$company_lang]\"\n");
	    return false;
	}
	$this->Base->query("START TRANSACTION");
	$res = $this->Base->query("SELECT doc_entry_id FROM document_entries WHERE doc_id=$doc_id");
	while ($entry = mysqli_fetch_assoc($res)) {
	    if (!$this->alterEntry('commit', $entry['doc_entry_id'], NULL, NULL)) {
		$name = $this->Base->get_row("SELECT $company_lang FROM document_entries JOIN prod_list USING(product_code) WHERE doc_entry_id=$entry[doc_entry_id]", 0);
		$this->Base->msg("Невозможно провести строку: \"$name\"\n");
		$this->Base->query("ROLLBACK");
		return false;
	    }
	}
	if ($this->Base->pcomp('is_supplier') && !$this->doc('is_reclamation') && $this->doc('doc_type') == 2) {
	    $this->updateBuyPriceFromDoc();
	    //$this->updateBuyPartyLabel();
	}
	$entry_num = mysqli_num_rows($res);
	mysqli_free_result($res);
	if ($entry_num == 0) {//Doc is empty
	    $this->Base->msg("\nДокумент пуст!");
	    $this->Base->query("ROLLBACK");
	    return false;
	}
	$this->Base->query("UPDATE document_list SET is_commited=1 WHERE doc_id=$doc_id");

	$this->selectDoc($doc_id);
	if (!$this->updateTrans()) {
	    $this->Base->msg("\nПроводки по документу не установленны!");
	    $this->Base->query("ROLLBACK");
	    return false;
	}
	$this->setDocumentModifyingUser();
	$this->Base->query("COMMIT");
	return true;
    }
    
    private function checkUserPermission( $right ){
        $user_data=$this->Base->svar('user');
        if( isset($user_data->user_permissions) && strpos($user_data->user_permissions, $right)!==false ){
            return true;
        }
        return false;
    }

    public function uncommit() {
	if ($this->isCommited())
	    $this->Base->set_level(2);
	if (!$this->isCommited())
	    return $this->delete();
	$doc_id = $this->doc('doc_id');
	$company_lang = $this->Base->pcomp('language');

	$this->Base->query("START TRANSACTION");
	$res = $this->Base->query("SELECT * FROM document_entries WHERE doc_id='$doc_id'");
	while ($entry = mysqli_fetch_assoc($res)) {
	    if (!$this->alterEntry('uncommit', $entry['doc_entry_id'], NULL, NULL)) {
		$this->Base->query("ROLLBACK");
		$name = $this->Base->get_row("SELECT $company_lang FROM document_entries JOIN prod_list USING(product_code) WHERE doc_entry_id=$entry[doc_entry_id]", 0);
		$this->Base->msg("Невозможно отменить строку: \"$name\"\n");
		return false;
	    }
	}
	mysqli_free_result($res);
	$this->Base->query("UPDATE document_list SET is_commited=0 WHERE doc_id=$doc_id");
	$this->Base->query("COMMIT");

	$this->selectDoc($doc_id);
	$this->clearTrans();
	$this->setDocumentModifyingUser();
	return true;
    }

    protected function alterEntry($action = 'update', $doc_entry_id, $new_quantity = NULL, $new_invoice = NULL, $new_party_label = NULL) {//Must be called within db transaction
	if ($this->isCommited()) {
	    $this->Base->set_level(2);
	}        
	$entry = $this->Base->get_row("SELECT * FROM document_entries WHERE doc_entry_id=$doc_entry_id");
	if ($this->doc('doc_id') != $entry['doc_id']) {
	    $this->Base->msg("Trying to update entry of unselected Doc!!!");
	    return false;
	}
	$invoice = $new_invoice != NULL ? abs($new_invoice) : $entry['invoice_price'];
	$quantity = $new_quantity !== NULL ? $new_quantity : $entry['product_quantity'];
	$party_label = $new_party_label !== NULL ? $new_party_label : $entry['party_label'];
	if ($this->doc('doc_type') == 1) {//Sell document
	    //if ($action == 'commit' || ($this->isCommited() && isset($new_invoice))) {
		$self = $this->getProductSellSelfPrice($entry['product_code'], $quantity, $this->doc('cstamp'));
	    //} else {
		//$self = $entry['self_price'];
	    //}
	} else {//Buy and other document
	    $self = $invoice;
	}
	if ($action == 'commit') {
	    $stock_action = 'increase';
	    $amount = $quantity;
	}
	if ($action == 'uncommit') {
	    $stock_action = 'decrease';
	    $amount = $quantity;
	}
	if ($action == 'update') {
	    $quantity = abs($quantity) * ($this->doc('is_reclamation') ? -1 : 1);
	    if ($this->isCommited() && ($this->doc('doc_type') == 1 || $this->doc('doc_type') == 2)) {
//                if ($entry['self_price'] != $self) {//remove product by old self price and then return by new 
//                    $amount = $entry['product_quantity'];
//                    if ($amount && !$this->moveProduct($entry['product_code'], false, $amount, $entry['self_price'])) {
//                        return false;
//                    }
//                    $stock_action = 'increase';
//                }
		if (isset($new_quantity)) {
		    $stock_action = $quantity > $entry['product_quantity'] ? 'increase' : 'decrease';
		    $amount = abs($quantity - $entry['product_quantity']);
		}
		else{
		    $amount = 0;
		}
	    } else {//don't move product if not commited document
		$amount = 0;
	    }
	}
	if ($amount && !$this->moveProduct($entry['product_code'], $stock_action, $amount, $self, $party_label)) {
	    return false;
	}
	//$signs_after_dot = $this->doc('signs_after_dot');
	$sql = "UPDATE document_entries 
                SET 
                    product_quantity = $quantity,
                    invoice_price = $invoice,
                    self_price = '$self',
                    party_label = '$party_label'
                WHERE
                    doc_entry_id = '$doc_entry_id'";
	$this->Base->query($sql);
	return true;
    }

    protected function moveProduct($product_code, $stock_action, $amount, $self_price, $party_label) {
	if (!$amount) {
	    return false;
	}
        if( $this->doc('notcount')==0 && $this->checkUserPermission( 'nocommit' ) ){
            $this->Base->msg("Нет прав для операий по складу");
            return false;
        }
	$doc_num = $this->doc('doc_num');
	$this->Base->LoadClass('StockOld');
	if ($this->doc('doc_type') == 1) {//Sell Document
	    if ($stock_action == 'increase') {
		return $this->Base->StockOld->decreaseStock($product_code, $amount, $self_price, "Расходный документ $doc_num");
	    } else {
                $this->Base->StockOld->stockEntryPartyUpdate($product_code,$party_label);
		return $this->Base->StockOld->increaseStock($product_code, $amount, $self_price, "Расходный документ $doc_num");
	    }
	} else if ($this->doc('doc_type') == 2) {//Buy Document
	    if ($stock_action == 'increase') {
                $this->Base->StockOld->stockEntryPartyUpdate($product_code,$party_label);
		return $this->Base->StockOld->increaseStock($product_code, $amount, $self_price, "Приходный документ $doc_num");
	    } else {
		return $this->Base->StockOld->decreaseStock($product_code, $amount, $self_price, "Приходный документ $doc_num");
	    }
	} else if ($this->doc('doc_type') == 3 || $this->doc('doc_type') == 4) {
	    return true; //OK For doc_type 3 4
	}
    }

    public function updateEntry($doc_entry_id, $new_quantity = NULL, $new_invoice = NULL, $new_party_label = NULL) {
	if ($this->Base->pcomp('curr_code') == 'USD') {
	    $new_invoice*=$this->doc('doc_ratio');
	}
	if (!$this->doc('use_vatless_price') && $new_invoice != NULL) {
	    $new_invoice = $new_invoice / $this->vat_rate;
	}
	$this->Base->query("START TRANSACTION");
	if (!$this->alterEntry('update', $doc_entry_id, $new_quantity, $new_invoice, $new_party_label)) {
	    $this->Base->query("ROLLBACK");
	    return false;
	}
	$this->setDocumentModifyingUser();

	$this->Base->query("COMMIT");
	return true;
    }

    public function addEntry($product_code, $product_quantity, $invoice_price=NULL, $add_duplicated_rows =false) {
	$this->Base->LoadClass('StockOld');
	if ($this->isCommited()) {
	    $this->Base->set_level(2);
	}
	if (strlen($product_code) == 13 && preg_match('/\d{13}/', $product_code)) {//
	    $_product_code = $this->Base->get_row("SELECT product_code FROM prod_list WHERE product_barcode='$product_code'", 0);
            if( $_product_code ){
                $product_code = $_product_code;
            }
	}
        if( !$product_code ){
            return false;
        }
	$doc_id = $this->doc('doc_id');
	$this->Base->query("START TRANSACTION");
	$party_label = $this->Base->StockOld->getEntryPartyLabel($product_code);
	//$this->Base->msg($party_label);
	$this->Base->query("INSERT INTO document_entries SET doc_id='$doc_id', product_code='$product_code',party_label='$party_label'", false);
	if (mysqli_errno($this->Base->db_link) == 1062) {//Duplicate entry
	    $this->Base->query("ROLLBACK");
	    if( $add_duplicated_rows ){
		return $this->addDuplicatedEntry($product_code,$product_quantity);
	    } else {
		return 'code_duplicated';
	    }
	} else
	if (mysqli_errno($this->Base->db_link) == 1452) {//Constraint fails
	    $this->Base->query("ROLLBACK");
	    return 'code_unknown';
	}
	$doc_entry_id = mysqli_insert_id($this->Base->db_link);
        /*
         *  bugfix getInvoicePrice accepts stripslashed product_code
         */
	if( $invoice_price===NULL ){
	    $invoice_price = $this->getProductInvoicePrice( stripslashes($product_code) );
	}
	if (!$this->alterEntry('update', $doc_entry_id, $product_quantity, $invoice_price)) {//update not ok
	    $this->Base->query("DELETE FROM document_entries WHERE doc_entry_id=$doc_entry_id");
       	    return false;
	}
	$this->Base->query("COMMIT");
	$this->updateTrans();
	$this->Base->StockOld->increaseFetchCount($product_code);
	$this->setDocumentModifyingUser();
	return $doc_entry_id;
    }
    private function addDuplicatedEntry($product_code,$additional_product_quantity){
	$doc_id = $this->doc('doc_id');
	$entry=$this->Base->get_row("SELECT doc_entry_id, product_quantity FROM document_entries WHERE doc_id='$doc_id' AND product_code='$product_code'");
	$new_quantity=$entry['product_quantity']+$additional_product_quantity;
	return $this->alterEntry('update', $entry['doc_entry_id'], $new_quantity);
    }

    public function deleteEntry($delete_ids) {
	if ($this->isCommited()) {
	    $this->Base->set_level(2);
	}
	$this->Base->query("START TRANSACTION");
	foreach ($delete_ids as $key) {
	    $doc_entry_id = $key[0];
	    if ($this->alterEntry('update', $doc_entry_id, 0, NULL)) {
		$this->Base->query("DELETE FROM document_entries WHERE doc_entry_id='$doc_entry_id'");
	    } else {
		$company_lang = $this->Base->pcomp('language');
		$name = $this->Base->get_row("SELECT $company_lang FROM document_entries JOIN prod_list USING(product_code) WHERE doc_entry_id=$doc_entry_id", 0);
		$this->Base->msg("Невозможно удалить строку: \"$name\"\n");
		$this->Base->query("ROLLBACK");
		return false;
	    }
	}
	$this->Base->query("COMMIT");
	if ($this->isCommited()) {
	    $doc_id = $this->doc('doc_id');
	    $entry_num = $this->Base->get_row("SELECT COUNT(*) FROM document_entries WHERE doc_id=$doc_id", 0);
	    if ($entry_num == 0) {
		$this->uncommit();
	    }
	}
	$this->updateTrans();
	$this->setDocumentModifyingUser();
	return true;
    }
/////////////////////////////////////////////////////////////////
//DOCUMENT VIEW
/////////////////////////////////////////////////////////////////


    public function getViewOut($doc_view_id) {
	$view = $this->getDocViewData($doc_view_id);
	if ($view) {
	    $curr_doc_id = $this->doc('doc_id');
	    $this->selectDoc($view['doc_id']);

	    if ($view['view_tpl']) {
		$entries = $this->fetchGridEntries(true, ',');
	    } else {
		$entries = $this->fetchEntries(true, ',');
	    }
	    $head = $this->fetchHead();
	    $footer = $this->fetchFooter();
	    $active = $this->Base->get_row("SELECT * FROM companies_list WHERE company_id='{$view['active_company_id']}'");
	    $passive = $this->Base->get_row("SELECT * FROM companies_list WHERE company_id='{$view['passive_company_id']}'");
	    $out = $this->makeViewOut($view, $head, $entries, $footer, $active, $passive);

	    $this->selectDoc($curr_doc_id);
	    return $out;
	} else {
	    return false;
	}
    }

    public function makeViewOut($view, $head, $entries, $footer, $active, $passive) {
	$this->Base->LoadClass('Utils');
	$footer['total_spell'] = $this->Base->Utils->spellAmount($footer['total']);
        
        
        //MOVE TO TAX BILL CUSTOM SCRIPT
	$footer['vatless'] = number_format($footer['vatless'], 2, ',', '');
	$footer['vat'] = number_format($footer['vat'], 2, ',', '');
	$footer['total'] = number_format($footer['total'], 2, ',', '');
	$active['cvi'] = str_pad($active['company_tax_id'], 12, ' ', STR_PAD_LEFT);
	$passive['cvi'] = str_pad($passive['company_tax_id'], 12, ' ', STR_PAD_LEFT);
	if (!$passive['company_agreement_date'] && !$passive['company_agreement_num']) {
	    $passive['company_agreement_date'] = $view['tstamp'];
	    $passive['company_agreement_num'] = '-';
	}
	$passive['ag_date'] = date('dmY', strtotime($passive['company_agreement_date']));
	$passive['ag_date_dot'] = date('d.m.Y', strtotime($passive['company_agreement_date']));
	$view['view_num_fill'] = str_pad($view['view_num'], 7, ' ', STR_PAD_LEFT);
	$view['vat_percent'] = ($this->vat_rate - 1) * 100;
        // END MOVE
        
	$view['a'] = $active;
	$view['p'] = $passive;

	$view['a']['all'] = $this->Base->Utils->getAllDetails($view['a']);
	$view['p']['all'] = $this->Base->Utils->getAllDetails($view['p']);
	$view['a']['allbr'] = str_replace("\n", '<br>', $view['a']['all']);
	$view['p']['allbr'] = str_replace("\n", '<br>', $view['p']['all']);
	$view['user_sign'] = $this->Base->svar('user_sign');
	$view['user_position'] = $this->Base->svar('user_position');

	$view['loc_date'] = $this->Base->Utils->getLocalDate($view['tstamp']);
	$view['date'] = date('dmY', strtotime($view['tstamp']));
	$view['date_dot'] = date('d.m.Y', strtotime($view['tstamp']));
	$view['extra'] = json_decode($view['view_efield_values']);
	$view['entries_num'] = count($entries['rows']);
	$view['head'] = $head;
	$view['entries'] = $entries['rows'];
	$view['footer'] = $footer;
	return $view;
    }

    public function getDocViewData($doc_view_id) {
	$sql = "SELECT 
                    *
                FROM
                    document_view_list
                        JOIN
                    document_list USING (doc_id)
                        JOIN
                    document_view_types USING (view_type_id)
                WHERE
                    doc_view_id = '$doc_view_id'";
	return $this->Base->get_row($sql);
    }

    public function fetchViews() {
	$doc_id = $this->doc('doc_id');

	function getExtraFields($labels, $values) {
	    if (!$labels)
		return false;
	    $efields = array();
	    $labels = json_decode($labels);
	    $values = json_decode($values);
	    foreach ($labels as $name => $label) {
		$efields[] = array('name' => $name, 'label' => $label, 'value' => isset($values->$name)?$values->$name:'');
	    }
	    return $efields;
	}

	$doc_views = array();
	$sql = "SELECT DISTINCT
		view_type_id,
		doc_view_id,
		view_num,
		view_name,
		view_date,
		view_efield_values,
		view_efield_labels,
		view_file,
		freezed,
		view_hidden
	    FROM
		((SELECT 
		    doc_view_id,
			view_num,
			view_name,
			DATE_FORMAT(tstamp, '%d.%m.%y') AS view_date,
			view_type_id,
			view_efield_values,
			view_efield_labels,
			view_file,
			freezed,
			view_hidden
		FROM
		    document_view_list
		JOIN document_view_types USING (view_type_id)
		WHERE
		    doc_id = '$doc_id') UNION (SELECT 
		    '' AS doc_view_id,
			0 AS view_num,
			view_name,
			0 AS view_date,
			view_type_id,
			'' AS view_efield_values,
			view_efield_labels,
			view_file,
			'' AS freezed,
			view_hidden
		FROM
		    document_view_types
		WHERE
		    view_hidden IS NULL AND
		    doc_type LIKE CONCAT('%/', (SELECT 
			    doc_type
			FROM
			    document_list
			WHERE
			    doc_id = '$doc_id'))) AS vl,'/%')
	    GROUP BY view_type_id";
	$res = $this->Base->query($sql);
	while ($row = mysqli_fetch_assoc($res)) {
	    $row['extra_fields'] = getExtraFields($row['view_efield_labels'], $row['view_efield_values']);
	    unset($row['view_efield_labels'], $row['view_efield_values']);
	    $doc_views[] = $row;
	}
	return $doc_views;
    }

    public function updateView($doc_view_id, $field, $value, $is_extra) {
	if ($this->isCommited())
	    $this->Base->set_level(2);
	if ($this->Base->get_row("SELECT freezed FROM document_view_list WHERE doc_view_id='$doc_view_id'", 0)) {
	    $this->Base->response_wrn('Образ заморожен! Чтобы изменить снимите блокировку!');
	}
	if ($is_extra) {
	    $extra_fields = $this->Base->get_row("SELECT view_efield_values FROM document_view_list WHERE doc_view_id='$doc_view_id'", 0);
	    $extra_fields = json_decode($extra_fields);
	    $extra_fields->$field = $value;

	    $field = 'view_efield_values';
	    $value = addslashes(json_encode($extra_fields));
	} else {
	    if (!in_array($field, array('view_num', 'view_date')))
		$this->Base->response_error('USING UNALLOWED FIELD NAME');
	    if ($field == 'view_date') {
		$field = 'tstamp';
		preg_match_all('/([0-9]{2})\.([0-9]{2})\.([0-9]{2,4})/', $value, $out);
		$value = date("Y-m-d H:i:s", mktime(0, 0, 0, $out[2][0], $out[1][0], $out[3][0]));
	    }
	}
	$user_id = $this->Base->svar('user_id');
	$this->Base->query("UPDATE document_view_list SET $field='$value',modified_by='$user_id' WHERE doc_view_id='$doc_view_id'");
	return true;
    }

    public function deleteView($doc_view_id) {
	$this->Base->query("DELETE FROM document_view_list WHERE doc_view_id='$doc_view_id'");
	return true;
    }

    public function unfreezeView($doc_view_id) {
	$this->Base->query("UPDATE document_view_list SET freezed=0, html='' WHERE doc_view_id='$doc_view_id'");
    }

    public function freezeView($doc_view_id, $html) {
	$html = addslashes($html);
	$this->Base->query("UPDATE document_view_list SET freezed=1, html='$html' WHERE doc_view_id='$doc_view_id'");
    }

    protected function getViewNextNum($view_type_id) {
        $this->Base->LoadClass('PrefOld');
        $counter_increase=1;
        $view_type_row=$this->Base->get_row("SELECT * FROM document_view_types WHERE view_type_id='$view_type_id'");
        $counter_name="counterViewNum_{$view_type_row['view_role']}";
        $nextNum=$this->Base->PrefOld->counterNumGet($counter_name,null,$counter_increase);
        if( !$nextNum ){
            $counter_title=$view_type_row['view_name']??'???';
            $this->Base->PrefOld->counterCreate($counter_name,null,$counter_title);
            $nextNum=$this->Base->PrefOld->counterNumGet($counter_name,null,$counter_increase);
        }
        return $nextNum;
    }

    public function insertView( $view_type_id, $doc_id=null ) {
        if( $doc_id ){
            $this->selectDoc($doc_id);
        } else {
            $doc_id = $this->doc('doc_id');
        }
        
        $doc_type = $this->doc('doc_type');
	$view_type_props = $this->Base->get_row("SELECT * FROM document_view_types WHERE view_type_id='$view_type_id'");
        $efields = addslashes($this->getLastEfields($view_type_id));
        $view_num = $this->doc('doc_num');
        
	if ($view_type_props['view_role'] != 'bill') {
	    $this->Base->set_level(2);
	}
	if ($view_type_props['view_role'] == 'tax_bill') {
	    if (!$this->isCommited()) {
		$this->Base->msg('Сначала сохраните документ!');
		return false;
	    }
            $sql="SELECT 
                    doc_view_id 
                FROM 
                    document_view_list dvl
                WHERE 
                    doc_id=$doc_id 
                    AND view_role='tax_bill'";
            $already_exists=$this->Base->get_row($sql,0);
            if( $already_exists ){
                $this->Base->msg('Бланк такого типа уже сформирован!');
                return false;
            }
            if( $doc_type==1 || $doc_type==3 ){
                $view_num = $this->getViewNextNum($view_type_id);
            } else {
                $view_num='';
            }
	} else if ($view_type_props['view_role'] == 'sell_bill') {
	    if (!$this->isCommited()) {
		$this->Base->msg('Сначала сохраните документ!');
		return false;
	    }
        }
	$cstamp = $this->doc('cstamp');
	$this->Base->query("INSERT INTO document_view_list SET doc_id='$doc_id', view_type_id='$view_type_id', view_efield_values=IF(".(empty($efields)?0:1).",'$efields',NULL), tstamp='$cstamp', view_num='$view_num', view_role='{$view_type_props['view_role']}'");
	return mysqli_insert_id($this->Base->db_link);
    }
    
    private function getLastEfields($view_type_id){
	$active_company_id=$this->Base->acomp('company_id');
        $pcomp_id=$this->Base->pcomp('company_id');
        return $this->Base->get_row("SELECT
                    view_efield_values
                FROM 
                    document_view_list dvl JOIN document_list USING(doc_id)
                WHERE 
                    view_type_id='$view_type_id' AND active_company_id='$active_company_id' AND passive_company_id='$pcomp_id' ORDER BY dvl.tstamp DESC",0);
    }
/////////////////////////////////////////////////////////////////
//DOCUMENT ALL
/////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////
    // CRUD
    /////////////////////////////////////////////
    public function add($doc_type=null,$creation_mode='increase_number') {
	$user_id = $this->Base->svar('user_id');
	$active_company_id = $this->Base->acomp('company_id');
	$passive_company_id = $this->Base->pcomp('company_id');
	$vat_rate = $this->Base->acomp('company_vat_rate');

	$doc_curr_code = strtolower($this->Base->pcomp('curr_code'));
	$this->Base->LoadClass('PrefOld');
	$ratios = $this->Base->PrefOld->prefGet();
	$doc_ratio = $ratios["usd_ratio"]??1;

	$prev_doc = $this->Base->get_row("SELECT use_vatless_price,signs_after_dot,notcount,doc_type,vat_rate FROM document_list WHERE active_company_id='$active_company_id' AND passive_company_id='$passive_company_id' AND doc_type<10 AND is_commited=1 ORDER BY cstamp DESC LIMIT 1");
        if( $doc_type==null ){
	    $doc_type=$prev_doc['doc_type']?$prev_doc['doc_type']:1;
	}
        if( !$doc_type ){
            return false;
        }
	$next_doc_num = $this->getNextDocNum($doc_type,$creation_mode);
	if ($prev_doc) {
	    $pnotcount = $prev_doc['notcount'];
	    $psignsafterdot = $prev_doc['signs_after_dot'];
	    $pusevatlessprice = $prev_doc['use_vatless_price'];
            /*if( $vat_rate!==$prev_doc['vat_rate'] ){
                $this->Base->msg("Внимание! НДС в документе отличется от значения по умолчанию");
            }
	    $vat_rate=$prev_doc['vat_rate'];*/
	} else {
	    $pnotcount = 0;
	    $psignsafterdot = 2;
	    $pusevatlessprice = 0;
	}
	$this->Base->query("INSERT INTO document_list SET 
            doc_type='$doc_type',
            cstamp=NOW(),
            active_company_id='$active_company_id',
            passive_company_id='$passive_company_id',
            use_vatless_price='$pusevatlessprice', 
            signs_after_dot='$psignsafterdot', 
            notcount='$pnotcount',
            doc_ratio='$doc_ratio',
            doc_num='$next_doc_num',
            doc_status_id=1,
            created_by=$user_id,
            modified_by=$user_id,
            vat_rate=$vat_rate"
	);
	$doc_id = mysqli_insert_id($this->Base->db_link);
	$this->selectDoc($doc_id);
	$this->updateTrans();
	return $doc_id;
    }

    protected function delete() {
	$doc_id = $this->doc('doc_id');
	$success = $this->clearTrans();
	if (!$success) {
	    $this->Base->msg("Can't cancel document transaction!");
	    return false;
	}
	$this->Base->query("DELETE FROM document_entries WHERE doc_id=$doc_id");
	$this->Base->query("DELETE FROM document_view_list WHERE doc_id=$doc_id");
	$this->Base->query("DELETE FROM document_list WHERE doc_id=$doc_id");
	return true;
    }

    //////////////////////////////////////////////////////
    // DOC LIST
    //////////////////////////////////////////////////////
    public function fetchList($table_query) {
	$select = "doc_id,
                (SELECT 
                        CONCAT(icon_name, ' ', doc_type_name)
                    FROM
                        document_types
                    WHERE
                        doc_type = dl.doc_type),
                doc_num,
                dl.cstamp,
                DATE_FORMAT(dl.cstamp, '%d.%m.%Y') as doc_date,
                (SELECT 
                        ROUND(amount, 2)
                    FROM
                        document_trans dt
                            JOIN
                        acc_trans USING (trans_id)
                    WHERE
                        dt.doc_id = dl.doc_id
                            AND (dt.type = '361_702'
                            OR dt.type = '28_631'
                            OR dt.type = '44_631')) as amount,
                IF(is_commited,
                    'ok Проведен',
                    'wrn Непроведен') as is_commited,
                (SELECT 
                        CONCAT(code, ' ', descr)
                    FROM
                        acc_trans_status
                            JOIN
                        acc_trans USING (trans_status)
                            JOIN
                        document_trans dt USING (trans_id)
                    WHERE
                        dt.doc_id = dl.doc_id
                            AND (dt.type = '361_702'
                            OR dt.type = '281_361')) as trans_status";
	$table = "document_list dl";
	$where = "doc_type<10 AND dl.active_company_id=" . $this->Base->acomp('company_id') . " AND dl.passive_company_id=" . $this->Base->pcomp('company_id');
	return $this->getTableData($table, $table_query, $select, $where, 'ORDER BY is_commited DESC, cstamp DESC, doc_num DESC');
    }

    public function fetchDefaultHead() {
	$active_company_id=$this->Base->acomp('company_id');
	$passive_company_id = $this->Base->pcomp('company_id');
	$doc_head = array();
	$doc_head['active_comp'] = $this->Base->acomp('company_name');
	$doc_head['passive_comp'] = $this->Base->pcomp('company_name');
	$doc_head['doc_date'] = date('d.m.Y');
	$doc_head['doc_num'] = '';
	$doc_head['curr_code'] = $this->Base->pcomp('curr_code');
	$doc_head['def_curr_code'] = $this->Base->acomp('curr_code');
	$doc_head['doc_ratio'] = "-";
	$doc_head['signs_after_dot'] = 2;
	$doc_head['vat_rate'] = $this->Base->acomp('company_vat_rate');
	$prev_doc = $this->Base->get_row("SELECT doc_type FROM document_list WHERE active_company_id='$active_company_id' AND passive_company_id='$passive_company_id' AND doc_type<10 AND is_commited=1 ORDER BY cstamp DESC LIMIT 1");
	$doc_head['doc_type'] = $prev_doc['doc_type'] ? $prev_doc['doc_type'] : 1;
	return $doc_head;
    }

    public function fetchHead() {
	$doc_id = $this->doc('doc_id');
	$sql = "SELECT 
                is_reclamation,
                is_commited,
                a.company_name AS active_comp,
                p.company_name AS passive_comp,
                date_format(cstamp, '%d.%m.%Y') as doc_date,
                doc_data,
                doc_num,
                ROUND(doc_ratio, 2) AS doc_ratio,
                a.curr_code AS def_curr_code,
                p.curr_code,
                p.company_id as passive_company_id,
                d.doc_type as doc_type,
                notcount,
                inernn,
                use_vatless_price,
                signs_after_dot,
                p.company_mobile,
                cr.last_name AS created_by,
                md.last_name AS modified_by,
                vat_rate
            FROM
                document_list d
                    JOIN
                companies_list a ON a.company_id = d.active_company_id
                    JOIN
                companies_list p ON p.company_id = d.passive_company_id
                    LEFT JOIN
                " . BAY_DB_MAIN . ".user_list cr ON cr.user_id=created_by
                    LEFT JOIN
                " . BAY_DB_MAIN . ".user_list md ON md.user_id=modified_by
            WHERE
                doc_id = '$doc_id'";
	return $this->Base->get_row($sql);
    }
    
    
    
    
    
    
    
    
    
    
    ////////////////////////////////////////////
    //NEW FUNCTIONS
    ////////////////////////////////////////////
    private function calcCorrections( $skip_vat_correction=false, $skip_curr_correction=false ) {
	$doc_id=$this->doc('doc_id');
	$curr_code=$this->Base->pcomp('curr_code');
	$native_curr=($this->Base->pcomp('curr_code') == $this->Base->acomp('curr_code'))?1:0;
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
	$this->Base->query($sql);
    }
    
    private function entriesTmpCreate( $skip_vat_correction=false, $skip_curr_correction=false ){
	$doc_id=$this->doc('doc_id');
	$this->calcCorrections( $skip_vat_correction, $skip_curr_correction );
        $curr_code=$this->Base->acomp('curr_code');
	$company_lang = $this->Base->pcomp('language')??'ru';
        $pcomp_price_label=$this->Base->pcomp('price_label');
        $this->Base->query("DROP TEMPORARY TABLE IF EXISTS tmp_doc_entries");
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
                    product_quantity*product_weight weight,
                    product_quantity*product_volume volume,
                    pl.product_code,
                    $company_lang product_name,
                    product_quantity,
                    CHK_ENTRY(doc_entry_id) AS row_status,
                    product_unit,
                    party_label,
                    product_article,
                    analyse_origin,
                    self_price,
                    buy*IF(curr_code && '$curr_code'<>ppl.curr_code,doc_ratio*@curr_correction,1) buy,
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
        $this->Base->query($sql);
    }
    protected function footerGet($mode){
        $this->entriesTmpCreate(false,false);
        $sql="SELECT
                ROUND(SUM(weight),2) total_weight,
                ROUND(SUM(volume),2) total_volume,
                SUM(product_sum_vatless) vatless,
                SUM(product_sum_total) total,
                SUM(product_sum_total-product_sum_vatless) vat,
                SUM(ROUND(product_quantity*self_price,2)) self,
                @curr_symbol curr_symbol
            FROM tmp_doc_entries";
	return $this->Base->get_row($sql);
    }
    protected function entriesFetch( $skip_vat_correction=false ){
        $this->entriesTmpCreate();
        if( $this->doc('use_vatless_price') ){
            $sql="SELECT *, product_price_vatless product_price, product_sum_vatless product_sum FROM tmp_doc_entries";
        } else {
            $sql="SELECT *, product_price_total product_price, product_sum_total product_sum FROM tmp_doc_entries";
        }
        return $this->Base->get_list($sql);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    public function fetchEntries($vatless = true, $dot = '.') {
	/* DEPRECATED, USE FETCHGRIDENTRIES INTEAD */
	$curr_correction = $this->getCurrCorrection();
	$vat_correction = $vatless ? 1 : $this->vat_rate;
	$sql = $this->getEntriesSqlParts();
	$document_entries = $this->getTableData($sql['table'], NULL, $sql['select'], $sql['where'], 'ORDER BY product_code');

	$signs_after_dot = $this->doc('signs_after_dot');
	$row_num = count($document_entries['rows']);
	for ($i = 0; $i < $row_num; $i++) {
	    $document_entries['rows'][$i][6] = number_format($document_entries['rows'][$i][6] * $vat_correction * $curr_correction, $signs_after_dot, $dot, '');
	    $document_entries['rows'][$i][7] = number_format($document_entries['rows'][$i][7] * $vat_correction * $curr_correction, 2, $dot, '');
	    if ($this->doc('doc_type') == 2 && $this->doc('is_commited') == 0) {
		$price = $this->getRawProductPrice($document_entries['rows'][$i][1], $this->doc('doc_ratio'));
		$current_price = round($price['buy'] * $vat_correction * $curr_correction, $signs_after_dot);
		$document_entries['rows'][$i][9] = $current_price;
	    } else {
		$document_entries['rows'][$i][9] = '';
	    }
	}
	return $document_entries;
    }

    protected function getEntriesSqlParts() {
	$doc_id = $this->doc('doc_id');
	$signs_after_dot = $this->doc('signs_after_dot');
	$company_lang = $this->Base->pcomp('language');
	$sql = array();
	$sql['select'] = "
            de.doc_entry_id,
            pl.product_code,
            pl.$company_lang,
            analyse_origin,
            de.product_quantity,
            pl.product_unit,
            ROUND(invoice_price,$signs_after_dot) AS product_price,
            ROUND(de.product_quantity*invoice_price,2) AS product_sum,
            CHK_ENTRY(de.doc_entry_id) AS row_status,
            '',
            party_label";
	$sql['table'] = "document_entries de JOIN prod_list pl USING(product_code)";
	$sql['where'] = "doc_id='$doc_id'";
	return $sql;
    }

    public function fetchGridEntries($vatless = true, $dot = '.') {
	$curr_correction = $this->getCurrCorrection();
	$vat_correction = $vatless ? 1 : $this->vat_rate;
	$doc_id = $this->doc('doc_id');
	$signs_after_dot = $this->doc('signs_after_dot');
	$company_lang = $this->Base->pcomp('language');
	$sql = "SELECT
                doc_entry_id,
                pl.product_code,
                $company_lang product_name,
                product_quantity,
                product_unit,
                ROUND(invoice_price * $vat_correction * $curr_correction,$signs_after_dot) AS product_price,
                ROUND(invoice_price * $vat_correction * $curr_correction * product_quantity,2) AS product_sum,
                CHK_ENTRY(doc_entry_id) AS row_status,
                party_label,
                analyse_origin
            FROM
                document_entries JOIN prod_list pl USING(product_code)
            WHERE
                doc_id='$doc_id'
            ORDER BY pl.product_code";
	return array('rows' => $this->Base->get_list($sql));
    }
    public function fetchFooter($mode = 'total_in_def_curr') {
	$doc_id = $this->doc('doc_id');

	$curr_correction = $this->getCurrCorrection($mode);
        $this->Base->LoadClass("PrefOld");
        
        
        $pref=$this->Base->PrefOld->prefGet();
        if( isset($pref['use_total_as_base']) && $pref['use_total_as_base'] ){
            $signs_after_dot=$this->doc('signs_after_dot');
            $sql = "SELECT
                    ROUND(SUM(product_quantity*product_weight),2) as total_weight,
                    ROUND(SUM(product_quantity*product_volume),2) as total_volume,
                    ROUND(SUM(ROUND(invoice_price * {$this->vat_rate} * $curr_correction,$signs_after_dot) * product_quantity),2) total,
                    SUM(ROUND(product_quantity*self_price,2)) as self
                FROM
                    document_entries JOIN prod_list USING(product_code)
                WHERE doc_id='$doc_id'";
            $footer = $this->Base->get_row($sql);
            $footer['vatless'] = number_format(round($footer['total'] / $this->vat_rate, 2), 2, '.', '');
            $footer['vat'] = number_format($footer['total'] - $footer['vatless'], 2, '.', '');            
        }else{
            $sql = "SELECT
                    ROUND(SUM(product_quantity*product_weight),2) as total_weight,
                    ROUND(SUM(product_quantity*product_volume),2) as total_volume,
                    SUM(ROUND(product_quantity*invoice_price*$curr_correction,2)) as vatless,
                    SUM(ROUND(product_quantity*self_price,2)) as self
                FROM
                    document_entries JOIN prod_list USING(product_code)
                WHERE doc_id='$doc_id'";
            $footer = $this->Base->get_row($sql);
            $footer['total'] = number_format(round($footer['vatless'] * $this->vat_rate, 2), 2, '.', '');
            $footer['vat'] = number_format($footer['total'] - $footer['vatless'], 2, '.', '');
        }
	$footer['curr_symbol'] = $this->Base->pcomp('curr_symbol');
	return $footer;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    

    public function setType($doc_type) {
	if ($this->isCommited())
	    $this->Base->response_error('You can not change doc_type of commited document');
	$doc_id = $this->doc('doc_id');
	$next_doc_num = $this->getNextDocNum($doc_type);
	$this->Base->query("DELETE FROM document_view_list WHERE doc_id='$doc_id'");
	$this->Base->query("UPDATE document_list SET doc_type='$doc_type', doc_num='$next_doc_num' WHERE doc_id='$doc_id'");
	$this->selectDoc($doc_id);
    }

    public function updateHead($new_val, $field) {
	if ($this->isCommited())
	    $this->Base->set_level(2);
	$doc_id = $this->doc('doc_id');
	$this->setDocumentModifyingUser();
	if ($field == 'ratio') {
            $curr_code=$this->Base->acomp('curr_code');
            $price_label=$this->Base->pcomp('price_label');
            $current_ratio=$this->doc('doc_ratio');
            $correction=$new_val/$current_ratio;
            $this->Base->query("START TRANSACTION");
	    $this->Base->query("UPDATE document_list SET doc_ratio='$new_val' WHERE doc_id='$doc_id'");
            $this->Base->query("UPDATE 
                        document_entries de 
                            JOIN 
                        price_list pl ON de.product_code=pl.product_code 
                            AND label='$price_label' 
                    SET invoice_price=invoice_price*$correction 
                    WHERE doc_id='$doc_id' AND curr_code<>'' AND curr_code<>'$curr_code'");
            $this->clearTrans(); // To change
            $this->updateTrans(); //Time of trans
            $this->Base->query("COMMIT");
	    $this->selectDoc($doc_id);
	} else
	if ($field == 'num') {
	    if ($new_val > 0) {// not null nan or zero
		$this->Base->query("UPDATE document_list SET doc_num='$new_val' WHERE doc_id='$doc_id'");
	    }
	} else
	if ($field == 'date') {
	    if ($new_val) {
		$this->Base->query("UPDATE document_list SET cstamp=CONCAT(STR_TO_DATE('$new_val','%d.%m.%Y'),DATE_FORMAT(NOW(),' %H:%i:%s')) WHERE doc_id='$doc_id'");
		$this->selectDoc($doc_id);
		if ($this->isCommited()) {
		    $this->clearTrans(); // To change
		    $this->updateTrans(); //Time of trans
		}
	    }
	} else
	
	if ($field == 'doc_data') {
	    $this->Base->query("UPDATE document_list SET doc_data='$new_val' WHERE doc_id='$doc_id'");
	} else
	if ($field == 'notcount') {
	    if ($this->isCommited())
		return false;
	    $this->Base->query("UPDATE document_list SET notcount=IF(notcount,0,1) WHERE doc_id='$doc_id'");
	    $this->selectDoc($doc_id);

	} else
	if ($field == 'is_reclamation') {
	    if ($this->isCommited())
		return false;
	    $this->Base->query("UPDATE document_list SET is_reclamation=IF(is_reclamation,0,1) WHERE doc_id='$doc_id'");
	    $this->selectDoc($doc_id);
	    $this->normalizeQuantitySign();
	} else
	if ($field == 'use_vatless_price') {
	    $this->Base->query("UPDATE document_list SET use_vatless_price=IF(use_vatless_price,0,1) WHERE doc_id='$doc_id'");
	    $this->selectDoc($doc_id);
	} else
	if ($field == 'signs_after_dot') {
	    $this->Base->query("UPDATE document_list SET signs_after_dot='$new_val' WHERE doc_id='$doc_id'");
	    $this->selectDoc($doc_id);
            $this->updateTrans();
	} else
	if ($field == 'vat_rate') {
	    $this->Base->set_level(3);
	    $this->Base->query("UPDATE document_list SET vat_rate='$new_val' WHERE doc_id='$doc_id'");
	    $this->selectDoc($doc_id);
	    if ($this->isCommited()) {
		$this->clearTrans(); // To change
		$this->updateTrans(); //Time of trans
	    }
	} else{
	    return false;
	}
	return true;
    }



    ///////////////////////////////////
    // TRANS
    // SECTION
    ///////////////////////////////////
    public function updateTrans( $mode=null ) {
	$doc_num = $this->doc('doc_num');
	if ($this->isCommited()) {
	    $sum = $this->footerGet('in_main_currency');
	    $sum['profit'] = $sum['vatless'] - $sum['self'];
	} else {
	    return false;
	}
	/*
	  1,2,3,8,9 Active

	  4,5,6,7 Passive
	 */
	if ($this->doc('doc_type') == 1) {//SELL DOCUMENT
	    $desc = "Расходный документ " . ($this->doc('is_reclamation') ? "(Возврат) " : "") . "№$doc_num";
            if( $mode!='profit_only' ){
                $this->makeTransaction(361, 702, $sum['total'], $desc, 'total');
                $this->makeTransaction(702, 641, $sum['vat'], $desc, 'vat');
                $this->makeTransaction(702, 791, $sum['vatless'], $desc, 'vatless');
            }
	    $this->makeTransaction(791, 281, $sum['self'], $desc, 'self');
	    $this->makeTransaction(791, 441, $sum['profit'], $desc, 'profit');
	    return true;
	}
	if ($this->doc('doc_type') == 2) {//BUY DOCUMENT
	    $desc = "Приходный документ " . ($this->doc('is_reclamation') ? "(Возврат) " : "") . "№$doc_num";
	    $this->makeTransaction(84, 631, $sum['total'], $desc, 'total');
	    $this->makeTransaction(281, 84, $sum['vatless'], $desc, 'vatless');
	    $this->makeTransaction(641, 84, $sum['vat'], $desc, 'vat');
	    return true;
	}
	if ($this->doc('doc_type') == 3) {//SERVICEOUT DOCUMENT
	    $desc = "Акт Оказанных Услуг №$doc_num";
	    $this->makeTransaction(361, 703, $sum['total'], $desc, 'total');
	    $this->makeTransaction(703, 441, $sum['vatless'], $desc, 'vatless');
	    $this->makeTransaction(703, 641, $sum['vat'], $desc, 'vat');
	    return true;
	}
	if ($this->doc('doc_type') == 4) {//SERVICEIN DOCUMENT
	    $desc = "Акт Полученных Услуг №$doc_num";
	    $this->makeTransaction(84, 631, $sum['total'], $desc, 'total');
	    $this->makeTransaction(85, 84, $sum['vatless'], $desc, 'vatless');
	    $this->makeTransaction(641, 84, $sum['vat'], $desc, 'vat');
	    return true;
	}
    }

    protected function makeTransaction($acc_debit_code, $acc_credit_code, $amount, $description, $trans_role) {
	if ($this->Base->pcomp('curr_code') == $this->Base->acomp('curr_code')) {
	    $amount_alt = 0;
	} else {
	    $amount_alt = $amount;
            $amount=$amount_alt * $this->doc('doc_ratio');
	}
	$doc_id = $this->doc('doc_id');
	$trans_type = $acc_debit_code . "_" . $acc_credit_code;
	$trans_id = $this->Base->get_row("SELECT trans_id FROM document_trans WHERE doc_id=$doc_id AND type='$trans_type'", 0);

        $active_company_id=$this->doc('active_company_id');
        $passive_company_id=$this->doc('passive_company_id');
	$this->Base->LoadClass('Accounts');
	if (!$trans_id) {//Transaction does not exists
	    $trans_id = $this->Base->Accounts->commitTransaction($acc_debit_code, $acc_credit_code, $amount, $description, false, $this->doc('cstamp'), $amount_alt, $active_company_id, $passive_company_id );
	    $this->Base->query("INSERT INTO document_trans SET doc_id=$doc_id, trans_id=$trans_id, type='$trans_type', trans_role='$trans_role'");
	} else {
	    $this->Base->Accounts->updateTransaction($trans_id, array('amount' => $amount, 'amount_alt' => $amount_alt, 'description' => $description,'trans_role'=>$trans_role,'doc_id'=>$doc_id));
	}
	if ($trans_type == '361_702' || $trans_type=='84_631') {//Doc sum is changed || $trans_type=='631->361'
	    if ($this->Base->Accounts->isTransConnected($trans_id)){//Break connection
		$this->Base->Accounts->breakTransConnection($trans_id);
            }
	    
	}
        if( $acc_debit_code == 361 ){
            $this->Base->Accounts->calculatePayments();
        } 
        if( $acc_credit_code == 631 ){
            $this->Base->Accounts->calculatePaymentsCredit();
        } 
        
    }

    protected function clearTrans() {
	$doc_id = $this->doc('doc_id');
	$this->Base->LoadClass('Accounts');
	$res = $this->Base->query("SELECT trans_id FROM document_trans WHERE doc_id=$doc_id");
	$this->Base->query("START TRANSACTION");
	$this->Base->query("DELETE FROM document_trans WHERE doc_id=$doc_id");
	$this->Base->query("DELETE FROM acc_trans WHERE doc_id=$doc_id");
	while ($row = mysqli_fetch_assoc($res)) {
	    if (!$this->Base->Accounts->cancelTransaction($row['trans_id'])) {
		$this->Base->query("ROLLBACK");
		return false;
	    }
	}
	mysqli_free_result($res);
	$this->Base->query("COMMIT");
	return true;
    }

    ///////////////////////////////////
    //PRICE FUNCTIONS SECTION
    ///////////////////////////////////

    public function getProductInvoicePrice($product_code) {
        $doc_ratio=$this->doc('doc_ratio');
        if( $this->doc('doc_type') == 1 || $this->doc('doc_type') == 3 ){
            $pcomp_id=$this->Base->pcomp("company_id");
            return $this->Base->get_row("SELECT ROUND(GET_SELL_PRICE('$product_code','$pcomp_id',$doc_ratio),2)/{$this->vat_rate} sell",0);
        } else {
            $def_curr_code = $this->Base->acomp('curr_code');
            $pcomp_price_label=$this->Base->pcomp('price_label');
            return $this->Base->get_row("SELECT 
                buy/{$this->vat_rate}*IF(curr_code<>'' AND curr_code<>'$def_curr_code',$doc_ratio,1) buy FROM price_list 
                WHERE product_code='$product_code' AND (label='$pcomp_price_label' OR label='')
                ORDER BY label='$pcomp_price_label' DESC
                LIMIT 1",0);
        }
    }

    protected function getProductSellSelfPrice($product_code, $invoice_qty,$fdate) {
        return $this->Base->get_row("SELECT LEFTOVER_CALC('$product_code','$fdate','$invoice_qty','selfprice')",0);

//	$this->Base->LoadClass('StockOld');
//	$stock_self = $this->Base->StockOld->getEntrySelfPrice($product_code);
//	if ($stock_self > 0)
//	    return $stock_self;
//	/*
//	 * IF self price is not set
//	 * qty=0 or something else set
//	 * selfPrice as current buy price
//	 */
//	$price = $this->getRawProductPrice($product_code, $this->doc('doc_ratio'));
//	$price_self = $price['buy'] ? $price['buy'] : $price['sell'];
//	//$this->Base->StockOld->setEntrySelfPrice($product_code, $price_self);
//	return $price_self;
    }

    protected function getProductPrice($product_code) {
	$discount = $this->getProductDiscount($this->doc('passive_company_id'), $product_code);
	$price = $this->getRawProductPrice($product_code, $this->doc('doc_ratio'));
	$price['sell']*=$discount;
	return $price;
    }

    public function getRawProductPrice($product_code, $curr_correction) {
        $product_code=  addslashes($product_code);//bugfix if special characters are used like \ 
	$pcomp_price_label=$this->Base->pcomp('price_label');
	$def_curr_code = $this->Base->acomp('curr_code');
        
	$price=$this->Base->get_row("SELECT 
            sell/{$this->vat_rate}*IF(curr_code<>'' AND curr_code<>'$def_curr_code',$curr_correction,1) sell,
            buy/{$this->vat_rate}*IF(curr_code<>'' AND curr_code<>'$def_curr_code',$curr_correction,1) buy,
            label='$pcomp_price_label' is_rightlabel
            FROM price_list 
            WHERE product_code='$product_code' AND (label='$pcomp_price_label' OR label='')
            ORDER BY label='$pcomp_price_label' DESC
            LIMIT 1");
	if( !$price ){
	    return ['sell'=>0,'buy'=>0];
	}
        if( !$price['is_rightlabel'] ){
            $this->Base->msg("Была использована категория цен по умолчанию!");
        }
        return $price;
    }

    public function updateBuyPriceFromDoc() {
	$doc_id = $this->doc('doc_id');
	$doc_ratio = $this->doc('doc_ratio');
	$def_curr_code = $this->Base->acomp('curr_code');
	$this->Base->query("UPDATE price_list
                JOIN
                    document_entries USING (product_code) 
                SET 
                    buy = invoice_price*{$this->vat_rate}/IF(curr_code='$def_curr_code' OR curr_code='',1, $doc_ratio)
                WHERE
                    doc_id = '$doc_id';");
    }
}
