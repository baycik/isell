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
    
    public $suggest=['q'=>'string','parent_id'=>['int',0],'company_id'=>['int',0]];
    public function suggest($q,$parent_id,$company_id){
        $clues=explode(' ',$q);
	$usd_ratio=$this->Hub->pref('usd_ratio');
	$cases=[];
	if($parent_id){
	    $parent_ids=$this->treeGetSub('stock_tree',$parent_id);
	    $cases[]="(parent_id='".implode("' OR parent_id='",$parent_ids)."')";
	}
        foreach($clues as $clue){
            $cases[]="(product_code LIKE '%$clue%' OR ru LIKE '%$clue%')";
        }
        $where=implode(' AND ',$cases);
        $sql="
		SELECT
		    *,
		    GET_PRICE(code,$company_id,$usd_ratio) product_price
		FROM
		(SELECT 
                    product_code,
                    ru product_name,
                    product_spack,
                    product_quantity,
                    product_unit,
		    product_img
                FROM
                    prod_list
                JOIN
                    stock_entries se USING (product_code)
                WHERE $where
                ORDER BY fetch_count - DATEDIFF(NOW(), fetch_stamp) DESC, product_code
                LIMIT 20) t";
        return $this->get_list($sql);
    }
        
    public $documentGet=["doc_id"=>"int"];
    public function documentGet($doc_id){
	$DocumentItems=$this->Hub->load_model("DocumentItems");
	$document=$DocumentItems->entryDocumentGet( $doc_id );
	$document['head']=$DocumentItems->headGet( $doc_id );
	$document['head']->acomp_id=$this->Hub->acomp('company_id');
	$document['head']->acomp_label=$this->Hub->acomp('label');
	return $document;
    }
    
    public $documentSave=['document'=>'json'];
    public function documentSave( $document ){
	
    }
    
    public $documentSuggest=['q'=>'string','offset'=>['int',0]];
    public function documentSuggest($q,$offset){
	$clues=  explode(' ', $q);
	$company_lang = $this->Hub->pcomp('language');
	$where=array();
	foreach ($clues as $clue) {
            if ($clue == ''){
                continue;
	    }
            $where[]="(product_code LIKE '%$clue%' OR $company_lang LIKE '%$clue%')";
        }
	$sql="
	    SELECT
		product_code,
		$company_lang product_name,
		product_spack,
		product_quantity,
                product_img
	    FROM
		prod_list
		    JOIN
		stock_entries USING(product_code)
	    WHERE
		".( implode(' AND ',$where) )."
		    ORDER BY fetch_count-DATEDIFF(NOW(),fetch_stamp) DESC, product_code
	    LIMIT 15 OFFSET $offset
	    ";
	return $this->get_list($sql);	
    }
}
