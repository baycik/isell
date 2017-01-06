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
    public function documentAdd( $doc_type ){
	return parent::documentAdd('sell');
    }
}