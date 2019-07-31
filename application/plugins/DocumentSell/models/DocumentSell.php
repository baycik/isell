<?php
/* User Level: 1
 * Group Name: Документ
 * Plugin Name: Расходный документ
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
	$doc_type='1';
	return parent::documentAdd($doc_type);
    }
    
    public $documentDelete=['doc_id'=>'int'];
    public function documentDelete( $doc_id ){
        return parent::documentDelete($doc_id);
    }
    
    public function documentGet(int $doc_id, array $parts_to_load){
	$this->documentSelect($doc_id);
	$doc_type=$this->doc('doc_type');
	if( $doc_type!='1' && $doc_type!=1 ){
            return parent::headGet($doc_id);
	}
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
    
    public function documentUpdate(int $doc_id, string $field, string $value){
        $this->documentSelect($doc_id);
        parent::documentUpdate($doc_id,$field,$value); 
        
        
	switch($field){
            case 'doc_status_id':
		return $this->documentSetStatus($doc_id,$value);
            default:
                return parent::documentUpdate($doc_id,$field,$value); 
	}
    }
    
    protected function bodyGet($doc_id){
	$this->entriesTmpCreate( $doc_id );
	if( $this->doc('use_vatless_price') ){
            $sql="SELECT *, product_price_vatless product_price, product_sum_vatless product_sum FROM tmp_doc_entries";
        } else {
            $sql="SELECT *, product_price_total product_price, product_sum_total product_sum FROM tmp_doc_entries";
        }
        return $this->get_list($sql);
    }
    private function viewsGet($doc_id){
        $DocumentView = $this->Hub->load_model("DocumentView");
        return $DocumentView->viewListFetch($doc_id);
    }
    /*
     * Entries section 
     */

    private function entryPriceNormalize( $price ){
	$doc_vat_ratio=1+$this->doc('vat_rate')/100;
	$curr_correction=$this->documentCurrencyCorrectionGet();
	return round($price,2)/$doc_vat_ratio/$curr_correction;	
    }
    public $entryAdd=['doc_id'=>'int','product_code'=>'string','product_quantity'=>'int'];
    public function entryAdd($doc_id,$product_code,$product_quantity){
	$this->documentSelect($doc_id);
	$pcomp_id=$this->doc('passive_company_id');
        if(!isset($pcomp_id)){
            $doc_id = $this->documentAdd();
            $this->documentSelect($doc_id);
            $pcomp_id=$this->doc('passive_company_id');
        }
        
	$doc_ratio=$this->doc('doc_ratio');
	$this->db_transaction_start();
        $this->query("INSERT INTO document_entries SET doc_id=$doc_id,product_code='$product_code',invoice_price=COALESCE(GET_PRICE('$product_code',$pcomp_id,'$doc_ratio'),0)",false);
	$error = $this->db->error();
	if($error['code']==1452){
	    $this->Hub->msg("product_code_unknown");
	    return false;
	} else 
	if($error['code']==1062){
	    $this->Hub->msg("already_exists");
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
	$this->db_transaction_commit();
    }
    
    public function entryUpdate(int $doc_id, int $doc_entry_id, string $field, string $value){
	$this->documentSelect($doc_id);
	$entry_updated=[];
	$this->db_transaction_start();	
	if( $field=='product_sum_total' ){
	    $entry_data=$this->entryGet($doc_entry_id);
	    $product_price_vatless=$this->entryPriceNormalize($value);
	    $entry_updated['invoice_price']=$product_price_vatless/$entry_data->product_quantity;	    
	} else
	if( $field=='product_price_total' ){
	    $product_price_vatless=$this->entryPriceNormalize($value);
	    $entry_updated['invoice_price']=$product_price_vatless;
	} else
	if( $field=='party_label' ){
	    $entry_updated['party_label']=$value;
	} else 
        if( $field == 'product_price'){
                $entry_updated['invoice_price']=$value;
         } else    
	if( $field=='product_quantity' ){//IF document is already commited then commit entry. If commit is failed then abort update
	    if( $value<=0 ){//quantity must be more than zero
		$this->Hub->msg('quantity_wrong');
		return false;
	    }
	    if( $this->doc('is_commited') ){
		$commit_ok=$this->entryCommit($doc_entry_id,$value);
		if( !$commit_ok){
		    return false;
		}
	    } else {
		$entry_updated['self_price']=0;
		$entry_updated['party_label']='';		
	    }
	    //$this->Hub->msg("entry_calculated $entry_calculated");
	    $entry_updated['product_quantity']=$value;
	}
	$update_ok=$this->update("document_entries",$entry_updated,['doc_entry_id'=>$doc_entry_id]);
	if( !$update_ok ){
            print_r($entry_updated);
	    return false;
	}
        $this->transUpdate();
	$this->db_transaction_commit();
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
    private function stockLeftoverSet($product_code,$leftover,$self_price,$party_label){
	return $this->update('stock_entries',['product_quantity'=>$leftover,'self_price'=>$self_price,'party_label'=>$party_label],['product_code'=>$product_code]);
    }
    private function entryGet($doc_entry_id){
	$sql="SELECT * FROM document_entries WHERE doc_entry_id='$doc_entry_id'";
	return $this->get_row($sql);
    }
    protected function entryUncommit($doc_entry_id){
	return $this->entryCommit($doc_entry_id, 0);
    }
    protected function entryCommit($doc_entry_id,$new_product_quantity=NULL){
	$this->documentSetLevel(2);
	$entry_data=$this->entryGet($doc_entry_id);
	if( !$entry_data ){
	    $this->Hub->msg("entry_deleted_before");
	    return false;	    
	}
	$stock_lefover=$this->stockLeftoverGet($entry_data->product_code);
	$substract_quantity=$entry_data->product_quantity;
	if( $new_product_quantity!==NULL ){
	    $substract_quantity=$new_product_quantity;
	    $stock_lefover=$stock_lefover+$entry_data->product_quantity;
	}
	if( $substract_quantity>$stock_lefover ){
	    $this->Hub->msg("not_enough");
	    $this->Hub->msg($substract_quantity-$stock_lefover);
	    return false;
	}
	$this->entryOriginsFind($entry_data->product_code,$stock_lefover);
	$entry_calculated=$this->entryOriginsCalc($substract_quantity);
	$this->update("document_entries",$entry_calculated,['doc_entry_id'=>$doc_entry_id]);
	
	$new_leftover=$stock_lefover-$substract_quantity;
	$new_leftover_calculated=$this->entryOriginsCalc($new_leftover,'DESC');
	$this->stockLeftoverSet($entry_data->product_code,$new_leftover,$new_leftover_calculated->self_price,$new_leftover_calculated->party_label);
	return true;
    }
    public function entryBreakevenPriceUpdate( int $doc_entry_id=null, int $doc_id=null ){
        if( !$doc_entry_id&&!$doc_id ){
            return;
        }
        $this->documentSelect($doc_id);
        $pcomp_id=$this->doc('passive_company_id');
        $usd_ratio=$this->doc('doc_ratio');
        $doc_type=$this->doc('doc_type');
        if( $doc_type!=1 ){
            echo '$doc_type!=1';
            return;
        }
        if( $doc_entry_id ){
            $where="doc_entry_id=$doc_entry_id";
        } else {
            $where="doc_id=$doc_id";
        }
        if( $this->Hub->pcomp('skip_breakeven_check') ){
            $sql="UPDATE 
                    document_entries 
                SET 
                    breakeven_price = 0
                WHERE 
                    $where";
        } else {
            $sql="UPDATE 
                    document_entries 
                SET 
                    breakeven_price = ROUND(GET_BREAKEVEN_PRICE(product_code,'$pcomp_id','$usd_ratio',self_price),2)
                WHERE 
                    $where";
        }
;        $this->query($sql);
    }
    
    /*
     * Find entries from buy documents wich are original (correspond) to commited sell entry. 
     * Orders by date entries from newest to oldest
     */
    private function entryOriginsFind($product_code,$stock_leftover){
        //FROGOT ABOUT FINAL DATE OF DOCUMENT UP TO THAT ENTRIES MUST BE SEARCHED???
        
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
                                    AND notcount=0
			    ORDER BY cstamp DESC) t
			WHERE
			    @total_buyed <= @stock_leftover");
    }
    /*
     * Finds party_label from buy document origin entries and calculated avg self price.
     * Before orders entries from oldest to newest
     */
    private function entryOriginsCalc($product_quantity,$sort_order='ASC'){
	$this->query("SET @sold_quantity:=$product_quantity,@total_sold:=0,@first_party_label:='';");
	$sql="SELECT 
		@first_party_label party_label, ROUND(SUM(self_sum) / @sold_quantity,2) self_price
	    FROM
		(SELECT 
		    LEAST(@sold_quantity - @total_sold, party_quantity) * self_price self_sum,
			@total_sold:=@total_sold + party_quantity ts,
			@first_party_label:=IF(@first_party_label<>'', @first_party_label, party_label) first_party_label
		FROM
		    (SELECT 
		    *
		FROM
		    tmp_original_entries
		ORDER BY cstamp $sort_order) t
		WHERE
		    @sold_quantity > @total_sold) t2;";
	return $this->get_row($sql);
    }
    protected function getProductSellSelfPrice($product_code, $invoice_qty,$fdate) {
        return $this->Base->get_row("SELECT LEFTOVER_CALC('$product_code','$fdate','$invoice_qty','all')",0);

//	$this->Base->LoadClass('StockOld');
//	$stock_self = $this->Base->StockOld->getEntrySelfPrice($product_code);
//	if ($stock_self > 0)
//	    return $stock_self;
//	/*
//	 * IF self price is not set
//	 * qty=0 or something else set
//	 * selfPrice as current buy price
//	 */
//	$price = $this->getRawProductPrice($product_code, $this->doc('doc_ratio'));
//	$price_self = $price['buy'] ? $price['buy'] : $price['sell'];
//	//$this->Base->StockOld->setEntrySelfPrice($product_code, $price_self);
//	return $price_self;
    }

    
    
    /*----------------------------
     * OTHER
     ------------------------*/
    

    public function reservedTaskAdd($doc_id){
        $this->Hub->load_model('Events');
        $event=$this->Hub->Events->eventGetByDocId($doc_id);
        if( $event ){
            return $this->Hub->Events->eventDelete( $event->event_id );
        }
        $user_id=$this->Hub->svar('user_id');
        if( $this->doc('doc_type')==1 ){
            $day_limit=$this->Hub->pref('reserved_limit');
        } else {
            $day_limit=$this->Hub->pref('awaiting_limit');
        }
        $stamp=time()+60*60*24*($day_limit?$day_limit:3);
        $alert="Счет №".$this->doc('doc_num')." для ".$this->Hub->pcomp('company_name')." снят с резерва";
        $name="Снятие с резерва";
        $description="$name счета №".$this->doc('doc_num')." для ".$this->Hub->pcomp('company_name');
        $event=[
            'doc_id'=>$doc_id,
            'event_name'=>$name,
            'event_status'=>'undone',
            'event_label'=>'-TASK-',
            'event_date'=>date("Y-m-d H:i:s",$stamp),
            'event_descr'=>$description,
            'event_program'=>json_encode([
                'commands'=>[
                    [
                        'model'=>'DocumentCore',
                        'method'=>'setStatusByCode',
                        'arguments'=>[$doc_id,'created']
                    ],
                    [
                        'model'=>'Chat',
                        'method'=>'addMessage',
                        'arguments'=>[$user_id,$alert]
                    ]
                ]
            ])
        ];
        return $this->Hub->Events->eventCreate($event);
    }
    
    public function reservedTaskRemove($doc_id){
        $this->Hub->load_model('Events');
        $event=$this->Hub->Events->eventGetByDocId($doc_id);
        if( $event ){
            return $this->Hub->Events->eventDelete( $event->event_id );
        }
        return false;
    }
    
    public function reservedCountUpdate(){
        $sql="
        UPDATE 
            stock_entries
                LEFT JOIN
            (SELECT 
                product_code,
                SUM(IF(doc_type = 1, de.product_quantity, 0)) reserved,
                SUM(IF(doc_type = 2, de.product_quantity, 0)) awaiting
            FROM
                document_entries de
            JOIN document_list USING (doc_id)
            JOIN document_status_list dsl USING (doc_status_id)
            WHERE
                dsl.status_code = 'reserved'
            GROUP BY product_code) reserve USING (product_code) 
        SET 
            product_reserved = COALESCE(reserved,0),
            product_awaiting = COALESCE(awaiting,0)
        WHERE 
	product_reserved IS NOT NULL 
        OR product_awaiting IS NOT NULL
	OR reserved IS NOT NULL 
        OR awaiting IS NOT NULL";
        return $this->query($sql);
    }
   
    
}