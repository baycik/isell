<?php
require_once 'Catalog.php';
class Pref extends Catalog {
    public function getStaffList() {
        $sql = "SELECT 
                    user_id,
                    user_position,
                    first_name,
                    middle_name,
                    last_name,
                    id_type,
                    id_serial,
                    id_number,
                    id_given_by,
                    id_date,
                    CONCAT(last_name,' ',first_name,' ',middle_name) AS full_name,
                    CONCAT(last_name,' ',first_name,' ',middle_name) AS label 
                FROM 
		    " . BAY_DB_MAIN . ".user_list
                WHERE 
		    first_name IS NOT NULL AND last_name IS NOT NULL";
        return $this->get_list($sql);
    }
    public function getPrefs($pref_names="") {// "pref1,pref2,pref3"
	$active_company_id=$this->Base->acomp('company_id');
	$this->check($pref_names,'[a-z_,]+');
	if( $pref_names ){
	    $where = "WHERE active_company_id='$active_company_id' AND (pref_name='" . str_replace(',', "' OR pref_name='", $pref_names) . "')"; 
	} else {
	    $where = "WHERE active_company_id='$active_company_id'";
	}
        $prefs = $this->get_row("SELECT GROUP_CONCAT(pref_value SEPARATOR '|') pvals,GROUP_CONCAT(pref_name SEPARATOR '|') pnames FROM pref_list  $where");
        return (object) array_combine(explode('|', $prefs->pnames), explode('|', $prefs->pvals));
    }
    public function setPrefs($field,$value='') {
	$active_company_id=$this->Base->acomp('company_id');
	$this->check($field,'[a-z_]+');
	$this->check($value,'[^|]+');
	if( $field==='usd_ratio' ){
	    $this->Base->set_level(2);
	} else {
	    $this->Base->set_level(3);	    
	}
	$this->query("REPLACE pref_list SET pref_name='$field',pref_value='$value',active_company_id='$active_company_id'");
	return $this->db->affected_rows()>0?1:0;
    }

}
