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
    private $errtype='ok';
    private $errmsg='';
    public function index(){
	echo 'hello';
    }
    public $extensionGet=[];
    public function extensionGet(){
	return [
	    'script'=>$this->load->view('sell_script.js',[],true),
	    'head'=>$this->load->view('head.html',[],true),
	    'body'=>$this->load->view('body.html',[],true),
	    'foot'=>$this->load->view('foot.html',[],true),
	    'views'=>$this->load->view('views.html',[],true)
	];
    }
    
    public function documentAdd( $doc_type=null ){
	$doc_type='sell';
	return parent::documentAdd($doc_type);
    }
    public $documentGet=['doc_id'=>'int','parts_to_load'=>'json'];
    public function documentGet($doc_id,$parts_to_load){
	$document=[];
	if( in_array("head",$parts_to_load) ){
	    $document["head"]=$this->headGet($doc_id);
	}
	if( in_array("body",$parts_to_load) ){
	    $document["body"]=$this->bodyGet($doc_id);
	}
	if( in_array("foot",$parts_to_load) ){
	    $document["foot"]=$this->footGet($doc_id);
	}
	if( in_array("views",$parts_to_load) ){
	    $document["views"]=$this->viewsGet($doc_id);
	}
	return $document;
    }
    private function bodyGet($doc_id){
	$this->entriesTmpCreate( $doc_id );
	return $this->get_list("SELECT * FROM tmp_doc_entries");
    }
    private function footGet(){
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
    private function viewsGet(){
	
    }
    /*
     * Entries section 
     */
    private function entriesTmpCreate( $doc_id ){
	$this->documentSelect($doc_id);
	$doc_vat_ratio=1+$this->doc('vat_rate')/100;
	//$signs_after_dot=$this->doc('signs_after_dot');
	$curr_correction=$this->documentCurrencyCorrectionGet();

        $this->query("DROP TEMPORARY TABLE IF EXISTS tmp_doc_entries");
        $sql="CREATE TEMPORARY TABLE tmp_doc_entries ( INDEX(product_code) ) ENGINE=MyISAM AS (
                SELECT 
                    *,
                    ROUND(corrected_price, 2) AS product_price_vatless,
                    ROUND(corrected_price * $doc_vat_ratio, 2) AS product_price_total,
                    ROUND(corrected_price * product_quantity,2) product_sum_vatless,
                    ROUND(corrected_price * $doc_vat_ratio * product_quantity,2) product_sum_total
                FROM
                (SELECT
                    de.*,
                    ru product_name,
		    invoice_price * $curr_correction corrected_price,
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
                ORDER BY pl.product_code) t)";
        $this->query($sql);
    }
    private function entryPriceNormalize( $price ){
	$doc_vat_ratio=1+$this->doc('vat_rate')/100;
	$curr_correction=$this->documentCurrencyCorrectionGet();
	return round($price,2)/$doc_vat_ratio/$curr_correction;	
    }
    public $entryAdd=['doc_id'=>'int','product_code'=>'string','product_quantity'=>'int'];
    public function entryAdd($doc_id,$product_code,$product_quantity){
	$this->documentSelect($doc_id);
	$pcomp_id=$this->doc('passive_company_id');
	$doc_ratio=$this->doc('doc_ratio');
	
	$this->query("START TRANSACTION");
	$this->query("INSERT INTO document_entries SET doc_id=$doc_id,product_code='$product_code',invoice_price=COALESCE(GET_PRICE('$product_code',$pcomp_id,'$doc_ratio'),0)",false);
	$error = $this->db->error();
	if($error['code']==1452){
	    $this->Hub->rcode("product_code_unknown");
	    return false;
	} else 
	if($error['code']==1062){
	    $this->Hub->rcode("already_exists");
	    return false;
	} else 
	if($error['code']!=0){
	    header("X-isell-type:error");
	    show_error($error['message'].' '.$this->db->last_query(), 500);
	}
	$doc_entry_id=$this->db->insert_id();
	$update_ok=$this->entryUpdate($doc_id,$doc_entry_id,'product_quantity',$product_quantity);
	
	if( !$update_ok ){
	    return false;
	}
	$this->query("COMMIT");
    }
    public $entryUpdate=['doc_id'=>'int','doc_entry_id'=>'int','field'=>'(product_price_total|product_quantity|product_sum_total)','value'=>'double'];
    public function entryUpdate($doc_id,$doc_entry_id,$field,$value){
	$this->documentSelect($doc_id);
	$entry_updated=[];
	$this->query("START TRANSACTION");	
	if( $field=='product_sum_total' ){
	    $entry_data=$this->entryGet($doc_entry_id);
	    $product_price_vatless=$this->entryPriceNormalize($value);
	    $entry_updated['invoice_price']=$product_price_vatless/$entry_data->product_quantity;	    
	} else
	if( $field=='product_price_total' ){
	    $product_price_vatless=$this->entryPriceNormalize($value);
	    $entry_updated['invoice_price']=$product_price_vatless;
	} else
	if( $field=='product_quantity' && $this->doc('is_commited') ){//IF document is already commited then commit entry. If commit is failed then abort update
	    $entry_calculated=$this->entryCommit($doc_entry_id,$value);
	    if( !$entry_calculated ){
		return false;
	    }
	    if( $value<=0 ){//quantity must be more than zero
		$this->Hub->rcode('quantity_wrong');
		return false;
	    }
	    $entry_updated['product_quantity']=$value;
	    $entry_updated['self_price']=$entry_calculated->self_price;
	    $entry_updated['party_label']=$entry_calculated->first_party_label;
	}
	$update_ok=$this->update("document_entries",$entry_updated,['doc_entry_id'=>$doc_entry_id]);
	if( !$update_ok ){
	    return false;
	}
	$this->query("COMMIT");
	return true;
    }
    public $entryDelete=['doc_id'=>'int','doc_entry_ids'=>'json'];
    public function entryDelete($doc_id,$doc_entry_ids){
	return parent::entryDelete($doc_id, $doc_entry_ids);
    }    
    /*
     * COMMIT SECTION
     */
    private function stockLeftoverGet($product_code){
	return $this->get_value("SELECT product_quantity FROM stock_entries WHERE product_code='$product_code'");
    }
    private function stockLeftoverSet($product_code,$leftover,$self_price){
	return $this->update('stock_entries',['product_quantity'=>$leftover,'self_price'=>$self_price],['product_code'=>$product_code]);
    }
    private function entryGet($doc_entry_id){
	$sql="SELECT * FROM document_entries WHERE doc_entry_id='$doc_entry_id'";
	return $this->get_row($sql);
    }
    private function entryUncommit($doc_entry_id){
	return $this->entryCommit($doc_entry_id, 0);
    }
    private function entryCommit($doc_entry_id,$new_product_quantity=NULL){
	if( !$this->doc('is_commited') ){
	    return true;
	}
	$this->documentSetLevel(2);
	$entry_data=$this->entryGet($doc_entry_id);
	if( !$entry_data ){
	    $this->Hub->msg("Строка уже удалена");
	    return false;	    
	}
	$stock_lefover=$this->stockLeftoverGet($entry_data->product_code);
	$substract_quantity=$entry_data->product_quantity;
	if( $new_product_quantity!==NULL ){
	    $substract_quantity=$new_product_quantity;
	    $stock_lefover=$stock_lefover+$entry_data->product_quantity;
	}
	if( $substract_quantity>$stock_lefover ){
	    $this->Hub->rcode("not_enough");
	    $this->Hub->msg($substract_quantity-$stock_lefover);
	    return false;
	}
	$this->stockLeftoverSet($entry_data->product_code,$stock_lefover-$substract_quantity,0);
	$this->entryOriginsFind($entry_data->product_code,$stock_lefover);
	$entry_calculated=$this->entryOriginsCalc($substract_quantity);
	return $entry_calculated;
    }
    /*
     * Find entries from buy documents wich are original (correspond) to commited sell entry. 
     * Orders by date entries from newest to oldest
     */
    private function entryOriginsFind($product_code,$stock_leftover){
	$this->query("SET @total_buyed:=0,@pcode='$product_code',@stock_leftover:='$stock_leftover';");
	$this->query("DROP TEMPORARY TABLE IF EXISTS tmp_original_entries;");#TEMPORARY
	$this->query("CREATE TEMPORARY TABLE tmp_original_entries AS 
			SELECT 
			    *,
			    LEAST(@stock_leftover - @total_buyed,product_quantity) party_quantity,
			    @total_buyed:=@total_buyed + product_quantity tb
			FROM
			    (SELECT 
				cstamp,
				party_label,
				self_price,
				product_quantity
			    FROM
				document_entries
				    JOIN
				document_list USING (doc_id)
			    WHERE
				product_code = @pcode
				    AND (doc_type = '2' OR doc_type = 'buy')
				    AND is_reclamation = 0
				    AND is_commited = 1
			    ORDER BY cstamp DESC) t
			WHERE
			    @total_buyed < @stock_leftover");
    }
    /*
     * Finds party_label from buy document origin entries and calculated avg self price.
     * Before orders entries from oldest to newest
     */
    private function entryOriginsCalc($product_quantity){
	$this->query("SET @sold_quantity:=$product_quantity,@total_sold:=0,@first_party_label:='';");
	$sql="SELECT 
		first_party_label, ROUND(SUM(self_sum) / @sold_quantity,2) self_price
	    FROM
		(SELECT 
		    LEAST(@sold_quantity - @total_sold, party_quantity) * self_price self_sum,
			@total_sold:=@total_sold + party_quantity ts,
			@first_party_label:=IF(@first_party_label, @first_party_label, party_label) first_party_label
		FROM
		    (SELECT 
		    *
		FROM
		    tmp_original_entries
		ORDER BY cstamp) t
		WHERE
		    @sold_quantity > @total_sold) t2;";
	return $this->get_row($sql);
    }
    /*
     * Suggestion
     */
    public $suggestFetch=['q'=>'string'];
    public function suggestFetch($q){
	if( $q=='' ){
	    return [];
	}
	$company_lang = $this->Hub->pcomp('language');
	if( !$company_lang ){
	    $company_lang='ru';
	}
	$where=['is_service=0'];
	$clues=  explode(' ', $q);
	foreach ($clues as $clue) {
            if ($clue == ''){
                continue;
	    }
            $where[]="(product_code LIKE '%$clue%' OR $company_lang LIKE '%$clue%')";
        }
	$sql="
	    SELECT
		product_code,
		$company_lang label,
		product_spack,
		product_quantity
	    FROM
		prod_list
		    JOIN
		stock_entries USING(product_code)
	    WHERE
		".( implode(' AND ',$where) )."
		    ORDER BY fetch_count-DATEDIFF(NOW(),fetch_stamp) DESC, product_code
	    LIMIT 15
	    ";
	return $this->get_list($sql);
    }
    
    public $pickerListFetch=['parent_id'=>'int','offset'=>['int',0],'limit'=>['int',10],'sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json'];
    public function pickerListFetch($parent_id,$offset,$limit,$sortby,$sortdir,$filter){
	$pcomp_id=$this->Hub->pcomp('company_id');
	$doc_ratio=$this->Hub->pref('usd_ratio');
	
	$having=$this->makeFilter($filter);
	$order='';
	$where='';
	if( $parent_id ){
	    $branch_ids=$this->treeGetSub('stock_tree',$parent_id);
	    $where="WHERE se.parent_id IN (".implode(',',$branch_ids).")";
	}
	if( $sortby ){
	    $order="ORDER BY $sortby $sortdir";
	}
	$sql="SELECT 
		pl.product_code,
		ru,
		product_quantity,
		ROUND(GET_PRICE(product_code,'$pcomp_id','$doc_ratio'),2) price,
		product_spack
	    FROM 
		stock_entries se
		    JOIN
		prod_list pl USING(product_code)
	    $where 
	    HAVING $having 
	    $order
	    LIMIT $limit OFFSET $offset";
	return $this->get_list($sql);
    }
}