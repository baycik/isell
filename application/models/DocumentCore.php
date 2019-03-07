<?php
require_once 'DocumentUtils.php';
class DocumentCore extends DocumentUtils{
    public $listFetch=['int','int','string'];
    public function listFetch( $page=1, $rows=30, $mode='' ){
	$offset=($page-1)*$rows;
	if( $offset<0 ){
	    $offset=0;
	}
	$having=$this->decodeFilterRules();
	$andwhere='';
	if( $mode==='show_only_pcomp_docs' ){
	    $pcomp_id=$this->Hub->pcomp('company_id');
            if( !$pcomp_id ){
                return [];
            }
	    $andwhere.=" AND passive_company_id=$pcomp_id";
	}
	$assigned_path=  $this->Hub->svar('user_assigned_path');
	if( $assigned_path ){
	    $andwhere.=" AND path LIKE '$assigned_path%'";
	}
	$active_company_id=$this->Hub->acomp('company_id');
        $amount_field='amount';
        if( $this->Hub->pcomp('curr_code')!=$this->Hub->acomp('curr_code') ){
            $amount_field='amount_alt';
        }
	$sql="
	    SELECT 
		doc_id,
		CONCAT(icon_name,' ',doc_type_name) doc_type_icon,
		DATE_FORMAT(dl.cstamp,'%d.%m.%Y') doc_date,
		doc_num,
		doc_type_name,
		(SELECT $amount_field 
		    FROM 
			acc_trans 
			    JOIN 
			document_trans dt USING(trans_id)
		    WHERE dt.doc_id=dl.doc_id 
		    ORDER BY trans_id LIMIT 1) amount,
		label company_name,
		GROUP_CONCAT(CONCAT(' ',LEFT(view_name,3),view_num)) views,
		IF(is_commited,'ok Проведен','') as commited,
		(SELECT CONCAT(code,' ',descr) FROM acc_trans_status JOIN acc_trans USING(trans_status) JOIN document_trans dt USING(trans_id) WHERE dt.doc_id=dl.doc_id ORDER BY trans_id LIMIT 1) trans_status
	    FROM 
		document_list dl
		    JOIN
		document_types dt USING(doc_type)
		    JOIN
		companies_list cl ON passive_company_id=company_id
		    JOIN
		companies_tree ct USING(branch_id)
		    LEFT JOIN
		document_view_list dv USING(doc_id)
		    LEFT JOIN
		document_view_types dvt USING(view_type_id)
	    WHERE dl.doc_type<10 AND dl.active_company_id = '$active_company_id' $andwhere
	    GROUP BY doc_id
	    HAVING $having
	    ORDER BY dl.is_commited,dl.cstamp DESC
	    LIMIT $rows OFFSET $offset
	";
	$result_rows=$this->get_list($sql);
	$total_estimate=$offset+(count($result_rows)==$rows?$rows+1:count($result_rows));
	return array('rows'=>$result_rows,'total'=>$total_estimate);
    }
    public $createDocument=['string'];
    public function createDocument( $doc_type=null ){
	$pcomp_id=$this->Hub->pcomp('company_id');
	if( $pcomp_id ){
	    $Document2=$this->Hub->bridgeLoad('Document');
	    return $Document2->add($doc_type);
	}
	return 0;
    }
    protected function headDefGet() {
        $this->selectDoc(0);
	$active_company_id=$this->Hub->acomp('company_id');
	$passive_company_id = $this->Hub->pcomp('company_id');
        $def_head=[
	    'doc_id'=>0,
            'doc_date'=>date('d.m.Y'),
            'doc_num'=>0,
            'doc_data'=>'',
            'doc_ratio'=>$this->Hub->pref('usd_ratio'),
            'doc_status_id'=>'',
            'label'=>$this->Hub->pcomp('label'),
            'passive_company_id'=>$passive_company_id,
            'curr_code'=>$this->Hub->pcomp('curr_code'),
            'vat_rate'=>$this->Hub->acomp('company_vat_rate'),
            'doc_type'=>1,
            'signs_after_dot'=>3
        ];
	$prev_doc = $this->get_row("SELECT 
		doc_type,
		signs_after_dot 
	    FROM 
		document_list 
	    WHERE 
		passive_company_id='$passive_company_id' 
		AND active_company_id='$active_company_id' 
		AND doc_type<10 
		AND is_commited=1 
	    ORDER BY cstamp DESC LIMIT 1");
        if( $prev_doc ){
            $def_head['doc_type']=$prev_doc->doc_type;
            $def_head['signs_after_dot']=$prev_doc->signs_after_dot;
        }
        $def_head['doc_num']=$this->getNextDocNum($def_head['doc_type']);
        return $def_head;
    }
    
    public $headGet=['int'];
    public function headGet( $doc_id ){
	if( $doc_id==0 ){
	    return $this->headDefGet();
	}
	$this->selectDoc($doc_id);
	$sql="
	    SELECT
		doc_id,
		active_company_id,
                (SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=active_company_id) active_company_label,
		passive_company_id,
                (SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=passive_company_id) label,
		IF(is_reclamation,-doc_type,doc_type) doc_type,
		is_reclamation,
		is_commited,
		notcount,
		vat_rate,
		use_vatless_price,
		signs_after_dot,
		doc_ratio,
		doc_num,
                doc_status_id,
		DATE_FORMAT(document_list.cstamp,'%d.%m.%Y') doc_date,
		doc_data,
		(SELECT last_name FROM user_list WHERE user_id=document_list.created_by) created_by,
		(SELECT last_name FROM user_list WHERE user_id=document_list.modified_by) modified_by,
                (SELECT last_name FROM user_list WHERE user_id=checkout_list.modified_by) checkout_modifier,
                checkout_status, checkout_id
	    FROM
		document_list
                    LEFT JOIN
                checkout_list ON checkout_list.parent_doc_id=document_list.doc_id
	    WHERE doc_id=$doc_id"
	;
	$head=$this->get_row($sql);
	$head->extra_expenses=$this->getExtraExpenses();
	return $head;
    }
    private function setType( $doc_type ){
	if( $this->isCommited() ){
	    return false;
	}
	else{
	    $doc_id = $this->doc('doc_id');
	    $next_doc_num = $this->getNextDocNum($doc_type);
	    $this->query("DELETE FROM document_view_list WHERE doc_id='$doc_id'");
	    $quantity_sign = $doc_type<0 ? -1 : 1;
	    $this->query("UPDATE document_entries SET product_quantity=ABS(product_quantity)*$quantity_sign WHERE doc_id=$doc_id");
	    $this->updateProps( array(
		'doc_type'=>abs($doc_type),
		'doc_num'=>$next_doc_num,
		'is_reclamation'=>($doc_type<0)
	    ));
	}
	return true;
    }
    public $headUpdate=['field'=>'string','new_val'=>'string'];
    public function headUpdate( $field, $new_val){
	switch( $field ){
	    case 'doc_ratio':
		$field='ratio';
		break;
	    case 'doc_num':
		$field='num';
		break;
	    case 'doc_date':
		$field='date';
		break;
	    case 'passive_company_id':
		if( $this->isCommited() ){
		    return false;
		}
		$doc_id=$this->doc('doc_id');
		$passive_company_id=$new_val;
		$this->db->query("UPDATE document_list SET passive_company_id=$passive_company_id WHERE doc_id=$doc_id");
		return true;
		break;
	    case 'active_company_id':
		if( $this->isCommited() ){
		    return false;
		}
		$doc_id=$this->doc('doc_id');
		$active_company_id=$new_val;
		$this->db->query("UPDATE document_list SET active_company_id=$active_company_id WHERE doc_id=$doc_id");
		return true;
		break;
	    case 'doc_type':
		return $this->setType($new_val);
	    case 'doc_status_id':
		return $this->setStatus(null,$new_val);
	    case 'extra_expenses':
		return $this->setExtraExpenses($new_val);
	}
	//$new_val=  rawurldecode($new_val);
	$Document2=$this->Hub->bridgeLoad('Document');
	$head_update_ok=$Document2->updateHead($new_val,$field);
        return $head_update_ok;
    }
    private function setExtraExpenses($expense){//not beautifull function at all
	$doc_type=$this->doc('doc_type');
	$doc_id=$this->doc('doc_id');
	if($doc_id && $doc_type==2){
	    //only for buy documents
	    $footer=$this->footerGet();
	    $expense_ratio=$expense/$footer->vatless+1;
	    return $this->query("UPDATE document_entries SET self_price=invoice_price*$expense_ratio WHERE doc_id=$doc_id");
	}
    }
    private function getExtraExpenses(){
	$doc_type=$this->doc('doc_type');
	$doc_id=$this->doc('doc_id');
	if($doc_id && $doc_type==2){
	    //only for buy documents
	    $footer=$this->footerGet();
	    $expense_ratio=$this->get_value("SELECT self_price/invoice_price FROM document_entries WHERE doc_id=$doc_id LIMIT 1");
	    $expense=$footer->vatless*($expense_ratio-1);
	    return $expense;
	}
	return 0;
    }
    
    public function setStatusByCode($doc_id,$new_status_code){
        if(!$new_status_code){
            return false;
        }
        $doc_status_id=$this->get_value("SELECT doc_status_id FROM document_status_list WHERE status_code='$new_status_code'");
        return $this->setStatus($doc_id,$doc_status_id);
    }
    
    public function setStatus($doc_id,$new_status_id){
        if(!$new_status_id){
            return false;
        }
        $this->Hub->set_level(2);
        $commited_only=$this->get_value("SELECT commited_only FROM document_status_list WHERE doc_status_id='$new_status_id'");
        if( $commited_only != $this->isCommited() ){
            return false;
        }
        if( !$doc_id ){
            $doc_id=$this->doc('doc_id');
        }
        $status_change_ok=$this->update('document_list',['doc_status_id'=>$new_status_id],['doc_id'=>$doc_id]);
        
        if( $new_status_id==2 ){//reserved 
            $this->reservedTaskAdd($doc_id);
        } else {
            $this->reservedTaskRemove($doc_id);
        }
        $this->reservedCountUpdate();
        return $status_change_ok;
    }
    
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
                JOIN
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
            product_reserved = reserved,
            product_awaiting = awaiting;";
        return $this->query($sql);
    }
}
