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
    
    
    private function entriesTmpCreate( $doc_id ){
	$this->documentSelect($doc_id);
	$doc_vat_ratio=1+$this->doc('vat_rate')/100;
	$signs_after_dot=$this->doc('signs_after_dot');
	$native_curr=($this->Hub->pcomp('curr_code') == $this->Hub->acomp('curr_code'))?1:0;
	$curr_correction=$native_curr?1:1/$this->doc('doc_ratio');
	//$company_lang = $this->Hub->pcomp('language');

        $this->query("DROP TEMPORARY TABLE IF EXISTS tmp_doc_entries");
        $sql="CREATE TEMPORARY TABLE tmp_doc_entries ( INDEX(product_code) ) ENGINE=MyISAM AS (
                SELECT 
                    *,
                    ROUND(product_price_vatless*product_quantity,2) product_sum_vatless,
                    ROUND(product_price_total*product_quantity,2) product_sum_total
                FROM
                (SELECT
                    de.*,
                    ru product_name,
                    ROUND(invoice_price * $curr_correction, $signs_after_dot) AS product_price_vatless,
                    ROUND(invoice_price * $curr_correction * $doc_vat_ratio, $signs_after_dot) AS product_price_total,
		    invoice_price<(self_price-0.01) is_loss,
		    product_quantity*product_weight weight,
                    product_quantity*product_volume volume,
                    CHK_ENTRY(doc_entry_id) AS row_status,
                    product_unit,
                    product_uktzet
                FROM
                    document_list
                        JOIN
                    document_entries de USING(doc_id)
                        JOIN 
                    prod_list pl USING(product_code)
                WHERE
                    doc_id='$doc_id'
                ORDER BY pl.product_code) t
                )";
        $this->query($sql);
    }
    
    
    
    private function entriesFooterFetch(){
	$curr_code=$this->Hub->pcomp('curr_code');
	$curr_symbol=$this->get_value("SELECT curr_symbol FROM curr_list WHERE curr_code='$curr_code'");
	$sql="SELECT
	    ROUND(SUM(weight),2) total_weight,
	    ROUND(SUM(volume),2) total_volume,
	    SUM(product_sum_vatless) vatless,
	    SUM(product_sum_total) total,
	    SUM(product_sum_total-product_sum_vatless) vat,
	    SUM(ROUND(product_quantity*self_price,2)) self,
	    '$curr_symbol' curr_symbol
	FROM tmp_doc_entries";
	return $this->get_row($sql);
    }
    
    public $entriesFetch=['doc_id'=>'int','offset'=>'int','limit'=>'int'];
    public function entriesFetch($doc_id,$offset,$limit){
	$this->entriesTmpCreate( $doc_id );
	
	$rows=$this->get_list("SELECT * FROM tmp_doc_entries LIMIT $limit OFFSET $offset");
	$entries=[
	    'hasmorerows'=>0,
	    'rows'=>$rows
	];
	
	
	
	return [
		'entries'=>$entries,
		'footer'=>$this->entriesFooterFetch()
		];
    }
    
    public $entriesFooterGet=['doc_id'=>'int'];
    public function entriesFooterGet($doc_id){
	$this->entriesTmpCreate( $doc_id );
	return $this->entriesFooterFetch();
    }
}