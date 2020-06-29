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
    function init() {
        header("Content-type:text/plain");
        $this->Topic("documentChangeIsCommited")->subscribe('DocumentSell','documentChangeIsCommited');
    }
    
    
    
    public function index(){
	echo 'hello';
    }
    
    public function extensionGet(){
	return [
	    'script'=>$this->load->view('sell_script.js',[],true),
	    'head'=>$this->load->view('head.html',[],true),
	    'body'=>$this->load->view('body.html',[],true),
	    'foot'=>$this->load->view('foot.html',[],true),
	    'views'=>$this->load->view('views.html',[],true)
	];
    }
    //////////////////////////////////////////
    // DOCUMENT SECTION
    //////////////////////////////////////////
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
    
    public function documentCreate( int $doc_type=null, string $handler=null ){
	$doc_type=1;
	return parent::documentCreate( $doc_type, 'DocumentSell' );
    }
    
    public function documentUpdate( int $doc_id, object $document ){
        return parent::documentUpdate($doc_id,$document);
    }
    
    public function documentDelete( int $doc_id ){
        return parent::documentDelete($doc_id);
    }
    public function documentNameGet(){
        return "Расходный документ ".($this->doc('is_reclamation')?" (Возврат)":"")." №".$this->doc('doc_num');
    }
    //////////////////////////////////////////
    // DOCUMENT EVENTS SECTION
    //////////////////////////////////////////
    
    public function documentChangeIsCommited( $field, $new_is_commited ){
        
        echo "COOOOOMMIT $field, $new_is_commited";
        
        $this->entryListChangeCommit( $new_is_commited );
        return false;
    }
    //////////////////////////////////////////
    // HEAD SECTION
    //////////////////////////////////////////
    public function headFieldUpdate( int $doc_id, string $field, string $value=null ):string{
        return parent::headFieldUpdate( $doc_id, $field, $value );
    }
    //////////////////////////////////////////
    // BODY SECTION
    //////////////////////////////////////////
    public function bodyGet( int $doc_id ){
        return $this->entryListGet( $doc_id );
    }
    
    // ENTRY LIST FUNCTIONS
    public function entryListGet(int $doc_id, int $doc_entry_id = 0) {
        return parent::entryListGet($doc_id, $doc_entry_id);
    }
    /**
     * Function creates temporary table of entries
     * @param int $doc_id
     * @return bool
     */
    protected $entryListCreated=false;
    protected function entryListCreate( int $doc_id, int $doc_entry_id=0 ){
        if( $this->entryListCreated ){
            return true;
        }
        $this->documentSelect($doc_id);
        
        $entry_filter=$doc_entry_id?" AND doc_entry_id=$doc_entry_id":"";
        $doc_curr_correction=$this->documentCurrCorrectionGet();
        $doc_vat_ratio=$this->doc('vat_rate')/100+1;
        $doc_lang=$this->doc('pcomp')->language??'ru';
        $sql_create="CREATE TEMPORARY TABLE tmp_entry_list AS (
            SELECT
                *,
                IF(use_vatless_price,entry_price_vatless,entry_price_total) entry_price,
                IF(use_vatless_price,entry_sum_vatless,entry_sum_total) entry_sum
            FROM
                (SELECT
                    doc_entry_id,
                    ROUND(invoice_price * $doc_curr_correction, 2) AS entry_price_vatless,
                    ROUND(invoice_price * $doc_curr_correction * product_quantity,2) entry_sum_vatless,
                    ROUND(invoice_price * $doc_curr_correction * $doc_vat_ratio, 2) AS entry_price_total,
                    ROUND(invoice_price * $doc_curr_correction * $doc_vat_ratio * product_quantity,2) entry_sum_total,
                    ROUND(self_price,2) self_price,
                    ROUND(breakeven_price,2) breakeven_price,
                    product_quantity*product_weight entry_weight_total,
                    product_quantity*product_volume entry_volume_total,
                    product_quantity*1 product_quantity,
                    party_label,
                    pl.product_id,
                    pl.product_code,
                    pl.$doc_lang product_name,
                    pl.product_unit,
                    pl.product_article,
                    pl.analyse_origin,
                    pl.analyse_class,
                    dl.use_vatless_price,
                    CHK_ENTRY(doc_entry_id) AS row_status
                FROM
                    document_list dl
                        JOIN
                    document_entries de USING(doc_id)
                        JOIN 
                    prod_list pl USING(product_code)
                WHERE
                    doc_id='$doc_id'
                    $entry_filter) entry_list)";
        $this->query($sql_create);
        $this->entryListCreated=true;
        return true;
    }
    /**
     * Bulk update of document entries
     * @param int $doc_id
     * @param array $entry_list
     * @return type
     */
    public function entryListUpdate(int $doc_id, array $entry_list) {
        return parent::entryListUpdate($doc_id, $entry_list);
    }
    /**
     * Bulk delete of entries
     * @param int $doc_id
     * @param array $entry_id_list
     */
    public function entryListDelete(int $doc_id, array $doc_entry_ids) {
        parent::entryListDelete( $doc_id, $doc_entry_ids );
    }
    /**
     * Bulk commit/uncommit entries
     * @param bool $new_is_commited
     * @return boolean
     */
    private function entryListChangeCommit( bool $new_is_commited ){
        $doc_id=$this->doc('doc_id');
        $current_is_commited=$this->doc('is_commited');
        if( $new_is_commited==$current_is_commited ){
            return true;
        }
        $entry_list=$this->entryListGet($doc_id);
        $this->db_transaction_start();
        $this->doc('is_commited',$new_is_commited);
        foreach($entry_list as $entry){
            if( $new_is_commited ){
                $old_entry_data=(object)[
                    'product_quantity'=>0
                ];
            } else {
                $old_entry_data=(object)[
                    'product_quantity'=>$entry->product_quantity*2
                ];
            }
            $change_ok=$this->entryChange($entry->doc_entry_id, $entry, $old_entry_data);
            if( !$change_ok ){
                $this->db_transaction_rollback();
                return false;
            }
        }
        if( $this->isCommited() ){
            $this->transUpdate();
        }
        $this->db_transaction_commit();
        return false;
    }
    //ENTRY FUNCTIONS
    
    /**
     * Get entry record by id
     * @param int $doc_entry_id
     * @return type
     */
    public function entryGet( int $doc_entry_id ){
	return parent::entryGet($doc_entry_id);
    }
    
    /**
     * Creates entry record in document
     * @param int $doc_id
     * @param object $entry
     * @return type
     */
    public function entryCreate( int $doc_id, object $entry ){
        $this->documentSelect($doc_id);
        $pcomp_id=$this->doc('passive_company_id');
        $usd_ratio=$this->doc('doc_ratio');
        $entry->entry_price=$this->get_value("SELECT GET_SELL_PRICE({$entry->product_code},{$pcomp_id},{$usd_ratio})")??0;
        return parent::entryCreate($doc_id, $entry);
    }
    
    /**
     * Makes changes to entry depend on commitment status. 
     * Must be called within transaction
     * 
     * @param int $doc_entry_id
     * @param object $new_entry_data
     * @param object $current_entry_data
     */
    protected function entrySave( int $doc_entry_id, object $new_entry_data, object $current_entry_data=null ){
        if( isset($new_entry_data->entry_price) ){
            $vat_correction=$this->doc('use_vatless_price')?$this->doc('vat_rate')/100+1:1;
            $new_entry_data->entry_price_vatless=$new_entry_data->entry_price/$vat_correction;
            unset($new_entry_data->entry_price);
        }
        if( isset($new_entry_data->entry_price_vatless) ){
            $doc_curr_correction=$this->documentCurrCorrectionGet();
            $new_entry_data->invoice_price=$new_entry_data->entry_price_vatless/$doc_curr_correction;
            unset($new_entry_data->entry_price_vatless);
        }
        $update_ok=$this->update('document_entries',$new_entry_data,['doc_entry_id'=>$doc_entry_id]);
        $error = $this->db->error();
        if($error['code']==1452){
            $this->db_transaction_rollback();
	    throw new Exception("product_code_unknown",424);//Failed Dependency
	} else 
	if($error['code']==1062){
            $this->db_transaction_rollback();
	    throw new Exception("already_exists",409);//Conflict
	} else 
	if($error['code']!=0){
            $this->db_transaction_rollback();
            throw new Exception($error['message'].' '.$this->db->last_query(),500);//Internal Server Error
	}        
        if( $new_entry_data->product_quantity??false && $this->isCommited() ){
            $product_delta_quantity=$current_entry_data->product_quantity - $new_entry_data->product_quantity;
            $product_code=$new_entry_data->product_code??$current_entry_data->product_code;
            $stock_id=1;
            $Stock=$this->Hub->load_model("Stock");
            $stock_ok=$Stock->productQuantityModify( $product_code, $product_delta_quantity, $stock_id  );
            if( !$stock_ok ){
                $this->db_transaction_rollback();
                throw new Exception("product_stock_error",507);//Insufficient Storage
            }
        }
        return $update_ok;
    }
    /**
     * Updates entry data
     * @param int $doc_entry_id
     * @param object $new_entry_data
     * @return boolean
     * @throws Exception
     */
    public function entryUpdate( int $doc_entry_id, object $new_entry_data ){
        return parent::entryUpdate($doc_entry_id, $new_entry_data);
    }
    
    
    public function entryAbsentSplit( int $old_doc_id, string $new_doc_comment ){
	$this->Hub->set_level(2);
	$this->documentSelect($old_doc_id);
        if( $this->isCommited() ||  $this->doc('doc_type')!=1 ){
            return false;
        }
        $new_doc_id=$this->documentCreate( 1 );
        $this->entryAbsentSplitMove( $new_doc_id, $old_doc_id );
        //$this->duplicateHead($new_doc_id, $old_doc_id);
        $this->documentSelect($new_doc_id);
        $this->doc('doc_data',$new_doc_comment);
        return $new_doc_id;
    }
    
    private function entryAbsentSplitMove( $new_doc_id, $old_doc_id ){
        $sql="SELECT 
            doc_entry_id,
            de.product_code,
            GREATEST(se.product_quantity - se.product_reserved,0) old_product_quantity,
            GREATEST(de.product_quantity - se.product_quantity + se.product_reserved,0) new_product_quantity,
            de.self_price,
            de.party_label,
            de.invoice_price
        FROM 
            document_entries de
                JOIN 
            stock_entries se USING(product_code)
        WHERE 
            doc_id='$old_doc_id'
            AND de.product_quantity > (se.product_quantity-se.product_reserved)";
	$old_entries=$this->get_list($sql);
	foreach($old_entries as $entry){
            $old_entry=[
                'product_quantity'=>$entry->old_product_quantity
            ];
            if($entry->old_product_quantity>0){
                $this->update("document_entries",$old_entry,['doc_entry_id'=>$entry->doc_entry_id]);
            } else {
                $this->delete("document_entries",['doc_entry_id'=>$entry->doc_entry_id]);
            }
            if($entry->new_product_quantity>0){
                $new_entry=[
                    'doc_id'=>$new_doc_id,
                    'product_code'=>$entry->product_code,
                    'product_quantity'=>$entry->new_product_quantity,
                    'self_price'=>$entry->self_price,
                    'party_label'=>$entry->party_label,
                    'invoice_price'=>$entry->invoice_price
                ];
                $this->create("document_entries",$new_entry);
            }
	}
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

    
    public function entryUpdate222(int $doc_id, int $doc_entry_id, string $field, string $value){
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

    public function entryDelete2222( int $doc_id, array $doc_entry_ids){
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
        $this->query($sql);
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
    
    protected function getProductSellSelfPrice( $product_code, $invoice_qty, $fdate ) {
        return $this->Hub->get_row("SELECT LEFTOVER_CALC('$product_code','$fdate','$invoice_qty','all')",0);
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