<?php
require_once 'Catalog.php';

class Checkout extends Stock {
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
                AND IF(checkout_list.parent_doc_id AND doc_id,level<='$level' AND path LIKE '$assigned_path%','$level'>1)
            HAVING {$having['inner']}
            ORDER BY $sortby $sortdir
            LIMIT $limit OFFSET $offset";
        return $this->get_list($sql);
    }
    
    public $checkoutDocumentGet = ['checkout_id' => 'int'];
    public function checkoutDocumentGet ($checkout_id){
        if( !$checkout_id ){
            return null;
        }
        $ch_document=[];
        $ch_document['head']=$this->checkoutDocumentHeadGet( $checkout_id );
        if( !$ch_document['head'] ){
            return null;
        }
        $ch_document['entries']=$this->checkoutEntriesFetch($checkout_id);
        $ch_document['log']=$this->checkoutLogFetch($checkout_id);
        return $ch_document;
    }
    
    public function checkoutDocumentHeadGet( int $checkout_id=0 ){
        if( !$checkout_id ){
            return [];
        }
        $assigned_path = $this->Hub->svar('user_assigned_path');
        $level = $this->Hub->svar('user_level');
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
                AND IF(checkout_list.parent_doc_id,level<='$level' AND path LIKE '$assigned_path%','$level'>1)";
        $head=$this->get_row($sql);
        if( $head->parent_doc_id ){
            $this->checkoutDocumentRefresh($checkout_id, $head->parent_doc_id);
        }
        return $head;
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
                checkout_entries (product_id, product_quantity, checkout_id, product_quantity_verified, verification_status, product_comment)
            
            SELECT * FROM
                (SELECT 
                    product_id, product_quantity, $checkout_id, 0 pqv, 0 vs,IF( POSITION('err' IN row_status) OR POSITION('wrn' IN row_status), SUBSTRING(row_status FROM POSITION(' ' IN row_status)),'') new_product_comment
                FROM
                (SELECT
                    product_id, product_quantity, CHK_ENTRY(doc_entry_id) row_status
                FROM
                    document_entries de
                        JOIN
                    prod_list pl USING(product_code)
                WHERE 
                    doc_id = '$parent_doc_id'
                        ) t) de
            ON DUPLICATE KEY UPDATE checkout_entries.product_quantity=de.product_quantity,
            verification_status=IF(de.product_quantity=product_quantity_verified,1,2),
            checkout_entries.product_comment=new_product_comment
            ";
        $this->query($sql_reset);
        $this->query($sql_update);
    }

    
    public function checkoutEntriesFetch ( int $checkout_id, int $offset=0, int $limit=1000, string $sortby=null, string $sortdir=null, array $filter = null  ){
        $this->Hub->set_level(2);
        if (empty($sortby)) {
	    $sortby = "cstamp";
	    $sortdir = "DESC";
	}
	$having = $this->makeStockFilter($filter);
        $sql = "
            SELECT 
                ce.*,
                ce.product_quantity_verified-ce.product_quantity quantity_difference,
                IF(ce.verification_status=1,'✔',IF(ce.verification_status=2,'±','')) verification_status_symbol,
                IF(product_comment<>'',CONCAT(ru,' [',product_comment,']'),ru) ru, 
                product_spack, 
                product_bpack, 
                product_code, 
                product_unit, 
                product_barcode,
                product_img
            FROM 
                checkout_entries ce
                    JOIN
                prod_list USING(product_id)
                    JOIN
                stock_entries USING(product_code)    
            WHERE
                checkout_id = '$checkout_id'
            HAVING {$having['inner']}
            ORDER BY '$sortby' '$sortdir'
            LIMIT $limit OFFSET $offset";
        return $this->get_list($sql)??[];
    }
    
    public $checkoutProductGet = ['barcode' => 'string']; 
    public function checkoutProductGet($barcode) {
        $this->Hub->set_level(2);
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
        $this->Hub->set_level(2);
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
        $this->Hub->set_level(2);
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
    
    public function checkoutLogFetch ( int $checkout_id=0, int $offset=0, int $limit=1000 ) {
        if( !$checkout_id ){
            return [];
        }
        $this->Hub->set_level(1);
        $sql = " 
            SELECT
                clg.*,
                DATE_FORMAT(clg.cstamp, '%d.%m.%Y %H:%i') cstamp_dmy,
                ru,
                product_code,
                product_unit
            FROM
                checkout_log clg
                    JOIN
                prod_list USING(product_id)
            WHERE
                checkout_id = '$checkout_id'
            ORDER BY cstamp ASC
            LIMIT $limit OFFSET $offset";
        return $this->get_list($sql);        
    }
    
    public $checkoutStockCreate = ['parent_id' => 'int', 'checkout_name'=>'string'];
    public function checkoutStockCreate ($parent_id, $checkout_name){
        $this->Hub->set_level(2);
        $stock_entries_list = $this->listFetch($parent_id, 0, 10000, 'product_code', 'ASC', null, 'advanced');
        $user_id = $this->Hub->svar('user_id');
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
    
    private function checkoutDocumentEntryCommentParse( $row_status_text ){
        $row_status=explode(' ',$row_status_text);
        $row_status_code= array_shift($row_status);
        if( strpos($row_status_code,'err')!==false || strpos($row_status_code,'wrn')!==false ){
            return implode(' ', $row_status);
        }
        return '';
    }
    
    public $checkoutDocumentCreate = ['parent_doc_id' => 'int', 'checkout_name'=>'string'];
    public function checkoutDocumentCreate ($parent_doc_id, $checkout_name){
        $this->Hub->set_level(2);
        $DocumentItems = $this->Hub->load_model("DocumentItems");
        $document_entries_list = $DocumentItems->entryDocumentGet($parent_doc_id);
        $user_id = $this->Hub->svar('user_id');
        $checkout_id=$this->create('checkout_list', ['checkout_name'=>$checkout_name, 'parent_doc_id'=>$parent_doc_id, 'created_by'=>$user_id, 'modified_by'=>$user_id]);
        foreach ($document_entries_list['entries'] as $entry){
            $product_id = $this->get_value("SELECT product_id FROM prod_list WHERE product_code = '$entry->product_code'");
            $product_comment=$this->checkoutDocumentEntryCommentParse( $entry->row_status );
            
            $this->create('checkout_entries', ['checkout_id'=>$checkout_id, 
                                                'product_id'=>$product_id, 
                                                'product_quantity'=>$entry->product_quantity,
                                                'product_comment'=>$product_comment,
                                                'product_quantity_verified'=>0,
                                                'verification_status'=>0]);
        }
        return $checkout_id;
    }
    
    public $checkoutDocumentOutput = ['checkout_id'=>'int'];
    public function checkoutDocumentOutput ($checkout_id){
        $this->Hub->set_level(2);
        $parent_doc_id = $this->get_value("SELECT parent_doc_id FROM checkout_list WHERE checkout_id=$checkout_id");
        if ($parent_doc_id){
            $result=$this->checkoutSourceDocUpdate($checkout_id);
            if( $result ){
                $this->checkoutUpdateDocStatus($checkout_id, 'checked');
            }
            return $result;
        }else{
            return $this->checkoutCalcDifference($checkout_id);
        }
    }
    
    private function checkoutSourceDocUpdate ($checkout_id){
        $this->Hub->set_level(2);
        $DocumentItems=$this->Hub->load_model('DocumentItems');
        $checkout_document = $this->checkoutDocumentGet($checkout_id);
        $source_doc_id = $checkout_document['head']->parent_doc_id;
        $document = $DocumentItems->entryDocumentGet($source_doc_id);
	$result=[
	    'added'=>0,
	    'deleted'=>0,
	    'updated'=>0
	];
        foreach ($checkout_document['entries'] as $entry_check){
            $entry_exists_in_document=false;
            foreach ($document['entries'] as $entry_doc ){
                $doc_product_id = $this->get_value("SELECT product_id FROM prod_list WHERE product_code = '$entry_doc->product_code'");
                if( $entry_check->product_id == $doc_product_id ){
                    if ( $entry_check->product_quantity_verified == 0 ){
                        $delete_ok=$DocumentItems->entryDeleteArray($source_doc_id, [[$entry_doc->doc_entry_id]]);
                        if( !$delete_ok ){
                            return false;
                        }
                        $result['deleted']++;
                    } else {
                        $update_ok=$DocumentItems->entryUpdate($source_doc_id, $entry_doc->doc_entry_id, 'product_quantity', $entry_check->product_quantity_verified );
                        if( !$update_ok ){
                            return false;
                        }
			$result['updated']++;
                    }
                    $entry_exists_in_document=true;
                }
            }
            if( !$entry_exists_in_document ){
                $check_product_code = $this->get_value("SELECT product_code FROM prod_list WHERE product_id = '$entry_check->product_id'");
                $add_ok=$DocumentItems->entryAdd(null,$check_product_code, $entry_check->product_quantity_verified );
                if( !$add_ok ){
                    return false;
                }
		$result['added']++;
            }
        }
        return $result;
    }
    
    private function checkoutCalcDifference($checkout_id){
        $this->Hub->set_level(2);
        $current_checkout=$this->checkoutDocumentGet ($checkout_id);
        $document_comment='Корректировка '.$current_checkout['head']->checkout_name.' от '.$current_checkout['head']->cstamp_dmy;
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
        $more_doc_id=0;
        if (count($entries_list_more)>0){
            $this->checkoutUpdateDocStatus($checkout_id, 'checked_with_divergence');
            $more_doc_id = $DocumentItems->createDocument(2);
            foreach($entries_list_more as $item){
                $DocumentItems->entryAdd(null,$item->product_code, $item->difference);
            }
            $DocumentItems->headUpdate('doc_data',$document_comment);
            //$DocumentItems->entryDocumentCommit($more_doc_id);
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
        $less_doc_id=0;
        if (count($entries_list_less)>0){
            $this->checkoutUpdateDocStatus($checkout_id, 'checked_with_divergence');
            $less_doc_id = $DocumentItems->createDocument(1);
            foreach($entries_list_less as $item){
                $DocumentItems->entryAdd(null,$item->product_code, $item->difference);
            }
            $DocumentItems->headUpdate('doc_data',$document_comment);
            //$DocumentItems->entryDocumentCommit($less_doc_id);
        }
        return [
	    'less'=>count($entries_list_less),
	    'more'=>count($entries_list_more)
	    ];
    }
    
    public $checkoutUp=['checkout_id'=>'int', 'file_name'=>'string'];
    public function checkoutUp( $checkout_id, $file_name){
        $this->Hub->set_level(2);
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

    public function checkoutViewGet($checkout_id){
	$out_type=$this->request('out_type');
	$ch_document=$this->checkoutDocumentGet ($checkout_id);
	$dump=[
	    'tpl_files'=>'/CheckoutResult.xlsx',
	    'title'=>"Проверка-".$ch_document['head']->checkout_name,
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'view'=>[
                'head'=>$ch_document['head'],
                'rows'=>$ch_document['entries'],
                'log'=>$ch_document['log']
	    ]
	];
        //print_r($dump);die;
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}