<?php

require_once 'Data.php';

class Accounts extends Data {

    public function commitTransaction($acc_debit_code, $acc_credit_code, $amount = 0, $description, $editable = 0, $cstamp = NULL, $passive_company_id = NULL, $amount_alt = 0) {
	/*MUST REVISE THIS FUNCTION*/
        $this->checkUserAccessLevel(0, $acc_debit_code, $acc_credit_code);
        if (!$cstamp)
            $cstamp = date('Y-m-d H:i:s');
        $active_company_id = $this->Base->acomp('company_id');
        if (!isset($passive_company_id))
            $passive_company_id = $this->Base->pcomp('company_id');
        $user_id = $this->Base->svar("user_id");
        $sql = "INSERT INTO acc_trans SET 
                cstamp='$cstamp',
                active_company_id='$active_company_id', 
                passive_company_id='$passive_company_id', 
                acc_debit_code='$acc_debit_code', 
                acc_credit_code='$acc_credit_code',
                amount='$amount', 
                amount_alt='$amount_alt', 
                description='$description', 
                editable='$editable',
                created_by='$user_id',
                modified_by='$user_id'";
        $this->Base->query($sql);
        return mysql_insert_id();
    }

    private function checkUserAccessLevel($trans_id, $acc_debit_code=null, $acc_credit_code=null) {
        return;
        $user_level = $this->Base->svar('user_level');
        if ($trans_id) {
            if ($user_level < 3 && !$this->Base->get_row("SELECT 1 
                        FROM acc_trans_names 
                        JOIN acc_trans USING(acc_debit_code,acc_credit_code)
                        WHERE user_level<='$user_level' AND trans_id='$trans_id'", 0)
            )
                $this->Base->response_wrn("Для редактирования проводки нужен более высокий уровень доступа!");
        }
        else {
            if ($user_level < 3 && !$this->Base->get_row("SELECT 1 
                        FROM acc_trans_names 
                        WHERE user_level<='$user_level' AND acc_debit_code='$acc_debit_code' AND acc_credit_code='$acc_credit_code'", 0)
            )
                $this->Base->response_wrn("Для проводки нужен более высокий уровень доступа!");
        }
    }

    public function updateTransaction($trans_id, $fields) {
        $this->checkUserAccessLevel($trans_id);
        $user_id = $this->Base->svar("user_id");
        $set = array("modified_by='$user_id'");
        foreach ($fields as $key => $val)
            if (preg_match('/[a-z_]+/', $key))
                $set[] = "$key='$val'";
        $set = implode(',', $set);
        $this->Base->query("UPDATE acc_trans SET $set WHERE trans_id=$trans_id");
    }

    public function cancelTransaction($trans_id) {//Is that enough?
        $this->checkUserAccessLevel($trans_id);
        $this->breakTransConnection($trans_id);
        $this->unlinkCheckTrans($trans_id);
        $trans_data = $this->getTransaction($trans_id);
        $this->Base->query("DELETE FROM acc_trans WHERE trans_id=$trans_id");
        $this->calculatePayments($trans_data['passive_company_id']);
        return true;
    }

    /*
     * Resolve duplicate functions!!!
     */

    public function getTransaction($trans_id) {
        return $this->Base->get_row("SELECT *,amount AS amount FROM acc_trans WHERE trans_id=$trans_id");
    }

    public function fetchTransData($trans_id) {
        $this->Base->msg("DEPRECATED!!!");
        return $this->getTransaction($trans_id);
    }

    public function breakTransConnection($trans_id) {
        $trans_data = $this->getTransaction($trans_id);
        if ($trans_data['trans_ref']) {//If tarnsaction has connection
            $this->updateTransaction($trans_id, array('trans_ref' => 0, 'trans_status' => 0));
            $this->updateTransaction($trans_data['trans_ref'], array('trans_ref' => 0, 'trans_status' => 0));
        }
    }

    private function unlinkCheckTrans($trans_id) {
        $trans_data = $this->getTransaction($trans_id);
        if ($trans_data['check_id']) {//If transaction has linked check
            $this->Base->query("UPDATE acc_check_list SET trans_id=0 WHERE check_id=$trans_data[check_id]");
        }
    }

    public function isTransConnected($trans_id) {
        $trans_data = $this->getTransaction($trans_id);
        return $trans_data['trans_ref'] != 0;
    }

    public function fetchArticlesList($selected_acc) {
        $response = array('identifier' => 'article_id', 'label' => 'article_name', 'items' => array());
        $response['items'] = $this->Base->get_list("SELECT article_id,article_name FROM acc_articles_list ORDER BY article_name");
        return $response;
    }

    public function fetchTransNameList($selected_acc) {
        if ($selected_acc) {
            $where = array();
            foreach (explode(',', $selected_acc) as $acc) {
                $where[] = "acc_debit_code = '" . intval($acc) . "' OR acc_credit_code = '" . intval($acc) . "'";
            }
            $where = "AND (" . implode(' OR ', $where) . ")";
        }
        $response = array('identifier' => 'trans_type', 'label' => 'trans_name', 'items' => array());
        $user_level = $this->Base->svar('user_level');
        $response['items'] = $this->Base->get_list("SELECT CONCAT(acc_debit_code,'_',acc_credit_code) AS trans_type,trans_name,user_level FROM acc_trans_names WHERE user_level<='$user_level' $where ORDER BY trans_name");
        return $response;
    }

    public function updateTransName($trans_type, $acc_debit_code, $acc_credit_code, $trans_user_level, $trans_name) {
        $this->Base->set_level($trans_user_level);
        $this->Base->query("INSERT INTO acc_trans_names SET 
				acc_debit_code='$acc_debit_code', 
				acc_credit_code='$acc_credit_code',
				user_level='$trans_user_level',
				trans_name='$trans_name'
				", false);
        if ( mysql_errno() == 1062 ){
            $this->Base->query("UPDATE acc_trans_names SET 
				acc_debit_code='$acc_debit_code', 
				acc_credit_code='$acc_credit_code',
				user_level='$trans_user_level',
				trans_name='$trans_name'
				WHERE CONCAT(acc_debit_code,'_',acc_credit_code)='$trans_type'
				");
        }
        if ( mysql_errno() || !mysql_affected_rows() ){
            $this->Base->msg('Изменеиния названия проводки не сохранены!');
        }
    }

    public function deleteTransName($trans_type) {
        $level = $this->Base->svar('user_level');
        $this->Base->query("DELETE FROM acc_trans_names 
			WHERE CONCAT(acc_debit_code,'_',acc_credit_code)='$trans_type' AND user_level<=$level
			");
        if (mysql_affected_rows() == 0)
            $this->Base->msg('Название проводки не удалено!');
    }

    /*     * ****************
      STATUS
      1 not payed
      2 partly
      3 payed
      4 closed
      5 closing payment
     * ***************** */

    public function calculatePayments($pcomp_id = NULL) {
	$active_company_id=$this->Base->acomp('company_id');
        if (!isset($pcomp_id))
            $pcomp_id = $this->Base->pcomp('company_id');
        $sensitivity=5.00;
        $acc_code = 361;
        $this->Base->query("SET @sum:=0.0;");
        $this->Base->query("
                UPDATE
                        acc_trans
                SET trans_status=IF(acc_debit_code = $acc_code,
                                (@sum:=@sum - amount)*0 + 
                                IF(amount<0,0,
                                        IF(@sum <= 0 ,1,
                                                IF(@sum+$sensitivity< amount, 2, 3)
                                        )
                                ),
                                (@sum:=@sum + amount)*0
                        )
                WHERE
			active_company_id = $active_company_id
                        AND passive_company_id = $pcomp_id
			AND trans_status <> 4
			AND trans_status <> 5
			AND (acc_debit_code = $acc_code
			OR acc_credit_code = $acc_code)
                ORDER BY acc_debit_code = $acc_code, amount>0, cstamp;");
    }

    private function getAccTransferView($acc_code, $company_only, $curr_id) {
        $def_curr_id = $this->Base->acomp('curr_id');
	$active_company_id = $this->Base->acomp('active_company_id');
        if ($def_curr_id != $curr_id || $company_only && $def_curr_id != $this->Base->pcomp('curr_id')) {
            $amount_column = "amount_alt"; //Foreign currency
        } else {
            $amount_column = "amount"; //Main currency
        }
        $select = array();
        $select[] = "trans_id";
        $select[] = "cstamp";
        $select[] = "IF(acc_trans.trans_status<>0,(SELECT CONCAT(code,' ',descr) FROM acc_trans_status WHERE trans_status=acc_trans.trans_status),'') AS trans_status";
        $select[] = "IF(editable,'user','gear') AS editable";
        $select[] = "DATE_FORMAT(cstamp,'%d.%m.%Y') AS date";
        if ($company_only) {
            $select[] = "'' AS pcomp_name";
            $passive_case = "AND passive_company_id=" . $this->Base->pcomp('company_id');
        } else {
            $select[] = "(SELECT company_name FROM companies_list WHERE company_id=passive_company_id) AS pcomp_name";
            $passive_case = '';
        }
        $select[] = "description";
        $select[] = "IF(acc_debit_code=$acc_code,ROUND($amount_column,2),'') AS debit";
        $select[] = "IF(acc_credit_code=$acc_code,ROUND($amount_column,2),'') AS credit";
        $select[] = "CONCAT(acc_debit_code,' ',acc_credit_code) AS transfer";
        $select[] = "CONCAT(uc.nick,' ',um.nick) AS byuser";
        $select = implode(',', $select);
        $table = "SELECT $select FROM acc_trans LEFT JOIN " . BAY_DB_MAIN . ".user_list uc ON uc.user_id=created_by LEFT JOIN " . BAY_DB_MAIN . ".user_list um ON um.user_id=modified_by WHERE (acc_debit_code='$acc_code' OR acc_credit_code='$acc_code') AND active_company_id='$active_company_id' $passive_case";
        return $table;
    }

    private function getTransTemp($acc_code, $company_only, $curr_id) {
        $acc_code = explode(',', $acc_code);
        $tables = array();
        foreach ($acc_code as $code) {
            $tables[] = $this->getAccTransferView($code, $company_only, $curr_id);
        }
        $view = "(" . implode(' UNION ', $tables) . ") AS acc_trans_view";
        $this->Base->query("DROP TEMPORARY TABLE IF EXISTS acc_trans_temp;");
        $this->Base->query("
            CREATE TEMPORARY TABLE acc_trans_temp(
            trans_id INT UNSIGNED NOT NULL,
            cstamp TIMESTAMP NOT NULL,
            trans_status VARCHAR(45),
            editable VARCHAR(45),
            date VARCHAR(10),
            pcomp_name VARCHAR(255),
            description VARCHAR(512),
            debit VARCHAR(10),
            credit VARCHAR(10),
            transfer VARCHAR(255),
            byuser VARCHAR(7)
            ) ENGINE=InnoDB;
        ");
        $this->Base->query("INSERT INTO acc_trans_temp SELECT * FROM $view");
        return "acc_trans_temp";
    }

    public function fetchAccountLedgerGrid($grid_query, $idate, $fdate, $acc_code, $company_only = false) {
        $frm = function($num) {
            $dnum = doubleval($num);
            return number_format($dnum ? $dnum : '', 2, '.', ' ');
        };
        $ledger = $this->getAccountProperties($acc_code); //Neded curr_id and acc_code
        if ($company_only) {
            $ledger['curr_id'] = $this->Base->pcomp('curr_id');
            $ledger['curr_symbol'] = $this->Base->pcomp('curr_symbol');
        }


        $tmp_table_name = $this->getTransTemp($acc_code, $company_only, $ledger['curr_id']);

        $idate.=' 00:00:00'; //???
        $fdate.=' 23:59:59'; //???
        $where = "cstamp>='$idate' AND cstamp<='$fdate'";
        $ledger['entries'] = $this->getGridData($tmp_table_name, $grid_query, '*', $where, 'ORDER BY cstamp DESC');

        $table_filter = $this->makeGridFilter($tmp_table_name, $grid_query);
        $table_filter = count($table_filter) ? ' AND ' . implode(' AND ', $table_filter) : '';

        $initialSum = $this->Base->get_row("SELECT ROUND(SUM(debit),2) AS debitSum,ROUND(SUM(credit),2) AS creditSum FROM $tmp_table_name WHERE cstamp<'$idate' $table_filter");
        $initialSum['debit'] = $initialSum['debitSum'] > $initialSum['creditSum'] ? $initialSum['debitSum'] - $initialSum['creditSum'] : '';
        $initialSum['credit'] = $initialSum['creditSum'] > $initialSum['debitSum'] ? $initialSum['creditSum'] - $initialSum['debitSum'] : '';
        $periodSum = $this->Base->get_row("SELECT ROUND(SUM(debit),2) AS debitSum,ROUND(SUM(credit),2) AS creditSum FROM $tmp_table_name WHERE cstamp>='$idate' AND cstamp<='$fdate' $table_filter");
        $closingBalance = $initialSum['debit'] - $initialSum['credit'] + $periodSum['debitSum'] - $periodSum['creditSum'];
        if ($closingBalance > 0) {
            $finalDebit = $closingBalance;
            $finalCredit = '';
        } else {
            $finalDebit = '';
            $finalCredit = -$closingBalance;
        }
        $initialBalanceRow = array(
            'trans_id' => 0,
            'date' => date("d.m.Y", strtotime($idate)),
            'description' => 'Входящий остаток',
            'debit' => $frm($initialSum['debit']),
            'credit' => $frm($initialSum['credit'])
        );
        $periodSumRow = array(
            'trans_id' => 0,
            'date' => date("d.m.Y", strtotime($fdate)),
            'description' => "Оборот за период",
            'debit' => $frm($periodSum['debitSum']),
            'credit' => $frm($periodSum['creditSum'])
        );
        $finalBalanceRow = array(
            'trans_id' => 0,
            'date' => date("d.m.Y", strtotime($fdate)),
            'description' => 'Исходящий остаток',
            'debit' => $frm($finalDebit),
            'credit' => $frm($finalCredit)
        );
        $balanceSign = $ledger['acc_type'] == 'P' ? -1 : 1;
        $ledger['entries']['items'] = array_merge(array($finalBalanceRow, $periodSumRow), $ledger['entries']['items'], array($initialBalanceRow));
        $ledger['incoming_balance'] = $frm($balanceSign * ($initialSum['debit'] - $initialSum['credit']));
        $ledger['closing_balance'] = $frm($balanceSign * $closingBalance);
        return $ledger;
    }

    public function storeTransView($grid_query, $idate, $fdate, $acc_code, $passive_only) {
        $doc_view_id = time();
        $views = array(
            $doc_view_id => array(
                'grid_query' => $grid_query,
                'idate' => $idate,
                'fdate' => $fdate,
                'acc_code' => $acc_code,
                'passive_only' => $passive_only
            )
        );
        $oldviews = $this->Base->svar('storedTransViews');
        foreach ($oldviews as $stored => $view) {
            if (time() - $stored < 60 * 60 * 3)// 3 hours
                $views[$stored] = $view;
        }
        $this->Base->svar('storedTransViews', $views);
        return $doc_view_id;
    }

    public function getViewPage($doc_view_id, $out_type) {
        $views = $this->Base->svar('storedTransViews');
        $view = $views[$doc_view_id];
        if (!$view) {
            die('Образ под таким номером не найден!');
        }
        $ledger = $this->fetchAccountLedgerGrid($view['grid_query'], $view['idate'], $view['fdate'], $view['acc_code'], $view['passive_only']);
        return $this->exportTransView($ledger, $view['fdate'], $view['passive_only'], $out_type, $doc_view_id);
    }

    private function exportTransView($view, $fdate, $validation_only, $out_type, $doc_view_id) {
        $this->Base->LoadClass('Utils');
        $this->Base->LoadClass('FileEngine');
        $view['a'] = $this->Base->_acomp;
        $view['p'] = $this->Base->_pcomp;
        $view['user_sign'] = $this->Base->svar('user_sign');
        $view['fdate'] = date('d.m.Y', strtotime($fdate));
        //$view['idate']=date('d.m.Y',strtotime($idate));
        $view['spell'] = $this->Base->Utils->spellAmount(str_replace(' ', '', $view['closing_balance']));
        $view['localDate'] = $this->Base->Utils->getLocalDate($fdate);
        if ($validation_only) {
            //Convert trans status to readible form
            foreach ($view['entries']['items'] as &$row) {
                $arr = explode(' ', $row['trans_status']);
                $row['trans_status'] = $arr[1];
            }
            $this->Base->FileEngine->assign($view, 'xlsx/TPL_PaymentValidation.xlsx');
            $file_name = "Акт_Сверки_$view[fdate]$out_type";
        } else {
            $this->Base->FileEngine->assign($view, 'xlsx/TPL_TransactionList.xlsx');
            $file_name = "Выписка_Счета_{$acc_code}$out_type";
        }
        if ($out_type == 'print') {
            $file_name = '.print';
            $this->Base->FileEngine->show_controls = true;
            $this->Base->FileEngine->user_data = array(title => "Виписка з рахунку", msg => 'Доброго дня', email => $view[p]->company_email, doc_view_id => $doc_view_id);
        }
        return $this->Base->FileEngine->fetch($file_name);
    }

//    public function getAccountIdFromBranch($branch_id) {
//        return $this->Base->get_row("SELECT acc_id FROM acc_list WHERE branch_id='$branch_id'", 0);
//    }

//    public function getAccountCodeFromBranch($branch_id) {
//        //$where=implode("' OR branch_id='",$this->getSubBranchIds( 'acc_tree', $branch_id ));
//        return $this->Base->get_row("SELECT acc_code FROM acc_tree WHERE branch_id='$branch_id'", 0);
//    }

//    public function insertNewAccount($branch_id, $acc_name, $acc_code) {
//        $default_curr_id = $this->Base->acomp('curr_id');
//        $ok=$this->Base->query("INSERT INTO acc_list SET
//                branch_id='$branch_id',
//                acc_code='$acc_code',
//                acc_type='A',
//                acc_name='$acc_name',
//                curr_id=$default_curr_id", 0);
//        $this->rewriteTreePaths(0);
//        return $ok;
//    }

//    public function updateAccount($acc_id, $data) {//This may be vulnerable
//        $set = '';
//        foreach ($data as $key => $val)
//            $set.=",$key='$val'";
//        $set = substr($set, 1);
//        $this->Base->query("UPDATE acc_list SET $set WHERE acc_id='$acc_id'");
//
//        //FIXME Optimize for subbranches
//        $this->rewriteTreePaths(0);
//    }

//    public function deleteAccountBranch($branch_id) {
//        $this->Base->set_level(4);
//        if ($this->Base->request('_confirmed')) {
//            $delIds = $this->getSubBranchIds('acc_tree', $branch_id);
//            $sub_branches_where = "branch_id='" . implode("' OR branch_id='", $delIds) . "'";
//            $this->Base->query("START TRANSACTION");
//            $this->Base->query("DELETE FROM acc_list WHERE $sub_branches_where");
//            $this->Base->query("COMMIT");
//            $this->deleteTreeBranch('acc_tree', $branch_id);
//            return true;
//        } else {
//            $this->Base->response_confirm("Удалить всю информацию данному счету?\nВсе проводки с корр счетами будут удалены!\n\nВнимание этот процесс необратим!\n\n");
//        }
//    }

//    private function rewriteTreePaths($branch_id) {
//
//        function findPath(&$_this, $prefix, $parent_id) {
//            $res = $_this->Base->query("SELECT branch_id,label FROM acc_tree WHERE parent_id='$parent_id'");
//            if (mysql_num_rows($res) > 0) {
//                while ($row = mysql_fetch_assoc($res)) {
//                    $new_prefix = $prefix . '>' . $row['label'];
//                    findPath($_this, $new_prefix, $row['branch_id']);
//                }
//            } else
//                $prefix = substr($prefix, 1);
//            $_this->Base->query("UPDATE acc_list SET branch_path='$prefix' WHERE branch_id='$parent_id'");
//            mysql_free_result($res);
//        }
//
//        findPath($this, '', $branch_id);
//    }

//    public function getPriceListStructure() {
//        return $this->getTableStructure('price_list');
//    }

//    public function fetchPriceListData($table_query) {
//        $table = 'price_list';
//        return $this->getTableData($table, $table_query, "product_code,price_usd,price_uah,buy_price_usd,buy_price_uah");
//    }

    public function fetchAccList($use_clientbank) {
        $response = array('identifier' => 'acc_code', 'label' => 'path', 'items' => array());
        $res = $this->Base->query("SELECT acc_code,label acc_name,path FROM acc_tree WHERE IF($use_clientbank,use_clientbank,1) ORDER BY acc_code");
        //$response['items'][]=array('acc_id'=>'0','acc_code'=>'99999','acc_name'=>'','branch_path'=>'---');
        while ($row = mysql_fetch_assoc($res)) {
            $response['items'][] = $row;
        }
        mysql_free_result($res);
        return $response;
    }

    public function getAccountBalance($acc_code, $pcomp_id = NULL) {
	$active_company_id=$this->Base->acomp('company_id');
        $passive_case = ($pcomp_id === NULL) ? "" : "passive_company_id=$pcomp_id AND";
        $account = $this->Base->get_row("SELECT * FROM acc_tree WHERE acc_code='$acc_code'");
        $account['balance'] = $this->Base->get_row("SELECT ROUND(SUM(IF(acc_debit_code='$acc_code',amount,-amount)),2) 
            FROM acc_trans 
            WHERE $passive_case (acc_debit_code='$acc_code' OR acc_credit_code='$acc_code') AND active_company_id='$active_company_id'", 0);
        $account['curr_symbol'] = $this->Base->acomp('curr_symbol');
        return $account;
    }

    public function getCompanyBalance($pcomp_id, $pcomp_code) {
        $where = $pcomp_id ? " company_id='$pcomp_id'" : " company_code='$pcomp_code' OR company_vat_id='$pcomp_code'";
        $company = $this->Base->get_row("SELECT company_id,company_acc_list FROM companies_list WHERE $where LIMIT 1");
        if (!$company['company_acc_list']) {
            return false;
        }
        $balance = array('passive_company_id' => $company['company_id'], 'accs' => array());
        foreach (explode(',', $company['company_acc_list']) as $acc_code) {
            $balance['accs'][] = $this->getAccountBalance($acc_code, $company['company_id']);
        }
        return $balance;
    }

    /////////////////////
    //CLIENT BANK SECTION
    /////////////////////
    public function checkListParseFile($UPLOADED_FILE, $main_acc_code) {
        if (strrpos($UPLOADED_FILE['name'], '.xml')) {
            $xml = file_get_contents($UPLOADED_FILE['tmp_name']);
            $report = new SimpleXMLElement($xml);
            foreach ($report->{'document-group'}->document as $document) {
                $this->addCheckDocument($document, $main_acc_code);
            }
        } else if (strrpos($UPLOADED_FILE['name'], '.csv')) {
            $csv = file_get_contents($UPLOADED_FILE['tmp_name']);
            $csv = iconv('Windows-1251', 'UTF-8', $csv);
            $csv_lines = explode("\n", $csv);
            array_shift($csv_lines);
            $this->Base->LoadClass('Pref');
            $prefs=$this->Base->Pref->prefGet();
            $csv_sequence=explode(",",$prefs['clientbank_fields']);
            foreach ($csv_lines as $line) {
                if (!$line)
                    continue;
                $vals = str_getcsv($line, ';');
                $doc = array();
                $i=0;
                foreach($csv_sequence as $field){
                    $doc[trim($field)]=$vals[$i++];
                }
                $this->addCheckDocument($doc, $main_acc_code);
            }
        } else
            $this->Base->response_error("Формат должен быть .xml .csv");
    }

    private function addCheckDocument($check, $main_acc_code) {
        $table_name = 'acc_check_list';
        if (!$this->cached_fields[$table_name]) {
            $this->cached_fields[$table_name] = $this->getTableStructure($table_name, 'field', 'fromdb');
        }
        if ($check['client-code'] != $this->Base->acomp('company_code')) {
            //$this->Base->response("Платежное поручение от другого предприятия!\nЕГРПОУ: {$check['client-code']}");
            //return false;
        }
        $fields = $this->cached_fields[$table_name]['columns'];
	$active_company_id=$this->Base->acomp('company_id');
	
        $set = ["active_company_id='$active_company_id'"];
        $check['main-acc-code'] = $main_acc_code;
        foreach ($fields as $field) {
            if ($field == 'check_id' || $field == 'status') {
                continue;
            }
            $xml_field = str_replace('_', '-', $field);
            $val = isset($check[$xml_field]) ? $check[$xml_field] : $check->$xml_field;
            if ($field == 'debit_amount' || $field == 'credit_amount') {
                $val = str_replace(',', '.', $val);
            }
            if (strpos($field, 'date') !== false) {
                preg_match_all('/(\d{2})[^\d](\d{2})[^\d](\d{4})( \d\d:\d\d(:\d\d)?)?/i', $val, $matches);
                $val = "{$matches[3][0]}-{$matches[2][0]}-{$matches[1][0]}{$matches[4][0]}";
            }
            $set[] = "$field='" . addslashes($val) . "' ";
        }
        $this->Base->query("INSERT INTO acc_check_list SET " . implode(',', $set), false);
        return true;
    }

    public function getCorrespondentStats($correspondent_code) {
        $balance = $this->getCompanyBalance(0, $correspondent_code);
        return $balance;
    }

    public function getCheckListData($main_acc_code = 0, $grid_query) {
	$active_company_id=$this->Base->acomp('company_id');
        $select = "
		    *,
		    IF(trans_id,'ok Проведен','gray Непроведен') AS status,
		    IF(debit_amount,ROUND(debit_amount,2),'') AS debit,
		    IF(credit_amount,ROUND(credit_amount,2),'') AS credit,
		    DATE_FORMAT(transaction_date,'%d.%m.%Y') AS tdate
		    ";
        return $this->getGridData('acc_check_list', $grid_query, $select, "main_acc_code='$main_acc_code' AND active_company_id=$active_company_id", 'ORDER BY transaction_date DESC');
    }

    public function checkListDelete($check_id) {
        $this->Base->set_level(3);
        $trans_id = $this->Base->get_row("SELECT trans_id FROM acc_check_list WHERE check_id=$check_id", 0);
        if ($trans_id)
            $this->cancelTransaction($trans_id);
        $this->Base->query("DELETE FROM acc_check_list WHERE check_id=$check_id");
    }

//    public function findCorrespondings($check_id) {
//        $sql = "SELECT 
//		    atr.trans_id,
//		    IF(atr.trans_status<>0,(SELECT CONCAT(code,' ',descr) FROM acc_trans_status WHERE trans_status=atr.trans_status),'') trans_status,
//		    DATE_FORMAT(atr.cstamp, '%d.%m.%Y') trans_date,
//		    atr.description,
//		    credit_amount debit,
//		    debit_amount credit,
//			company_name,
//		acc_debit_code,acc_credit_code
//		FROM
//		    acc_check_list acl
//			JOIN
//		    companies_list cl ON company_code = correspondent_code
//			JOIN
//		    acc_trans atr ON cl.company_id=atr.passive_company_id AND (debit_amount = atr.amount OR credit_amount = atr.amount)
//		where
//		    acl.check_id = 6136";
//    }

    public function linkCheck2Trans($check_id, $trans_id) {
        $this->Base->query("UPDATE acc_check_list SET trans_id=$trans_id WHERE check_id=$check_id");
        $this->Base->query("UPDATE acc_trans SET check_id=$check_id WHERE trans_id=$trans_id");
    }

//    public function favoriteUpdate($acc_code, $is_favorite) {
//        $this->Base->set_level(3);
//        $this->Base->query("UPDATE acc_tree SET is_favorite='$is_favorite' WHERE acc_code='$acc_code'");
//    }

    public function favoriteFetch() {
        return $this->Base->get_list("SELECT * FROM acc_tree WHERE is_favorite=1 ORDER BY acc_code");
    }

//    public function clientBankUseSet($acc_code, $use_clientbank) {
//        $this->Base->set_level(3);
//        $this->Base->query("UPDATE acc_list SET use_clientbank='$use_clientbank' WHERE acc_code='$acc_code'");
//    }

    /////////////////////////////////////
    // REPORTS SECTION
    /////////////////////////////////////
    public function documentRegistryFetch($period, $direction, $grid_query,$group_by) {
        $this->Base->set_level(3);
	$active_company_id=$this->Base->acomp('company_id');
        if ($direction == 'both') {
            return array(
                'sell' => $this->documentRegistryFetch($period, 'sell'),
                'buy' => $this->documentRegistryFetch($period, 'buy')
            );
        } else
        if ($direction == 'sell') {
            $dir = '(doc_type=1 OR doc_type=3)';
        } else
        if ($direction == 'buy') {
            $dir = '(doc_type=2 OR doc_type=4)';
        }
        $select = "
            dl.doc_id,
            DATE_FORMAT(reg_stamp,'%Y-%m') AS reg,
            DATE_FORMAT(IF(doc_type=1,dvl.tstamp,cstamp),'%d.%m.%Y') AS given,
            IF(dvl.tstamp,dvl.tstamp,cstamp),
            IF(doc_type=1,view_num,doc_num),
            IF(LOCATE('type_of_reason\":\"0',view_efield_values),SUBSTRING(view_efield_values,LOCATE('type_of_reason',view_efield_values)+17,2),'') tor,
            CONCAT(label,IF(company_vat_id,'',' (Неплатник податку)')) AS short_name,
            company_id,
            CONCAT(icon_name,' ',doc_type_name) AS icon,
            IF(company_vat_id,company_name,'Неплатник податку'),
            IF(company_vat_id,company_vat_id,'100000000000'),
            doc_data,
            (SELECT 
                    CAST(ROUND(amount,2) AS CHAR(10))
                    FROM acc_trans JOIN document_trans USING(trans_id) 
                    WHERE doc_id=dl.doc_id AND (type='28_631' OR type='44_631' OR type='361_702')
            ) AS total,
            (SELECT 
                    CAST(ROUND(amount,2) AS CHAR(10))
                    FROM acc_trans JOIN document_trans USING(trans_id) 
                    WHERE doc_id=dl.doc_id AND (type='281_28' OR type='441_44' OR type='702_791')
            ) AS vatless,
            (SELECT 
                    CAST(ROUND(amount,2) AS CHAR(10))
                    FROM acc_trans JOIN document_trans USING(trans_id) 
                    WHERE doc_id=dl.doc_id AND (type='641_28' OR type='641_44' OR type='702_641')
            ) AS vat,
	    doc_view_id,
	    IF(doc_view_id,'print Напечатать Налоговую Накладную','') print,
	    IF(doc_view_id,'down Скачать Налоговую Накладную','') down";
        $table = "
            document_list dl
            JOIN document_types USING(doc_type)
            JOIN companies_list ON company_id=passive_company_id
            JOIN companies_tree USING(branch_id)
            LEFT JOIN document_view_list dvl ON dl.doc_id=dvl.doc_id 
	    AND view_type_id IN (SELECT view_type_id FROM document_view_types WHERE view_role='tax_bill')";
        $where = "
	    active_company_id='$active_company_id'
            AND reg_stamp LIKE CONCAT('$period','%')
            AND is_commited=1
            AND $dir";
        $order = "ORDER BY reg,cstamp";
        $this->Base->query("DROP TEMPORARY TABLE IF EXISTS doc_registry_temp;"); //
        $this->Base->query("
            CREATE TEMPORARY TABLE doc_registry_temp(
            doc_id INT,
            reg VARCHAR(10),
            given VARCHAR(10),
            cstamp VARCHAR(10),
            doc_num INT,
            tor VARCHAR(2),
            short_name VARCHAR(45),
            pcomp_id INT,
            icon VARCHAR(45),
            company_name VARCHAR(255),
            company_vat_id VARCHAR(12),
            description VARCHAR(45),
            total VARCHAR(10),
            vatless VARCHAR(10),
            vat VARCHAR(10),
	    doc_view_id INT,
	    print VARCHAR(45),
	    down VARCHAR(45)
            ) ENGINE=Memory;
        ");
        $this->Base->query("INSERT INTO doc_registry_temp SELECT $select FROM $table WHERE $where");
        $registry = $this->Base->get_row("SELECT 
            SUM(total) AS gtotal,
            SUM(vatless) AS gvatless,
            SUM(vat) AS gvat,
            COUNT(*) AS num
            FROM doc_registry_temp");
        $registry['gtotal'] = number_format($registry['gtotal'], 2, '.', ' ');
        $registry['gvatless'] = number_format($registry['gvatless'], 2, '.', ' ');
        $registry['gvat'] = number_format($registry['gvat'], 2, '.', ' ');
	
	
	if( $group_by ){
	    $select="
		company_name,
		company_vat_id,
		SUM(total) total,
		SUM(vatless) vatless,
		SUM(vat) vat";
	    $order="ORDER BY company_name";
	    
	    
	    
	    $registry['entries'] = $this->getGridData('doc_registry_temp GROUP BY company_name ', $grid_query, $select, '', $order);
	} else {
	    $registry['entries'] = $this->getGridData('doc_registry_temp', $grid_query, '*', '', $order);
	}
	
	
        
        return $registry;
    }

    public function documentRegistryUpdate($doc_id, $period) {
        $this->Base->set_level(3);
        $this->Base->query("UPDATE document_list SET reg_stamp=CONCAT('$period','-01') WHERE doc_id='$doc_id'");
    }

    public function documentRegistryUpdateSinvoice($doc_id, $pcomp_id, $doc_num, $doc_date, $reg_date, $total, $description) {
        $this->Base->set_level(3);
        $this->Base->LoadClass('Document');
        $this->Base->selectPassiveCompany($pcomp_id);

        if (!$doc_id) {
            $this->Base->Document->add(4);
            $doc_entry_id = $this->Base->Document->addEntry('srv_spend', 1);
        } else {
            $this->Base->Document->selectDoc($doc_id);
        }

        if ($this->Base->Document->doc('doc_type') == 4) {
            if ($this->Base->Document->doc('is_commited')){
                $this->Base->Document->uncommit();
            }
            if ($this->Base->Document->doc('passive_company_id') != $pcomp_id){
                $this->Base->Document->moveDoc($pcomp_id);
            }
            $entries = $this->Base->Document->fetchEntries();
            $doc_entry_id = $entries['rows'][0][0];
            $this->Base->Document->updateEntry($doc_entry_id, NULL, $total );// / (1+$this->Base->Document->doc('vat_rate')/100) 
            $this->Base->Document->commit();
        }
        else {
            $pcomp_id = $this->Base->Document->doc('passive_company_id');
            $this->Base->selectPassiveCompany($pcomp_id);
        }

        $this->Base->Document->updateHead($doc_date, 'date');
        $this->Base->Document->updateHead($reg_date, 'reg_date');
        $this->Base->Document->updateHead($description, 'doc_data');
        $this->Base->Document->updateHead($doc_num, 'num');

        $pcomp_name = $this->Base->get_row("SELECT company_name FROM companies_list WHERE company_id='$pcomp_id'", 0);
        $this->Base->msg("\nДокумент №$doc_num от $doc_date на сумму $total грн сохранен.\n\nКонтрагент: $pcomp_name\nПериод: $reg_date");
    }

    public function documentRegistryDeleteSinvoice($doc_id) {
        $this->Base->set_level(3);
        $this->Base->LoadClass('Document');
        $this->Base->Document->selectDoc($doc_id);
        if ($this->Base->Document->doc('is_commited') && $this->Base->Document->doc('doc_type') == 4) {
            $this->Base->Document->uncommit();
            $this->Base->Document->uncommit();
        } else {
            $this->Base->msg("Удалять можно только Акт Полученных Услуг!");
        }
    }



    //public $getAccountProperties='(int) acc_code';
    
    public function getAccountProperties($acc_code) {
        $sql="SELECT
            * 
            FROM acc_tree at
            JOIN curr_list cl ON IF(at.curr_id,cl.curr_id=at.curr_id,cl.curr_id=1)
            WHERE acc_code='$acc_code'";
        return $this->Base->get_row($sql);
    }

//    public $setAccountProperties='(int) acc_code,(json) props'; JOIN curr_list ON (curr_list.curr_id=1)
//    
//    public function setAccountProperties($acc_code, $props) {
//        $this->Base->set_level(4);
//        $sql = "UPDATE acc_list SET 
//                is_favorite='{$props['is_favorite'][0]}',
//                use_clientbank='{$props['use_clientbank'][0]}',
//                use_articles='{$props['use_articles'][0]}',
//                acc_name='{$props['acc_name']}',
//                acc_type='{$props['acc_type']}',
//                curr_id='{$props['curr_id']}'
//                WHERE acc_code=$acc_code";
//        $this->Base->query($sql);
//	return true;
//    }

}

?>
