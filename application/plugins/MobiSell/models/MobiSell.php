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
    
    public $documentGet=["doc_id"=>"int"];
    public function documentGet($doc_id){
	$DocumentItems=$this->Hub->load_model("DocumentItems");
	return $DocumentItems->entryDocumentGet( $doc_id );
    }
}
