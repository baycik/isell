<?php
require_once 'Catalog.php';

class Checkout extends Stock {

// I AM A PRINCESS
    
    public $checkoutListFetch = ['date' => 'string' ,'offset' => ['int', 0], 'limit' => ['int', 5], 'sortby' => 'string', 'sortdir' => '(ASC|DESC)', 'filter' => 'json'];
    public function checkoutListFetch( $date, $offset, $limit, $sortby, $sortdir, $filter = null ){
        $assigned_path = $this->Hub->svar('user_assigned_path');
        $level = $this->Hub->svar('user_level');
        if (empty($sortby)) {
	    $sortby = "cstamp";
	    $sortdir = "DESC";
	}
        $null = null;
	$having = $this->makeStockFilter($filter);
        $where = '';
        $sql = "        
            SELECT
                checkout_list.*,
                DATE_FORMAT(checkout_list.cstamp, '%d.%m.%Y %H:%i') cstamp_dmy, 
                (SELECT user_sign FROM user_list WHERE user_id = checkout_list.created_by) AS creator_nick,
                (SELECT user_sign FROM user_list WHERE user_id = checkout_list.modified_by) AS modifier_nick
            FROM 
                checkout_list 
                    LEFT JOIN
                document_list ON doc_id=checkout_list.parent_doc_id 
		    LEFT JOIN 
		companies_list ON company_id=passive_company_id
		    LEFT JOIN 
		companies_tree USING(branch_id)
            WHERE
		checkout_list.cstamp LIKE '$date%'
                AND IF(checkout_list.parent_doc_id,level<='$level' AND path LIKE '$assigned_path%','$level'>1)
            HAVING {$having['inner']}
            ORDER BY $sortby $sortdir
            LIMIT $limit OFFSET $offset";
        return $this->get_list($sql);
    }
    
    public $checkoutDocumentGet = ['checkout_id' => 'int'];
    public function checkoutDocumentGet ($checkout_id){
        $assigned_path = $this->Hub->svar('user_assigned_path');
        $level = $this->Hub->svar('user_level');
        if( !$checkout_id ){
            return null;
        }
        $sql = "        
            SELECT
                checkout_list.*,
                DATE_FORMAT(checkout_list.cstamp, '%d.%m.%Y %H:%i') cstamp_dmy, 
                (SELECT user_sign FROM user_list WHERE user_id = checkout_list.created_by) AS creator_nick,
                (SELECT user_sign FROM user_list WHERE user_id = checkout_list.modified_by) AS modifier_nick
            FROM 
                checkout_list
                    LEFT JOIN
                document_list ON doc_id=checkout_list.parent_doc_id 
		    LEFT JOIN 
		companies_list ON company_id=passive_company_id
		    LEFT JOIN 
		companies_tree USING(branch_id)
            WHERE 
                checkout_id='$checkout_id'
                AND IF(checkout_list.parent_doc_id,level<='$level' AND path LIKE '$assigned_path%','$level'>1)
                ";
        
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

    public $checkoutEntriesFetch = ['checkout_id' => 'int', 'sortby' => 'string', 'sortdir' => '(ASC|DESC)', 'filter' => 'json'];
    public function checkoutEntriesFetch ($checkout_id, $sortby=null, $sortdir=null, $filter = null  ){
        if (empty($sortby)) {
	    $sortby = "cstamp";
	    $sortdir = "DESC";
	}
	$having = $this->makeStockFilter($filter);
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
                checkout_id = '$checkout_id'
            HAVING {$having['inner']}
            ORDER BY '$sortby' '$sortdir'";
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
		    product_barcode= '$barcode'
                        OR
                    product_code = '$barcode'    
                ";
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
       // $cstamp = date('Y-m-d H:i:s');
        $checkout_id=$this->create('checkout_list', ['checkout_name'=>$checkout_name, 'parent_doc_id'=>null, 'created_by'=>$user_id, 'modified_by'=>$user_id]);
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
        $checkout_id=$this->create('checkout_list', ['checkout_name'=>$checkout_name, 'parent_doc_id'=>$parent_doc_id, 'created_by'=>$user_id, 'modified_by'=>$user_id]);
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
                        print_r($DocumentItems->entryUpdate($source_doc_id, $entry_doc->doc_entry_id, 'product_quantity', $entry_check->product_quantity_verified));
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