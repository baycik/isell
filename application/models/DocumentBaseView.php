<?php

trait DocumentBaseView {
    protected function viewCreateLastFields( $view_type_id ){
        $acomp_id=$this->doc('active_company_id');
        $pcomp_id=$this->doc('passive_company_id');
        $sql="SELECT
                @last_efields:=view_efield_values
            FROM 
                document_view_list dvl 
                    JOIN 
                document_list USING(doc_id)
            WHERE 
                view_type_id='$view_type_id' 
                AND active_company_id='$acomp_id' 
                AND passive_company_id='$pcomp_id' 
            ORDER BY dvl.tstamp DESC
            LIMIT 1";
        $this->query("SET @last_efields:=NULL");
        return $this->get_row($sql);
    }

    protected function viewNumNext($view_role, $creation_mode = null) {
        $Pref=$this->Hub->load_model('Pref');
        
        $counter_increase=$creation_mode=='not_increase_number'?0:1;
        $counter_name="counterViewNum_".$view_role;
        $nextNum=$Pref->counterNumGet($counter_name,null,$counter_increase);
        if( !$nextNum ){
            $view_name=$this->get_value("SELECT view_name FROM document_view_types WHERE view_role='$view_role'");
            $counter_title=$view_name??'???';
            $Pref->counterCreate($counter_name,null,$counter_title);
            $nextNum=$Pref->counterNumGet($counter_name,null,$counter_increase);
        }
        return $nextNum;
    }
    
    protected function viewGet( $doc_view_id ){
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

    protected function viewGetDump($doc_view_id){
	$doc_view=$this->viewGet($doc_view_id);
	if( !$doc_view ){
	    return null;
	}
	if( $doc_view->html ){
	    return [
		    'html'=>$doc_view->html,
		    'title'=>"$doc_view->view_name №$doc_view->view_num от $doc_view->view_date_dot",
		    'user_data'=>[
			'email'=>$pcomp->company_email,
			'text'=>'Доброго дня'
		    ],
		];
	}
        $Utils=$this->Hub->load_model('Utils');
	$Company=$this->Hub->load_model('Company');
        

	$head=$this->headGet($doc_view->doc_id);
	$rows=$this->entryListGet($doc_view->doc_id);
	$footer=$this->footGet($doc_view->doc_id);
        
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
        
        $view_title=$doc_view->view_name;
        if($doc_view->view_num){
            $view_title.=" №$doc_view->view_num";
        }
        if($doc_view->date_dot){
            $view_title.=" от $doc_view->date_dot";
        }
        
        $dump=[
	    'tpl_files'=>$doc_view->view_tpl,
	    'title'=>$view_title,
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
        
    public function viewExport(int $doc_view_id,string $out_type) {
        $dump=$this->viewGetDump($doc_view_id);
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
    

    public function viewCreate(int $doc_id, int $view_type_id) {
        $this->documentSelect($doc_id);
        $doc_type = $this->doc('doc_type');
        $view_role=$this->get_value("SELECT view_role FROM document_view_types WHERE view_type_id='$view_type_id'");
        $view_num = $this->doc('doc_num');
        $view_stamp = $this->doc('cstamp');
        
        if( $view_role!='bill' ){
            $this->Hub->set_level(2);
        }
        if( $view_role=='sell_bill' ){
            if( !$this->isCommited() ){
                throw new Exception('document_uncommited',400);
            }
        } else 
        if( $view_role=='tax_bill' ){
            if( !$this->isCommited() ){
                throw new Exception('document_uncommited',400);
            }
            $sql="SELECT 
                    doc_view_id 
                FROM 
                    document_view_list dvl
                WHERE 
                    doc_id=$doc_id 
                    AND view_role='tax_bill'";
            $tax_bill_exists=$this->get_value($sql);
            if( $tax_bill_exists ){
                throw new Exception('view_type_duplicate',400);
            }
            if( $doc_type==1 || $doc_type==3 ){
                $view_num = $this->viewNumNext($view_role);
            } else {
                $view_num='';
            }
        }
        $this->viewCreateLastFields( $view_type_id );
        $sql="
            INSERT INTO
                document_view_list
            SET 
                doc_id='$doc_id',
                view_type_id='$view_type_id',
                view_efield_values=@last_efields,
                view_num='$view_num',
                view_role='$view_role',
                tstamp='$view_stamp',
                html=''
            ";
        $this->query($sql);
        return $this->db->insert_id();
    }

    public function viewUpdate( int $doc_id, int $doc_view_id, string $is_extra, string $field, string $value='' ) {
        $this->documentSelect($doc_id);
	if ( $this->isCommited() ){
	    $this->Hub->set_level(2);
	}
	if ( $this->get_value("SELECT freezed FROM document_view_list WHERE doc_view_id='$doc_view_id'") ){
            throw new Exception('view_is_freezed',403);
	}
	if ( $is_extra==='extra' ) {
	    $extra_fields_str = $this->get_value("SELECT COALESCE(view_efield_values,'{}') FROM document_view_list WHERE doc_view_id='$doc_view_id'");
            $extra_fields = json_decode($extra_fields_str);
	    $extra_fields->$field = $value;
	    $field = 'view_efield_values';
	    $value = addslashes(json_encode($extra_fields));
	} else {
	    if ( !in_array($field, array('view_num', 'view_date')) ){
                throw new Exception('view_field_unallowed',403);
	    }
	    if ($field == 'view_date') {
		$field = 'tstamp';
	    }
	}
	$user_id = $this->Hub->svar('user_id');
	$this->query("UPDATE document_view_list SET $field='$value',modified_by='$user_id' WHERE doc_view_id='$doc_view_id'");
	return true;
    }

    public function viewDelete(int $doc_view_id) {
        $doc_id=$this->get_value("SELECT doc_id FROM document_view_list WHERE doc_view_id='$doc_view_id'");
        $this->documentSelect($doc_id);
        $this->query("DELETE FROM document_view_list WHERE doc_view_id='$doc_view_id'");
	return true;
    }

    public function viewListGet(int $doc_id) {
	if( $doc_id ){
	    $this->documentSelect($doc_id);
            $acomp_id=$this->doc('active_company_id');
	    $doc_type=$this->doc('doc_type');
            $blank_set=$this->Hub->pref('blank_set');
	    $sql="SELECT 
			doc_view_id,
			view_num,
			view_name,
                        dvt.view_role,
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
			view_type_id,pref_int,pref_value
		    ORDER BY
			ISNULL(doc_view_id),pref_int-DATEDIFF(NOW(),pref_value) DESC,view_hidden
		    ";
	    return $this->get_list($sql);	    
	} else {
	    return [];
	}
    }

    public function viewListCreate(int $doc_id, array $view_list) {
        return null;
    }

    public function viewListUpdate(int $doc_id, array $view_list) {
        return null;
    }

    public function viewListDelete(int $doc_id, array $view_id_list) {
        return null;
    }
}