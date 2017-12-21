<?php
/* User Level: 1
 * Group Name: Мобильное
 * Plugin Name: MobiSell
 * Version: 2017-03-26
 * Description: Мобильное приложение
 * Author: baycik 2017
 * Author URI: isellsoft.com
 * Trigger before: MobiSell
 * 
 * Description of DocumentSell
 * This class handles all of sell documents
 * @author Baycik
 */
class MobiSell extends Catalog{
    public $min_level=1;
    function __construct() {
	ini_set('zlib.output_compression_level', 6);
	ob_start("ob_gzhandler");
	parent::__construct();
    }
    
    public $index=[];
    public function index(){
	$this->load->view('index.html');
    }
    
    public $version=[];
    public function version(){
	return date ("Y-m-d-Hi", filemtime(__FILE__));
    } 
    
    public $doclistGet=['type'=>'string','date'=>'([0-9\-]+)','offset'=>['int',0],'limit'=>['int',10], 'compFilter'=>'string'];
    public function doclistGet($type,$date,$offset,$limit,$compFilter){
	$assigned_path=$this->Hub->svar('user_assigned_path');
	$level=$this->Hub->svar('user_level');
	$doc_type=($type=='sell'?1:2);
	$sql="SELECT
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
    
    public $compListFetch=['mode'=>'string','q'=>'string'];
    public function compListFetch($mode,$q){
	return [
	    'success'=>true,
	    'results'=>$this->Hub->load_model('Company')->listFetchAll($mode,$q)
	    ];
    }
    
    public $documentCreate=["doc_type"=>"int","pcomp_id"=>"int"];
    public function documentCreate($doc_type,$pcomp_id){
	$Company=$this->Hub->load_model("Company");
	$Company->selectPassiveCompany($pcomp_id);
	
	$DocumentItems=$this->Hub->load_model("DocumentItems");
	return $DocumentItems->createDocument($doc_type);
    }
    
    public $documentGet=["doc_id"=>"int"];
    public function documentGet($doc_id){
	$DocumentItems=$this->Hub->load_model("DocumentItems");
	$document=$DocumentItems->entryDocumentGet( $doc_id );
	$document['head']=$DocumentItems->headGet( $doc_id );
	$document['head']->is_event_created= $this->documentShipmentEventId($doc_id);
	return $document;
    }
    
    private function documentShipmentEventId($doc_id){
	$sql="SELECT event_id FROM event_list WHERE doc_id='$doc_id' AND event_label LIKE '%Доставка%'";
	return $this->get_value($sql);
    }
    

    private function documentShipmentEventAdd($doc_id){
	$DocumentItems=$this->Hub->load_model("DocumentItems");
	$head=$DocumentItems->headGet( $doc_id );
	$event=[
	    'doc_id'=>$doc_id,
	    'event_id'=>0,
	    'event_date'=>date("Y-m-d"),
	    'event_label'=>'Доставка',
	    'event_creator_user_id'=>$this->Hub->svar('user_id'),
	    'event_name'=>'Документ №'.$head->doc_num,
	    'event_descr'=>$head->doc_data,
	    'event_target'=>$this->Hub->pcomp('company_person')." (".$this->Hub->pcomp('label').")",
	    'event_place'=>$this->Hub->pcomp('company_address'),
	    'event_note'=>$this->Hub->pcomp('company_mobile'),
	    'event_status'=>'undone'
	];
	return $this->create('event_list', $event);
    }
    
    private function documentShipmentEventDelete( $doc_id ){
	$this->query("DELETE FROM event_list WHERE doc_id='$doc_id'  AND event_label LIKE '%Доставка%'");
    }
    
    public $documentHeadUpdate=["doc_id"=>"int","field"=>"string","value"=>"string"];
    public function documentHeadUpdate($doc_id,$field,$value){
	$DocumentItems=$this->Hub->load_model("DocumentItems");
	switch($field){
	    case 'is_commited':
		if( $value==1 ){
		    $DocumentItems->entryDocumentCommit( $doc_id );
		} else {
		    $DocumentItems->entryDocumentUncommit( $doc_id );
		}
		break;
	    case 'is_event_created':
		if( $value==1 ){
		    $this->documentShipmentEventAdd( $doc_id );
		} else {
		    $this->documentShipmentEventDelete( $doc_id );
		}
		break;
	    default:
		$DocumentItems->headUpdate($field,$value);
		break;
	}
	return $this->documentGet($doc_id);
    }
    
    public $documentEntryUpdate=['doc_id'=>'int','doc_entry_id'=>'int','product_code'=>'string','product_quantity'=>'int'];
    public function documentEntryUpdate( $doc_id, $doc_entry_id, $product_code, $product_quantity ){
	$DocumentItems=$this->Hub->load_model("DocumentItems");
	if( $doc_entry_id ){
	    $DocumentItems->entryUpdate($doc_id, $doc_entry_id, 'product_quantity', $product_quantity);
	} else {
	    $DocumentItems->entryAdd($product_code,$product_quantity);
	}
	return $this->documentGet($doc_id);
    }
    
    public $documentEntryRemove=['doc_id'=>'int', 'doc_entry_id'=>'int'];
    public function documentEntryRemove($doc_id, $doc_entry_id){
	$DocumentItems=$this->Hub->load_model("DocumentItems");
	$DocumentItems->entryDeleteArray($doc_id,[[$doc_entry_id]]);
	return $this->documentGet($doc_id);
    }
    
    public $documentDiscountsGet=['passive_company_id'=>['int',0]];
    public function documentDiscountsGet($passive_company_id){
	$Company=$this->Hub->load_model("Company");
	$Company->selectPassiveCompany($passive_company_id);
	return $Company->companyPrefsGet();
    }
}
