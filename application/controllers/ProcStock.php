<?php

require_once('iSellBase.php');

class ProcStock extends iSellBase {

    public function ProcStock() {
        $this->ProcessorBase(1);
    }

    public function onDefault() {
        $this->response_tpl('stock/stock_main.html');
    }

    public function onStockTree() {
        $parent_id = $this->request('id', 1, 0);
        $direct = $this->request('direct', 1);
        $whole = $this->request('whole', 1);

        $loadbranches = $whole ? 'all' : 'toplevel';

        $this->LoadClass('Stock');
        $tree_obj = array();
        $tree_obj['id'] = $parent_id;
        $tree_obj['item'] = $this->Stock->getTreeChildren('stock_tree', $parent_id, 'id', 'parent_id', 'text', $loadbranches);
        if ($direct){
            $this->response($tree_obj, 1);
        }
        else{
            $this->response($tree_obj, 1);
        }
    }

    public function onTreeItemUpdate() {
        $branch_id = $this->request('branch_id', 1);
        $parent_id = $this->request('parent_id', 1);
        $text = $this->request('text');
        $this->LoadClass('Stock');
        if ($branch_id == 999999) {
            $new_branch_id = $this->Stock->insertTreeBranch('stock_tree', $parent_id, $text);
            $this->response($new_branch_id);
        } else {
            $this->Stock->updateTreeBranch('stock_tree', $branch_id, $parent_id, $text);
        }
    }

    public function onTreeItemDelete() {
        $this->set_level(2);
        $branch_id = $this->request('branch_id', 1);
        $this->LoadClass('Stock');
        $ok = $this->Stock->deleteTreeBranch('stock_tree', $branch_id);
        if (!$ok) {
            $this->response_wrn("Невозможно удалить ветку.");
        }
        $this->response($ok);
    }

    public function onTreeItemInsert() {
        $parent_id = $this->request('parent_id', 1);
        $is_leaf = $this->request('is_leaf', 1);
        $text = $this->request('text');
        $this->LoadClass('Stock');

        $branch_data = '{"im0":"box.png"}';

        $new_branch_id = $this->Stock->insertTreeBranch('stock_tree', $parent_id, $text, $is_leaf, $branch_data);

        if ($new_branch_id == -1)
            $this->msg("Невозможно добавить ветку!");
        $this->response($new_branch_id);
    }

    public function onStockEntryData() {
        $table_query = $this->getGridQuery();
        $parent_id = $this->request('parent_id', 1);
        $this->LoadClass('Stock');
        $table_data = $this->Stock->fetchStockEntries($table_query, $parent_id);
        $this->response($table_data);
    }

    public function onStockEntryUpdate() {
        $key = $this->request('key', 3);
        $value = $this->request('value', 3);
        $this->LoadClass('Stock');
        if ( isset($value['parent_label']) ) {
            $this->Stock->stockEntryParentUpdate($key['product_code'], (string) $value['parent_label']);
        } else
        if ( isset($value['party_label']) ) {
            $this->Stock->stockEntryPartyUpdate($key['product_code'], (string) $value['party_label']);
        } else
        if ( isset($value['product_wrn_quantity']) ) {
            $this->Stock->stockEntryWrnUpdate($key['product_code'], (int) $value['product_wrn_quantity']);
        } else {
            $this->response_error("Not allowed to change another fields");
        }
    }

    public function onStockEntryInsert() {
        $this->set_level(2);
        $product_code = $this->request('product_code');
        $parent_id = $this->request('parent_id', 1);
        $this->LoadClass('Stock');
        $this->Stock->stockEntryInsert($product_code, $parent_id);
    }

    public function onStockEntryDelete() {
        $delIds = $this->request('delIds', 3);
        $this->LoadClass('Stock');
        if (!$this->Stock->stockEntryDelete($delIds)) {
            $this->msg("Строка не удалена!\nКолличество должно быть нулевым!");
        }
    }

    public function onStockGridOut() {
        $table_name = 'stock_entry_view';
        $grid_query = $this->getGridQuery();
        $out_type = $this->request('out_type', 0, '.print');
        $parent_id = $this->request('parent_id', 1);
        $this->LoadClass('Stock');
        $grid_data = $this->Stock->fetchStockEntries($table_query, $parent_id);
        //$grid_data=$this->Data->getGridData( $table_name, $grid_query,'*',"parent_id=$parent_id");//, "CONCAT('ok ',product_code) AS product_code"
        $grid_structure = $this->Stock->getGridStructure($table_name);

        $this->Stock->getGridOut($grid_structure, $grid_data, $out_type);
        exit;
    }

    public function onIncomeOrder() {
        $this->set_level(2);
        $parent_id = $this->request('parent_id', 3);
        $isVatOrder = $this->request('isVatOrder', 1);
        $confirmed = $this->request('_confirmed', 1);
        $pcomp_name = $this->pcomp('company_name', false);
        if ($pcomp_name == false) {
            $this->response_wrn('Необходимо выбрать компанию-поставщика во вкладке Компании!');
        }
        if ($confirmed) {
            $this->LoadClass('Stock');
            $this->LoadClass('Document');
            if ($isVatOrder)
                $order = $this->Stock->getVatIncomeOrder($parent_id);
            else
                $order = $this->Stock->getIncomeOrder($parent_id);
            $this->Document->add(2);
            for ($i = 0; $i < count($order); $i++) {
                $this->Document->addEntry($order[$i]['product_code'], $order[$i]['count']);
            }
            $this->msg("Заказ загружен к $pcomp_name");
        } else {
            $this->response_confirm("Загрузить заказ как приходный документ к\n$pcomp_name?");
        }
    }

    public function onAdjustMin() {
        $this->set_level(2);
        $parent_id = $this->request('parent_id', 1, 0);
        $ratio = $this->request('ratio', 2);
        $this->LoadClass('Stock');
        $this->Stock->adjustMin($parent_id, $ratio);
    }

    public function onStockLogStructure() {
        $table_name = 'stock_log';
        $this->LoadClass('Stock');
        $table_structure = $this->Stock->getTableStructure($table_name);
        $this->response($table_structure);
    }

    public function onStockLogData() {
        $table_query = array();
        $table_query['cols'] = $this->request('cols', 3);
        $table_query['vals'] = $this->request('vals', 3);
        $table_query['page'] = $this->request('page', 1, 1);
        $table_query['limit'] = $this->request('limit', 1, 30);
        $this->LoadClass('Stock');
        $table_data = $this->Stock->getLogEntries($table_query);
        $this->response($table_data);
    }

}

?>