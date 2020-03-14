<?php
class DocumentView extends DocumentItems{
    public $min_level=1;
    public $viewListFetch=['int'];
    public function viewListFetch( $doc_id ){
	$blank_set=$this->Hub->pref('blank_set');
	$acomp_id=$this->Hub->acomp('company_id');
	if( $doc_id ){
	    $this->selectDoc($doc_id);
	    $doc_type=$this->doc('doc_type');
	    $sql="SELECT 
			doc_view_id,
			view_num,
			view_name,
			DATE_FORMAT(tstamp, '%Y-%m-%d') AS view_date,
                        tstamp,
			dvt.view_type_id,
			view_efield_values,
			view_efield_labels,
			view_file,
			freezed,
			IF(view_hidden AND doc_view_id IS NULL,1,0) view_hidden
		    FROM
			document_view_types dvt
			    LEFT JOIN 
			document_view_list dvl ON dvl.view_type_id=dvt.view_type_id AND doc_id = '$doc_id'
			    LEFT JOIN
			pref_list ON active_company_id='$acomp_id' AND pref_name=CONCAT('view_fetch_count_',dvt.view_type_id)
		    WHERE
			doc_types LIKE '%/$doc_type/%' AND blank_set='$blank_set'
		    GROUP BY 
			view_type_id
		    ORDER BY
			pref_int-DATEDIFF(NOW(),pref_value) DESC,ISNULL(doc_view_id),view_hidden
		    ";
	    return $this->get_list($sql);	    
	} else {
	    return [];
	}
    }
    
    public function viewUpdate(int $doc_view_id, string $is_extra, string $field, string $value='') {
	if ( $this->isCommited() ){
	    $this->Hub->set_level(2);
	}
	if ( $this->get_value("SELECT freezed FROM document_view_list WHERE doc_view_id='$doc_view_id'") ){
	    $this->Hub->msg('Образ заморожен! Чтобы изменить снимите блокировку!');
	    return false;
	}
	if ( $is_extra==='extra' ) {
	    $extra_fields_str = $this->get_value("SELECT view_efield_values FROM document_view_list WHERE doc_view_id='$doc_view_id'");
            $extra_fields = json_decode($extra_fields_str);
	    $extra_fields->$field = $value;
	    $field = 'view_efield_values';
	    $value = addslashes(json_encode($extra_fields));
	} else {
	    if ( !in_array($field, array('view_num', 'view_date')) ){
		$this->Hub->msg('USING UNALLOWED FIELD NAME');
		return false;
	    }
	    if ($field == 'view_date') {
		$field = 'tstamp';
		//preg_match_all('/([0-9]{2})\.([0-9]{2})\.([0-9]{2,4})/', $value, $out);
		//$value = date("Y-m-d H:i:s", mktime(0, 0, 0, $out[2][0], $out[1][0], $out[3][0]));
	    }
	}
	$user_id = $this->Hub->svar('user_id');
	$this->query("UPDATE document_view_list SET $field='$value',modified_by='$user_id' WHERE doc_view_id='$doc_view_id'");
	return true;
    }

    public function viewDelete( int $doc_view_id ){
	$Document2=$this->Hub->bridgeLoad('Document');
	return $Document2->deleteView($doc_view_id);
    }

    public function viewCreate( int $view_type_id ){
	$Document2=$this->Hub->bridgeLoad('Document');
	$view_id= $Document2->insertView($view_type_id);
        if( $view_id ){
            $this->viewIncreaseFetchCount($view_type_id);
        }
	return $view_id;
    }
    private function viewIncreaseFetchCount($view_type_id){
	$acomp_id=$this->Hub->acomp('company_id');
	$sql="INSERT INTO 
		pref_list 
	    SET 
		active_company_id='$acomp_id',
		pref_name='view_fetch_count_$view_type_id',
		pref_value=NOW(),
		pref_int=1
		ON DUPLICATE KEY UPDATE pref_value=NOW(),pref_int=pref_int+1";
	$this->query($sql);
    }
    public $unfreezeView=['int'];
    public function unfreezeView($doc_view_id) {
	$this->query("UPDATE document_view_list SET freezed=0, html='' WHERE doc_view_id='$doc_view_id'");
	return true;
    }
    public $freezeView=['int','string'];
    public function freezeView($doc_view_id, $html) {
	$html = addslashes($html);
	$this->query("UPDATE document_view_list SET freezed=1, html='$html' WHERE doc_view_id='$doc_view_id'");
	return true;
    }
    public $documentViewGet=['doc_view_id'=>'int','out_type'=>'string'];
    public function documentViewGet($doc_view_id,$out_type){
        $dump=$this->fillDump($doc_view_id);
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
    
    private function viewGet( $doc_view_id ){
	$sql="SELECT
		*,
                (SELECT doc_data FROM document_list dl WHERE dl.doc_id=dvl.doc_id) doc_data
	    FROM 
		document_view_list dvl
		    JOIN
		document_view_types USING (view_type_id)
	    WHERE 
		doc_view_id='$doc_view_id'";
	return $this->get_row($sql);
    }
    
    private function fillDump($doc_view_id){
	$doc_view=$this->viewGet($doc_view_id);
	if( !$doc_view ){
	    return null;
	}
	if( $doc_view->html ){
	    return [
		    'html'=>$doc_view->html,
		    'title'=>$doc_view->view_name,
		    'user_data'=>[
			'email'=>$pcomp->company_email,
			'text'=>'Доброго дня'
		    ],
		];
	}
        $Utils=$this->Hub->load_model('Utils');
	$Company=$this->Hub->load_model('Company');
	//$doc_id=$doc_view->doc_id;
	
        //$this->selectDoc($doc_id);
	$head=$this->headGet($doc_view->doc_id);
	$rows=$this->entriesFetch( 1 );
	$footer=$this->footerGet();
        
        $acomp=$Company->companyGet( $this->doc('active_company_id') );
        $pcomp=$Company->companyGet( $this->doc('passive_company_id') );
        
        
	if( $head->doc_type==1 || $head->doc_type==3 ){
	    /*if sell document use straight seller=acomp else buyer=pcomp*/
	    $seller=$acomp;
	    $buyer=$pcomp;
            
            $AccountsData=$this->Hub->load_model('AccountsData');
            $doc_view->debt=$AccountsData->clientDebtGet();
            
	    $reciever_email=$pcomp->company_email;
	} else {
	    $seller=$pcomp;
	    $buyer=$acomp;
	    $reciever_email=$acomp->company_email;
	}
        
	
        $doc_view->total_spell=$Utils->spellAmount($footer->total);
        $doc_view->loc_date=$Utils->getLocalDate($doc_view->tstamp);
	$doc_view->user_sign=$this->Hub->svar('user_sign');
	$doc_view->user_position=$this->Hub->svar('user_position');
	$doc_view->date=date('dmY', strtotime($doc_view->tstamp));
	$doc_view->date_dot=date('d.m.Y', strtotime($doc_view->tstamp));
	$doc_view->entries_num=count($rows);
	if( $doc_view->view_efield_values ){
	    $doc_view->extra=json_decode($doc_view->view_efield_values);
	} else {
	    $doc_view->extra=json_decode("{}");
	}
        
        $dump=[
	    'tpl_files'=>$doc_view->view_tpl,
	    'title'=>$doc_view->view_name,
	    'user_data'=>[
		'email'=>$reciever_email,
		'text'=>'Доброго дня'
	    ],
            'view'=>[
		'doc_view'=>$doc_view,
		'a'=>$acomp,
		'p'=>$pcomp,
                'seller'=>$seller,
                'buyer'=>$buyer,
                'head'=>$head,
                'rows'=>$rows,
                'footer'=>$footer,
                'director_name'=>$this->Hub->pref('director_name'),
                'director_tin'=>$this->Hub->pref('director_tin'),
                'accountant_name'=>$this->Hub->pref('accountant_name'),
                'accountant_tin'=>$this->Hub->pref('accountant_tin'),
            ]
        ];
        return $dump;
    }
}
