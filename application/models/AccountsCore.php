<?php
/**
 * ACCOUNTS SPECIFIC FUNCTIONS
 * Accounting processing
 *
 * @author Baycik
 */
include 'Catalog.php';
class AccountsCore extends Catalog{
    public $min_level=1;
    protected function getAccountProperties( $acc_code, $calc_balance=false, $use_passive_filter=false ) {
	$active_company_id=$this->Base->acomp('company_id');
	$default_curr_id=$this->Base->acomp('curr_id');
	$balance='';
	if( $calc_balance ){
	    $passive_filter="";
	    if( $use_passive_filter ){
		$passive_filter=" AND passive_company_id='".$this->Base->pcomp('company_id')."'";
	    }
	    $balance=",(
		SELECT 
		    SUM(ROUND(IF(at.acc_code=acc_debit_code,-amount,amount),2))
		FROM acc_trans
		WHERE (acc_debit_code=at.acc_code OR acc_credit_code=at.acc_code) AND active_company_id=$active_company_id $passive_filter)*IF(acc_type='P',1,-1) balance";
	}
        if( $use_passive_filter ){
            $acc_list=$this->Base->pcomp('company_acc_list');
            $is_favorite=($acc_list&&strpos($acc_list,$acc_code)!==false)?1:0;
        } else {
            $is_favorite="is_favorite";
        }
        
        $sql="SELECT
		* $balance, $is_favorite is_favorite
	    FROM 
		acc_tree at
		    JOIN curr_list cl ON IF(at.curr_id,cl.curr_id=at.curr_id,cl.curr_id='$default_curr_id')
            WHERE acc_code='$acc_code'";
        return $this->get_row($sql);
    }
    private function ledgerCreate( $acc_code, $using_alt_currency=false, $use_passive_filter=false ){
	$this->check($acc_code);
	$this->check($using_alt_currency,'bool');
	$active_company_id=$this->Base->acomp('company_id');
	$passive_filter="";
	if( $use_passive_filter ){
	    $passive_filter=" AND passive_company_id='".$this->Base->pcomp('company_id')."'";
	}
	
	$this->db->query("SET @acc_code:=?, @use_alt_amount=?;",[$acc_code,$using_alt_currency]);
	$this->db->query("DROP TEMPORARY TABLE IF EXISTS tmp_ledger;");
	$sql="CREATE TEMPORARY TABLE tmp_ledger ( INDEX(cstamp) ) ENGINE=MyISAM AS (
	    SELECT 
		trans_id,
		editable,
		nick,
		description,
		cstamp,
		passive_company_id,
		company_name,
		DATE_FORMAT(cstamp, '%d.%m.%Y') trans_date,
		CONCAT(code, ' ', descr) trans_status,
		CONCAT(acc_debit_code, ' ', acc_credit_code) trans_type,
		IF(@acc_code = acc_debit_code,
		    ROUND(IF(@use_alt_amount,amount_alt,amount),2),
		    0) debit,
		IF(@acc_code = acc_credit_code,
		    ROUND(IF(@use_alt_amount,amount_alt,amount),2),
		    0) credit
	    FROM
		acc_trans
		    JOIN
		companies_list ON company_id = passive_company_id
		    JOIN
		acc_trans_status USING (trans_status)
		    LEFT JOIN
		user_list ON user_id = modified_by
	    WHERE
		(@acc_code = acc_debit_code OR @acc_code = acc_credit_code)
		AND active_company_id='$active_company_id'
		    $passive_filter)";
	$this->query($sql);
    }
    private function ledgerGetSubtotals( $idate, $fdate, $having ){
	$sql="SELECT 
		    SUM(IF('$idate'>cstamp,debit-credit,0)) ibal,
		    SUM(IF('$idate'<cstamp AND cstamp<='$fdate',debit,0)) pdebit,
		    SUM(IF('$idate'<cstamp AND cstamp<='$fdate',credit,0)) pcredit,
		    SUM(IF(cstamp<='$fdate',debit-credit,0)) fbal
		FROM tmp_ledger
                WHERE $having";
	return $this->get_row($sql);
    }
    public function ledgerFetch( $acc_code, $idate='', $fdate='', $page=1, $rows=30, $use_passive_filter=false ){
	$this->check($idate,'\d\d\d\d-\d\d-\d\d');
	$this->check($fdate,'\d\d\d\d-\d\d-\d\d');
	$this->check($acc_code);
	$this->check($page,'int');
	$this->check($rows,'int');
	$idate.=' 00:00:00';
	$fdate.=' 23:59:59';
        
	$props=$this->getAccountProperties( $acc_code, false, $use_passive_filter );
	if( $use_passive_filter ){
	    $this->Base->set_level(1);
            $props->curr_id=$this->Base->pcomp('curr_id');
            $props->curr_symbol=$this->Base->pcomp('curr_symbol');
	} else {
	    $this->Base->set_level(3);
	}
	if( !$acc_code || !$idate || !$fdate ){
	    return [];
	}
	$using_alt_currency=false;
	if( $props->curr_id ){
	    $default_curr_id=$this->Base->acomp('curr_id');
	    $using_alt_currency=$default_curr_id!=$props->curr_id;
	}
	$this->ledgerCreate($acc_code, $using_alt_currency, $use_passive_filter );
	
	$having=$this->decodeFilterRules();
	$offset=$page>0?($page-1)*$rows:0;
	$sql="SELECT * FROM tmp_ledger 
		WHERE '$idate'<cstamp AND cstamp<='$fdate'
		HAVING $having
		ORDER BY cstamp DESC 
		LIMIT $rows OFFSET $offset";
	$result_rows=$this->get_list($sql);
	$total_estimate=$offset+(count($result_rows)==$rows?$rows+1:count($result_rows));
	
	$sub_totals=$this->ledgerGetSubtotals($idate, $fdate, $having);
	return ['rows'=>$result_rows,'total'=>$total_estimate,'props'=>$props,'sub_totals'=>$sub_totals,'using_alt_currency'=>$using_alt_currency];
    }
    public function ledgerPaymentFetch( $acc_code, $idate='', $fdate='', $page=1, $rows=30 ){
	return $this->ledgerFetch($acc_code, $idate, $fdate, $page, $rows, true);
    }
    public function accountBalanceTreeFetch( $parent_id=0, $idate='', $fdate='', $show_unused=1 ){
	$this->Base->set_level(3);
	$this->check($parent_id,'int');
	$this->check($idate,'\d\d\d\d-\d\d-\d\d');
	$this->check($fdate,'\d\d\d\d-\d\d-\d\d');
	$this->check($show_unused,'bool');
	$active_company_id=$this->Base->acomp('company_id');
	$this->db->query("SET @idate='$idate 00:00:00', @fdate='$fdate 23:59:59', @parent_id='$parent_id';");
	$sql=
	"SELECT 
	    d.branch_id,
	    d.label,
	    d.acc_code,
	    d.acc_type,
	    d.curr_id,
	    (SELECT curr_symbol FROM curr_list WHERE curr_id=d.curr_id) curr_symbol,
	    d.is_favorite,
	    d.use_clientbank,
	    IF( is_leaf,'','closed') state,
	    is_leaf,
	    IF(d.acc_type='P',-1,1)*(COALESCE(open_d,0)-COALESCE(open_c,0)) open_bal,
	    period_d,
	    period_c,
	    IF(d.acc_type='P',-1,1)*(COALESCE(close_d,0)-COALESCE(close_c,0)) close_bal
	FROM
	    (SELECT 
		tree.*,
		ROUND(SUM(IF(dtrans.cstamp < @idate, dtrans.amount, 0)), 2) open_d,
		ROUND(SUM(IF(dtrans.cstamp > @idate AND dtrans.cstamp < @fdate,dtrans.amount,0)),2) period_d,
		ROUND(SUM(IF(dtrans.cstamp < @fdate, dtrans.amount, 0)), 2) close_d
	    FROM
		acc_tree tree
		    LEFT JOIN 
		acc_tree subtree ON subtree.path LIKE CONCAT(tree.path,'%')
		    LEFT JOIN
		acc_trans dtrans ON dtrans.acc_debit_code = subtree.acc_code AND active_company_id='$active_company_id'
	    WHERE
		tree.parent_id=@parent_id
	    GROUP BY tree.branch_id) d
	JOIN
	    (SELECT 
		tree.branch_id,
		ROUND(SUM(IF(ctrans.cstamp < @idate, ctrans.amount, 0)), 2) open_c,
		ROUND(SUM(IF(ctrans.cstamp > @idate AND ctrans.cstamp < @fdate,ctrans.amount,0)),2) period_c,
		ROUND(SUM(IF(ctrans.cstamp < @fdate, ctrans.amount, 0)), 2) close_c
	    FROM
		acc_tree tree
		    LEFT JOIN 
		acc_tree subtree ON subtree.path LIKE CONCAT(tree.path,'%')
		    LEFT JOIN
		acc_trans ctrans ON ctrans.acc_credit_code = subtree.acc_code AND active_company_id='$active_company_id'
	    WHERE
		tree.parent_id=@parent_id
	    GROUP BY tree.branch_id) c 
	ON (d.branch_id=c.branch_id) 
	HAVING IF( '$show_unused', 1, open_bal OR  period_d OR period_c OR close_bal )
	ORDER BY acc_code";
        $balance=$this->get_list($sql);
	return $balance?$balance:[];
    }
    public function accountBalanceTreeCreate( $parent_id, $label ){
	$this->Base->set_level(3);
	$this->treeUpdate('acc_tree',$parent_id,'is_leaf',0);
	$new_code=  $this->accountCodeAssign( $parent_id );
	$branch_id= $this->treeCreate('acc_tree','leaf',$parent_id,$label);
	$ok=$this->rowUpdate('acc_tree',array('acc_code'=>$new_code),array('branch_id'=>$branch_id));
	if( $ok ){
	    return "$branch_id,$new_code";
	}
	return "$branch_id,";
    }
    private function accountCodeAssign( $parent_id ){
	$acc_code=$this->get_value("SELECT MAX(acc_code)+1 acc_code FROM acc_tree WHERE parent_id=$parent_id");
	if( !$acc_code ){
	    $acc_code=$this->get_value("SELECT CONCAT(acc_code,'1') acc_code FROM acc_tree WHERE branch_id=$parent_id");
	}
	return $acc_code;
    }

    public function transFullGet( $trans_id ){
	$this->check($trans_id,'int');
	$curr_id=$this->Base->acomp('curr_id');
	$sql="SELECT
		trans.*,
		(SELECT IF(label,label,company_name) FROM companies_list LEFT JOIN companies_tree USING(branch_id) WHERE company_id=passive_company_id) label,
		CONCAT(acc_debit_code, '_', acc_credit_code) trans_type,
		trans_name,
		IF(atd.curr_id<>'$curr_id' OR atc.curr_id<>'$curr_id',1,0) use_alt_currency,
		atn.user_level,
		nick
	    FROM 
		acc_trans trans
		    LEFT JOIN
		acc_trans_names atn USING(acc_debit_code,acc_credit_code)
		    JOIN
		acc_tree atd ON atd.acc_code=acc_debit_code
		    JOIN
		acc_tree atc ON atc.acc_code=acc_credit_code
		    LEFT JOIN
		user_list ON user_id=modified_by
	    WHERE
		trans_id='$trans_id'";
	return $this->get_row($sql);
    }
    public function transGet( $trans_id ){
	$this->check($trans_id,'int');
	$sql="SELECT * FROM acc_trans trans WHERE trans_id='$trans_id'";
	return $this->get_row($sql);	
    }
    public function transGetDocId( $trans_id ){
	$this->check($trans_id,'int');
	$sql="SELECT doc_id FROM document_trans WHERE trans_id='$trans_id'";
	return $this->get_value($sql);	
    }
    public function transCheckLevel($trans_type){
	$user_level=$this->Base->svar('user_level');
	if( $user_level>=3 ){
	    return true;
	}
	$sql="SELECT 1 FROM acc_trans_names WHERE CONCAT(acc_debit_code,'_',acc_credit_code)='$trans_type' AND user_level<='$user_level'";
	return $this->get_value($sql);
    }
    /******************
      STATUS
      1 unpayed
      2 partly
      3 payed
      4 closed
      5 closing payment
    *******************/

    private function transPaymentCalculate($pcomp_id = NULL,$acc_code) {
        if ( !isset($pcomp_id) ){
            $pcomp_id = $this->Base->pcomp('company_id');
        }
        $active_company_id=$this->Base->acomp('company_id');
        $sensitivity=5.00;
        $this->query("SET @sum:=0.0;");
        $this->query("
                UPDATE
                    acc_trans
                SET trans_status=IF(acc_debit_code = $acc_code,
                        (@sum:=@sum - amount)*0 + 
                        IF(amount<0,0,
                            IF(@sum <= 0 ,1,
                                IF(@sum+$sensitivity< amount, 2, 3)
                            )
                        ),
                        (@sum:=@sum + amount)*0
                    )
                WHERE
                    active_company_id=$active_company_id
                    AND passive_company_id = $pcomp_id
                    AND trans_status <> 4
                    AND trans_status <> 5
                    AND (acc_debit_code = $acc_code
                    OR acc_credit_code = $acc_code)
                ORDER BY acc_debit_code = $acc_code, amount>0, cstamp;");
    }
    private $payment_account=361;
    private function transCheckCalculate($trans){
        if( isset($trans['acc_debit_code']) && ($trans['acc_debit_code']==$this->payment_account || $trans['acc_credit_code']==$this->payment_account)  ){
            $this->transPaymentCalculate($trans['passive_company_id'], $this->payment_account);
        }
    }
    private function checkTransLink($trans_id,$trans) {
	if( $trans['check_id'] ){
	    $this->update('acc_check_list',['trans_id'=>$trans_id],['check_id'=>$trans['check_id']]);
	}
    }
    private function checkTransBreakLink( $check_id ){
	if( isset($check_id) ){
	    $this->update('acc_check_list',['trans_id'=>0],['check_id'=>$check_id]);
	}	
    }
    private function transCrossLink($trans_id,$trans){
	if( $trans['trans_ref'] ){
	    $this->update('acc_trans', ['trans_ref'=>$trans['trans_ref'],'trans_status'=>5], ['trans_id'=>$trans_id]);
	    $this->update('acc_trans', ['trans_ref'=>$trans_id,'trans_status'=>4], ['trans_id'=>$trans['trans_ref']]);
	}
    }
    private function transBreakLink($trans_ref){
	if( isset($trans_ref) ){
	    $this->update('acc_trans', ['trans_ref'=>0,'trans_status'=>0], ['trans_id'=>$trans_ref]);
	}	
    }
    private function transInnerCreateUpdate($trans_id,$trans){
	$this->Base->set_level(2);
	if( $trans_id ){
	    $this->update('acc_trans', $trans, ['trans_id'=>$trans_id,'editable'=>1]);
	    $trans_id= $this->db->affected_rows()>0?$trans_id:false;
	} else {
	    $trans['editable']=1;
	    $trans['active_company_id']=$this->Base->acomp('company_id');
	    $trans['created_by']=$trans['modified_by'];
	    $this->create('acc_trans', $trans);
	    $trans_id= $this->db->insert_id();
	}
	$this->checkTransLink($trans_id,$trans);
	$this->transCrossLink($trans_id,$trans);
	$this->transCheckCalculate($trans);
	return $trans_id;	
    }
    public function transPostCreateUpdate(){
	$trans_id=$this->request('trans_id','int',0);
	$check_id=$this->request('check_id','int');
	$passive_company_id=$this->request('passive_company_id','int');
	$trans_type=$this->request('trans_type');
	$trans_date=$this->request('trans_date','\d\d\d\d-\d\d-\d\d');
	$trans_ref=$this->request('trans_ref','int',null);
	$amount=$this->request('amount','double');
	$amount_alt=$this->request('amount_alt','double');
	$description=$this->request('description');
	$user_id=$this->Base->svar('user_id');
	
	$acc_codes=  explode('_',$trans_type);
	if( !$this->transCheckLevel($trans_type) ){
	    $this->Base->msg('access denied');
	    return false;
	}
	$trans=[
	    'trans_ref'=>$trans_ref,
	    'check_id'=>$check_id,
	    'passive_company_id'=>$passive_company_id,
	    'acc_debit_code'=>$acc_codes[0],
	    'acc_credit_code'=>$acc_codes[1],
	    'cstamp'=>$trans_date.date(" H:i:s"),
	    'amount'=>$amount,
	    'amount_alt'=>$amount_alt,
	    'description'=>$description,
	    'modified_by'=>$user_id
	];
	return $this->transInnerCreateUpdate($trans_id,$trans);
    }
    public function transDelete( $trans_id ){
	$this->Base->set_level(2);
	$trans=$this->transGet($trans_id);
	if( $trans && $this->transCheckLevel($trans->acc_debit_code.'_'.$trans->acc_credit_code) ){
	    $this->delete('acc_trans',['trans_id'=>$trans_id,'editable'=>1]);
            $ok=$this->db->affected_rows()>0?true:false;
	    $this->checkTransBreakLink($trans->check_id);
	    $this->transBreakLink($trans->trans_ref);
            if( $trans->acc_debit_code==$this->payment_account || $trans->acc_credit_code==$this->payment_account ){
                $this->transPaymentCalculate($trans->passive_company_id, $this->payment_account);
            }
	    return $ok;
	}
	$this->Base->msg('access denied');
	return false;
    }

}
