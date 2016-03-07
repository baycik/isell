<?php

include 'Data.php';

class Blank extends Data {
    
    public function Init() {
        $this->Base->LoadClass('Document');
    }

    public function fetchBlankList() {
        $sql = "SELECT 
                    doc_id,
                    icon_name,
                    doc_type_name,
                    dl.cstamp,
                    DATE_FORMAT(dl.cstamp, '%d.%m.%Y') as doc_date,
                    doc_num,
                    COALESCE(view_name, CONCAT('REG ', doc_type_name)) as view_name
                FROM
                    document_list dl
                        JOIN
                    document_types USING (doc_type)
                        LEFT JOIN
                    document_view_list USING (doc_id)
                        LEFT JOIN
                    document_view_types USING (view_type_id)
                WHERE
                    dl.doc_type > 9
                        AND dl.active_company_id = " . $this->Base->acomp('company_id') . "
                        AND dl.passive_company_id = " . $this->Base->pcomp('company_id') . "
                ORDER BY cstamp , doc_num";
        return $this->Base->get_list($sql);
    }

    public function fetchAvailBlanks() {
        $avail_docs = $this->Base->get_list("SELECT * FROM document_types WHERE doc_type>=10");
        foreach ($avail_docs as &$doc) {
            $doc['avail_views'] = $this->Base->get_list("SELECT view_type_id,view_name,IF(view_file='',0,1) AS only_reg FROM document_view_types WHERE doc_type='$doc[doc_type]'"); //
        }
        return $avail_docs;
    }

    /*
     * Must be carefull selecting as current Blank may select Invoice Document 
     * and make manipultion like delete update on it!
     */
    
    
    public function getBlank($doc_id) {
        $this->Base->svar('selectedBlankId',$doc_id);
        $this->Base->Document->selectDoc($this->Base->svar('selectedBlankId'));
        $blank = $this->Base->get_row("SELECT * FROM document_view_list JOIN document_view_types USING(view_type_id) WHERE doc_id='$doc_id'");
        if (!$blank) {//only registry record
            $doc_type = $this->Base->Document->doc('doc_type');
            $blank = $this->Base->get_row("SELECT view_name FROM document_view_types WHERE doc_type='$doc_type'");
        } elseif ($blank['html']) {
            $blank['html'] = stripslashes($blank['html']);
        } else {
            $blank['html'] = file_get_contents('views/rpt/' . $blank['view_file'], true);
            $blank['loaded_is_tpl'] = true;
        }
        $blank['doc_num'] = $this->Base->Document->doc('doc_num');
        $blank['doc_date'] = $this->Base->Document->doc('doc_date');
        $blank['doc_data'] = $this->Base->Document->doc('doc_data');
        return $blank;
    }

    public function addBlank($view_type_id, $register_only = false) {
        $doc_type = $this->Base->get_row("SELECT doc_type FROM document_view_types WHERE view_type_id='$view_type_id'", 0);
        $this->Base->Document->add($doc_type);
        if ($register_only == false){
            $this->Base->Document->insertView($view_type_id);
        }
        $doc_id=$this->Base->Document->doc('doc_id');
        $this->Base->svar('selectedBlankId',$doc_id);
        return $doc_id;
    }

    public function deleteBlank() {
        $this->Base->Document->selectDoc($this->Base->svar('selectedBlankId'));
        $this->Base->Document->uncommit();
    }

    public function saveBlank($num, $date, $html) {
        $this->Base->Document->selectDoc($this->Base->svar('selectedBlankId'));
        $doc_id = $this->Base->Document->doc('doc_id');
        $this->Base->Document->updateHead($num, 'num');
        $this->Base->Document->updateHead($date, 'date');
        $doc_view_id = $this->Base->get_row("SELECT doc_view_id FROM document_view_list WHERE doc_id='$doc_id'", 0);
        if ($doc_view_id) {
            $this->Base->Document->unfreezeView($doc_view_id);
            $this->Base->Document->updateView($doc_view_id, 'view_num', $num, false);
            $this->Base->Document->updateView($doc_view_id, 'view_date', $date, false);
            $this->Base->Document->freezeView($doc_view_id, $html);
        }
    }

    public function updateBlankReg($field, $value) {
        $this->Base->Document->selectDoc($this->Base->svar('selectedBlankId'));
        if ($field == 'num')
            $this->Base->Document->updateHead($value, 'num');
        if ($field == 'date')
            $this->Base->Document->updateHead($value, 'date');
        if ($field == 'data')
            $this->Base->Document->updateHead($value, 'doc_data');
    }

}
?>