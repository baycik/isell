<?php
/**
 * ACCOUNTS SPECIFIC FUNCTIONS
 * Accounting processing
 *
 * @author Baycik
 */
class AccountsCore extends Catalog{
    public $min_level=1;
    
    
    //////////////////////////////////////////////
    // TRANS UTILS SECTION
    //////////////////////////////////////////////
    private function transLevelCheck( $trans_type=null, $acc_debit_code=null, $acc_credit_code=null ){
        if( $acc_debit_code==null || $acc_credit_code==null ){
            $acc_codes=  explode('_',$trans_type);
            $acc_debit_code=$acc_codes[0];
            $acc_credit_code=$acc_codes[1];
        }
	$user_level=$this->Hub->svar('user_level');
	if( $user_level>=3 ){
	    return true;
	}
	$sql="SELECT 
                1 
            FROM 
                acc_trans_names 
            WHERE 
                acc_debit_code='$acc_debit_code'
                AND acc_credit_code=$acc_credit_code;
                AND user_level<='$user_level'";
	return $this->get_value($sql);
    }

    
    //////////////////////////////////////////////
    // TRANS SECTION
    //////////////////////////////////////////////
    public function transGet( int $trans_id ){
	$sql="SELECT * FROM acc_trans WHERE trans_id='$trans_id'";
	return $this->get_row($sql);
    }
    
    public function transCreate( array $trans_data ){
        $this->Hub->set_level(2);
        if( !$this->transLevelCheck(null,$trans_data['acc_debit_code'],$trans_data['acc_credit_code']) ){
	    throw new Exception("Access denied for creation of this transaction: {$trans_data['acc_debit_code']}_{$trans_data['acc_credit_code']}",403);
	}
        $trans_data['active_company_id']=$this->Hub->acomp('company_id');
        $trans_data['created_by']=$this->Hub->svar('user_id');
        $this->transValidate( $trans_data );
        $trans_id= $this->create('acc_trans', $trans_data);
        $this->transResolveConnections($trans_id,$trans_data);
	return $trans_id;        
    }
    
    public function transUpdate( int $trans_id, array $trans_data ){
	$this->Hub->set_level(2);
        $user_id=$this->Hub->svar('user_id');
        $trans_data['modified_by']=$user_id;
        $ok= $this->update('acc_trans', $trans_data, ['trans_id'=>$trans_id]);
        $this->transResolveConnections($trans_id,$trans_data);
        return $ok;
    }

    public function transDelete( int $trans_id ){
	$this->Hub->set_level(2);
	$trans=$this->transGet($trans_id);
	if( $trans /*&& $this->transLevelCheck(null,$trans->acc_debit_code,$trans->acc_credit_code)*/ ){
	    $ok=$this->delete('acc_trans',['trans_id'=>$trans_id]);
            if( $ok ){
                $trans_data=(array)$trans;
                $this->checkTransBreakLink($trans_data['check_id']);
                $this->transBreakLink($trans_data['trans_ref']);
                $this->transCalculate($trans_data);
                return true;
            }
	    return false;
	}
        throw new Exception("Access denied for deletion of this transaction: {$trans_data['acc_debit_code']}_{$trans_data['acc_credit_code']}",403);
    }
    
    private function transResolveConnections($trans_id,$trans_data){
        $this->checkTransLink($trans_id,$trans_data);
	$this->transCrossLink($trans_id,$trans_data);
	$this->transCalculate($trans_data);
    }
    
    private function transValidate( $trans_data ){
        $trans_data_valid=
                   $trans_data['active_company_id']??false
                && $trans_data['passive_company_id']??false
                && $trans_data['acc_debit_code']??false
                && $trans_data['acc_credit_code']??false
                && $trans_data['cstamp']??false
                && $trans_data['amount']??false
                && $trans_data['description']??false;
        if( !$trans_data_valid ){
            print_r($trans_data);
	    throw new Exception("Transaction validation failed",500);
        }
        return true;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    //////////////////////////////////////////////
    // ACCOUNTS SECTION
    //////////////////////////////////////////////
    protected function getAccountProperties( $acc_code, $calc_balance=false, $use_passive_filter=false ) {
	$active_company_id=$this->Hub->acomp('company_id');
	$default_curr_id=$this->Hub->acomp('curr_id');
	$balance='';
	if( $calc_balance ){
	    $passive_filter="";
	    if( $use_passive_filter ){
		$passive_filter=" AND passive_company_id='".$this->Hub->pcomp('company_id')."'";
	    }
	    $balance=",(
		SELECT 
		    SUM(ROUND(IF(at.acc_code=acc_debit_code,-amount,amount),2))
		FROM acc_trans
		WHERE (acc_debit_code=at.acc_code OR acc_credit_code=at.acc_code) AND active_company_id=$active_company_id $passive_filter)*IF(acc_type='P',1,-1) balance";
	}
        if( $use_passive_filter ){
            $acc_list=$this->Hub->pcomp('company_acc_list');
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
    
    //////////////////////////////////////////////
    // LEDGER SECTION
    //////////////////////////////////////////////
    public $ledgerFetch=[
	'acc_code'=>'string',
	'idate'=>'\d\d\d\d-\d\d-\d\d',
	'fdate'=>'\d\d\d\d-\d\d-\d\d',
	'page'=>['int',1],
	'rows'=>['int',30],
	'use_passive_filter'=>['int',0]
	];
    public function ledgerFetch( string $acc_code, $idate='', $fdate='', $page=1, $rows=30, $use_passive_filter=false ){
	$idate.=' 00:00:00';
	$fdate.=' 23:59:59';
        
	$props=$this->getAccountProperties( $acc_code, false, $use_passive_filter );
	if( $use_passive_filter ){
	    $this->Hub->set_level(1);
            $props->curr_id=$this->Hub->pcomp('curr_id');
            $props->curr_symbol=$this->Hub->pcomp('curr_symbol');
	} else {
	    $this->Hub->set_level(3);
	}
	if( !$acc_code || !$idate || !$fdate ){
	    return [];
	}
	$using_alt_currency=false;
	if( $props->curr_id ){
	    $default_curr_id=$this->Hub->acomp('curr_id');
	    $using_alt_currency=$default_curr_id!=$props->curr_id;
	}
	$this->ledgerCreate($acc_code, $using_alt_currency, $use_passive_filter );
	
	$having=$this->decodeFilterRules();
	$offset=$page>0?($page-1)*$rows:0;
	$sql="SELECT * FROM tmp_ledger 
		WHERE '$idate'<cstamp AND cstamp<='$fdate'
		HAVING $having
		ORDER BY cstamp DESC,trans_id 
		LIMIT $rows OFFSET $offset";
	$result_rows=$this->get_list($sql);
	$total_estimate=$offset+(count($result_rows)==$rows?$rows+1:count($result_rows));
	
	$sub_totals=$this->ledgerGetSubtotals($idate, $fdate, $having);
	return ['rows'=>$result_rows,'total'=>$total_estimate,'props'=>$props,'sub_totals'=>$sub_totals,'using_alt_currency'=>$using_alt_currency];
    }
    
    public $ledgerPaymentFetch=[
	'acc_code'=>'string',
	'idate'=>'\d\d\d\d-\d\d-\d\d',
	'fdate'=>'\d\d\d\d-\d\d-\d\d',
	'page'=>['int',1],
	'rows'=>['int',30],
	'use_passive_filter'=>['int',0]
	];
    public function ledgerPaymentFetch( $acc_code, $idate='', $fdate='', $page=1, $rows=30 ){
	return $this->ledgerFetch($acc_code, $idate, $fdate, $page, $rows, true);
    }
    private function ledgerCreate( $acc_code, $using_alt_currency=false, $use_passive_filter=false ){
	$active_company_id=$this->Hub->acomp('company_id');
	$passive_filter="";
	if( $use_passive_filter ){
	    $passive_filter=" AND passive_company_id='".$this->Hub->pcomp('company_id')."'";
	}
	
	$this->db->query("SET @acc_code:=?, @use_alt_amount=?,@row_total:=0.0",[$acc_code,$using_alt_currency]);
	$this->db->query("DROP TEMPORARY TABLE IF EXISTS tmp_ledger;");#
	$sql="CREATE TEMPORARY TABLE tmp_ledger ( INDEX(cstamp) ) AS (
            SELECT *,
                ROUND(@row_total:=@row_total+debit-credit,2) row_total
            FROM(SELECT 
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
		    LEFT JOIN
		acc_trans_status USING (trans_status)
		    LEFT JOIN
		user_list ON user_id = modified_by
	    WHERE
		(@acc_code = acc_debit_code OR @acc_code = acc_credit_code)
		AND active_company_id='$active_company_id'
		    $passive_filter
            ORDER BY cstamp) t)";
        
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
    //////////////////////////////////////////////
    // BALANCE TREE SECTION
    //////////////////////////////////////////////
    public $accountBalanceTreeFetch=[
	'int',
	'\d\d\d\d-\d\d-\d\d',
	'\d\d\d\d-\d\d-\d\d',
	'int'
	];
    public function accountBalanceTreeFetch( $parent_id=0, $idate='', $fdate='', $show_unused=1 ){
	$this->Hub->set_level(3);
	$active_company_id=$this->Hub->acomp('company_id');
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
    
    public $accountBalanceTreeCreate=['parent_id'=>'int','label'=>'string'];
    public function accountBalanceTreeCreate( $parent_id, $label ){
	$this->Hub->set_level(3);
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
    //////////////////////////////////////////////
    // TRANS UTILS SECTION
    //////////////////////////////////////////////
    public $transFullGet=['trans_id'=>'int'];
    public function transFullGet( $trans_id ){
	$this->check($trans_id,'int');
	$curr_id=$this->Hub->acomp('curr_id');
	$sql="SELECT
		trans.*,
		(SELECT IF(label,label,company_name) FROM companies_list LEFT JOIN companies_tree USING(branch_id) WHERE company_id=passive_company_id) label,
		CONCAT(acc_debit_code, '_', acc_credit_code) trans_type,
		trans_name,
                IF(trans_article<>'',trans_article,trans_label) trans_article,
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
    
    
    public $transGetDocId=['int'];
    public function transGetDocId( int $trans_id ){
	$sql="SELECT doc_id FROM document_trans WHERE trans_id='$trans_id'";
	return $this->get_value($sql);	
    }
    

    /******************
      STATUS
      1 unpayed
      2 partly
      3 payed
      4 closed
      5 closing payment
      6 unpayedsq
      7 partlysq
      8 payedsq
      9 closedsq
      10 closingsq
    *******************/

    private function transPaymentCalculate($pcomp_id = NULL,$acc_code,$side) {
        if ( !isset($pcomp_id) ){
            $pcomp_id = $this->Hub->pcomp('company_id');
        }
        $active_company_id=$this->Hub->acomp('company_id');
        $sensitivity=5.00;
        //$this->profile("before  transPaymentCalculate");
        $this->query("SET @sum:=0.0;");
        if( $side=='debit' ){
            $calculate_sql="
                    UPDATE
                        acc_trans
                    SET trans_status=IF(acc_debit_code = $acc_code,
                            (@sum:=@sum - amount)*0 + 
                            IF(amount<0,0,
                                IF(ROUND(@sum,2) <= 0 ,1,
                                    IF(@sum+$sensitivity< amount, 2, 3)
                                )
                            ),
                            (@sum:=@sum + amount)*0
                        )
                    WHERE
                        active_company_id=$active_company_id
                        AND passive_company_id = '$pcomp_id'
                        AND trans_status <> 4
                        AND trans_status <> 5
                        AND (acc_debit_code = $acc_code
                        OR acc_credit_code = $acc_code)
                    ORDER BY acc_debit_code = $acc_code, amount>0, cstamp;";
        } else {
            $calculate_sql="
                    UPDATE
                        acc_trans
                    SET trans_status=IF(acc_credit_code = $acc_code,
                            (@sum:=@sum - amount)*0 + 
                            IF(amount<0,0,
                                IF(ROUND(@sum,2) <= 0 ,6,
                                    IF(@sum+$sensitivity< amount, 7, 8)
                                )
                            ),
                            (@sum:=@sum + amount)*0
                        )
                    WHERE
                        active_company_id=$active_company_id
                        AND passive_company_id = $pcomp_id
                        AND trans_status <> 9
                        AND trans_status <> 10
                        AND (acc_debit_code = $acc_code
                        OR acc_credit_code = $acc_code)
                    ORDER BY acc_credit_code = $acc_code, amount>0, cstamp;";
        }
        $this->query($calculate_sql);
        //$this->profile("after  transPaymentCalculate");
    }
    private function transPaymentCalculateIfNeeded( $pcomp_id, $account ){
        $debit_payment_accounts=[361];
        $credit_payment_accounts=[631];
        if( in_array($account, $debit_payment_accounts) ){
            $this->transPaymentCalculate( $pcomp_id, $account, 'debit' );
        }
        if( in_array($account, $credit_payment_accounts) ){
            $this->transPaymentCalculate( $pcomp_id, $account, 'credit' );
        }
    }
    //private $payment_account=361;
    private function transCalculate($trans){
        if( isset($trans['acc_debit_code']) ){
            $this->transPaymentCalculateIfNeeded( $trans['passive_company_id'], $trans['acc_debit_code'] );
            $this->transPaymentCalculateIfNeeded( $trans['passive_company_id'], $trans['acc_credit_code'] );
        }
    }
    private function checkTransLink($trans_id,$trans) {
	if( $trans['check_id']??false ){
	    $this->update('acc_check_list',['trans_id'=>$trans_id],['check_id'=>$trans['check_id']]);
	}
    }
    private function checkTransBreakLink( $check_id ){
	if( isset($check_id) ){
	    $this->update('acc_check_list',['trans_id'=>0],['check_id'=>$check_id]);
	}	
    }
    private function transCrossLink($trans_id,$trans){
	if( $trans['trans_ref']??false ){
	    $this->update('acc_trans', ['trans_ref'=>$trans['trans_ref'],'trans_status'=>5], ['trans_id'=>$trans_id]);
	    $this->update('acc_trans', ['trans_ref'=>$trans_id,'trans_status'=>4], ['trans_id'=>$trans['trans_ref']]);
	}
    }
    private function transAlreadyLinked( $trans_id ){
	return $this->get_value("SELECT trans_ref FROM acc_trans WHERE trans_id='$trans_id'");
    }
    private function transBreakLink($trans_ref){
	if( isset($trans_ref) ){
	    $this->update('acc_trans', ['trans_ref'=>0,'trans_status'=>0], ['trans_id'=>$trans_ref]);
	}	
    }
    
    public $transCreateUpdate=[
	'trans_id'=>['int',0],
	'check_id'=>'int',
	'passive_company_id'=>'int',
	'trans_type'=>'string',
	'trans_date'=>'\d\d\d\d-\d\d-\d\d',
	'trans_ref'=>['int',null],
	'amount'=>['double',0],
	'amount_alt'=>['double',0],
	'description'=>['string',''],
	'trans_article'=>['string','']
    ];
    public function transCreateUpdate($trans_id,$check_id,$passive_company_id,$trans_type,$trans_date,$trans_ref,$amount,$amount_alt,$description,$trans_article=''){
	if( !$this->transLevelCheck($trans_type) ){
	    $this->Hub->msg('access denied');
	    return false;
	}
	$user_id=$this->Hub->svar('user_id');
	$acc_codes=  explode('_',$trans_type);
	$trans_data=[
	    'trans_ref'=>$trans_ref,
	    'check_id'=>$check_id,
	    'passive_company_id'=>$passive_company_id,
	    'acc_debit_code'=>$acc_codes[0],
	    'acc_credit_code'=>$acc_codes[1],
            'trans_article'=>$trans_article,
	    'cstamp'=>$trans_date,
	    'amount'=>$amount,
	    'amount_alt'=>$amount_alt,
	    'description'=>$description
	];
        $update_trans_data=true;
        if( !$trans_id ){
            $this->Hub->set_level(2);
	    $trans_data['editable']=1;
	    $trans_data['active_company_id']=$this->Hub->acomp('company_id');
	    $trans_data['created_by']=$user_id;
            $trans_data['modified_by']=$user_id;
	    if( $trans_data['trans_ref'] && $this->transAlreadyLinked($trans_data['trans_ref']) ){//Check whether referenced trans is already linked
		return false;
	    }
	    $this->create('acc_trans', $trans_data);
	    $trans_id= $this->db->insert_id();
            $update_trans_data=false;
        }
        $this->transUpdate($trans_id, $trans_data, $update_trans_data);
	return $trans_id;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    

    
    public function documentPay( int $doc_id, string $acc_debit_code, float $amount, string $description ){
	$sql="SELECT 
		trans_id,
		type
	    FROM 
		document_trans dtr
		    JOIN 
		acc_trans at USING(trans_id) 
	    WHERE 
		dtr.doc_id='$doc_id' 
		AND dtr.trans_role='total' 
		AND amount='$amount'";
	$trans=$this->get_row($sql);
	if( $trans->trans_id ){
	    $trans_accs=explode('_',$trans->type);
	    $trans_type="{$acc_debit_code}_$trans_accs[0]";
	    $passive_company_id=$this->Hub->pcomp('company_id');
	    return $this->transCreateUpdate(0,0,$passive_company_id,$trans_type,date("Y-m-d"),$trans->trans_id,$amount,0,$description);
	}
	return false;
    }
}
