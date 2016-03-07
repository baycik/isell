<?php
require_once 'DocumentCore.php';
class DocumentBlank extends DocumentCore {
    public function listFetch( $page=1, $rows=30, $mode='' ) {
	$this->check($page,'int');
	$this->check($rows,'int');
	$this->check($mode);
	$having=$this->decodeFilterRules();
	$offset=($page-1)*$rows;
	if( $offset<0 ){
	    $offset=0;
	}
	$andwhere='';
	if( $mode==='show_only_pcomp_docs' ){
	    $pcomp_id=$this->Base->pcomp('company_id');
	    $andwhere.=" AND passive_company_id='$pcomp_id'";
	}
	$assigned_path=  $this->Base->svar('user_assigned_path');
	if( $assigned_path ){
	    $andwhere.=" AND path LIKE '$assigned_path%'";
	}
	$active_company_id=$this->Base->acomp('company_id');
        $sql = "SELECT 
                    doc_id,
                    doc_type_name,
                    doc_num,
		    IF(html>'','ok','unknown') saved,
		    label company_name,
                    icon_name doc_type_icon,
                    DATE_FORMAT(dl.cstamp, '%d.%m.%Y') as doc_date,
                    COALESCE(view_name, CONCAT('REG ', doc_type_name)) as view_name
                FROM
                    document_list dl
			JOIN
		    companies_list cl ON passive_company_id=company_id
			JOIN
		    companies_tree ct USING(branch_id)
                        JOIN
                    document_types USING (doc_type)
                        LEFT JOIN
                    document_view_list USING (doc_id)
                        LEFT JOIN
                    document_view_types USING (view_type_id)
                WHERE
                    dl.doc_type > 9
                        AND dl.active_company_id = '$active_company_id'
			    $andwhere
		HAVING $having
                ORDER BY html>'',cstamp , doc_num
		LIMIT $rows OFFSET $offset";
	$result_rows=$this->get_list($sql);
	$total_estimate=$offset+(count($result_rows)==$rows?$rows+1:count($result_rows));
	return array('rows'=>$result_rows,'total'=>$total_estimate);
    }
    public function availFetch() {
        $avail_docs = $this->get_list("SELECT * FROM document_types WHERE doc_type>=10");
        foreach ($avail_docs as &$doc) {
            $doc->avail_views = $this->get_list("SELECT view_type_id,view_name,IF(view_file='',0,1) AS only_reg FROM document_view_types WHERE doc_types LIKE '%/$doc->doc_type/%' AND view_file<>''");
        }
        return $avail_docs;
    }
    public function blankCreate( $view_type_id, $register_only = false ){
	if( $this->Base->pcomp('company_id') ){
	    $doc_types = $this->get_value("SELECT doc_types FROM document_view_types WHERE view_type_id='$view_type_id'");
	    $doc_types_arr=explode('/',$doc_types);
	    $doc_type=$doc_types_arr[1];
	    
	    $Document2=$this->Base->bridgeLoad('Document');
	    $Document2->add($doc_type);
	    if ($register_only === false){
		$Document2->insertView($view_type_id);
	    }
	    $doc_id=$Document2->doc('doc_id');
	    $this->Base->svar('selectedBlankId',$doc_id);
	    return $doc_id;
	}
	return 0;
    }
    public function blankGet($doc_id) {
        $this->Base->svar('selectedBlankId',$doc_id);
        $this->selectDoc($this->Base->svar('selectedBlankId'));
        $blank = $this->get_row("SELECT * FROM document_view_list JOIN document_view_types USING(view_type_id) WHERE doc_id='$doc_id'");
        if (!$blank) {//only registry record
            $doc_type = $this->doc('doc_type');
            $blank = $this->get_row("SELECT view_name FROM document_view_types WHERE doc_types LIKE '%/$doc_type/%'");
        } elseif ($blank->html) {
            $blank->html = stripslashes($blank->html);
        } else {
            $blank->html = file_get_contents('application/views/rpt/' . $blank->view_file, true);
            $blank->loaded_is_tpl = true;
        }
        $blank->doc_num = $this->doc('doc_num');
        $blank->doc_date = $this->doc('doc_date');
        $blank->doc_data = $this->doc('doc_data');
        return $blank;
    }
    public function getFillData(){
	$Company=$this->Base->load_model('Company');
	$Pref=$this->Base->load_model('Pref');
	$fillData = new stdClass();
	$fillData->a=$Company->companyGet($this->Base->acomp('company_id'));
	$fillData->p=$Company->companyGet($this->Base->pcomp('company_id'));
	$fillData->staff=$Pref->getStaffList();
	return $fillData;
    }
    public function save(){
	$num=$this->input->post('num');
	$date=$this->input->post('date');
	$html=$this->input->post('html');
	
	$this->selectDoc($this->Base->svar('selectedBlankId'));
	$doc_id = $this->doc('doc_id');
	$this->headUpdate('num', $num);
	$this->headUpdate('date', $date);
	$doc_view_id=$this->get_value("SELECT doc_view_id FROM document_view_list WHERE doc_id='$doc_id'");
	
        if ($doc_view_id) {
	    $View=$this->Base->load_model('DocumentView');
            $View->unfreezeView($doc_view_id);
            $View->viewUpdate($doc_view_id, false, 'view_num', $num);
            $View->viewUpdate($doc_view_id, false, 'view_date', $date);
            $View->freezeView($doc_view_id, $html);
	    return true;
        }
	return false;
    }
    public function blankDelete(){
	$this->selectDoc($this->Base->svar('selectedBlankId'));
	$doc_id = $this->doc('doc_id');
	$this->query("DELETE FROM document_view_list WHERE doc_id=$doc_id");
	$this->query("DELETE FROM document_list WHERE doc_id=$doc_id");
	return true;
    }
}
