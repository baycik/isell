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
    
    public $view=[];
    public function view(){
	$path = '';
	foreach (func_get_args() as $chunk) {
	    $path.= $chunk;
	}
	$this->load->view($path);
    }
    
    public $doclistGet=['date'=>'([0-9\-]+)','clientFilter'=>'string'];
    public function doclistGet($date,$clientFilter){
	return [
	    'sell'=>$this->getList($date,$clientFilter,'1'),
	    'buy'=>$this->getList($date,$clientFilter,'2')
	];
    }
    private function getList($date,$clientFilter,$doc_type){
	$sql="SELECT
		dl.*,
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
		AND label LIKE '%$clientFilter%'
	    ORDER BY doc_type
	    ";
	return $this->get_list($sql);
    }
}
