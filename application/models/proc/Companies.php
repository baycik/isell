<?php

require_once 'Data.php';

class Companies extends Data {

    public function insertNewCompany($branch_id, $company_name) {
        $def_lang = $this->Base->acomp('language');
        $def_curr_code = $this->Base->acomp('curr_code');
        $this->Base->query("INSERT INTO companies_list SET 
            branch_id='$branch_id',
            company_name='$company_name',
            language='$def_lang',
            curr_code='$def_curr_code'
        ");
    }

    public function deleteCompanyBranch($branch_id) {
        if (!$this->Base->checkAssignedBranch($branch_id))
            return false;
        $this->Base->set_level(4);
        if ($this->Base->request('_confirmed')) {
            $delIds = $this->getSubBranchIds('companies_tree', $branch_id);
            $sub_branches_where = "branch_id='" . implode("' OR branch_id='", $delIds) . "'";
            $this->Base->query("START TRANSACTION");
            $this->Base->query("DELETE FROM companies_list WHERE $sub_branches_where");
            $this->Base->query("COMMIT");
            $this->deleteTreeBranch('companies_tree', $branch_id);
            return true;
        } else {
            $this->Base->response_confirm("Удалить всю информацию по выбранным предприятиям?\nВнимание этот процесс необратим!");
        }
    }

    public function updateCompaniesTreeBranch($branch_id, $parent_id, $text) {
        if (!$this->Base->checkAssignedBranch($branch_id) || !$this->Base->checkAssignedBranch($parent_id))
            return false;
        $new_branch = $this->updateTreeBranch('companies_tree', $branch_id, $parent_id, $text);
        $this->updateTreeBranchPath('companies_tree', $branch_id);
        return $new_branch;
    }

    public function getCompanyIdFromBranch($branch_id) {
        return $this->Base->get_row("SELECT company_id FROM companies_list WHERE branch_id='$branch_id'", 0);
    }

    public function getCompaniesTreeChildren($branch_id) {
        $assigned_path = $this->Base->svar('user_assigned_path');
        if ($assigned_path && $branch_id == 0) { //Requested root of tree
            $tree_obj = array(
                'id' => 0,
                'item' => array()
            );
            $where = "path = '" . str_replace(',', "' OR path='", $assigned_path) . "'";
            $assigned = $this->Base->get_list("SELECT branch_id FROM companies_tree WHERE $where");
            foreach ($assigned as $branch) {
                $tree_obj['item'] = array_merge($tree_obj['item'], $this->getTreeChildren('companies_tree', $branch['branch_id'], 'id', 'parent_id', 'text', 'toplevel'));
            }
            return $tree_obj;
        } else if ($this->Base->checkAssignedBranch($branch_id)) {
            $tree_obj = array(
                'id' => $branch_id,
                'item' => $this->getTreeChildren('companies_tree', $branch_id, 'id', 'parent_id', 'text', 'toplevel')
            );
            return $tree_obj;
        }
        return false;
    }

//	public function getCompaniesTreeChildren2( $branch_id ){
//		$top_id=$branch_id;
//		if( $this->Base->svar('user_assigned_branch') && $branch_id==0 ){
//			$branch_id=$this->Base->svar('user_assigned_branch')*1;
//			$top_id=0;
//		}
//		if( $this->Base->checkAssignedBranch($branch_id) ){
//			$tree_obj=array();
//			$tree_obj['id']=$top_id;
//			$tree_obj['item']=$this->getTreeChildren( 'companies_tree', $branch_id, 'id', 'parent_id', 'text', 'toplevel' );
//			return $tree_obj;
//		}
//		return false;
//	}
    ////////////////////////DETAILS//////////////////////////////
    public function getCompanyDetails($is_active = false /* Is Active Company */) {
        $company_id = $is_active ? $this->Base->acomp('company_id') : $this->Base->pcomp('company_id');
        $details = $this->Base->get_row("
                    SELECT 
                        cl.*,
                        label AS company_tree_name,
                        path,
                        DATE_FORMAT(company_agreement_date, '%d.%m.%Y') AS company_agreement_date
                    FROM
                        companies_list cl
                    LEFT JOIN 
                        companies_tree ct ON ct.branch_id = cl.branch_id
                    WHERE
                        company_id = '$company_id'"
        );
        return $details;
    }

    public function updateDetail($field_name, $field_value, $is_active = false /* Is Active Company */) {
        $company_id = $is_active ? $this->Base->acomp('company_id') : $this->Base->pcomp('company_id');
        if (in_array($field_name, array('manager_id', 'is_supplier', 'deferment', 'debt_limit', 'company_acc_list'))){
            $this->Base->set_level(4);
	}
        if ($field_name == 'company_tree_name'){
            $this->Base->query("UPDATE companies_tree SET label='$field_value' WHERE branch_id=(SELECT branch_id FROM companies_list WHERE company_id='$company_id')");
	}
        else if ($this->checkField('companies_list', $field_name)){
            $this->Base->query("UPDATE companies_list SET $field_name='$field_value' WHERE company_id='$company_id'");
	}
        else{
            $this->Base->response_error('!!! Поле отсутствует ' . $field_name);
	}
        $is_active ? $this->Base->reloadActiveCompany() : $this->Base->reloadPassiveCompany();
    }

    ////////////////////////-DETAILS//////////////////////////////
    ///////////////////////Adjustments////////////////////////////
    private function getDiscounts($company_id) {
        $discounts = array();
        $res = $this->Base->query("SELECT branch_id,label,(SELECT discount FROM companies_discounts cd WHERE cd.branch_id=st.branch_id AND cd.company_id=$company_id) AS discount FROM stock_tree st  WHERE parent_id=0 ORDER BY label");

        while ($row = mysql_fetch_assoc($res)) {
            if ($row['discount'] > 1)
                $row['plus'] = $row['discount'] * 100 - 100;
            if ($row['discount'] < 1 && $row['discount'] > 0)
                $row['minus'] = 100 - $row['discount'] * 100;
            $discounts[] = $row;
        }
        mysql_free_result($res);
        return $discounts;
    }

    public function getAdjustments() {
        $passive_company_id = $this->Base->pcomp('company_id');
        $this->Base->LoadClass('Pref');
        $staffListStruct = $this->Base->Pref->getStaffList();
        //array_splice($staffListStruct['items'], 0,0,array('staff_id'=>0,'full_name'=>'---'))

        $adj = array();
        $adj = $this->Base->get_row("SELECT deferment,curr_code,manager_id,is_supplier,company_acc_list FROM companies_list WHERE company_id='$passive_company_id'");
        $adj['discounts'] = $this->getDiscounts($passive_company_id);
        $adj['staffList'] = $staffListStruct['items'];
        return $adj;
    }

    public function updateDiscount($branch_id, $raw_discount) {
        $this->Base->set_level(2);
        $passive_company_id = $this->Base->pcomp('company_id');
        $discount = (100 + $raw_discount) / 100;
        $this->Base->query("DELETE FROM companies_discounts WHERE company_id=$passive_company_id AND branch_id=$branch_id");
        if ($discount != 1){
            $this->Base->query("INSERT INTO companies_discounts SET company_id=$passive_company_id, branch_id=$branch_id, discount=$discount");
        }
    }

    ///////////////////////-Adjustments////////////////////////////
    public function getCompaniesList($clue, $start = 0, $count = 999, $selected_comp_id) {
        $user_id = $this->Base->svar('user_id');
        $user_level = $this->Base->svar('user_level');
        $assigned_path = $this->Base->svar('user_assigned_path');
        $assigned_where = $assigned_path ? "AND (path LIKE '" . str_replace(',', "%' OR path LIKE '", $assigned_path) . "%')" : "";
        //$user_only_assigned=$this->Base->svar('user_only_assigned');
        /*
         * If clue is not set use selected_comp_id
         */
        if ($clue)
            $where = " label LIKE '%$clue%' AND";
        else if ($selected_comp_id)
            $where = "company_id='$selected_comp_id' AND";
        $response = array('identifier' => 'company_id', 'label' => 'label', 'items' => array());
        $sql = "SELECT '' label, 0 company_id 
                UNION SELECT 
                    label, company_id
                FROM
                    companies_list
                        JOIN
                    companies_tree USING (branch_id)
                WHERE
                    $where level<='$user_level' AND label<>''
                    $assigned_where
                ORDER BY label
                LIMIT $start , $count";
        $response['items'] = $this->Base->get_list($sql);
        if ($user_level > 2) {
            $response['items'][] = array('label' => $this->Base->acomp('company_name'), 'company_id' => $this->Base->acomp('company_id'));
        }
        return $response;
    }

    /*
     * TODO REBUILD FILE EXPORT SYSTEM maybe move to plugin or module
     */

    public function getViewPage($doc_view_id, $out_type) {
        $this->Base->LoadClass('Document');
        if (!$view = $this->Base->Document->getViewOut($doc_view_id)) {
            die('Образ под таким номером не найден!');
        }
	$file_name = "$view[view_name]_№$view[view_num]$out_type";
	
        $this->Base->LoadClass('FileEngine');
        if ($out_type == '.print') {
            $file_name = '.print';
            $msg = $view[head][doc_data];
            $this->Base->FileEngine->show_controls = true;
            $this->Base->FileEngine->user_data = array(
                title => "$view[view_name] №$view[view_num]",
                msg => $msg,
                email => $view['p']['company_email']
            );
        }
        if ($view['freezed']) {
            $this->Base->FileEngine->loadHTML(stripslashes($view['html']));
        } else {
            $this->Base->FileEngine->assign($view, $view['view_tpl']?$view['view_tpl']:$view['view_file']);
        }
        return $this->Base->FileEngine->fetch($file_name);
    }

    /////////////////////////////
    //STATS SECTION
    /////////////////////////////

//    public function getSellStats() {
//        $stats = array();
//        $vat_rate = (1 + $this->Base->acomp('company_vat_rate') / 100);
//        $m = date('Y-m', time() - 60 * 60 * 24 * 31);
//        $passive_company_id = $this->Base->pcomp('company_id');
//        $sql = "SELECT 
//			stt.label brand,
//			ROUND(SUM(de.product_quantity*de.invoice_price)*$vat_rate) s,
//			SUBSTR(cstamp,1,7) m
//				FROM document_entries de
//				JOIN document_list dl USING(doc_id)
//				JOIN stock_entries se USING(product_code)
//				JOIN stock_tree st ON st.branch_id=se.parent_id
//				JOIN stock_tree stt ON st.top_id=stt.branch_id
//			WHERE passive_company_id=$passive_company_id AND cstamp>'$m' AND doc_type=1
//			GROUP BY SUBSTR(cstamp,1,7) DESC,st.top_id";
//        $stats['sell'] = $this->Base->get_list($sql);
//        if ($this->Base->svar('user_level') > 2) {
//            $this->Base->query("SET @mval:=0");
//            $sql = "
//				SELECT m,s,p,IF(@mval<s,IF(s<p,@mval:=p,@mval:=s),0) n
//				FROM
//				(SELECT
//					SUBSTR(cstamp,6,2) m,
//					ROUND(SUM(IF(acc_debit_code=361,amount,0))) s,
//					ROUND(SUM(IF(acc_credit_code=361,amount,0))) p
//				FROM
//					acc_trans 
//				WHERE passive_company_id=$passive_company_id
//				GROUP BY SUBSTR(cstamp,1,7) LIMIT 12) AS t";
//            $stats['pay'] = $this->Base->get_list($sql);
//            $stats['pay_mval'] = $this->Base->get_row("SELECT @mval", 0);
//        }
//        return $stats;
//    }

}

?>