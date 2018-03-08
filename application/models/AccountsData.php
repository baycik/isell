<?php
class AccountsData extends AccountsCore{
    public $min_level=1;
    
    public $transNameListFetch=['q'=>'string','selected_acc'=>['string',null]];
    public function transNameListFetch( $q, $selected_acc=null ){
	$q=  str_replace(["_","%"], ["\_","\%"], $q);
	$this->check($selected_acc);
	$user_level = $this->Hub->svar('user_level');
	$curr_id=$this->Hub->acomp('curr_id');
	$sql="SELECT
		acc_debit_code,
		acc_credit_code,
		trans_name,
                trans_label,
		CONCAT(acc_debit_code,'_',acc_credit_code) trans_type,
		CONCAT(trans_name,' ',acc_debit_code,'_',acc_credit_code) trans_type_name,
		IF(atd.curr_id<>'$curr_id' OR atc.curr_id<>'$curr_id',1,0) use_alt_currency,
		user_level
	    FROM 
		acc_trans_names 
		    JOIN
		acc_tree atd ON atd.acc_code=acc_debit_code
		    JOIN
		acc_tree atc ON atc.acc_code=acc_credit_code
	    WHERE 
		user_level<='$user_level' AND CONCAT(acc_debit_code,'_',acc_credit_code) LIKE '%$selected_acc%'
	    HAVING trans_type_name LIKE '%$q%'
	    ORDER BY trans_name";
	return $this->get_list($sql);
    }
    
    public $transNameUpdate=['trans_type'=>'[a-z0-9_]*','field'=>'[a-z0-9_]*','value'=>'string'];
    public function transNameUpdate($trans_type,$field,$value){
	$this->Hub->set_level(3);
	$type=  explode('_', $trans_type);
	$this->query("UPDATE acc_trans_names SET $field='$value' WHERE acc_debit_code='$type[0]' AND acc_credit_code='$type[1]'");
	return $this->db->affected_rows()>0;
    }
    
    public $transNameCreate=['debit_code'=>'string','credit_code'=>'string'];
    public function transNameCreate($acc_debit_code,$acc_credit_code){
	$this->Hub->set_level(3);
	return $this->query("INSERT INTO acc_trans_names SET acc_debit_code='$acc_debit_code', acc_credit_code='$acc_credit_code', trans_name='---', user_level=3");
    }
    
    public $transNameDelete=['trans_type'=>'string'];
    public function transNameDelete($trans_type){
	$dc=  explode('_', $trans_type);
	$this->query("DELETE FROM acc_trans_names WHERE acc_debit_code='$dc[0]' AND acc_credit_code='$dc[1]'");
	return $this->db->affected_rows()>0;
    }
    
    public $accountTreeFetch=['id'=>['int',0]];
    public function accountTreeFetch( $parent_id=0 ) {
	$res = $this->query("SELECT *,CONCAT(acc_code,' ',label) text,branch_id id FROM acc_tree WHERE parent_id='$parent_id' ORDER BY acc_code");
	$branches = array();
	foreach ($res->result() as $row) {
	    $row->state = $row->is_leaf ? '' : 'closed';
	    $branches[] = $row;
	}
	$res->free_result();
	return $branches;
    }
    
    public $accountTreeUpdate=['branch_id'=>'int','field'=>'[a-z0-9_]*','value'=>'string'];
    public function accountTreeUpdate($branch_id,$field,$value='') {
	$this->Hub->set_level(3);
	return $this->treeUpdate('acc_tree', $branch_id, $field, $value);
    }
    
    public $balanceTreeDelete=['branch_id'=>'int'];
    public function balanceTreeDelete( $branch_id ){
	$this->Hub->set_level(3);
	return $this->treeDelete('acc_tree',$branch_id);
    }
    
    public $accountFavoritesFetch=['use_passive_filter'=>['int',0],'get_client_bank_accs'=>['int',0]];
    public function accountFavoritesFetch( $use_passive_filter=false, $get_client_bank_accs=false ){
	if( $use_passive_filter ){
	    $acc_list=$this->Hub->pcomp('company_acc_list');
	} else {
	    $where=$get_client_bank_accs?'use_clientbank=1':'is_favorite=1';
	    $acc_list= $this->get_value("SELECT GROUP_CONCAT(acc_code SEPARATOR ',') FROM acc_tree WHERE $where");
	}
	$accs=explode(',',$acc_list);
	$favs=[];
	if( count($accs) ){
	    foreach( $accs as $acc_code ){
		$favs[]=$this->getAccountProperties($acc_code, true, $use_passive_filter);
	    }
	}
	return $favs;
   }
   
   public $accountFavoritesToggle=['acc_code'=>'string','is_favorite'=>'int','use_passive_filter'=>['int',0]];
   public function accountFavoritesToggle( $acc_code, $is_favorite, $use_passive_filter=false ){
	$this->Hub->set_level(3);
	$this->check($acc_code);
	$this->check($is_favorite,'bool');
	$this->check($use_passive_filter,'bool');
	if( $use_passive_filter ){
	    $passive_company_id=$this->Hub->pcomp('company_id');
	    $acc_list=$this->Hub->pcomp('company_acc_list');
	    $accs=explode(',',$acc_list);
	    $accs=array_diff($accs,['']);
	    
	    $is_favorite?$accs[]=$acc_code:$accs=array_diff($accs,[$acc_code]);
	    
	    $new_acc_list=  implode(',', array_unique($accs));
	    $this->Hub->load_model('Company');
	    $ok=$this->Hub->Company->companyUpdate($passive_company_id,'company_acc_list',$new_acc_list);
	    $this->Hub->Company->selectPassiveCompany($passive_company_id);
	    
	    return $ok;
	} else {
	    return $this->update('acc_tree',['is_favorite'=>$is_favorite],['acc_code'=>$acc_code]);
	}
    }
    
    public $accountCashGet=[];
    public function accountCashGet(){
	$sql="SELECT acc_code,label FROM acc_tree WHERE acc_code LIKE '301%'";
	return $this->get_list($sql);
    }
    
    public $articleListFetch=[];
    public function articleListFetch(){
        return $this->get_list("SELECT article_name FROM acc_article_list");
    }
    
        
    public function clientDebtGet(){
        $acc_code='361';
        $active_company_id=$this->Hub->acomp('company_id');
        $passive_company_id=$this->Hub->pcomp('company_id');
        $deferment=$this->Hub->pcomp('deferment');
        $sql="SELECT FORMAT(total,2,'ru_RU') total,FORMAT(total-allowed,2,'ru_RU') expired FROM
                (SELECT 
		    SUM( IF($acc_code=acc_debit_code,amount,-amount) ) total,
                    SUM(
                        IF(DATEDIFF(NOW(),cstamp)<=$deferment AND (trans_status=1 OR trans_status=2),IF(acc_debit_code=361,amount,0),0)
		    ) allowed
		FROM 
                    acc_trans
		WHERE 
                    (acc_debit_code=$acc_code OR acc_credit_code=$acc_code) 
                    AND active_company_id=$active_company_id 
                    AND passive_company_id='$passive_company_id') t";
        return $this->get_row($sql);
    }
}