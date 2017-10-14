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
    function __construct() {
	ini_set('zlib.output_compression_level', 6);
	ob_start("ob_gzhandler");
	parent::__construct();
    }
    
    public $index=[];
    public function index(){
	$this->view('index.html');
    }
    
    public $view=['string'];
    public function view($path){
	$this->load->view($path);
    }
    
    public $doclistGet=['type'=>'string','date'=>'([0-9\-]+)','offset'=>['int',0],'limit'=>['int',10], 'compFilter'=>'string'];
    public function doclistGet($type,$date,$offset,$limit,$compFilter){
	$doc_type=($type=='sell'?1:2);
	$sql="SELECT
		doc_id,
		dl.doc_num,
		is_commited,
		COALESCE(
		    (SELECT amount 
			FROM 
			    acc_trans 
				JOIN 
			    document_trans dt USING(trans_id)
			WHERE dt.doc_id=dl.doc_id 
			ORDER BY trans_id LIMIT 1
		    ),
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
	    ORDER BY doc_type
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
    
    public $documentGet=["doc_id"=>"int"];
    public function documentGet($doc_id){
	$DocumentItems=$this->Hub->load_model("DocumentItems");
	$document=$DocumentItems->entryDocumentGet( $doc_id );
	$document['head']=$DocumentItems->headGet( $doc_id );
	return $document;
    }
    
    public $documentHeadUpdate=["doc_id"=>"int","field"=>"string","value"=>"string"];
    public function documentHeadUpdate($doc_id,$field,$value){
	$DocumentItems=$this->Hub->load_model("DocumentItems");
	$DocumentItems->headUpdate($field,$value);
	
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
    
    public $documentSuggest=['q'=>'string','offset'=>['int',0],'doc_id'=>['int',0]];
    public function documentSuggest($q,$offset,$doc_id){
	$clues=  explode(' ', $q);
	
	$DocumentCore=$this->Hub->load_model("DocumentCore");
	$head=$DocumentCore->headGet( $doc_id );
	$where=[1];
	foreach ($clues as $clue) {
            if ($clue == ''){
                continue;
	    }
            $where[]="(product_code LIKE '%$clue%' OR ru LIKE '%$clue%')";
        }
	$sql="
	    SELECT
		product_code,
		ru product_name,
		product_spack,
		product_quantity leftover,
                product_img,
		product_unit,
		GET_PRICE(product_code,{$head->passive_company_id},{$head->doc_ratio}) product_price_total
	    FROM
		stock_entries
		    JOIN
		prod_list USING(product_code)
	    WHERE
		".( implode(' AND ',$where) )."
		    ORDER BY fetch_count-DATEDIFF(NOW(),fetch_stamp) DESC, product_code
	    LIMIT 10 OFFSET $offset
	    ";
	return $this->get_list($sql);	
    }
}
