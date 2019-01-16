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
    public $index = [];
    public function index() {
        $this->load->view('index.html');
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
            'event_label' => 'Доставка',
            'event_creator_user_id' => $this->Hub->svar('user_id'),
            'event_name' => 'Документ №' . $head->doc_num,
            'event_descr' => $head->doc_data,
            'event_target' => $this->Hub->pcomp('company_person') . " (" . $this->Hub->pcomp('label') . ")",
            'event_place' => $this->Hub->pcomp('company_address'),
            'event_note' => $this->Hub->pcomp('company_mobile'),
            'event_status' => 'undone'
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
    public $documentEntryUpdate = ['doc_id' => 'int', 'doc_entry_id' => 'int', 'product_code' => 'string', 'product_quantity' => 'int'];
    public function documentEntryUpdate($doc_id, $doc_entry_id, $product_code, $product_quantity) {
        $DocumentItems = $this->Hub->load_model("DocumentItems");
        if ($doc_entry_id) {
            $DocumentItems->entryUpdate($doc_id, $doc_entry_id, 'product_quantity', $product_quantity);
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
    public $suggestFetch = ['q' => 'string', 'offset' => ['int', 0], 'limit' => ['int', 10], 'doc_id' => ['int', 0], 'category_id' => ['int', 0], 'pcomp_id' => ['int', 0]];
    public function suggestFetch($q, $offset, $limit, $doc_id, $category_id, $pcomp_id) {
        $Company = $this->Hub->load_model("Company");
        $DocumentItems = $this->Hub->load_model("DocumentItems");
        if ($this->Hub->pcomp('company_id') != $pcomp_id) {
            $Company->selectPassiveCompany($pcomp_id);
        }
        return $DocumentItems->suggestFetch($q, $offset, $limit, $doc_id, $category_id);
    }
    
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
    public $notify_pending=[];
    public function notify_pending(){
        $message=$this->Hub->svar('Mobisell_create_notification');
        $this->notify($message['subject'],$message['view'],$message['data']);
    }
}