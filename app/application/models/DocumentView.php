<?php
require_once 'DocumentItems.php';
class DocumentView extends DocumentItems{
    public $min_level=1;
    public function viewListFetch( $doc_id ){
	$this->check($doc_id);
	if( $doc_id ){
	    $this->selectDoc($doc_id);
	    $doc_type=$this->doc('doc_type');
	    $sql="SELECT 
			doc_view_id,
			view_num,
			view_name,
			DATE_FORMAT(tstamp, '%d.%m.%Y') AS view_date,
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
		    WHERE
			doc_types LIKE '%/$doc_type/%'
		    GROUP BY 
			view_type_id
		    ORDER BY
			view_hidden
		    ";
	    return $this->get_list($sql);	    
	} else {
	    return [];
	}

    }
    public function viewUpdate($doc_view_id, $is_extra, $field, $value='') {
	$this->check($doc_view_id,'int');
	$this->check($field);
	$this->check($value);
	$this->check($is_extra);
	
	if ( $this->isCommited() ){
	    $this->Base->set_level(2);
	}
	if ( $this->get_value("SELECT freezed FROM document_view_list WHERE doc_view_id='$doc_view_id'") ){
	    $this->Base->msg('Образ заморожен! Чтобы изменить снимите блокировку!');
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
		$this->Base->msg('USING UNALLOWED FIELD NAME');
		return false;
	    }
	    if ($field == 'view_date') {
		$field = 'tstamp';
		preg_match_all('/([0-9]{2})\.([0-9]{2})\.([0-9]{2,4})/', $value, $out);
		$value = date("Y-m-d H:i:s", mktime(0, 0, 0, $out[2][0], $out[1][0], $out[3][0]));
	    }
	}
	$user_id = $this->Base->svar('user_id');
	$this->query("UPDATE document_view_list SET $field='$value',modified_by='$user_id' WHERE doc_view_id='$doc_view_id'");
	return true;
    }
    public function viewDelete( $doc_view_id ){
	$Document2=$this->Base->bridgeLoad('Document');
	return $Document2->deleteView($doc_view_id);
    }
    public function viewCreate( $view_type_id ){
	$Document2=$this->Base->bridgeLoad('Document');
	return $Document2->insertView($view_type_id);
    }
    public function unfreezeView($doc_view_id) {
	$this->query("UPDATE document_view_list SET freezed=0, html='' WHERE doc_view_id='$doc_view_id'");
	return true;
    }

    public function freezeView($doc_view_id, $html) {
	$html = addslashes($html);
	$this->query("UPDATE document_view_list SET freezed=1, html='$html' WHERE doc_view_id='$doc_view_id'");
	return true;
    }
    
    public function documentViewGet(){
        $doc_view_id=$this->request('doc_view_id', 'int');
        $out_type=$this->request('out_type');
        $dump=$this->fillDump($doc_view_id);
	
	//print_r($dump);
	//exit;
	$ViewManager=$this->Base->load_model('ViewManager');
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
        $Utils=$this->Base->load_model('Utils');
	$Company=$this->Base->load_model('Company');
	$doc_view=$this->viewGet($doc_view_id);
	if( !$doc_view ){
	    return null;
	}
	//$doc_id=$doc_view->doc_id;
	
        //$this->selectDoc($doc_id);
	$head=$this->headGet($doc_view->doc_id);
	$rows=$this->entriesFetch( 1 );
	$footer=$this->footerGet();
	if( $head->doc_type==1 || $head->doc_type==3 ){
	    /*if sell document use straight seller=acomp else buyer=pcomp*/
	    $acomp=$Company->companyGet( $this->doc('active_company_id') );
	    $pcomp=$Company->companyGet( $this->doc('passive_company_id') );
	} else {
	    $pcomp=$Company->companyGet( $this->doc('active_company_id') );
	    $acomp=$Company->companyGet( $this->doc('passive_company_id') );	    
	}
        
	
        $doc_view->total_spell=$Utils->spellAmount($footer->total);
        $doc_view->loc_date=$Utils->getLocalDate($doc_view->tstamp);
	$doc_view->user_sign=$this->Base->svar('user_sign');
	$doc_view->user_position=$this->Base->svar('user_position');
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
		'email'=>$pcomp->company_email,
		'text'=>'Доброго дня'
	    ],
            'view'=>[
		'doc_view'=>$doc_view,
		'a'=>$acomp,
		'p'=>$pcomp,
                'head'=>$head,
                'rows'=>$rows,
                'footer'=>$footer,
                'director_name'=>$this->Base->pref('director_name'),
                'director_tin'=>$this->Base->pref('director_tin'),
                'accountant_name'=>$this->Base->pref('accountant_name'),
                'accountant_tin'=>$this->Base->pref('accountant_tin'),
            ]
        ];
        return $dump;
    }
}
