<?php
class Stock extends Catalog {
    /**
     * Changes quantity on particular depot stock
     * @param int $product_id
     * @param float $delta_quantity
     * @param int $stock_id
     * @return boolean
     */
    public function productQuantityModify( string $product_code, float $product_delta_quantity, int $stock_id=1  ){
        if( $product_delta_quantity==0 ){
            return true;
        }
        $modify_qty_sql="
            UPDATE
                stock_entries
            SET
                product_quantity=product_quantity+$product_delta_quantity
            WHERE
                product_code='$product_code'
                AND stock_id=$stock_id
                AND product_quantity+$product_delta_quantity>=0";
        $this->query($modify_qty_sql);
        return $this->db->affected_rows();
    }

    public function productGet( string $product_code=null, string $product_barcode=null ) {
        if( $product_code!=null ){
            $where="pl.product_code='{$product_code}'";
        }
        if( $product_barcode!=null ){
            $where="pl.product_barcode='{$product_barcode}'";
        }
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
		    prod_list pl
                    	LEFT JOIN
		    stock_entries se ON pl.product_code=se.product_code
			LEFT JOIN
		    price_list pp ON pp.product_code=se.product_code AND pp.label=''
			LEFT JOIN
		    stock_tree st ON se.parent_id=branch_id
		WHERE 
		    $where";
        $product_data = $this->get_row($sql);
        return $product_data;
    }
    
    public function productCheck( string $product_code=null ){
        return $this->get_value("SELECT 1 FROM prod_list WHERE product_code='$product_code'");
    }
    

    public function productUpdate( string $product_code, string $field, string $value ) {
        $this->Hub->set_level(2);
        $this->query("UPDATE stock_entries JOIN prod_list USING(product_code) JOIN price_list USING(product_code) SET $field='$value' WHERE product_code='$product_code'");
        return $this->db->affected_rows();
    }    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    public $branchFetch = ['id' => ['int', 0], 'depth' => ['string', 'top']];

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

    public $makeStockFilter = ['filter' => 'json'];

    public function makeStockFilter($filter) {
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
        $lvl1 = "product_id, parent_id,parent_label,t.product_code,ru,t.product_quantity,product_unit,product_reserved,product_awaiting";
        $lvl2 = ",product_id, product_wrn_quantity,SUM(IF(TO_DAYS(NOW()) - TO_DAYS(dl.cstamp) <= 30,de.product_quantity,0)) m1,ROUND( SUM(IF(TO_DAYS(NOW()) - TO_DAYS(dl.cstamp) <= 92,de.product_quantity,0))/3 ) m3";
        $adv = ",product_id, t.self_price,sell,buy,curr_code,product_img,product_spack,product_bpack,product_weight,product_volume,t.party_label,analyse_origin,product_barcode,analyse_type,analyse_brand,analyse_class,product_article";
        if ($this->Hub->svar('user_level') < 2) {
            return $lvl1;
        }
        return $lvl1 . ($mode == "advanced" ? $lvl2 . $adv : $lvl2);
    }


    public function listFetch( int $parent_id=0, int $offset=0, int $limit=0, string $sortby=null, string $sortdir=null, array $filter = null, string $mode = "simple") {
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
			se.self_price,
                        product_reserved,
                        product_awaiting
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

    private function stripWhite( $sentence ){
        return trim(preg_replace('/\s+/', ' ', $sentence));
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
            $affected_rows += $this->db->affected_rows() * 1;
        }
        $product_code = $product_code_new;
        $prod_list = [
            'product_code' => $this->stripWhite($product_code),
            'ru' => $this->stripWhite($this->request('ru')),
            'ua' => $this->stripWhite($this->request('ua')),
            'en' => $this->stripWhite($this->request('en')),
            'product_unit' => $this->stripWhite($this->request('product_unit')),
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
        $affected_rows += $this->db->affected_rows() * 1;

        $stock_entries = [
            'product_code' => $product_code,
            'parent_id' => $this->request('parent_id', 'int'),
            'product_wrn_quantity' => $this->request('product_wrn_quantity', 'int'),
            'product_img' => $this->request('product_img'),
            'party_label' => $this->request('party_label')
        ];
        $stock_entries_set = $this->makeSet($stock_entries);
        $this->query("INSERT INTO stock_entries SET $stock_entries_set ON DUPLICATE KEY UPDATE $stock_entries_set");
        $affected_rows += $this->db->affected_rows() * 1;

        $price_list = [
            'product_code' => $product_code,
            'buy' => $this->request('buy', 'double'),
            'sell' => $this->request('sell', 'double'),
            'curr_code' => $this->request('curr_code'),
        ];
        $price_list_set = $this->makeSet($price_list);
        $this->query("INSERT INTO price_list SET $price_list_set ON DUPLICATE KEY UPDATE $price_list_set");
        $affected_rows += $this->db->affected_rows() * 1;

        $labeled_prices = $this->request('labeled_prices', 'json');
        foreach ($labeled_prices as $price_list_entry) {
            $price_list_entry['product_code'] = $product_code;
            $price_list_set = $this->makeSet($price_list_entry);
            $this->query("INSERT INTO price_list SET $price_list_set ON DUPLICATE KEY UPDATE $price_list_set");
            $affected_rows += $this->db->affected_rows() * 1;
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

    public $import = ['label' => 'string', 'source' => 'raw', 'target' => 'raw', 'parent_id' => 'int', 'only_update'=>'bool', 'move_to_category'=>'bool'];

    public function import( $label, $source, $target, $parent_id, $only_update, $move_to_category) {
        $source = array_map('addslashes', $source);
        $target = array_map('addslashes', $target);
        if ($parent_id) {
            $source[] = $parent_id;
            $target[] = 'parent_id';
        }
        $this->importInTable('prod_list', $source, $target, $only_update, $move_to_category, '/product_code/ru/ua/en/product_spack/product_bpack/product_weight/product_volume/product_unit/analyse_origin/product_barcode/analyse_type/analyse_brand/analyse_class/product_article/', $label);
        $this->importInTable('price_list', $source, $target, $only_update, $move_to_category, '/product_code/sell/buy/curr_code/label/', $label);
        $this->importInTable('stock_entries', $source, $target, $only_update, $move_to_category, '/product_code/party_label/parent_id/product_img/', $label);
        $this->query("DELETE FROM imported_data WHERE label LIKE '%$label%' AND {$source[0]} IN (SELECT product_code FROM stock_entries)");
        return $this->db->affected_rows();
    }

    private function importInTable($table, $src, $trg, $only_update, $move_to_category, $filter, $label) {
        $target = [];
        $source = [];
        for ($i = 0; $i < count($trg); $i++) {
            if (strpos($filter, "/{$trg[$i]}/") !== false && !empty($src[$i])) {
                $target[] = $trg[$i];
                $source[] = $src[$i];
            }
        }
        $source_list = implode(',', $source);
        $import_list=$this->get_list("SELECT $source_list FROM imported_data WHERE label LIKE '%$label%'");
        foreach($import_list as $product){
            $set_parent_id="";
            $image_url="";
            $set=[];
            foreach($target as $i=>$field){
                if( $field=='parent_id' ){
                    $value=$source[$i];
                    $set_parent_id=",parent_id='$value'";
                    continue;
                } else {
                    $value=$this->stripWhite($product->{$source[$i]});
                }
                if( $field=='product_code' && !$value ){
                    continue 2;
                }
                $value= addslashes($value);
                if( $field=='product_code' ){
                    $product_code="$value";
                }
                if( $field=='product_img' ){
                    $filename=microtime(1);
                    $Storage=$this->Hub->load_model('Storage');
                    $Storage->download('dynImg',$value,$filename);
                    $value=$filename;
                }
                $set[]="$field='$value'";
            }
            $set_list = implode(',', $set);
            if( $move_to_category ){
                $set_list.=$set_parent_id;
                $set_parent_id="";
            }
            if( $only_update ){
                $this->query("UPDATE $table SET $set_list WHERE product_code='$product_code'");
            } else {
                $this->query("INSERT INTO $table SET $set_list $set_parent_id ON DUPLICATE KEY UPDATE $set_list");
            }
        }
        return $this->db->affected_rows();
    }

    public $productDelete = ['product_code' => 'string'];

    public function productDelete($product_codes) {
        $this->Hub->set_level(2);
        $product_codes = str_replace(",", "','", $product_codes);
        $this->query("DELETE FROM stock_entries WHERE product_quantity=0 AND product_code IN ('$product_codes')");
        return $this->db->affected_rows();
    }

    public $productMove = ['parent_id' => 'int', 'product_code' => 'string'];

    public function productMove($parent_id, $product_codes) {
        $this->Hub->set_level(2);
        $product_codes = str_replace(",", "','", $product_codes);
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

    public $calcABC = ['parent_id' => ['int', 0], 'period' => 'int', 'all_active' => 'bool', 'fdate' => 'string'];

    public function calcABC($parent_id, $period, $all_active, $fdate) {
        $where = "";
        if ($parent_id) {
            $branch_ids = $this->treeGetSub('stock_tree', $parent_id);
            $where = "WHERE  se.parent_id IN (" . implode(',', $branch_ids) . ")";
        }
        $where_active = "";
        if (!$all_active) {
            $acomp_id = $this->Hub->acomp('company_id');
            $where_active = " AND active_company_id='$acomp_id'";
        }
        $sql_prepare1 = "DROP TEMPORARY TABLE IF EXISTS tmp_abc_chart;"; #TEMPORARY
        $sql_prepare2 = "SET @sold_total:=0;";
        $sql_create = "CREATE TEMPORARY TABLE tmp_abc_chart AS (
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
                        AND doc_type=1
			AND is_commited=1 
			AND notcount=0
                        AND dl.cstamp<'$fdate 23:59:59'
			AND DATEDIFF('$fdate 23:59:59',dl.cstamp)<$period
			$where_active
		    ),0) sold_sum
		FROM
		    stock_entries se
		    $where
		) t
	    ORDER BY sold_sum);";
        $sql_calc = "SET @A:=@sold_total*0.8,@B:=@sold_total*0.15,@C:=@sold_total*0.05;";
        $sql_update = "UPDATE prod_list JOIN tmp_abc_chart USING(product_code) SET analyse_class=IF(sold_total<@C,'C',IF(sold_total<@B+@C,'B','A'));";
        $this->query($sql_prepare1);
        $this->query($sql_prepare2);
        $this->query($sql_create);
        $this->query($sql_calc);
        $this->query($sql_update);
        //echo $sql_prepare1.$sql_prepare2.$sql_create.$sql_calc.$sql_update;
        return $this->db->affected_rows();
    }

    
    ////////////////////////////////////////////
    // RESERVING SYSTEM
    ////////////////////////////////////////////
    public function reserveSystemStatusChange( bool $active ){
        $Events=$this->Hub->load_model("Events");
        if( $active ){
            $Events->Topic('documentAfterChangeDocStatusId')->subscribe('Stock','reserveStatusChange');
            $Events->Topic('documentEntryChanged')->subscribe('Stock','reserveEntryChange');
            $this->reserveCountUpdate();
        } else {
            $Events->Topic('documentAfterChangeDocStatusId')->unsubscribe('Stock','reserveStatusChange');
            $Events->Topic('documentEntryChanged')->unsubscribe('Stock','reserveEntryChange');
            $this->query("UPDATE stock_entries SET product_reserved = 0, product_awaiting = 0");
        }
    }
    
    public function reserveStatusChange( $old_status_id, $new_status_id, $doc ){
        if( $new_status_id==2 ){//reserved
            $this->reserveTaskAdd($doc);
            $this->reserveCountUpdate();
        }
        if( $old_status_id==2 ){
            $this->reserveTaskRemove($doc);
            $this->reserveCountUpdate();
        }
        return true;
    }

    public function reserveEntryChange( $doc_entry_id,$doc ){
        if( isset($doc->doc_status_id) && $doc->doc_status_id==2 ){
            $this->reserveCountUpdate();
        }
        return true;
    }

    public function reserveListFetch(int $offset=0, int $limit=0, string $sortby='cstamp', string $sortdir='DESC', array $filter = null) {
        $this->Hub->set_level(2);
        if (empty($sortby)) {
            $sortby = "cstamp";
            $sortdir = "DESC";
        }
        $having=$this->makeFilter($filter);
        $sql_set="SET @reserved_sum:=0,@awaiting_sum:=0,@acomp_name:='',@pcomp_name:='',@doc:='';";
        $this->query($sql_set);
        $sql = "SELECT
            doc_id,
            IF(@acomp_name<>acomp_name,@acomp_name:=acomp_name,'-') acomp_name,
            IF(@pcomp_name<>pcomp_name,@pcomp_name:=pcomp_name,'-') pcomp_name,
            IF(@doc<>doc,@doc:=doc,'-') doc,
            product_code,
            product_name,
            reserved,
            reserved_until,
            awaiting,
            awaiting_at,
            @reserved_sum:=@reserved_sum+reserved,
            @awaiting_sum:=@awaiting_sum+awaiting
        FROM (
            SELECT 
                doc_id,
                (SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=active_company_id) acomp_name,
                (SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=passive_company_id) pcomp_name,
                CONCAT(dt.doc_type_name,IF(dl.is_reclamation,' (Возврат)',''),' #',dl.doc_num) doc,
                product_code,
                ru product_name,
                IF(doc_type=1,product_quantity,'') reserved,
                IF(doc_type=1,DATE_FORMAT(DATE_ADD(dl.cstamp, INTERVAL 3 DAY),'%d.%m.%Y'),'') reserved_until,
                IF(doc_type=2,product_quantity,'') awaiting,
                IF(doc_type=2,DATE_FORMAT(dl.cstamp,'%d.%m.%Y'),'') awaiting_at
            FROM
                document_list dl
                    JOIN
                document_types dt USING(doc_type)
                    JOIN
                document_entries de USING(doc_id)
                    JOIN
                prod_list pl USING(product_code)
            WHERE
                doc_status_id = 2
            HAVING {$having}
            ORDER BY $sortby $sortdir
            LIMIT $limit OFFSET $offset) t
        ";
        $rows=$this->get_list($sql);
        $rows[]=$this->get_row("SELECT 'Σ' product_name,@reserved_sum reserved,@awaiting_sum awaiting");
        return $rows;
    }
    
    private function reserveTaskAdd($doc){
        $this->Hub->load_model('Events');
        $this->Hub->Events->eventDeleteDocumentTasks($doc->doc_id);
        $user_id=$this->Hub->svar('user_id');
        if( $doc->doc_type==1 ){
            $day_limit=$this->Hub->pref('reserved_limit');
        } else {
            $day_limit=$this->Hub->pref('awaiting_limit');
        }
        $stamp=time()+60*60*24*($day_limit?$day_limit:3);
        $alert="Документ №".$doc->doc_num." для ".$this->Hub->pcomp('company_name')." снят с резерва";
        $name="Снятие с резерва";
        $description="$name Снятие с резерва счета №".$doc->doc_num." для ".$this->Hub->pcomp('company_name');
        $event=[
            'doc_id'=>$doc->doc_id,
            'event_name'=>$name,
            'event_status'=>'undone',
            'event_label'=>'-TASK-',
            'event_date'=>date("Y-m-d H:i:s",$stamp),
            'event_descr'=>$description
        ];
        $event_id=$this->Hub->Events->eventCreate($event);
        $event_update=[
            'event_program'=>json_encode([
                'commands'=>[
                    [
                        'model'=>'Stock',
                        'method'=>'reserveTaskExecute',
                        'arguments'=>[$doc->doc_id,$user_id,$alert,$event_id]
                    ]
                ]
            ])
        ];
        if( $event_id ){
            $this->Hub->Events->eventChange($event_id, $event_update);
        }
        return $event_id;
    }
    
    public function reserveTaskExecute($doc_id,$user_id,$alert,$event_id){
        $DocumentItems=$this->Hub->load_model("DocumentItems");
        $DocumentItems->setStatusByCode($doc_id,'created');
        $new_status_id=$DocumentItems->documentStatusGet();
        if( $new_status_id!=2 ){//document is not reserved anymore
            $ok=$this->Hub->load_model("Events")->eventDelete($event_id);
            if( $ok ){
                $this->Hub->load_model("Chat")->addMessage($user_id,$alert,true);
            }
            return true;
        }
        return false;
    }
    
    private function reserveTaskRemove($doc){
        $this->Hub->load_model('Events');
        return $this->Hub->Events->eventDeleteDocumentTasks($doc->doc_id);
    }
    
    public function reserveCountUpdate(){
        $sql="
        UPDATE 
            stock_entries
                LEFT JOIN
            (SELECT 
                product_code,
                SUM(IF(doc_type = 1, de.product_quantity, 0)) reserved,
                SUM(IF(doc_type = 2, de.product_quantity, 0)) awaiting
            FROM
                document_entries de
            JOIN document_list USING (doc_id)
            JOIN document_status_list dsl USING (doc_status_id)
            WHERE
                dsl.status_code = 'reserved'
            GROUP BY product_code) reserve USING (product_code) 
        SET 
            product_reserved = COALESCE(reserved,0),
            product_awaiting = COALESCE(awaiting,0)
        WHERE 
	product_reserved IS NOT NULL 
        OR product_awaiting IS NOT NULL
	OR reserved IS NOT NULL 
        OR awaiting IS NOT NULL";
        return $this->query($sql);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    ////////////////////////////////////////////////////
    //MATCHES LIST FETCHING
    ////////////////////////////////////////////////////
    protected function matchesFilterStore(){
        $this->Hub->svar('filter_tree',$this->filter_tree);
    }
    
    public function matchesFilterGet(){
        $filter_tree=$this->Hub->svar('filter_tree');
        if( !$filter_tree ){
            return [];
        }
        $tree=[];
        foreach($filter_tree as $group){
            $filter_group_options=[];
            foreach($group->options as $option){
                $filter_group_options[]=[
                    'filter_group_id'=>$option->filter_group_id,
                    'filter_option_id'=>$option->filter_option_id,
                    'filter_option_range'=>$option->filter_option_range,
                    'filter_option_label'=>$option->filter_option_label,
                    'is_selected'=>$option->is_selected,
                    'match_count'=>$option->match_count
                ];
            }
            $tree[]=[
                'filter_group_id'=>$group->filter_group_id,
                'filter_group_name'=>$group->filter_group_name,
                'filter_group_minmax'=>$group->filter_group_minmax,
                'filter_group_options'=>$filter_group_options
            ];
        }
        return $tree;
    }
    
    protected function matchesFilterBuildRange( $group_id, $group_name ){
        $minmax=$this->get_row("SELECT FLOOR(MIN($group_id)) minval, CEIL(MAX($group_id)) maxval FROM tmp_matches_list");
        $this->matchesFilterBuildGroup( $group_id, $group_name, "{$minmax->minval}_{$minmax->maxval}" );
        $fraction_count=4;
        $fraction=($minmax->maxval - $minmax->minval)/$fraction_count;
        $roundto=pow(10,strlen(round($fraction))-1);
        $rounded_fraction=round($fraction/$roundto)*$roundto;
        $custom_option_range=null;
        if( isset($this->filter_selected_grouped[$group_id]) ){
            $custom_option_range=$this->filter_selected_grouped[$group_id];
        }
        for( $i=1; $i<=$fraction_count; $i++ ){
            $from =$rounded_fraction*($i-1);
            $to   =$rounded_fraction*$i;
            $is_selected=0;
            $lower_condition=">";
            if( $i==0 ){
                $from=$minmax->minval;
                $lower_condition=">=";
            }
            if( $i==$fraction_count ){
                $to=$minmax->maxval;
            }
            if( $custom_option_range!=null && $custom_option_range<$from ){
                $custom_option_range_parts=explode("_",$custom_option_range);
                $from=(float) $custom_option_range_parts[0];
                $to  =(float) $custom_option_range_parts[1];
                $i--;
            }
            if( $custom_option_range!=null && $custom_option_range=="{$from}_{$to}" ){
                $custom_option_range=null;
                $is_selected=1;
            }
            if( $from===null || $to===null ){
                continue;
            }
            $option_range="{$from}_{$to}";
            $option_label="$from - $to";
            $option_condition="$group_id $lower_condition $from AND $group_id <= $to";
            $this->matchesFilterBuildOption( $group_id, $option_label, $option_condition, $is_selected, $option_range );
        }
    }
    
    public function matchesFilterBuildGroup( $group_id, $group_name, $group_minmax=null ){
        $this->filter_tree[$group_id]=(object) [
            'filter_group_id'=>$group_id,
            'filter_group_name'=>$group_name,
            'filter_group_minmax'=>$group_minmax,
            'options'=>[]
        ];
    }
    
    public function matchesFilterBuildOption($group_id,  $option_label, $option_condition, $is_selected=null, $option_range=null ){
        $option_id=substr(md5("$group_id-$option_condition"),0,10);//may be collisions. do we need collision check?
        if( $is_selected===null && isset($this->filter_selected_grouped[$group_id]) ){
            $selected_options=$this->filter_selected_grouped[$group_id];
            $is_selected=(int)in_array($option_id, $selected_options);
        }
        $this->filter_tree[$group_id]->options[$option_id]=(object)[
            'filter_group_id'=>$group_id,
            'filter_option_id'=>$option_id,
            'filter_option_label'=>$option_label,
            'filter_option_range'=>$option_range,
            'filter_option_condition'=>$option_condition,
            'is_selected'=>$is_selected,
            'match_count'=>''
        ];
        return $option_id;
    }
    
    protected function matchesFilterBuildOptionSelect($group_id,$option_id,$is_selected=true){
        $this->filter_tree[$group_id]->options[$option_id]->is_selected=$is_selected;
    }
    
    protected function matchesFilterBuildCount(){
        $count_fields=[];
        function getCountCondition($group_id,$filter_tree,$filter_selected_grouped){
            $and_case=[];
            foreach( $filter_selected_grouped as $selected_group_id=>$options ){
                if( $group_id===$selected_group_id ){
                    continue;
                }
                $and_case[]=$filter_tree[$selected_group_id]->condition;
            }
            return $and_case;
        }
        foreach($this->filter_tree as $group){
            $other_group_conditions=getCountCondition($group->filter_group_id,$this->filter_tree,$this->filter_selected_grouped);
            foreach($group->options as $option){
                $full_condition=array_merge($other_group_conditions,[$option->filter_option_condition]);
                $option_condition='('.implode(") AND (",$full_condition).')';
                $count_fields[]="SUM(IF($option_condition,1,0)) `{$group->filter_group_id}___{$option->filter_option_id}`";
            }
        }
        $select= implode(',', $count_fields);
        $counts=$this->get_row("SELECT $select FROM tmp_matches_list");
        foreach($counts as $field=>$count){
            $ids=explode('___',$field);
            $this->filter_tree[$ids[0]]->options[$ids[1]]->match_count=$count?$count:0;
        }
    }
    
    protected function matchesFilterBuildPrice(){
        $group_id="price_final";
        $this->matchesFilterBuildRange($group_id,$this->lang("Price"));
    }
    
    protected function matchesFilterBuildAnalytics(){
        $group_id='analyse_brand';
        $group_name=$this->lang("Brand");
        $options=$this->get_list("SELECT DISTINCT $group_id FROM tmp_matches_list ORDER BY $group_id");
        $this->matchesFilterBuildGroup( $group_id, $group_name );
        foreach( $options as $option ){
            $option_value=$option->{$group_id};
            $option_label=$option_value?$option_value:$this->lang("Other");
            $option_condition=" $group_id='$option_value'";
            $this->matchesFilterBuildOption($group_id,  $option_label, $option_condition );
        }
    }
    
    protected function matchesFilterBuildPromotions(){
        $group_id='special_prices';
        $group_name=$this->lang("Promotion");
        $this->matchesFilterBuildGroup( $group_id, $group_name );
        $this->matchesFilterBuildOption($group_id,  $this->lang("Promotion"), 'NOT ISNULL(price_promo)' );
        $this->matchesFilterBuildOption($group_id,  $this->lang("Discount"), 'price_basic < price_fixed' );
        $this->matchesFilterBuildOption($group_id,  $this->lang("Special_price"), 'price_label < price_basic' );
    }
    
    
    protected function matchesListGetWhere( $q, $category_id ){
        /*
         * PREPEARING WHERE BY QUERY STRING AND CATEGORY
         */
        $lang='ru';
        $cases=[];
        if( strlen($q)==13 && is_numeric($q) ){
	    $cases[]="pl.product_barcode=$q";
	} else if( $q ){
	    $clues=  explode(' ', $q);
	    foreach ($clues as $clue) {
		if ($clue == ''){
		    continue;
		}
		$cases[]="(pl.product_code LIKE '%$clue%' OR pl.$lang LIKE '%$clue%')";
	    }
	}
        if( $category_id ){//maybe its good idea to switch to tree path filtering?
            $branch_ids = $this->treeGetSub('stock_tree', $category_id);
            $cases[]= "se.parent_id IN (" . implode(',', $branch_ids) . ")";
        }
        //plugins where_cases goes here
        return $cases?implode(' AND ',$cases):'1';
    }
    
    protected function matchesListGetOrderBy($sortby,$sortdir){
        /*
         * PREPEARING ORDER BY
         */
        switch($sortby){
            //plugins order_by_cases goes here
            case 'price':
                $order_by="price_final";
                break;
            case 'newness':
                $order_by='product_id';
                break;
            case 'name':
                $order_by='product_name';
                break;
            case 'popularity':
            default :
                $order_by='leftover=0,popularity';
        }
        if( $sortdir=='ASC' ){
            $order_by.=" ASC";
        } else {
            $order_by.=" DESC";
        }
        return $order_by;
    }
    
    protected function matchesListCreateTemporary( $where ){
        $result_window_size=3000;
        $usd_ratio=$this->Hub->pref('usd_ratio');
        $pcomp_id=$this->Hub->pcomp('company_id');
        $price_label=$this->Hub->pcomp('price_label');
        $this->query("SET @promo_limit:=1;");
        
        $query=[
            'inner'=>[
                'select'=>'',
                'table'=>'',
                'where'=>'',
                'limit'=>'',
                'groupby'=>''
            ],
            'outer'=>[
                'select'=>'',
                'table'=>'',
                'where'=>'',
                'limit'=>'',
                'groupby'=>''
            ]
        ];
        
        $query['inner']['select']="
            pl.product_id,
            pl.product_code,
            pl.ru product_name,
            pl.product_spack,
            pl.product_unit,
            pl.analyse_brand,
            pl.analyse_type,
            pl.analyse_class,
            se.product_quantity leftover,
            se.product_img,
            se.fetch_count,
            se.fetch_stamp,
            se.product_awaiting,
            se.product_reserved,
            CONCAT( 
                product_quantity<>0,
                IF( prl_promo.product_code IS NOT NULL AND (@promo_limit:=@promo_limit-1)>=0,1,0),
                LPAD(fetch_count,6,'0')
            ) popularity,
            se.parent_id,
            prl_basic.sell*IF(prl_basic.curr_code='USD',$usd_ratio,1) price_fixed,
            prl_basic.sell*IF(prl_basic.curr_code='USD',$usd_ratio,1)*IF(discount,discount,1) price_basic,
            prl_label.sell*IF(prl_label.curr_code='USD',$usd_ratio,1)*IF(discount,discount,1) price_label,
            prl_promo.sell*IF(prl_promo.curr_code='USD',$usd_ratio,1) price_promo";
        $query['inner']['table']="
            stock_entries se
                JOIN
            prod_list pl USING(product_code)
                JOIN
            stock_tree st ON st.branch_id=se.parent_id
                LEFT JOIN
            companies_discounts cd ON st.top_id=cd.branch_id AND company_id='$pcomp_id'
                LEFT JOIN
            price_list prl_promo ON prl_promo.label='PROMO' AND se.product_code=prl_promo.product_code
                LEFT JOIN
            price_list prl_label ON prl_label.label='$price_label' AND se.product_code=prl_label.product_code
                LEFT JOIN
            price_list prl_basic ON prl_basic.label='' AND se.product_code=prl_basic.product_code";
        $query['inner']['where']="
            WHERE 
             $where";
        $query['inner']['limit']="
            LIMIT $result_window_size";
        $query['outer']['select']="inner_tmp_matches_list.*,
            CAST(COALESCE(price_promo,price_label,price_basic) AS CHAR) price_final";
        $query['outer']['table']="";
        $query['outer']['where']="";
        $query['outer']['limit']="";
        $query['outer']['groupby']="GROUP BY product_id";
        
        $Events=$this->Hub->load_model("Events");
        $modified_query=$Events->Topic('beforeMatchesTmpCreated')->publish( $query );
        if( $modified_query ){
            $query=$modified_query;
        }
        
        //$this->query("DROP  TABLE IF EXISTS tmp_matches_list");#TEMPORARY
        $sql="CREATE TEMPORARY TABLE tmp_matches_list (PRIMARY KEY(product_id)) AS 
            SELECT 
                {$query['outer']['select']}
            FROM (
                SELECT 
                    {$query['inner']['select']}
                FROM 
                    {$query['inner']['table']}
                    {$query['inner']['where']}
                    {$query['inner']['limit']}
                    {$query['inner']['groupby']}
                ) inner_tmp_matches_list
                {$query['outer']['table']}
                {$query['outer']['where']}
                {$query['outer']['limit']}
                {$query['outer']['groupby']}";
        return $this->query($sql);
    }
    
    private function matchesListCalibrate(){
        if( rand(1,1000)!=1 ){
            return;
        }
        $this->query("
            UPDATE 
                stock_entries 
            SET 
                fetch_count = GREATEST(COALESCE(fetch_count - DATEDIFF(NOW(), fetch_stamp),0),0)");
    }
    
    protected function matchesFilterBuild(){
        $this->filter_tree=[];
        $this->filter_selected_grouped=$this->request('filter_selected_grouped','json',[]);
        $Events=$this->Hub->load_model("Events");
        $this->matchesFilterBuildPrice();
        $this->matchesFilterBuildPromotions();
        $this->matchesFilterBuildAnalytics();
        $Events->Topic('beforeMatchesFilterBuild')->publish($this);
    }
    
    public function matchesListFetch(string $q='', int $limit=12, int $offset=0, string $sortby, string $sortdir, int $category_id=0, int $pcomp_id=0) {
        $where=     $this->matchesListGetWhere( $q, $category_id );
        $order_by=  $this->matchesListGetOrderBy($sortby,$sortdir);
        
        $this->matchesListCalibrate();
        $this->matchesListCreateTemporary($where);
        $this->matchesFilterBuild();
        $matches=$this->matchesGet($order_by,$limit,$offset);
        foreach($matches as &$product){
            $product->product_name= htmlentities($product->product_name);
        }
        $this->immediateResponse($matches);
        
        $this->matchesFilterBuildCount();
        $this->matchesFilterStore();
    }
    
    private function matchesGet( $order_by, $limit, $offset ){
        $and_case=[];
        foreach($this->filter_tree as &$group){
            $or_case=[];
            foreach($group->options as $option){
                if( $option->is_selected ){
                    $or_case[]=$option->filter_option_condition;
                }
            }
            if( $or_case ){
                $group->condition=implode(" OR ",$or_case);
                $and_case[]=$group->condition;
            }
        }
        $having="";
        if( count($and_case) ){
            $filter_clause='('.implode(") AND (",$and_case).')';
            $having="HAVING $filter_clause";
        }

        $query=[
            'select'=>'tmp_matches_list.*',
            'table'=>'tmp_matches_list',
            'where'=>'',
            'having'=>$having,
            'order_by'=>"ORDER BY $order_by",
            'group_by'=>'GROUP BY product_id',
            'limit'=>"LIMIT $limit OFFSET $offset"
        ];

        $Events=$this->Hub->load_model("Events");
        $modified_query=$Events->Topic('beforeMatchesGet')->publish($query);
        if( $modified_query ){
            $query=$modified_query;
        }
        
        $sql="
            SELECT
                {$query['select']}
            FROM
                {$query['table']}
            {$query['where']}
            {$query['group_by']}
            {$query['having']}
            {$query['order_by']}
            {$query['limit']}";
        return $this->get_list($sql);
    }
    
    private function immediateResponse( $response ){
        ob_start();
        echo json_encode($response);
        header('Connection: close');
        header('Content-Length: '.ob_get_length());
        ob_end_flush();
        ob_flush();
        flush();
    }
}
