<?php

require_once 'Catalog.php';

class Stock extends Catalog {

    public $branchFetch = ['id' => ['int', 0],'depth'=>['string','top']];

    public function branchFetch($parent_id = 0, $depth) {
	return $this->treeFetch("stock_tree", $parent_id, $depth);
    }

    public $stockTreeCreate = ['parent_id' => 'int', 'label' => 'string'];

    public function stockTreeCreate($parent_id, $label) {
	$this->Hub->set_level(2);
	return $this->treeCreate('stock_tree', 'folder', $parent_id, $label, 'calc_top_id');
    }

    public $stockTreeUpdate = ['branch_id' => 'int', 'field' => 'string', 'value' => 'string'];

    public function stockTreeUpdate($branch_id, $field, $value) {
	$this->Hub->set_level(2);
	return $this->treeUpdate('stock_tree', $branch_id, $field, $value, 'calc_top_id');
    }

    public $stockTreeDelete = ['int'];

    public function stockTreeDelete($branch_id) {
	$this->Hub->set_level(4);
	$sub_ids = $this->treeGetSub('stock_tree', $branch_id);
	$in = implode(',', $sub_ids);
	$this->query("DELETE FROM stock_tree WHERE branch_id IN ($in)");
	$deleted = $this->db->affected_rows();
	return $deleted;
    }

    private function makeStockFilter($filter) {
	if (!$filter) {
	    return ['inner' => 1, 'outer' => 1];
	}
	$havingInner = [1];
	$havingOuter = [1];
	foreach ($filter as $field => $value) {
	    if ($field == 'm1' || $field == 'm3') {
		$havingOuter[] = "$field LIKE '%$value%'";
	    } else {
		$havingInner[] = "$field LIKE '%$value%'";
	    }
	}
	return ['inner' => implode(' AND ', $havingInner), 'outer' => implode(' AND ', $havingOuter)];
    }

    private function columnsGet($mode) {
	$lvl1 = "product_id, parent_id,parent_label,t.product_code,ru,t.product_quantity,product_unit";
	$lvl2 = ",product_id, product_wrn_quantity,SUM(IF(TO_DAYS(NOW()) - TO_DAYS(dl.cstamp) <= 30,de.product_quantity,0)) m1,ROUND( SUM(IF(TO_DAYS(NOW()) - TO_DAYS(dl.cstamp) <= 92,de.product_quantity,0))/3 ) m3";
	$adv = ",product_id, t.self_price,sell,buy,curr_code,product_img,product_spack,product_bpack,product_weight,product_volume,analyse_origin,analyse_origin,product_barcode,analyse_type,analyse_brand,analyse_class,product_article";
	if ($this->Hub->svar('user_level') < 2) {
	    return $lvl1;
	}
	return $lvl1 . ($mode == "advanced" ? $lvl2 . $adv : $lvl2);
    }

    public $listFetch = ['parent_id' => 'int', 'offset' => ['int', 0], 'limit' => ['int', 0], 'sortby' => 'string', 'sortdir' => '(ASC|DESC)', 'filter' => 'json', 'mode' => 'string'];

    public function listFetch($parent_id, $offset, $limit, $sortby, $sortdir, $filter = null, $mode = "simple") {
	if (empty($sortby)) {
	    $sortby = "se.parent_id,se.product_code";
	    $sortdir = "ASC";
	}
	$having = $this->makeStockFilter($filter);
	$columns = $this->columnsGet($mode);
	$where = '';
	if ($parent_id) {
	    $branch_ids = $this->treeGetSub('stock_tree', $parent_id);
	    $where = "WHERE se.parent_id IN (" . implode(',', $branch_ids) . ")";
	}
	$sql = "SELECT
		$columns
	    FROM
		    (SELECT 
			st.label parent_label,
			pl.*,
			ROUND(pp.sell,5) sell,
			ROUND(pp.buy,5) buy,
			pp.curr_code,
			se.stock_entry_id,
			se.parent_id,
			se.party_label,
			se.product_quantity,
			se.product_wrn_quantity,
			se.product_img,
			se.self_price		
		    FROM
			stock_entries se
			    JOIN
			prod_list pl ON pl.product_code=se.product_code
			    LEFT JOIN
			price_list pp ON pp.product_code=se.product_code AND pp.label=''
			    LEFT JOIN
			stock_tree st ON se.parent_id=branch_id
			$where
			HAVING {$having['inner']}
			ORDER BY $sortby $sortdir
			LIMIT $limit OFFSET $offset) t		
 		    LEFT JOIN
		document_entries de ON de.product_code=t.product_code
		    LEFT JOIN
		document_list dl ON de.doc_id=dl.doc_id AND dl.is_commited=1 AND dl.doc_type=1 AND notcount=0
	    GROUP BY t.product_code
	    HAVING {$having['outer']}
	    ";
	return $this->get_list($sql);
    }

    public $labelFetch = ['q' => 'string'];

    public function labelFetch($q = 0) {
	return $this->get_list("SELECT branch_id,label FROM stock_tree WHERE label LIKE '%$q%'");
    }

    public $productGet = ['product_code' => 'string'];

    public function productGet($product_code) {
	$sql = "SELECT
		    st.label parent_label,
		    pl.*,
		    ROUND(product_volume,5) product_volume,
		    ROUND(product_weight,5) product_weight,
		    ROUND(pp.sell,5) sell,
		    ROUND(pp.buy,5) buy,
		    pp.curr_code,
		    se.stock_entry_id,
		    se.parent_id,
		    se.party_label,
		    se.product_quantity,
		    se.product_wrn_quantity,
		    se.product_img,
		    se.self_price
		FROM
		    stock_entries se
			JOIN
		    prod_list pl ON pl.product_code=se.product_code
			LEFT JOIN
		    price_list pp ON pp.product_code=se.product_code AND pp.label=''
			LEFT JOIN
		    stock_tree st ON se.parent_id=branch_id
		WHERE 
		    se.product_code='{$product_code}'";
	$product_data = $this->get_row($sql);
	return $product_data;
    }

    public $productGetLabeledPrices = ['product_code' => 'string'];

    public function productGetLabeledPrices($product_code) {
	$sql_price = "
	    SELECT 
		ROUND(sell,2) sell,
		ROUND(buy,2) buy,
		curr_code,
		label 
	    FROM 
		price_list 
	    WHERE 
		product_code='{$product_code}' AND label<>''";
	return $this->get_list($sql_price);
    }

    public $productLabeledPriceRemove = ['product_code' => 'string', 'label' => 'string'];

    public function productLabeledPriceRemove($product_code, $label) {
	return $this->delete("price_list", ['product_code' => $product_code, 'label' => $label]);
    }

    public $productLabeledPriceAdd = ['product_code' => 'string', 'label' => 'string'];

    public function productLabeledPriceAdd($product_code, $label) {
	return $this->create("price_list", ['product_code' => $product_code, 'label' => $label]);
    }

    public $productUpdate = ['product_code' => 'string', 'field' => 'string', 'value' => 'string'];

    public function productUpdate($product_code, $field, $value) {
	$this->Hub->set_level(2);
	$this->query("UPDATE stock_entries JOIN prod_list USING(product_code) JOIN price_list USING(product_code) SET $field='$value' WHERE product_code='$product_code'");
	return $this->db->affected_rows();
    }

    public $productSave = [];
    public function productSave() {
	$this->Hub->set_level(2);
	$affected_rows = 0;
	$product_code = $this->request('product_code');
	$product_code_new = $this->request('product_code_new', '^[\(\)\[\]\<\>\p{L}\d\. ,-_]+$');
	if (!$product_code_new) {// do not match the pattern
	    return false;
	}
	if ($product_code && $product_code <> $product_code_new) {
	    $this->update('prod_list', ['product_code' => $product_code_new], ['product_code' => $product_code]);
	    $affected_rows+=$this->db->affected_rows() * 1;
	}
	$product_code = $product_code_new;
	$prod_list = [
	    'product_code' => $product_code,
	    'ru' => $this->request('ru'),
	    'ua' => $this->request('ua'),
	    'en' => $this->request('en'),
	    'product_unit' => $this->request('product_unit'),
	    'product_spack' => $this->request('product_spack', 'int'),
	    'product_bpack' => $this->request('product_bpack', 'int'),
	    'product_weight' => $this->request('product_weight', 'double'),
	    'product_volume' => $this->request('product_volume', 'double'),
	    'analyse_origin' => $this->request('analyse_origin'),
	    'analyse_type' => $this->request('analyse_type'),
	    'analyse_brand' => $this->request('analyse_brand'),
	    'analyse_class' => $this->request('analyse_class'),
	    'product_article' => $this->request('product_article'),
	    'product_barcode' => $this->request('product_barcode'),
	    'is_service' => $this->request('is_service', 'bool')
	];
	$prod_list_set = $this->makeSet($prod_list);
	$this->query("INSERT INTO prod_list SET $prod_list_set ON DUPLICATE KEY UPDATE $prod_list_set");
	$affected_rows+=$this->db->affected_rows() * 1;

	$stock_entries = [
	    'product_code' => $product_code,
	    'parent_id' => $this->request('parent_id', 'int'),
	    'product_wrn_quantity' => $this->request('product_wrn_quantity', 'int'),
	    'product_img' => $this->request('product_img'),
	    'party_label' => $this->request('party_label')
	];
	$stock_entries_set = $this->makeSet($stock_entries);
	$this->query("INSERT INTO stock_entries SET $stock_entries_set ON DUPLICATE KEY UPDATE $stock_entries_set");
	$affected_rows+=$this->db->affected_rows() * 1;

	$price_list = [
	    'product_code' => $product_code,
	    'buy' => $this->request('buy', 'double'),
	    'sell' => $this->request('sell', 'double'),
	    'curr_code' => $this->request('curr_code'),
	];
	$price_list_set = $this->makeSet($price_list);
	$this->query("INSERT INTO price_list SET $price_list_set ON DUPLICATE KEY UPDATE $price_list_set");
	$affected_rows+=$this->db->affected_rows() * 1;

	$labeled_prices = $this->request('labeled_prices', 'json');
	foreach ($labeled_prices as $price_list_entry) {
	    $price_list_entry['product_code'] = $product_code;
	    $price_list_set = $this->makeSet($price_list_entry);
	    $this->query("INSERT INTO price_list SET $price_list_set ON DUPLICATE KEY UPDATE $price_list_set");
	    $affected_rows+=$this->db->affected_rows() * 1;
	}
	return $affected_rows;
    }

    private function makeSet($array) {
	$set = [];
	foreach ($array as $key => $val) {
	    $set[] = "$key='$val'";
	}
	return implode(',', $set);
    }

    public $import = ['label' => 'string', 'source' => 'raw', 'target' => 'raw', 'parent_id' => 'int'];

    public function import($label, $source, $target, $parent_id) {
	$source = array_map('addslashes', $source);
	$target = array_map('addslashes', $target);
	if ($parent_id) {
	    $source[] = $parent_id;
	    $target[] = 'parent_id';
	}
	$this->importInTable('prod_list', $source, $target, '/product_code/ru/ua/en/product_spack/product_bpack/product_weight/product_volume/product_unit/analyse_origin/product_barcode/analyse_type/analyse_brand/analyse_class/product_article/', $label);
	$this->importInTable('price_list', $source, $target, '/product_code/sell/buy/curr_code/label/', $label);
	$this->importInTable('stock_entries', $source, $target, '/product_code/party_label/parent_id/', $label);
	$this->query("DELETE FROM imported_data WHERE label LIKE '%$label%' AND {$source[0]} IN (SELECT product_code FROM stock_entries)");
	return $this->db->affected_rows();
    }

    private function importInTable($table, $src, $trg, $filter, $label) {
	$set = [];
	$target = [];
	$source = [];
	for ($i = 0; $i < count($trg); $i++) {
	    if (strpos($filter, "/{$trg[$i]}/") !== false && !empty($src[$i])) {
		$target[] = $trg[$i];
		$source[] = $src[$i];
		if ($trg[$i] != 'parent_id') {/* set parent_id only for new added rows */
		    $set[] = "{$trg[$i]}=$src[$i]";
		}
	    }
	}
	$target_list = implode(',', $target);
	$source_list = implode(',', $source);
	$set_list = implode(',', $set);
	$this->query("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label LIKE '%$label%' ON DUPLICATE KEY UPDATE $set_list");
	//print("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label='$label' ON DUPLICATE KEY UPDATE $set_list");
	return $this->db->affected_rows();
    }

    public $productDelete = ['product_code' => 'string'];

    public function productDelete($product_codes) {
	$this->Hub->set_level(2);
	$product_codes= str_replace(",", "','", $product_codes);
	$this->query("DELETE FROM stock_entries WHERE product_quantity=0 AND product_code IN ('$product_codes')");
	return $this->db->affected_rows();
    }
    public $productMove=['parent_id'=>'int','product_code'=>'string'];
    public function productMove($parent_id,$product_codes){
        $this->Hub->set_level(2);
        $product_codes= str_replace(",", "','", $product_codes);
        $this->query("UPDATE stock_entries SET parent_id='$parent_id' WHERE product_code IN ('$product_codes')");
        return $this->db->affected_rows();
    }

    public $movementsFetch = ['int', 'int', 'string'];

    public function movementsFetch($page = 1, $rows = 30, $having = null) {
	$this->Hub->set_level(2);
	$offset = ($page - 1) * $rows;
	if ($offset < 0) {
	    $offset = 0;
	}
	if (!$having) {
	    $having = $this->decodeFilterRules();
	}
	$sql = "SELECT
		doc_id,
                DATE_FORMAT(dl.cstamp,'%d.%m.%Y') oper_date,
                CONCAT(dt.doc_type_name,IF(dl.is_reclamation,' (Возврат)',''),' #',dl.doc_num) doc,
		(SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=passive_company_id) plabel,
		(SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=active_company_id) alabel,
                product_code,
                ru,
                IF(doc_type=1,product_quantity,'') sell,
                IF(doc_type=2,product_quantity,'') buy,
		ROUND(self_price,2) self
            FROM
                document_entries de
                    JOIN
                document_list dl USING(doc_id)
                    JOIN
                document_types dt USING(doc_type)
                    JOIN
                prod_list USING(product_code)
            WHERE
                is_commited AND NOT notcount
            HAVING $having
            ORDER BY dl.cstamp DESC
            LIMIT $rows OFFSET $offset";
	$result_rows = $this->get_list($sql);
	$this->distinctMovementsRows($result_rows);
	$total_estimate = $offset + (count($result_rows) == $rows ? $rows + 1 : count($result_rows));
	return array('rows' => $result_rows, 'total' => $total_estimate);
    }

    private function distinctMovementsRows(&$result_rows) {
	$prev_concat = '';
	foreach ($result_rows as $row) {
	    $concat = $row->alabel . $row->oper_date . $row->doc . $row->plabel;
	    if ($prev_concat == $concat) {
		$row->oper_date = '-';
		$row->doc = '-';
		$row->doc_id = '';
		$row->alabel = '-';
		$row->plabel = '-';
	    }
	    $prev_concat = $concat;
	}
    }

    public $getPriceLabels = [];

    public function getPriceLabels() {
	$sql = "SELECT DISTINCT label FROM price_list";
	return $this->get_list($sql);
    }

    public $calcABC=['parent_id'=>['int',0],'period'=>'int', 'all_active'=>'bool'];
    public function calcABC($parent_id,$period,$all_active){
	$where="";
	if ($parent_id) {
	    $branch_ids = $this->treeGetSub('stock_tree', $parent_id);
	    $where .= " se.parent_id IN (" . implode(',', $branch_ids) . ")";
	}
	$where_active="";
	if( !$all_active ){
	    $acomp_id=$this->Hub->acomp('company_id');
	    $where_active= " AND active_company_id='$acomp_id'";
	}
	$sql_prepare1="DROP TEMPORARY TABLE IF EXISTS tmp_abc_chart;";
	$sql_prepare2="SET @sold_total:=0;";
	$sql_create="CREATE TEMPORARY TABLE tmp_abc_chart AS (
	    SELECT 
		product_code, sold_sum, @sold_total:=@sold_total+sold_sum sold_total
	    FROM
		(SELECT
		    product_code,
		    COALESCE((SELECT 
			SUM(de.product_quantity*invoice_price)
		    FROM 
			document_entries de
			    JOIN
			document_list dl USING(doc_id)
		    WHERE
			de.product_code=se.product_code
			AND is_commited=1 
			AND notcount=0
			AND DATEDIFF(NOW(),dl.cstamp)<$period
			$where_active
		    ),0) sold_sum
		FROM
		    stock_entries se
		WHERE
		    $where
		ORDER BY sold_sum) t
	    );";
	$sql_calc="SET @A:=@sold_total*0.8,@B:=@sold_total*0.15,@C:=@sold_total*0.05;";
	$sql_update="UPDATE prod_list JOIN tmp_abc_chart USING(product_code) SET analyse_class=IF(sold_total<@C,'C',IF(sold_total<@B,'B','A'));";
	$this->query($sql_prepare1);
	$this->query($sql_prepare2);
	$this->query($sql_create);
	$this->query($sql_calc);
	$this->query($sql_update);
	//echo $sql_prepare1.$sql_prepare2.$sql_create.$sql_calc.$sql_update;
	return $this->db->affected_rows();
    }
    // I AM A PRINCESS
    
    public $checkoutListFetch = ['date'=>'([0-9\-]+)', 'offset'=>'int'];
    public function checkoutListFetch($date, $offset){
        $where = '';
        if ($date){
            $where = "WHERE cstamp LIKE '$date%'";
        }
        $sql = "        
            SELECT
                checkout_list.*,
                DATE_FORMAT(cstamp, '%d.%m.%Y %H:%i') cstamp_dmy, 
                (SELECT user_sign FROM user_list WHERE user_id = created_by) AS creator_nick,
                (SELECT user_sign FROM user_list WHERE user_id = modified_by) AS modifier_nick
            FROM 
                checkout_list
            $where
            ORDER BY cstamp DESC 
            LIMIT 5 OFFSET $offset";
        return $this->get_list($sql);
    }
    
    public $checkoutDocumentGet = ['checkout_id' => 'int'];
    public function checkoutDocumentGet ($checkout_id){
        $sql = "        
            SELECT
                checkout_list.*,
                DATE_FORMAT(cstamp, '%d.%m.%Y %H:%i') cstamp_dmy, 
                (SELECT user_sign FROM user_list WHERE user_id = created_by) AS creator_nick,
                (SELECT user_sign FROM user_list WHERE user_id = modified_by) AS modifier_nick
            FROM 
                checkout_list
            WHERE checkout_id='$checkout_id'";
        $head= $this->get_row($sql);
        if( $head -> parent_doc_id ){
            $this->checkoutDocumentRefresh($checkout_id, $head->parent_doc_id);
        }    
        return
            ['head'=>$head,
            'entries' => $this->checkoutEntriesFetch($checkout_id),
            'log'=>$this->checkoutLogFetch($checkout_id)    
            ];
    }
    private function checkoutDocumentRefresh ($checkout_id,$parent_doc_id){
        $sql_reset="
            UPDATE 
                checkout_entries
            SET
                product_quantity = 0
            WHERE
                checkout_id = '$checkout_id'
        ";
        $sql_update = "
            INSERT
                checkout_entries (product_id, product_quantity, checkout_id, product_quantity_verified, verification_status)
            SELECT
                product_id, product_quantity, $checkout_id, 0, 0
            FROM
                document_entries
                    JOIN
                prod_list USING(product_code)
            WHERE 
                doc_id = '$parent_doc_id'
            ON DUPLICATE KEY UPDATE checkout_entries.product_quantity=document_entries.product_quantity    
            ";
        $this->query($sql_reset);
        $this->query($sql_update);
    }

    public $checkoutEntriesFetch = ['checkout_id' => 'int'];
    public function checkoutEntriesFetch ($checkout_id){
        $sql = "
            SELECT 
                checkout_entries.*,
                ru,product_spack, product_bpack, product_code, product_unit, product_barcode,product_img
            FROM 
                checkout_entries
                    JOIN
                prod_list USING(product_id)
                    JOIN
                stock_entries USING(product_code)    
            WHERE
                checkout_id = '$checkout_id'";
        return $this->get_list($sql);
    }
    
    public $checkoutProductGet = ['barcode' => 'string']; 
    public function checkoutProductGet($barcode) {
	$sql = "SELECT
		    product_id, product_code,ru, product_barcode,
                    product_bpack, product_spack, product_unit
		FROM
		    prod_list 
		WHERE 
		    product_barcode= '$barcode'";
	$product_data = $this->get_row($sql);
	return $product_data;
    }
    
    public $checkoutLogCommit = ['checkout_id'=>'int', 'entries'=>'json'];
    public function checkoutLogCommit ($checkout_id, $entries = null) {
        $this->query("START TRANSACTION");
        foreach($entries as $entry){
            $sql = "
                INSERT
                    checkout_entries
                SET
                    product_quantity_verified = {$entry['operation_quantity']},
                    checkout_id = '$checkout_id',
                    product_id = {$entry['product_id']},
                    verification_status = {$entry['verification_status']}    
                ON DUPLICATE KEY UPDATE
                    verification_status = {$entry['verification_status']},
                    product_quantity_verified = product_quantity_verified + {$entry['operation_quantity']}
                ";
            $this->query($sql);
            $sql = "
                INSERT
                    checkout_log
                SET
                    operation_quantity = {$entry['operation_quantity']},
                    checkout_id = '$checkout_id',
                    cstamp = '{$entry['cstamp']}',
                    product_id = {$entry['product_id']}";
            $this->query($sql);
        }
        $this->checkoutUpdateDocStatus($checkout_id, 'is_checking');
        $this->query("COMMIT");
        return true;
    }
    
    public $checkoutUpdateDocStatus = ['checkout_id' => 'int', 'doc_status' => 'int'];
    public function checkoutUpdateDocStatus ($checkout_id, $doc_status){
        $sql = " 
            UPDATE
                checkout_list
            SET
                checkout_status = '$doc_status'
             WHERE
                checkout_id = '$checkout_id'";
        $this->query($sql);
        return true;
    }
    public $checkoutLogFetch = ['checkout_id' => 'int'];
    public function checkoutLogFetch ($checkout_id) {
        $sql = " 
            SELECT
                checkout_log.*,
                ru, product_code, product_unit
            FROM
                checkout_log
                    JOIN
                prod_list USING(product_id)
             WHERE
                checkout_id = '$checkout_id'";
        return $this->get_list($sql);        
    }
    
    public $checkoutStockCreate = ['parent_id' => 'int', 'checkout_name'=>'string'];
    public function checkoutStockCreate ($parent_id, $checkout_name){
        $stock_entries_list = $this->listFetch($parent_id, 0, 10000, 'product_code', 'ASC', null, 'advanced');
        $user_id = $this->Hub->svar('user_id');
        $cstamp = date('Y-m-d H:i:s');
        $checkout_id=$this->create('checkout_list', ['checkout_name'=>$checkout_name, 'parent_doc_id'=>null, 'created_by'=>$user_id, 'modified_by'=>$user_id, 'cstamp'=> $cstamp]);
        foreach ($stock_entries_list as $entry){
            $this->create('checkout_entries', ['checkout_id'=>$checkout_id, 
                                                'product_id'=>$entry->product_id, 
                                                'product_quantity'=>$entry->product_quantity,
                                                'product_quantity_verified'=>0,
                                                'verification_status'=>0]);
        }
        return $checkout_id;
    }
    public $checkoutDocumentCreate = ['parent_doc_id' => 'int', 'checkout_name'=>'string'];
    public function checkoutDocumentCreate ($parent_doc_id, $checkout_name){
        $DocumentItems = $this->Hub->load_model("DocumentItems");
        $document_entries_list = $DocumentItems->entryDocumentGet($parent_doc_id);
        $user_id = $this->Hub->svar('user_id');
        $cstamp = date('Y-m-d H:i:s');
        $checkout_id=$this->create('checkout_list', ['checkout_name'=>$checkout_name, 'parent_doc_id'=>$parent_doc_id, 'created_by'=>$user_id, 'modified_by'=>$user_id, 'cstamp'=> $cstamp]);
        foreach ($document_entries_list['entries'] as $entry){
            $product_id = $this->get_value("SELECT product_id FROM prod_list WHERE product_code = '$entry->product_code'");
            $this->create('checkout_entries', ['checkout_id'=>$checkout_id, 
                                                'product_id'=>$product_id, 
                                                'product_quantity'=>$entry->product_quantity,
                                                'product_quantity_verified'=>0,
                                                'verification_status'=>0]);
        }
        return $checkout_id;
    }
    public $checkoutDocumentOutput = ['checkout_id'=>'int'];
    public function checkoutDocumentOutput ($checkout_id){
        $parent_doc_id = $this->get_value("SELECT parent_doc_id FROM checkout_list WHERE checkout_id=$checkout_id");
        if ($parent_doc_id){
            $this->checkoutUpdateDocStatus($checkout_id, 'checked');
            return $this->checkoutSourceDocUpdate($checkout_id);
        }else{
            return $this->checkoutCalcDifference($checkout_id);
        }
    }
    
    private function checkoutSourceDocUpdate ($checkout_id){
        $DocumentItems=$this->Hub->load_model('DocumentItems');
        $checkout_document = $this->checkoutDocumentGet($checkout_id);
        $source_doc_id = $checkout_document['head']->parent_doc_id;
        $document = $DocumentItems->entryDocumentGet($source_doc_id);
        foreach ($checkout_document['entries'] as $entry_check){
            foreach ($document['entries'] as $entry_doc ){
                $doc_product_id = $this->get_value("SELECT product_id FROM prod_list WHERE product_code = '$entry_doc->product_code'");
                if( $entry_check->product_id == $doc_product_id ){
                    if ( $entry_check->product_quantity_verified == 0 ){
                        $DocumentItems->entryDeleteArray($source_doc_id, [[$entry_doc->doc_entry_id]]);
                    } else {
                        $DocumentItems->entryUpdate($source_doc_id, $entry_doc->doc_entry_id, 'product_quantity', $entry_check->product_quantity_verified );
                    }
                    continue;
                } else if ($entry_check->product_id == $doc_product_id ) {
                    $check_product_code = $this->get_value("SELECT product_code FROM prod_list WHERE product_id = '$entry_check->product_id'");
                    print_r($check_product_code);
                    $DocumentItems->entryAdd($check_product_code, $entry_check->product_quantity_verified );
                    continue;
                }
                
            }
        }
        return; 
        
    }
    
    private function checkoutCalcDifference($checkout_id){
        $sql_more = "
            SELECT
                product_code,
                product_quantity_verified - product_quantity AS difference
            FROM
                checkout_entries
                JOIN
                prod_list USING(product_id)
            WHERE
                checkout_id = $checkout_id
                AND product_quantity_verified>product_quantity
            ";
        $entries_list_more = $this->get_list($sql_more);
        $DocumentItems=$this->Hub->load_model('DocumentItems');
        if (count($entries_list_more)>0){
            $this->checkoutUpdateDocStatus($checkout_id, 'checked_with_divergence');
            $more_doc_id = $DocumentItems->createDocument(2);
            foreach($entries_list_more as $item){
                $DocumentItems->entryAdd($item->product_code, $item->difference);
            }
            $DocumentItems->entryDocumentCommit($more_doc_id);
        }
        $sql_less = "
            SELECT
                product_code,
                product_quantity - product_quantity_verified AS difference
            FROM
                checkout_entries
                JOIN
                prod_list USING(product_id)
            WHERE
                checkout_id = $checkout_id
                AND product_quantity_verified<product_quantity
            ";
        $entries_list_less = $this->get_list($sql_less);
        $DocumentItems=$this->Hub->load_model('DocumentItems');
        if (count($entries_list_less)>0){
            $this->checkoutUpdateDocStatus($checkout_id, 'checked_with_divergence');
            $less_doc_id = $DocumentItems->createDocument(1);
            foreach($entries_list_less as $item){
                $DocumentItems->entryAdd($item->product_code, $item->difference);
            }
            $DocumentItems->entryDocumentCommit($less_doc_id);
        }
        return [$more_doc_id, $less_doc_id];
    }
    public $checkoutUp=['checkout_id'=>'int', 'file_name'=>'string'];
    public function checkoutUp( $checkout_id, $file_name){
        $Storage = $this->Hub->load_model('Storage');
        $Storage->upload('checkout', $file_name);
	$sql="
            UPDATE
                checkout_list
            SET
                checkout_photos = CONCAT(checkout_photos, ',' , '$file_name')
            WHERE
                checkout_id = '$checkout_id'
            ";
        $this->query($sql);
        return 'uploaded';
    }
    
//    public $checkoutPhotosDown=['checkout_id'=>'int'];
//    public function checkoutPhotosDown( $checkout_id ){
//	$sql="
//            SELECT
//                checkout_photos
//            FROM
//                checkout_list
//            WHERE
//                checkout_id = '$checkout_id'";
//        $simple_array = explode(',', $this->get_value($sql));
//        $final_array = [];
//        foreach ($simple_array as $entry){
//            $miliseconds = substr($entry, 0, -4);
//            array_push($final_array, array('photo'=>$entry, 'cstamp'=>$miliseconds));
//        }
//        return $final_array;
//    }
}


