<?php
require_once 'DocumentBase.php';
class DocumentTrans extends DocumentBase{
    private function transSelfCorrect(){
	
	do_action("document_".$this->doc('doc_type')."_update_trans",$this);
    }
    private function transChangeCurrRatio( $new_ratio ){
	$doc_id=$this->doc('doc_id');
	$old_ratio=$this->doc('doc_ratio');
	$change_ratio=$new_ratio/$old_ratio;
	$default_currency=$this->Base->acomp('curr_code');
	$price_label=$this->Base->pcomp('price_label');
	$sql="UPDATE
		document_entries de
		    JOIN
		price_list pl ON de.product_code=pl.product_code AND price_label='$price_label' AND curr_code<>'' AND curr_code<>'$default_currency'
	    SET
		invoice_price=ROUND(invoice_price*$change_ratio,2)
	    WHERE
		doc_id=$doc_id";
	$this->query($sql);
	$this->transCorrectSelf();
    }
    private function transChangeVatRate( $new_rate ){
	do_action("document_".$this->doc('doc_type')."_update_trans",$this);
    }
    private function transChangeNotcount($value){
	
    }
}