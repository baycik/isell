<?php

require_once 'Data.php';

class Stock extends Data {

    private $stockEntry, $productCode;

    private function stockEntryLoad($product_code) {
        $this->productCode = addslashes($product_code);//bugfix if special characters are used like \ 
        $sql = "SELECT 
                product_wrn_quantity,
		(SELECT product_quantity FROM " . BAY_DB_MAIN . ".stock_entries WHERE product_code='{$this->productCode}') product_quantity,
 		vat_quantity,
		self_price,
		parent_id,
                party_label
            FROM
                stock_entries
            WHERE
                product_code='{$this->productCode}'";
        $this->stockEntry = (object) $this->Base->get_row($sql);
    }

    private function stockEntryMainSave() {
        if ($this->Base->Document && $this->Base->Document->doc('notcount')) {
            return true;
        }
        if ($this->stockEntry->product_quantity < 0) {
            $this->Base->msg("Нехватает в колличестве " . (-$this->stockEntry->product_quantity) . "!");
            return false;
        }
        $sql = "UPDATE
		" . BAY_DB_MAIN . ".stock_entries
	    SET
		product_quantity={$this->stockEntry->product_quantity}
	    WHERE
		product_code='{$this->productCode}'";
        $this->Base->query($sql);
        return true;
    }

    private function stockEntrySave() {
        $this->Base->set_level(2);
        if( $this->stockEntryMainSave() ){
            $sql = "UPDATE
                    stock_entries
                SET
                    product_wrn_quantity='{$this->stockEntry->product_wrn_quantity}',
                    vat_quantity='{$this->stockEntry->vat_quantity}',
                    self_price='{$this->stockEntry->self_price}',
                    parent_id='{$this->stockEntry->parent_id}',
                    party_label='{$this->stockEntry->party_label}'
                WHERE
                    product_code='{$this->productCode}'";
            $this->Base->query($sql);
            if ( !mysql_errno() ) {
                return true;
            }
        }
        $this->Base->msg('Немогу изменить строку на складе!');
        return false;
    }

    private function stockEntryQtyAlter($action = 'increase', $product_code, $amount, $description = NULL, $self_price = NULL) {
        $this->stockEntryLoad($product_code);
        switch ($action) {
            case 'increase':
                $this->stockEntry->product_quantity += $amount;
                $this->stockEntry->vat_quantity += $amount;
                break;
            case 'decrease':
                $this->stockEntry->product_quantity -= $amount;
                $this->stockEntry->vat_quantity -= $amount;
                break;
        }
        if (isset($self_price)) {
            $this->stockEntry->self_price = $self_price;
        }
        return $this->stockEntrySave();
    }

    ///////////////////////////////////////////////////////////
    //PUBLIC FUNCTIONS |
    ///////////////////////////////////////////////////////////
    public function increaseStock($product_code, $amount, $self_price = NULL, $description) {
        return $this->stockEntryQtyAlter('increase', $product_code, $amount, $description, $self_price = NULL);
    }

    public function decreaseStock($product_code, $amount, $self_price = NULL, $description) {
        return $this->stockEntryQtyAlter('decrease', $product_code, $amount, $description, $self_price = NULL);
    }

    public function stockEntryWrnUpdate($product_code, $new_wrn) {
        $this->stockEntryLoad($product_code);
        $this->stockEntry->product_wrn_quantity = $new_wrn;
        return $this->stockEntrySave();
    }

    public function stockEntryParentUpdate($product_code, $new_parent_label) {
        $new_parent_id=$this->Base->get_row("SELECT branch_id FROM stock_tree WHERE label='$new_parent_label' LIMIT 1",0);
        if( $new_parent_id ){
            $this->stockEntryLoad($product_code);
            $this->stockEntry->parent_id = $new_parent_id;
            return $this->stockEntrySave();
        }
        else{
            $this->Base->response_wrn("Категория с названием '$new_parent_label' отсутствует!");
        }
    }

    public function stockEntryPartyUpdate($product_code, $new_party_label) {
        $this->stockEntryLoad($product_code);
        $this->stockEntry->party_label = $new_party_label;
        return $this->stockEntrySave();
    }

    public function stockEntryInsert($product_code, $parent_id) {
        $this->Base->query("INSERT INTO " . BAY_DB_MAIN . ".stock_entries SET product_code='$product_code',party_label=NULL, parent_id='$parent_id'",false);
        if (mysql_errno() == 1062){
            $this->Base->response_wrn("Строка с артикулом '$product_code' уже есть");
        }
        if (mysql_errno() == 1452){
            $this->Base->response_wrn("Артикул '$product_code' отсутствует в Справочнике Товаров");
        }
    }

    public function stockEntryDelete($delIds) {//[{product_code:''}]
        $this->Base->set_level(2);
        $where = array();
        foreach ($delIds as $rowkey) {
            $where[] = "product_code='" . $rowkey['product_code'] . "'";
        }
        $where = count($where) ? implode(' OR ', $where) : '';
        $this->Base->query("DELETE FROM " . BAY_DB_MAIN . ".stock_entries WHERE product_quantity=0 AND ($where)");
        return !!mysql_affected_rows();
    }

    public function increaseFetchCount($product_code) {
        $sql = "UPDATE 
	    " . BAY_DB_MAIN . ".stock_entries
	SET 
	    fetch_count=fetch_count+1,
	    fetch_stamp=NOW()
	WHERE 
	    product_code='$product_code'";
        $this->Base->query($sql);
    }

    public function getEntrySelfPrice($product_code) {//////////////// REVISE ME!!!!!!!!!!!!!!!!
        $this->stockEntryLoad($product_code);
        return $this->stockEntry->self_price;
    }

    public function setEntrySelfPrice($product_code, $price_self) {
        $this->stockEntryLoad($product_code);
        $this->stockEntry->self_price = $price_self;
        return $this->stockEntrySave();
    }
    
    public function getEntryPartyLabel($product_code){
        $this->stockEntryLoad($product_code);
        return $this->stockEntry->party_label;
    }

    ///////////////////////////////////////////////////////////
    ///////////////////////////////STOCK TABLE'S DATA///////////////////////////////////
    public function fetchStockEntries($table_query, $parent_id) {
        if ($parent_id === 0) {
            $sub_parents_where = "";
        } else {
            $sub_parents_ids = $this->getSubBranchIds('stock_tree', $parent_id);
            $sub_parents_where = "parent_id='" . implode("' OR parent_id='", $sub_parents_ids) . "'";
        }
        return $this->getGridData('stock_entry_view', $table_query, '*', $sub_parents_where, 'ORDER BY product_code');
    }
    private function find_sub_branches($branch_id) {
        $res = $this->Base->query("SELECT branch_id FROM stock_tree WHERE parent_id='$branch_id'");
        while ($row = mysql_fetch_row($res)) {
            $this->find_sub_branches($row[0]);
        }
        mysql_free_result($res);
        $this->branches[] = $branch_id;
    }

    public function getIncomeOrder($parent_id = 0) {
        $cases = array();
        if ($parent_id != 0) {
            $this->find_sub_branches($parent_id);
            $cases[] = ' ( parent_id=' . implode(" OR parent_id=", $this->branches) . ' ) ';
        }
        $cases[] = "product_wrn_quantity>product_quantity";
        $where = count($cases) ? implode(' AND ', $cases) : '';
        $order = array();
        return $this->Base->get_list("SELECT pl.product_code,CEIL((product_wrn_quantity-product_quantity)/product_bpack)*product_bpack as count FROM stock_entries se JOIN prod_list pl USING(product_code) WHERE $where");
    }

    public function adjustMin($parent_id, $ratio) {
        if ($ratio < 0.5){
            $this->Base->response_wrn("Коэффициэнт не может быть меньше 0,5");
	}
        $sub_parents_ids = $this->getSubBranchIds('stock_tree', $parent_id);
        $sub_parents_where = "parent_id='" . implode("' OR parent_id='", $sub_parents_ids) . "'";
        $this->Base->query("
		UPDATE stock_entries se
				JOIN
			(SELECT 
				product_code, ROUND(SUM(mc3)*$ratio/10)*10 calc_wrn
			FROM
				(SELECT 
					parent_id,product_code, mc3
				FROM
					stock_entry_view) u
			WHERE $sub_parents_where
			GROUP BY product_code) wholeStockUnion

			USING (product_code) 
		SET 
			product_wrn_quantity = IF(calc_wrn<1,1,calc_wrn)
		WHERE
			product_wrn_quantity <> 0
			AND ($sub_parents_where)
	");
    }

}

?>