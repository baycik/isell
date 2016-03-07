<?php

require_once 'iSellBase.php';

class ProcCompanies extends iSellBase {

    public function ProcCompanies() {
        $this->ProcessorBase(1);
    }

    public function onDefault() {
        $this->response_tpl('companies/companies_main.html');
    }

    public function onCompaniesTree() {
        $parent_id = $this->request('id', 1, 0);
        $direct = $this->request('direct', 1);
        $this->LoadClass('Companies');
        $tree_obj = $this->Companies->getCompaniesTreeChildren($parent_id);
        if ($direct)
            $this->response($tree_obj, 1);
        else
            $this->response($tree_obj, 1);
    }

    public function onTreeItemUpdate() {
        $branch_id = $this->request('branch_id', 1);
        $parent_id = $this->request('parent_id', 1);
        $text = $this->request('text');
        $this->LoadClass('Companies');
        $ok = $this->Companies->updateCompaniesTreeBranch($branch_id, $parent_id, $text);
        $this->response($ok);
    }

    public function onTreeItemInsert() {
        $parent_id = $this->request('parent_id', 1);
        $is_leaf = $this->request('is_leaf', 1);
        $text = $this->request('text');
        $this->LoadClass('Companies');
        if ($is_leaf)
            $branch_data = '{"im0":"user.gif"}';
        $new_branch_id = $this->Companies->insertTreeBranch('companies_tree', $parent_id, $text, $is_leaf, $branch_data);
        $this->Companies->updateTreeBranchPath('companies_tree', $new_branch_id);
        if ($new_branch_id == -1)
            $this->msg("Невозможно добавить ветку в компанию!");
        else if ($is_leaf)//Its a company leaf
            $this->Companies->insertNewCompany($new_branch_id, $text);

        $this->response($new_branch_id);
    }

    public function onTreeItemDelete() {
        $this->set_level(2);
        $branch_id = $this->request('branch_id', 1);
        $this->LoadClass('Companies');
        $ok = $this->Companies->deleteCompanyBranch($branch_id);
        $this->response($ok);
    }

    public function onTreeItemLock() {
        $branch_id = $this->request('branch_id', 1);
        $level = $this->request('level', 1);
        $this->LoadClass('Data');
        $this->Data->lockTreeBranch('companies_tree', $branch_id, $level);
    }

    /////////////////////////////DETAILS////////////////////////////////
    public function onDetails() {
        $this->LoadClass('Companies');
        $company_data = $this->Companies->getCompanyDetails();
        $this->response($company_data);
    }

    public function onDetailUpdate() {
        $field_name = $this->request('field_name');
        $field_value = $this->request('field_value');
        $this->LoadClass('Companies');

        if ($field_name == 'company_agreement_date') {
            preg_match('|(\d{2})[^\d](\d{2})[^\d](\d{4})|', $field_value, $dt);
            $field_value = "$dt[3]-$dt[2]-$dt[1]";
        }
        $this->Companies->updateDetail($field_name, $field_value);
    }

    /////////////////////////////-DETAILS////////////////////////////////
    /////////////////////////////ADJUSTMETS//////////////////////////////
    public function onAdjustments() {
        $this->LoadClass('Companies');
        $adj = $this->Companies->getAdjustments();
        $this->response($adj);
    }

    public function onUpdateDiscount() {
        $this->set_level(2);
        $branch_id = $this->request('branch_id', 1);
        $discount = $this->request('discount', 2);
        $this->LoadClass('Companies');
        $this->Companies->updateDiscount($branch_id, $discount);
    }

    /////////////////////////////-ADJUSTMETS/////////////////////////////
    /////////////////////////////DOCUMENT///////////////////////////////
    public function onUncommitDocument() {
        $this->set_level(2);
        $this->LoadClass('Document');
        $success = $this->Document->uncommit();
        $this->response($success);
    }

    public function onCommitDocument() {
        $this->set_level(2);
        $this->LoadClass('Document');
        $success = $this->Document->commit();
        $this->response($success);
    }

    public function onChangeDocType() {
        $this->set_level(1);
        $new_doc_type = $this->request('new_doc_type', 1);
        $this->LoadClass('Document');
        $this->Document->setType($new_doc_type);
    }

    public function onRecalc() {
        $this->set_level(1);
        $perc = $this->request('perc', 2);
        $this->LoadClass('Document');
        $this->Document->recalc($perc);
    }

    public function onDocumentHead() {
        $doc_id = $this->request('doc_id', 1);
        $this->LoadClass('Document');
        $this->Document->selectDoc($doc_id);
        $data = $this->Document->fetchHead();
        $this->response($data);
    }

    public function onDefaultDocumentHead() {
        $this->LoadClass('Document');
        $this->Document->selectDoc(0);
        $data = $this->Document->fetchDefaultHead();
        $this->response($data);
    }

    public function onDocumentListStructure() {
        $this->LoadClass('Document');
        $structure = $this->Document->getTableStructure('document_list');
        $this->response($structure);
    }

    public function onDocumentListData() {
        $table_query = $this->get_table_query();
        ;
        $this->LoadClass('Document');

        $document_list = $this->Document->fetchList($table_query);
        $empty_row = array('0', '', '+', '', '', '', '', '');
        $document_list['rows'] = array_merge(array($empty_row), $document_list['rows']);
        $this->response($document_list);
    }

    public function onDocumentStructure() {
        $this->LoadClass('Document');
        $structure = $this->Document->getTableStructure('document_entry_view');
        $this->response($structure);
    }

    public function onDocumentData() {
        $this->LoadClass('Document');
        $document_data = array();
        $use_vatless_price = $this->Document->doc('use_vatless_price');
        $document_data['entries'] = $this->Document->fetchEntries($use_vatless_price);
        $document_data['footer'] = $this->Document->fetchFooter();
        $this->response($document_data);
    }

    public function onSuggestion() {//transfer to Document
        $doc_type = $this->request('doc_type', 1);
        $rawclue = $this->request('clue');
        $where = '';
        $clues = explode(' ', $rawclue);
        $company_lang = $this->pcomp('language');
        foreach ($clues as $clue) {
            if ($clue == '')
                continue;
            $where.=" AND (product_code LIKE '%$clue%' OR $company_lang LIKE '%$clue%') ";
        }
        if ($where != '') {
            $is_service = ($doc_type == 3 || $doc_type == 4) ? 1 : 0;
            $where = 'WHERE ' . substr($where, 4) . " AND is_service=$is_service";
        } else
            $where = 'WHERE 1=2';
        if ($is_service) {
            $sql = "SELECT product_code, $company_lang AS label FROM prod_list $where LIMIT 15";
        } else {
            $sql = "SELECT product_code, $company_lang AS label,product_spack,product_quantity FROM " . BAY_DB_MAIN . ".prod_list JOIN " . BAY_DB_MAIN . ".stock_entries USING(product_code) $where ORDER BY fetch_count-DATEDIFF(NOW(),fetch_stamp) DESC,product_code LIMIT 15";
        }
        $res = $this->query($sql);
        $suggestion = array();
        $suggestion['identifier'] = 'product_code';
        $suggestion['items'] = array();
        while ($row = mysql_fetch_assoc($res)) {
            $suggestion['items'][] = $row;
        }
        $this->response($suggestion);
    }

    public function onAddDocument() {
        $doc_type = $this->request('doc_type', 1);
        $this->LoadClass('Document');
        $doc_id = $this->Document->add($doc_type);
        $this->response($doc_id); //response new doc_id
    }

    public function onAddEntry() {//response id doc_id
        $product_code = $this->request('product_code');
        $product_quantity = $this->request('product_quantity', 1);
        $this->LoadClass('Document');
        $success = $this->Document->addEntry($product_code, $product_quantity);
        if (!$success) {
            $this->response_wrn('Невозможно добавить строку.\n Недостаточное количество');
        }
    }

    public function onDeleteEntry() {
        $delete_id = $this->request('delete_ids', 3);
        $this->LoadClass('Document');
        $this->Document->deleteEntry($delete_id);
    }

    public function onUpdateEntry() {
        $update_id = $this->request('update_id', 3);
        $update_col = $this->request('update_col');
        $update_val = $this->request('update_val', 2);
        $doc_entry_id = $update_id[0][0];
        $this->LoadClass('Document');

        if ($update_col == "product_quantity") {
            $this->Document->updateEntry($doc_entry_id, $update_val, NULL);
        } else if ($update_col == "product_price") {
            $this->Document->updateEntry($doc_entry_id, NULL, $update_val);
        }
        $this->Document->updateTrans();
    }

    public function onDocumentOut() {
        $doc_view_id = $this->request('doc_view_id', 1);
        $out_type = $this->request('out_type', 0, '.print');

        $this->LoadClass('Companies');
        echo $this->Companies->getViewPage($doc_view_id, $out_type);
        exit;
    }

    public function onDocumentViews() {
        $this->LoadClass('Document');
        $views = $this->Document->fetchViews();
        $this->response(array('views' => $views));
    }

    public function onUpdateDocumentView() {
        $doc_view_id = $this->request('doc_view_id', 1);
        $field = $this->request('field');
        $value = $this->request('value');
        $is_extra = $this->request('is_extra', 1);
        $this->LoadClass('Document');
        $this->Document->updateView($doc_view_id, $field, $value, $is_extra);
    }

    public function onDeleteDocumentView() {
        $this->set_level(2);
        $doc_view_id = $this->request('doc_view_id', 1);
        $this->LoadClass('Document');
        $this->Document->deleteView($doc_view_id);
    }

    public function onFreezeDocumentView() {
        $this->set_level(3);
        $doc_view_id = $this->request('doc_view_id', 1);
        $this->LoadClass('Document');
        $this->LoadClass('Companies');
        $html = $this->Companies->getViewPage($doc_view_id, '.html');
        $this->Document->freezeView($doc_view_id, $html);
    }

    public function onUnfreezeDocumentView() {
        $this->set_level(3);
        $doc_view_id = $this->request('doc_view_id', 1);
        $this->LoadClass('Document');
        $this->Document->unfreezeView($doc_view_id);
    }

    public function onInsertDocumentView() {
        $view_type_id = $this->request('view_type_id', 1);
        $this->LoadClass('Document');
        $this->Document->insertView($view_type_id);
    }

    public function onDefaultView() {
        $this->LoadClass('Document');
        $def_view_id = $this->Document->getDefaultView();
        $this->response($def_view_id);
    }

    public function onUpdateHead() {
        $field = $this->request('field');
        $new_val = $this->request('val');
        $this->LoadClass('Document');
        $this->Document->updateHead($new_val, $field);
    }

    /////////////////////////////-DOCUMENT///////////////////////////////

    public function onCompaniesList() {
        $clue = str_replace('*', '', $this->request('label'));
        $start = $this->request('start', 1, 0);
        $count = $this->request('count', 1, 999);
        $selected_comp_id = $this->request('selected_comp_id', 1);
        $this->LoadClass('Companies');
        $this->response($this->Companies->getCompaniesList($clue, $start, $count, $selected_comp_id));
    }

    public function onSelectPassiveCompany() {
        $branch_id = $this->request('branch_id', 1);
        $passive_company_id = $this->request('passive_company_id', 1);
        $this->LoadClass('Companies');
        if ($branch_id)
            $passive_company_id = $this->Companies->getCompanyIdFromBranch($branch_id);
        if ($passive_company_id) {
            $this->selectPassiveCompany($passive_company_id);
            $this->response($this->_pcomp);
        } else
            $this->response(false);
    }

    public function onMoveDoc() {
        $passive_company_id = $this->request('passive_company_id', 1);
        $copy = $this->request('copy', 1);
        $this->LoadClass('Document');
        if ($copy) {
            $this->Document->duplicateDoc();
        }
        $this->Document->moveDoc($passive_company_id);
	$this->response(1);
    }

    //////////////////////////////////////
    //
	//	BLANKS SECTION
    //
	//////////////////////////////////////
    public function onBlankListStructure() {
        $this->LoadClass('Blank');
        $structure = $this->Blank->getTableStructure('blank_list');
        $this->response($structure);
    }

    public function onBlankListData() {
        $this->LoadClass('Blank');
        $document_list = $this->Blank->fetchBlankList($table_query);
        $this->response($document_list);
    }

    public function onAvailBlanks() {
        $this->LoadClass('Blank');
        $avail_blanks = $this->Blank->fetchAvailBlanks();
        $this->response($avail_blanks);
    }

    public function onGetBlank() {
        $doc_id = $this->request('doc_id', 1);
        $this->LoadClass('Blank');
        $blank = $this->Blank->getBlank($doc_id);
        $this->response($blank);
    }

    public function onAddBlank() {
        $view_type_id = $this->request('view_type_id', 1);
        $register_only = $this->request('register_only', 1, 0);
        $this->LoadClass('Blank');
        $doc_id = $this->Blank->addBlank($view_type_id, $register_only);
        $this->response($doc_id);
    }

    public function onSaveBlank() {
        $num = $this->request('num');
        $date = $this->request('date');
        $html = $this->request('html');

        $this->LoadClass('Blank');
        $this->Blank->saveBlank($num, $date, $html);
    }

    public function onUpdateBlankReg() {
        $field = $this->request('field');
        $value = $this->request('value');
        $this->LoadClass('Blank');
        $this->Blank->updateBlankReg($field, $value);
    }

    public function onDeleteBlank() {//its exploit use set level
        $this->LoadClass('Blank');
        $this->Blank->deleteBlank();
    }

    public function onGetSellStats() {
        $this->LoadClass('Companies');
        $stats = $this->Companies->getSellStats();
        $this->response($stats);
    }

}

?>