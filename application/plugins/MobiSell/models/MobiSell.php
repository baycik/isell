<?php
/* User Level: 1
 * Group Name: Мобильное
 * Plugin Name: MobiSell
 * Version: 2017-03-26
 * Description: Мобильное приложение
 * Author: baycik 2017
 * Author URI: isellsoft.com
 * Trigger before: MobiSell
 */
class MobiSell extends PluginManager {
    public $min_level = 1;
    function __construct() {
        ini_set('zlib.output_compression_level', 6);
        ob_start("ob_gzhandler");
        parent::__construct();
    }

    public function index() {
        $this->load->view('index.html');
    }
    
    public function view( string $path ){
	$this->load->view($path);
    }
    
    public $version = [];
    public function version() {
        $parent_dir=dirname(__DIR__);
        if( file_exists($parent_dir."/version.txt") ){
            return file_get_contents($parent_dir."/version.txt");
        }
        $checksum=$this->hashDirectory($parent_dir);
        file_put_contents($parent_dir."/version.txt",$checksum);
        return $checksum;
    }
    private function hashDirectory($directory) {
        if (!is_dir($directory)) {
            return false;
        }
        $files = array();
        $dir = dir($directory);
        while (false !== ($file = $dir->read())) {
            if ($file != '.' and $file != '..') {
                if (is_dir($directory . '/' . $file)) {
                    $files[] = $this->hashDirectory($directory . '/' . $file);
                } else {
                    $files[] = md5_file($directory . '/' . $file);
                }
            }
        }
        $dir->close();//
        return md5(implode('', $files));
    }
    public $doclistGet = ['type' => 'string', 'date' => '([0-9\-]+)', 'offset' => ['int', 0], 'limit' => ['int', 10], 'compFilter' => 'string'];
    public function doclistGet($type, $date, $offset, $limit, $compFilter) {
        $assigned_path = $this->Hub->svar('user_assigned_path');
        $level = $this->Hub->svar('user_level');
        $doc_type = ($type == 'sell' ? 1 : 2);
        $sql = "SELECT
		doc_id,
		dl.doc_num,
		DATE_FORMAT(cstamp,'%d.%m.%Y') doc_date,
		is_commited,
		COALESCE(
		    ROUND((SELECT amount 
			FROM 
			    acc_trans 
				JOIN 
			    document_trans dt USING(trans_id)
			WHERE dt.doc_id=dl.doc_id 
			ORDER BY trans_id LIMIT 1
		    ),2),
		    '') amount,
		label
	    FROM
		document_list dl
		    JOIN 
		companies_list ON company_id=passive_company_id
		    JOIN 
		companies_tree USING(branch_id)
	    WHERE
		cstamp LIKE '$date%'
		AND doc_type='$doc_type'
		AND label LIKE '%$compFilter%'
		AND path LIKE '$assigned_path%'
		AND level<=$level
	    ORDER BY cstamp DESC, doc_type
	    LIMIT $limit OFFSET $offset
	    ";
        return $this->get_list($sql);
    }
    public $compListFetch = ['mode' => 'string', 'q' => 'string'];
    public function compListFetch($mode, $q) {
        return [
            'success' => true,
            'results' => $this->Hub->load_model('Company')->listFetchAll($mode, $q)
        ];
    }
    public $documentCreate = ["doc_type" => "int", "acomp_id" => "int",  "pcomp_id" => "int", 'entries' => ['json', null]];
    public function documentCreate($doc_type, $acomp_id, $pcomp_id, $entries) {
        $Company = $this->Hub->load_model("Company");
        $Company->selectPassiveCompany($pcomp_id);
        $Company->selectActiveCompany($acomp_id);
        $DocumentItems = $this->Hub->load_model("DocumentItems");
        $doc_id = $DocumentItems->createDocument($doc_type);
        if ($entries) {
            $this->documentEntryFill($doc_id, $entries);
        }
        
        $message=[
            "subject"=>"MobiSell уведомление от ".$this->Hub->svar('user_sign'),
            "view"=>'document_created.html',
            "data"=>[
                'user_sign'=>$this->Hub->svar('user_sign'),
                'pcomp_label'=>$this->Hub->pcomp('label')
            ]
        ];
        $this->Hub->svar('Mobisell_create_notification',$message);
        return $doc_id;
    }
    private function documentEntryFill($doc_id, $entries) {
        foreach ($entries as $entry) {
            $this->documentEntryUpdate($doc_id, null, $entry['product_code'], $entry['product_quantity']);
        }
    }
    public $documentGet = ["doc_id" => "int"];
    public function documentGet($doc_id) {
        $DocumentItems = $this->Hub->load_model("DocumentItems");
        $document = $DocumentItems->entryDocumentGet($doc_id);
        $document['head'] = $DocumentItems->headGet($doc_id);
        $document['head']->is_event_created = $this->documentShipmentEventId($doc_id);
        return $document;
    }
    private function documentShipmentEventId($doc_id) {
        $sql = "SELECT event_id FROM event_list WHERE doc_id='$doc_id' AND event_label LIKE '%Доставка%'";
        return $this->get_value($sql);
    }
    private function documentShipmentEventAdd($doc_id) {
        $DocumentItems = $this->Hub->load_model("DocumentItems");
        $head = $DocumentItems->headGet($doc_id);
        $event = [
            'doc_id' => $doc_id,
            'event_id' => 0,
            'event_date' => date("Y-m-d"),
            'event_label' => 'Доставка MobiSell',
            'event_creator_user_id' => $this->Hub->svar('user_id'),
            'event_name' => 'Документ №' . $head->doc_num,
            'event_descr' => $head->doc_data,
            'event_target' => $this->Hub->pcomp('company_person') . " (" . $this->Hub->pcomp('label') . ")",
            'event_place' => $this->Hub->pcomp('company_address'),
            'event_note' => $this->Hub->pcomp('company_mobile'),
            'event_status' => 'undone',
            'event_priority'=>'2high'
        ];
        return $this->create('event_list', $event);
    }
    private function documentShipmentEventDelete($doc_id) {
        $this->query("DELETE FROM event_list WHERE doc_id='$doc_id'  AND event_label LIKE '%Доставка%'");
    }
    public $documentHeadUpdate = ["doc_id" => "int", "field" => "string", "value" => "string"];
    public function documentHeadUpdate($doc_id, $field, $value) {
        $DocumentItems = $this->Hub->load_model("DocumentItems");
        switch ($field) {
            case 'is_commited':
                if ($value == 1) {
                    $DocumentItems->entryDocumentCommit($doc_id);
                } else {
                    $DocumentItems->entryDocumentUncommit($doc_id);
                }
                break;
            case 'is_event_created':
                if ($value == 1) {
                    $this->documentShipmentEventAdd($doc_id);
                } else {
                    $this->documentShipmentEventDelete($doc_id);
                }
                break;
            default:
                $DocumentItems->headUpdate($field, $value);
                break;
        }
        return $this->documentGet($doc_id);
    }

    public function documentEntryUpdate(int $doc_id, int $doc_entry_id=null, string $product_code, float $product_quantity, float $product_price=null) {
        $DocumentItems = $this->Hub->load_model("DocumentItems");
        if ($doc_entry_id) {
            $DocumentItems->entryUpdate($doc_id, $doc_entry_id, 'product_quantity', $product_quantity);
            if( $product_price ){
                $DocumentItems->entryUpdate($doc_id, $doc_entry_id, 'product_price', $product_price);
            }
        } else {
            $DocumentItems->entryAdd($doc_id, $product_code, $product_quantity);
        }
        return $this->documentGet($doc_id);
    }
    public $documentEntryRemove = ['doc_id' => 'int', 'doc_entry_id' => 'int'];
    public function documentEntryRemove($doc_id, $doc_entry_id) {
        $DocumentItems = $this->Hub->load_model("DocumentItems");
        $DocumentItems->entryDeleteArray($doc_id, [[$doc_entry_id]]);
        return $this->documentGet($doc_id);
    }
    public $documentDiscountsGet = ['passive_company_id' => ['int', 0]];
    public function documentDiscountsGet($passive_company_id) {
        $Company = $this->Hub->load_model("Company");
        $Company->selectPassiveCompany($passive_company_id);
        return $Company->companyPrefsGet();
    }
    ////////////////////////////////////////////////////
    //PRODUCT LIST FETCHING
    ////////////////////////////////////////////////////
    public function productListFetch(string $q, int $offset=0, int $limit=0, int $category_id=0, int $pcomp_id=0, string $order_by, array $selected_attribute_hashes=null) {
        $cases=[];
        if( strlen($q)==13 && is_numeric($q) ){
	    $cases[]="product_barcode=$q";
	} else if( $q ){
	    $clues=  explode(' ', $q);
	    foreach ($clues as $clue) {
		if ($clue == ''){
		    continue;
		}
		$cases[]="(product_code LIKE '%$clue%' OR ru LIKE '%$clue%')";
	    }
	}
        if( $category_id ){//maybe its good idea to switch to tree path filtering?
            $branch_ids = $this->treeGetSub('stock_tree', $category_id);
            $cases[]= " AND parent_id IN (" . implode(',', $branch_ids) . ")";
        } 
        $where=$cases?implode(' AND ',$cases):'1';
        $this->productListCreateTemporary($where);
        
        
        
        $attribute_list = $this->attributeListFetch($selected_attribute_hashes,$where);
        
        
        $where="1";
        if( $selected_attribute_hashes ){
             foreach($selected_attribute_hashes as $attribute_value_hash){
                 $where .= " AND attribute_value_hash='$attribute_value_hash' ";
             }
        }
        
        
        
        
        
        
        
        $usd_ratio=$this->Hub->pref('usd_ratio');
        $sql="
	   SELECT 
                t.product_id,
                t.product_code,
                t.product_spack,
                t.leftover,
                t.product_name,
                t.product_img,
                t.fetch_count,
                t.fetch_stamp,
                t.parent_id,
                t.product_unit,
                GET_SELL_PRICE(t.product_code, {$pcomp_id}, {$usd_ratio}) product_price_total,
                GET_PRICE(t.product_code,{$pcomp_id}, {$usd_ratio}) product_price_total_raw
            FROM
                (SELECT 
                *, ru product_name
	    FROM
		product_list_temp
            WHERE $where
            GROUP BY product_id
	    ORDER BY $order_by, product_code
	    LIMIT $limit OFFSET $offset ) t
            ";
        $sql = "SELECT 
            tmp.*, GROUP_CONCAT(IFNULL(srl.supplier_delivery, null)) as delivery_group, GROUP_CONCAT(COALESCE (CONCAT (sl.supply_leftover,' ',tmp.product_unit), CONCAT (sl1.supply_leftover,' ',tmp.product_unit), null)) as  supleftover
        FROM 
            (  $sql ) AS tmp 
        LEFT JOIN 
        supply_list sl ON (sl.product_code = tmp.product_code AND sl.supply_leftover > 0 ) 
            LEFT JOIN 
        supply_list sl1 ON (sl1.supply_code = tmp.product_code AND sl1.supply_leftover > 0 )
            LEFT JOIN 
        supplier_list srl ON (sl.supplier_id = srl.supplier_id OR sl1.supplier_id = srl.supplier_id) 
        GROUP BY product_code 
        ORDER BY tmp.popularity  DESC
        ";        
        $product_list = $this->get_list($sql);
        return ['product_list'=> $product_list, 'attribute_list'=> $attribute_list];
    }
    
    private function productListCreateTemporary( $where ){
         $sql="
            CREATE TEMPORARY TABLE tmp_product_list
            SELECT
                pl.product_id,
                pl.product_code,
                pl.ru,
                pl.product_spack,
                se.product_quantity leftover,
                se.product_img,
                fetch_count,
                fetch_stamp,
                parent_id,
                product_unit,
                self_price
                ,GROUP_CONCAT(DISTINCT av.attribute_value_hash SEPARATOR ',') attribute_value_hash
            FROM
                stock_entries se
                    JOIN
                prod_list pl USING(product_code)
                    LEFT JOIN	
                attribute_values av ON pl.product_id = av.product_id
                    LEFT JOIN
                attribute_list al USING(attribute_id)
                WHERE $where
                GROUP BY pl.product_id
        ";
        return $this->query($sql);
    }
    
    private function attributeListFetch($attribute_value_ids, $where){
        $this->query("CREATE TEMPORARY TABLE attributes_temp SELECT av.*, pl.parent_id FROM attribute_values av JOIN product_list_temp pl ON av.product_id = pl.product_id AND $where");
        $attributes_where = '1';
        if( $attribute_value_ids ){
             foreach($attribute_value_ids as $index=>$attribute_value){
                 $attributes_where .= " AND pl.attribute_value_hash LIKE '%$attribute_value%' ";
             }
        }
        $sql="
            SELECT 
                *,
                GROUP_CONCAT(DISTINCT CONCAT(t.attribute_value, '::', t.attribute_value_hash, '::', t.product_total) 
                    ORDER BY t.attribute_id ASC, t.attribute_value*1 ASC
                    SEPARATOR '|') attribute_values
            FROM
                (SELECT 
                        al.attribute_id,
                        al.attribute_name,
                        al.attribute_unit,
                        al.attribute_prefix,
                        av.attribute_value,
                        av.product_id,
                        av.attribute_value_hash,
                        IF(ptotal.product_total IS NOT NULL, ptotal.product_total, 0) product_total
                    FROM
                        attributes_temp av
                        JOIN 
                        attribute_list al USING (attribute_id)
                    LEFT JOIN 
                        (SELECT 
                           av.attribute_value_hash,  parent_id, COUNT(product_id) as product_total
                        FROM
                                product_list_temp pl
                                        JOIN 
                        attribute_values av USING (product_id)
                                        JOIN 
                        attribute_list al USING (attribute_id)
                        WHERE  $attributes_where  
                        GROUP BY av.attribute_value_hash) ptotal ON  ptotal.attribute_value_hash = av.attribute_value_hash
                    GROUP BY av.attribute_value_hash) t
            GROUP BY t.attribute_id
            ";
        $attribute_list = $this->get_list($sql);
        
        return $this->attributeListCompose($attribute_list);
    }
    
    private function attributeListCompose($attribute_list){
        foreach($attribute_list as &$attribute){
            $attribute->attribute_values = explode('|',$attribute->attribute_values);
            foreach($attribute->attribute_values as &$attribute_value){
                $attribute_value_exploded = explode('::', $attribute_value);
                $attribute_value = [
                    'attribute_value' => $attribute_value_exploded[0],
                    'attribute_value_id' => isset($attribute_value_exploded[1])?$attribute_value_exploded[1]:'',
                    'product_total' => isset($attribute_value_exploded[2])?$attribute_value_exploded[2]*1:0,
                    'attribute_id' => $attribute->attribute_id,
                    'attribute_unit' => $attribute->attribute_unit,
                    'attribute_name' => $attribute->attribute_name,
                    'attribute_prefix' => $attribute->attribute_prefix
                ];
            }
        }
        return $attribute_list;
    }
    /*
    //public $productGet = ['product_code' => 'string'];
    public function productGet(string $product_code) {
        $pcomp_id=$this->Hub->pcomp('company_id');
        $usd_ratio=$this->Hub->pref('usd_ratio');
        $sql = "SELECT
                  st.label parent_label,
                  pl.*,
                  ROUND(product_volume,5) product_volume,
                  ROUND(product_weight,5) product_weight,
                  product_quantity leftover,
                  product_img,
                  product_unit,
                  GET_SELL_PRICE(se.product_code,'{$pcomp_id}','{$usd_ratio}') product_price_total,
                  GET_PRICE(se.product_code,'{$pcomp_id}','{$usd_ratio}') product_price_total_raw,
                  pp.curr_code,
                  se.party_label,
                  se.product_quantity,
                  se.product_img
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
    }*/
    
    public $userPropsGet=[];
    public function userPropsGet(){
        $props=$this->Hub->load_model('User')->userFetch();
        if( $props->user_assigned_path ){
            $props->debt=$this->Hub->load_model('AccountsData')->clientDebtGet('all_active',$props->user_assigned_path);
        }
        return $props;
    }
    
    private function notify($subject,$view_file,$data){
	$this->settings=$this->settingsDataFetch('MobiSell');
	$Utils=$this->Hub->load_model('Utils');
        $text=$this->load->view($view_file,$data,true);
        if( isset($this->settings->plugin_settings->email) && $this->settings->plugin_settings->email ){
            $Utils->sendEmail( $this->settings->plugin_settings->email, $subject, $text, NULL, 'nocopy' );
        }
        if( isset($this->settings->plugin_settings->phone) && $this->settings->plugin_settings->phone ){
            $phones=  explode(',',preg_replace('|[^\d,]|', '', $this->settings->plugin_settings->phone));
            foreach($phones as $phone){
                $Utils->sendSms($phone,"$text");
            }
        }
    }
    
    public function notify_pending( string $custom_message='' ){
        $message=$this->Hub->svar('Mobisell_create_notification');
        if( $message ){
            $message['subject'].=" $custom_message";
        }
        $this->notify($message['subject'],$message['view'],$message['data']);
    }
}