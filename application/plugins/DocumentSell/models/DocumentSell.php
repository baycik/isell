<?php
/* User Level: 1
 * Group Name: Документ
 * Plugin Name: DocumentSell
 * Version: 2017-01-01
 * Description: Документ продажи товара
 * Author: baycik 2017
 * Author URI: isellsoft.com
 * Trigger before: DocumentSell
 * 
 * Description of DocumentSell
 * This class handles all of sell documents
 * @author Baycik
 */
class DocumentSell extends DocumentBase{
    public function index(){
	echo 'hello';
    }
    public function documentAdd( $doc_type=null ){
	$doc_type='sell';
	return parent::documentAdd($doc_type);
    }
    public $headDataGet=['doc_id'=>'int'];
    public function headDataGet( $doc_id ){
	return parent::headDataGet($doc_id);
    }
    public function headFormGet(){
	return $this->load->view('DocumentSellForm.html',[],true);
    }
    public $headUpdate=['doc_id'=>'int','field'=>'string','value'=>'string'];
    public function headUpdate( $doc_id, $field, $value ){
	
    }
    
    public $entryListFetch=['doc_id'=>'int'];
    public function entryListFetch($doc_id){
	$sql="";
	
	return $this->get_list($sql);
    }
}