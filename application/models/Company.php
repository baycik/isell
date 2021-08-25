<?php
/**
 * COMPANY SPECIFIC FUNCTIONS
 *
 * @author Baycik
 */
require_once 'Catalog.php';
class Company extends Catalog{
    
    public function companyGet( int $company_id=0 ){
	if( $company_id==0 ){
	    return false;
	}
        $user_level=$this->Hub->svar('user_level');
	$assigned_path=$this->Hub->svar('user_assigned_path');
	$sql="SELECT
		*
	    FROM
		companies_list cl
	    JOIN
		companies_tree USING(branch_id)
	    LEFT JOIN 
		curr_list USING(curr_code)
	    WHERE
		(path LIKE '$assigned_path%' OR is_active)
		    AND
                level <= $user_level 
                    AND
		company_id=$company_id";
	return $this->get_row($sql);
    }
    
    public function companyUpdate( int $company_id, string $field, string $value='' ) {
	$this->Hub->set_level(2);
	$assigned_path=$this->Hub->svar('user_assigned_path');
	if( $this->Hub->acomp('company_id')==$company_id ){
	    $this->Hub->set_level(3);
	}
	if( $field=='label' ){
	    $branch_id=$this->get_value("SELECT branch_id FROM companies_list JOIN companies_tree USING(branch_id)  WHERE (path LIKE '$assigned_path%' OR path IS NULL) AND company_id='$company_id'");
	    $ok=$this->treeUpdate('companies_tree', $branch_id, $field, $value);
	} else {
	    	$sql="UPDATE 
			companies_list
		    JOIN 
			companies_tree USING(branch_id) 
		    SET $field='$value' 
		    WHERE 
			(path LIKE '$assigned_path%' OR path IS NULL)
			    AND
			company_id=$company_id";
		$this->query($sql);
		$ok=$this->db->affected_rows()>0?1:0;
	}
	if( $this->Hub->acomp('company_id')==$company_id ){/*@TODO move to lazy loading of pcomp/acomp in v4.0*/
	    $this->selectActiveCompany($company_id);
	}
	if( $this->Hub->pcomp('company_id')==$company_id ){
	    $this->selectPassiveCompany($company_id);
	}
	return $ok;
    }
    
    public function companyCheckUserPermission( int $passive_company_id ){
        $company=$this->companyGet( $passive_company_id );
        return (bool) $company??false;
    }
    
    public function companyFindByCode( string $company_code=null, string $company_tax_id=null, string $company_bank_account=null ){
        $sql="SELECT 
                company_id 
            FROM 
                companies_list 
            WHERE 
                IF('$company_code',company_code='$company_code',0) 
                OR IF('$company_tax_id',company_tax_id='$company_tax_id',0)
                OR IF('$company_bank_account',company_bank_account='$company_bank_account',0)";
        return $this->get_value($sql);
    }
    
    //////////////////////////////////////////////
    // COMPANY LIST SECTION
    //////////////////////////////////////////////
    public function listFetch( string $q='', string $mode='', bool $transliterated=false, int $offset=0, int $limit=20 ){
	$assigned_path=$this->Hub->svar('user_assigned_path');
	$level=$this->Hub->svar('user_level');
        $and_where="AND path LIKE '$assigned_path%' AND level<=$level AND label LIKE '%$q%'";
	if( $mode=='active_only' ){
	    $and_where='AND is_active=1';
	}
        $sql="SELECT 
                company_id,
                label,
                path,
                company_name
            FROM
                companies_tree
            JOIN 
                companies_list USING(branch_id)
            WHERE
                is_leaf=1
                $and_where
            ORDER BY path
            LIMIT $limit OFFSET $offset";
        $companies=$this->get_list( $sql );
	if( $mode=='with_active' ){
	    array_push($companies,['company_id'=>$this->Hub->acomp('company_id'),'label'=>$this->Hub->acomp('company_name'),'path'=>'']);
	}
        if( !count($companies) && !$transliterated ){
            return $this->listFetch($this->transliterate($q), $mode, true, $offset, $limit );
        }
	return $companies;
    }
    
    public function listFetchAll( string $mode=NULL ){
	$assigned_path=$this->Hub->svar('user_assigned_path');
	$level=$this->Hub->svar('user_level');
	$where='';
	if( $mode=='active_only' ){
	    $where.='AND is_active=1';
            $assigned_path='';
	}
	$sql="SELECT 
		company_id,
		label,
		path
	    FROM
		companies_tree
	    JOIN 
		companies_list USING(branch_id)
	    WHERE
		is_leaf=1
		    AND
		path LIKE '$assigned_path%'
		    AND
		level<=$level
		$where";
	return $this->get_list( $sql );
    }    
    //////////////////////////////////////////////
    // COMPANY TREE SECTION
    //////////////////////////////////////////////
    public $branchFetch=['id'=>['int',0]];
    public function branchFetch($parent_id) {
	$table = "companies_tree LEFT JOIN companies_list USING(branch_id)";
	$assigned_path=  $this->Hub->svar('user_assigned_path');
        if( $assigned_path && $parent_id==0 ){
            $parent_id=null;
        }
	$level=$this->Hub->svar('user_level');
	return $this->treeFetch($table, $parent_id, 'top', $assigned_path, $level, 'is_active,is_leaf,label');
    }
    
    public $companyTreeCreate=['parent_id'=>['int',0],'label'=>'string','branch_type'=>'string'];
    public function companyTreeCreate($parent_id,$label,$branch_type){
	$this->Hub->set_level(2);
        $def_lang = $this->Hub->acomp('language');
        $def_curr_code = $this->Hub->acomp('curr_code');
	$branch_id=$this->treeCreate('companies_tree', $branch_type, $parent_id,$label);
	if( $branch_type=='leaf' ){
	    $this->query("INSERT INTO companies_list SET 
                branch_id=$branch_id,
                company_name='$label',
                language='$def_lang',
                curr_code='$def_curr_code'");
	    return $this->db->insert_id();
	}
	return 0;
    }
    
    public $companyTreeUpdate=['branch_id'=>'int','field'=>'[a-z_]+','value'=>'string'];
    public function companyTreeUpdate($branch_id,$field,$value) {
	$this->Hub->set_level(2);
	return $this->treeUpdate('companies_tree', $branch_id, $field, $value);
    }
    
    public $companyTreeDelete=['int'];
    public function companyTreeDelete( $branch_id ){
	$this->Hub->set_level(4);
	$this->check($branch_id,'int');
	$sub_ids=$this->treeGetSub('companies_tree', $branch_id);
	$in=implode(',', $sub_ids);
	$this->query("START TRANSACTION");
	$this->query("DELETE FROM companies_list WHERE branch_id IN ($in)");
	$this->query("DELETE FROM companies_tree WHERE branch_id IN ($in)");
	$deleted=$this->db->affected_rows();
	$this->query("COMMIT");
	return $deleted;
    }
    
    public $selectPassiveCompany=['int'];
    public function selectPassiveCompany( int $company_id ){
	$company=$this->companyGet( $company_id );
	$this->Hub->svar('pcomp',$company);
	return $company;
    }
    
    public $selectActiveCompany=['int'];
    public function selectActiveCompany( int $company_id ){
	$company=$this->companyGet( $company_id );
	if( $company && $company->is_active ){
	    $this->Hub->svar('acomp',$company);
	    
	    $user_id=$this->Hub->svar('user_id');
	    $this->update('user_list',['company_id'=>$company_id],['user_id'=>$user_id]);
	    return $company;
	}
	return $this->switchActiveCompany();//supplied company id is not active so provide valid acomp from list
    }
    
    public $switchActiveCompany=[];
    public function switchActiveCompany(){
        $current_acomp_id=$this->Hub->acomp('company_id');
        $sql="SELECT 
                company_id 
            FROM 
                companies_list
            WHERE 
                is_active=1 
            ORDER BY company_id>'$current_acomp_id' ,company_id
            LIMIT 1";
        $company_id=$this->get_value($sql);
        return $this->selectActiveCompany($company_id);
    }
    
    public $companyMakeActive=['int'];
    public function companyMakeActive( $company_id ){
	$this->Hub->set_level(4);
	if( $company_id ){
	    return $this->update('companies_list',['is_active'=>1],['company_id'=>$company_id]);
	}
	return false;
    }
    
    public $companyPrefsGet=[];
    public function companyPrefsGet(){
	$passive_company_id=$this->Hub->pcomp('company_id');
	if( !$passive_company_id ){
	    return null;
	}
	$Pref=$this->Hub->load_model("Pref");
	$Stock=$this->Hub->load_model("Stock");
	$sql_discount_cat="
            (SELECT
                cd.discount_id,
                cd.analyse_brand,
                cd.round_to,
                cd.discount,
                IF(cd.discount>1,ROUND(cd.discount*100-100,1),'') plus,
                IF(cd.discount<1,ROUND(100-cd.discount*100,1),'') minus,
		0 branch_id,
		'*' label,
                '/' path
	    FROM
		companies_discounts cd
	    WHERE 
                company_id=$passive_company_id
                AND analyse_brand IS NULL
		AND branch_id IS NULL)
            
            UNION
            
            (SELECT
                cd.discount_id,
                cd.analyse_brand,
                cd.round_to,
                cd.discount,
                IF(cd.discount>1,ROUND(cd.discount*100-100,1),'') plus,
                IF(cd.discount<1,ROUND(100-cd.discount*100,1),'') minus,
		st.branch_id,
		label,
                path
	    FROM
		stock_tree st
	    LEFT JOIN
		companies_discounts cd ON st.branch_id=cd.branch_id AND cd.company_id=$passive_company_id
	    WHERE 
		cd.discount_id IS NULL AND parent_id=0
                    OR
                cd.discount_id IS NOT NULL)
            ORDER BY path
                ";
	$sql_discount_brand="
            SELECT
                cd.discount_id,
                cd.analyse_brand,
                cd.round_to,
                cd.discount,
                IF(discount>1,ROUND(discount*100-100,1),'') plus,
                IF(discount<1 AND discount>0,ROUND(100-discount*100,1),'') minus,
		NULL AS branch_id,
		cd.analyse_brand AS label,
                cd.analyse_brand AS path
	    FROM
		companies_discounts cd
	    WHERE 
		company_id=$passive_company_id
                AND analyse_brand IS NOT NULL
                ";
        
	$sql_other="SELECT
		deferment,
                debt_limit,
		curr_code,
		price_label,
                expense_label,
		manager_id,
		is_supplier,
                skip_breakeven_check,
		company_acc_list,
		language,
		'".$this->Hub->pcomp('path')."' path
	    FROM
		companies_list
	    WHERE 
		company_id='$passive_company_id'
	    ";
	return [
		'discount_cat'=>$this->get_list($sql_discount_cat),
		'discount_brand'=>$this->get_list($sql_discount_brand),
		'other'=>$this->get_row($sql_other),
		'staff_list'=>$Pref->getStaffList(),
		'price_label_list'=>$Stock->getPriceLabels()
		];
    }
    
    public $companyPrefsUpdate=['type'=>'string','field'=>'[0-9a-z_]+','value'=>'string'];
    public function companyPrefsUpdate( $type, $field, $value='' ){
	$this->Hub->set_level(3);
        if( in_array($field, array('deferment','debt_limit','skip_breakeven_check')) ){
            $this->Hub->set_level(4);
        }
        if( in_array($field, array('deferment','debt_limit','curr_code','price_label','expense_label','manager_id','is_supplier','skip_breakeven_check','company_acc_list','language')) ){
            $passive_company_id = $this->Hub->pcomp('company_id');
            $this->query("UPDATE companies_list SET $field='$value' WHERE company_id=$passive_company_id");
            $ok=$this->db->affected_rows();
            $this->selectPassiveCompany( $passive_company_id );
            return $ok;
        }
        return false;
    }
    
    
    public function discountCreate( int $branch_id=null, string $analyse_brand=null){
        $this->Hub->set_level(3);
	$passive_company_id = $this->Hub->pcomp('company_id');
        return $this->create('companies_discounts',['company_id'=>$passive_company_id,'branch_id'=>$branch_id,'analyse_brand'=>$analyse_brand]);
    }
    
    public function discountUpdate( int $discount_id, string $field, string $value ){
        $this->Hub->set_level(3);
	if( $field=='discount' && $value==1 ){/*Discount is zero so lets delete it*/
            if( !$this->get_value("SELECT round_to FROM companies_discounts WHERE discount_id='$discount_id'") ){
                return $this->discountDelete($discount_id);
            }
	}
        return $this->update('companies_discounts',[$field=>$value],['discount_id'=>$discount_id]);
    }
    
    public function discountDelete(int $discount_id){
        $this->Hub->set_level(3);
        return $this->delete('companies_discounts',['discount_id'=>$discount_id]);
    }
}
