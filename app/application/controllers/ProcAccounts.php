<?php

require_once('iSellBase.php');

class ProcAccounts extends iSellBase {

    public function ProcAccounts() {
        $this->ProcessorBase(1);
    }

    public function onDefault() {
        $this->set_level(3);
        $this->response_tpl('accounts/accounts_main.html');
    }

//    public function onAccountsTree() {
//        $this->set_level(3);
//        $parent_id = $this->request('id', 1, 0);
//        $direct = $this->request('direct', 1);
//
//        $this->LoadClass('Accounts');
//        $tree_obj = array();
//        $tree_obj['id'] = $parent_id;
//        $tree_obj['item'] = $this->Accounts->getTreeChildren('acc_tree', $parent_id, 'id', 'parent_id', 'text', 'toplevel');
//        if ($direct)
//            $this->response($tree_obj, 1);
//        else
//            $this->response($tree_obj, 1);
//    }
//
//    public function onTreeItemUpdate() {
//        $this->set_level(3);
//        $branch_id = $this->request('branch_id', 1);
//        $parent_id = $this->request('parent_id', 1);
//        $text = $this->request('text');
//
//        $this->LoadClass('Accounts');
//        $acc_id = $this->Accounts->getAccountIdFromBranch($branch_id);
//
//        $ok = $this->Accounts->updateTreeBranch('acc_tree', $branch_id, $parent_id, $text);
//        $this->Accounts->updateAccount($acc_id, array('acc_name' => $text));
//        $this->response($ok);
//    }
//
//    public function onTreeItemInsert() {
//        $this->set_level(3);
//        $parent_id = $this->request('parent_id', 1);
//        $is_leaf = $this->request('is_leaf', 1);
//        $text = $this->request('text');
//        if ($is_leaf)
//            $branch_data = '{"im0":"coins.png"}';
//        $this->LoadClass('Accounts');
//        $new_branch_id = $this->Accounts->insertTreeBranch('acc_tree', $parent_id, $text, $is_leaf, $branch_data);
//        if ($new_branch_id == -1)
//            $this->msg("Невозможно добавить ветку!");
//        else if ($is_leaf) {//Its a company leaf
//            //$ok = $this->Accounts->insertNewAccount($new_branch_id, $text, intval($text)/* using first digits */);
//            if (!$ok) {
//                $this->msg(mysql_error());
//                $this->Accounts->deleteTreeBranch('acc_tree', $new_branch_id);
//            }
//        }
//        $this->response($new_branch_id);
//    }
//
//    public function onTreeItemDelete() {
//        $this->set_level(3);
//        $branch_id = $this->request('branch_id', 1);
//        $this->LoadClass('Accounts');
//        $ok = $this->Accounts->deleteAccountBranch($branch_id);
//        $this->response($ok);
//    }

    ///////////////////////////
    //LEGACY TRANS LEDGER FUNCTIONS
    ///////////////////////////
//	public function onAccountTransListStructure(){
//	    $this->set_level(1);	
//	    $table_name='acc_trans_view';
//	    $this->LoadClass('Accounts');
//	    $table_structure=$this->Accounts->getTableStructure( $table_name );
//	    $this->response($table_structure);
//	}
    public function onAccList() {
        $this->set_level(3);
        $use_clientbank = $this->request('useClientbank', 1, 0);
        $this->LoadClass('Accounts');
        $acc_list = $this->Accounts->fetchAccList($use_clientbank);
        $this->response($acc_list);
    }

    public function onFetchTransData() {
        $this->set_level(2);
        $trans_id = $this->request('trans_id', 1);
        $this->LoadClass('Accounts');
        $trans_data = $this->Accounts->getTransaction($trans_id);
        $this->response($trans_data);
    }

    public function onUpdateTransaction() {
        $this->set_level(2);
        $trans_id = $this->request('trans_id', 1);
        $payed_trans_id = $this->request('payed_trans_id', 1);
        $trans_type = explode('_', $this->request('trans_type'));
        $trans = array();
        $trans['cstamp'] = $this->request('date', 0);
        $trans['check_id'] = $this->request('check_id', 1);
        $trans['description'] = $this->request('description');
        $trans['passive_company_id'] = $this->request('passive_company_id', 1);
        $trans['acc_debit_code'] = $this->request('acc_debit_code', 1, $trans_type[0]);
        $trans['acc_credit_code'] = $this->request('acc_credit_code', 1, $trans_type[1]);
        $this->LoadClass('Accounts');
        if ($trans_id == 0) {//new trans, lets create it
            $trans_id = $this->Accounts->commitTransaction($trans['acc_debit_code'], $trans['acc_credit_code'], 0, '', 1, NULL, $trans['passive_company_id']);
        }
        if ($payed_trans_id) {
            $this->Accounts->updateTransaction($trans_id, array('trans_ref' => $payed_trans_id, 'trans_status' => 5));
            $this->Accounts->updateTransaction($payed_trans_id, array('trans_ref' => $trans_id, 'trans_status' => 4));
            $payed_trans = $this->Accounts->getTransaction($payed_trans_id);
            $trans['amount'] = $payed_trans['amount'];
            $trans['amount_alt'] = $payed_trans['amount_alt'];
        }
        if (!$this->Accounts->isTransConnected($trans_id)) {// Update amount unless Closing payment
            $trans['amount'] = $this->request('amount', 2);
            $trans['amount_alt'] = $this->request('amount_alt', 2);
        }
        if ($trans['check_id'] != 0) {//if trans is made from check link them
            $this->Accounts->linkCheck2Trans($trans['check_id'], $trans_id);
        }
        $this->Accounts->updateTransaction($trans_id, $trans);
        //??????????????????????????????????????
        if ($trans['acc_debit_code'] == 361 || $trans['acc_credit_code'] == 361) {
            $trans = $this->Accounts->getTransaction($trans_id); //Get pcomp_id if pcomp not selected
            $this->Accounts->calculatePayments($trans['passive_company_id']);
        }
    }

    public function onCancelTransaction() {
        $this->set_level(2);
        $trans_id = $this->request('trans_id', 1);
        $this->LoadClass('Accounts');
        $this->Accounts->cancelTransaction($trans_id);
    }

    ///////////////////////////
    //NEW TRANS LEDGER FUNCTIONS
    ///////////////////////////
    public function onAccountLedgerGrid() {
        $this->set_level(1);
        $table_query = $this->getGridQuery();
        $idate = $this->request('idate');
        $fdate = $this->request('fdate');
        $passive_only = $this->request('passive_only');
        $acc_code = $this->request('acc_code');
        $acc_branch_id = $this->request('acc_branch_id', 1);
        $out_type = $this->request('out_type', 0, 'json');
        $this->LoadClass('Accounts');
        if (!$acc_code && $acc_branch_id) {
            //$acc_code = $this->Accounts->getAccountCodeFromBranch($acc_branch_id);
        }

        if ($out_type == 'json') {
            $ledger = $this->Accounts->fetchAccountLedgerGrid($table_query, $idate, $fdate, $acc_code, $passive_only);
            $this->response($ledger);
        } else {
            $doc_view_id = $this->Accounts->storeTransView($table_query, $idate, $fdate, $acc_code, $passive_only);
            echo $this->Accounts->getViewPage($doc_view_id, $out_type);
            exit;
        }
    }

    public function onTransNameList() {
        $this->set_level(1);
        $selected_acc = $this->request('selected_acc', 1);
        $this->LoadClass('Accounts');
        $acc_list = $this->Accounts->fetchTransNameList($selected_acc);
        $this->response($acc_list);
    }

    public function onTransNameUpdate() {
        $trans_type = $this->request('trans_type');
        $acc_debit_code = $this->request('acc_debit_code', 1);
        $acc_credit_code = $this->request('acc_credit_code', 1);
        $acc_trans_code = $this->request('acc_trans_code', 1);
        $user_level = $this->request('user_level', 1);
        $trans_name = $this->request('trans_name');
        $remove = $this->request('remove', 1);

        $this->LoadClass('Accounts');
        if ($remove)
            $this->Accounts->deleteTransName($trans_type);
        else
            $this->Accounts->updateTransName($trans_type, $acc_debit_code, $acc_credit_code, $user_level, $trans_name);
    }

    public function onArticlesList() {
        $this->set_level(1);
        $selected_acc = $this->request('selected_acc', 1);
        $this->LoadClass('Accounts');
        $acc_list = $this->Accounts->fetchArticlesList($selected_acc);
        $this->response($acc_list);
    }

    public function onGetCompanyBalance() {
        $passive_company_id = $this->request('company_id', 1, $this->pcomp('company_id'));
        $this->LoadClass('Accounts');
        $this->response(
                $this->Accounts->getCompanyBalance($passive_company_id)
        );
    }

    /////////////////////
    //CLIENT BANK SECTION
    /////////////////////
    public function onCheckListStructure() {
        $this->set_level(3);
        $this->LoadClass('Data');
        $this->response(
                $this->Data->getGridStructure('acc_check_list')
        );
    }

    public function onCheckListData() {
        $this->set_level(3);
        $main_acc_code = $this->request('main_acc_code');
        $table_query = $this->getGridQuery();
        $this->LoadClass('Accounts');
        $table_data = $this->Accounts->getCheckListData($main_acc_code, $table_query);
        $this->response($table_data);
    }

    public function onCheckListDelete() {
        $check_id = $this->request('check_id', 1);
        $this->LoadClass('Accounts');
        $this->Accounts->checkListDelete($check_id);
    }

    public function onXMLUpload() {
        $this->set_level(3);
        $main_acc_code = $this->request('main_acc_code');
        set_time_limit(240);
        $this->rmethod = 'alert';
        $this->LoadClass('Accounts');
        $this->Accounts->checkListParseFile($_FILES['Filedata'], $main_acc_code);
        $this->response("Файл успешно импортирован");
    }

//	public function onFoundListData(){
//	    $this->set_level(3);	
//	    $check_id=$this->request('check_id',1,0);
//	    $table_query=$this->get_table_query();
//	    $this->LoadClass('Accounts');
//	    $crsp=$this->Accounts->findCorrespondings( $check_id, $table_query );
//	    $this->response($crsp);
//	}
    public function onCheckListViewOut() {
        $this->set_level(3);
        $main_acc_code = $this->request('main_acc_code');
        $table_query = $this->getGridQuery();
        $this->LoadClass('Accounts');
        $this->LoadClass('FileEngine');
        $out_type = $this->request('out_type', 0, '.html');

        $view = $this->Accounts->getCheckListData($main_acc_code, $table_query);

        $this->FileEngine->assign($view, 'xlsx/TPL_BankCheckList.xlsx');
        $this->FileEngine->show_controls = true;
        $this->FileEngine->send($out_type);
        exit;
    }

    public function onCorrespondentStats() {
        $this->set_level(3);
        $correspondent_code = $this->request('correspondent_code');
        $this->LoadClass('Accounts');
        $this->response(
            $this->Accounts->getCorrespondentStats($correspondent_code)
        );
    }

    public function onFavoriteUpdate() {
        $is_favorite = $this->request('is_favorite', 1);
        $acc_code = $this->request('acc_code');
        $this->LoadClass('Accounts');
        $this->Accounts->favoriteUpdate($acc_code, $is_favorite);
    }

    public function onFavoriteFetch() {
        $this->LoadClass('Accounts');
        $fav = $this->Accounts->favoriteFetch();
        $this->response($fav);
    }

    public function onUseClientBank() {
        $use_clientbank = $this->request('use_clientbank', 1);
        $acc_code = $this->request('acc_code');
        $this->LoadClass('Accounts');
        $this->Accounts->clientBankUseSet($acc_code, $use_clientbank);
    }

//	public function onUseClientBankAccs(){
//	    $this->LoadClass('Accounts');
//	    $list=$this->Accounts->clientBankUseAccList();
//	    $this->response($list);
//	}
    public function onAccountBalance() {
        $this->set_level(3);
        $acc_code = $this->request('acc_code');
        $this->LoadClass('Accounts');
        $account = $this->Accounts->getAccountBalance($acc_code);
        $this->response($account);
    }
    public function onGetAccountProperties(){
	$acc_code=$this->request('acc_code');
	$this->LoadClass('Accounts');
	$props=$this->Accounts->getAccountProperties($acc_code);
	$this->response($props);
    }
    public function onSetAccountProperties(){
	$acc_code=$this->request('acc_code');
	$props=$this->request('props',3);
	$this->LoadClass('Accounts');
	$this->Accounts->setAccountProperties($acc_code,$props);
    }

    ///////////////////////////
    //REPORTS SECTION
    ///////////////////////////
    public function onDocumentRegistry() {
        $grid_query = $this->getGridQuery();
        $period = $this->request('period', "\d{4}-\d{2}");
        $direction = $this->request('direction', "(buy|sell)");
        $out_type = $this->request('out_type', 0, 'json');
	$group_by=$this->request('group_by',1,0);
	
        $this->LoadClass('Accounts');
        $view = $this->Accounts->documentRegistryFetch($period, $out_type == '.xml' ? 'both' : $direction, $grid_query,$group_by);
        if ($out_type == 'json') {
            $this->response($view);
        } else
        if ($out_type == '.xml') {
            $view['a'] = $this->_acomp;
	    $view['period']=$period;
            $this->LoadClass('Pref');
            $view['pref'] = $this->Pref->prefGet();
            $view['today'] = date('dmY');
            $this->LoadClass('FileEngine');
            $this->FileEngine->assign($view, 'xml/reestr_podatkovih_2015_1.xml');
	    echo $this->FileEngine->fetch($out_type);
            exit;
        } else {
            $filename = "Реестр_Накладных_$period$out_type";
            $this->LoadClass('FileEngine');
            $this->FileEngine->assign($view, 'xlsx/TPL_TaxInvoiceReg.xlsx,.xml');
            $this->FileEngine->show_controls = true;
            $this->FileEngine->send($filename);
            exit;
        }
    }

    public function onDocumentRegistryUpdate() {
        $key = $this->request('key', 3);
        $value = $this->request('value', 3);
        $this->LoadClass('Accounts');
        $this->Accounts->documentRegistryUpdate(intval($key['doc_id']), $value['reg']);
    }

    public function onDocumentRegistryUpdateSinvoice() {
        $doc_id = $this->request('doc_id', 1);
        $pcomp_id = $this->request('pcomp_id', 1);
        $doc_num = $this->request('doc_num', 1);
        $doc_date = $this->request('given');
        $reg_date = $this->request('reg_date');
        $total = $this->request('total', 2);
        $description = $this->request('description');
        $this->LoadClass('Accounts');
        $this->Accounts->documentRegistryUpdateSinvoice($doc_id, $pcomp_id, $doc_num, $doc_date, $reg_date, $total, $description);
    }

    public function onDocumentRegistryDeleteSinvoice() {
        $doc_id = $this->request('doc_id', 1);
        $this->LoadClass('Accounts');
        $this->Accounts->documentRegistryDeleteSinvoice($doc_id);
    }

}

?>