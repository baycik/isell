<?php
require_once 'Catalog.php';
class Pref extends Catalog {
    public $min_level=1;
    public $getStaffList=[];
    public function getStaffList() {
        $sql = "(SELECT 
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
		    user_is_staff AND first_name IS NOT NULL AND last_name IS NOT NULL)
                UNION
                (SELECT
                    0,
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-',
                    '-'
                )";
        return $this->get_list($sql);
    }
    public $getPrefs=['[a-zA-Z0-9_\-,]+'];
    public function getPrefs($pref_names="") {// "pref1,pref2,pref3"
	$active_company_id=$this->Hub->acomp('company_id');
	if( $pref_names ){
	    $where = "WHERE active_company_id='$active_company_id' AND (pref_name='" . str_replace(',', "' OR pref_name='", $pref_names) . "')"; 
	} else {
	    $where = "WHERE active_company_id='$active_company_id'";
	}
        $this->query("SET SESSION group_concat_max_len = 1000000;");
        $prefs = $this->get_row("SELECT GROUP_CONCAT(pref_value SEPARATOR '~|~') pvals,GROUP_CONCAT(pref_name SEPARATOR '~|~') pnames FROM pref_list  $where");
        return (object) array_combine(explode('~|~', $prefs->pnames), explode('~|~', $prefs->pvals));
    }
    //public $setPrefs=['[a-zA-Z0-9_\-]+','[^|]+'];
    public function setPrefs( string $field, string $value='' ) {
	$active_company_id=$this->Hub->acomp('company_id');
	$this->Hub->set_level(2);
	if( !$field ){
	    return false;
	}
	$this->query("REPLACE pref_list SET pref_name='$field',pref_value='$value',active_company_id='$active_company_id'");
	return $this->db->affected_rows()>0?1:0;
    }
    
    
    
    
    
    public function prefListGet( string $pref_name_query, int $acomp_id=null ){
        if( $acomp_id===null ){
            $acomp_id=$this->Hub->acomp('company_id');
        }
        return $this->get_list("SELECT * FROM pref_list WHERE pref_name LIKE '$pref_name_query' AND active_company_id=$acomp_id ORDER BY pref_name");
    }
    
    
    //////////////////////////////////////////////
    //COUNTER SECTION
    //////////////////////////////////////////////
    public function counterNumGet(  string $counter_name, int $counter_acomp_id=null, bool $counter_increase=false ){
        if( $counter_acomp_id===null ){
            $counter_acomp_id=$this->Hub->acomp('company_id');
        }
        $counter=$this->counterGet($counter_name,$counter_acomp_id);
        if( !$counter ){
            return null;
        }
        if( $counter_increase ){
            $pref_int=$counter->pref_int+1;
            $modified_year= substr($counter->data['modified_at'], 0, 4);
            if( $modified_year!=date("Y") ){
                $pref_int=1;
            }
            $this->counterUpdate($counter_name,$counter_acomp_id,(array) $counter->data,$pref_int);
        }
        return ($counter->data['counter_prefix']??'').$counter->pref_int;
    }
    
    public function counterGet( string $counter_name, int $counter_acomp_id ){
        $counter=$this->get_row("SELECT * FROM pref_list WHERE pref_name='$counter_name' AND active_company_id='$counter_acomp_id'");
        if( !$counter ){
            return null;
        }
        $counter->data= json_decode($counter->pref_value??'{}',true);
        return $counter;
    }
    
    
    public function counterCreate(  string $counter_name, int $counter_acomp_id=null, string $counter_title ){
        if( $counter_acomp_id===null ){
            $counter_acomp_id=$this->Hub->acomp('company_id');
        }
        $this->create('pref_list',['active_company_id'=>$counter_acomp_id,'pref_name'=>$counter_name,'pref_int'=>1]);
        $counter_data=[
            'counter_title'=>$counter_title
        ];
        return $this->counterUpdate($counter_name, $counter_acomp_id, $counter_data, 1);
    }
    
    public function counterUpdate( string $counter_name, int $counter_acomp_id=0, array $counter_data=null, int $counter_int=0 ){
        $pref_updated=[];
        $counter_data_combined=$this->counterGet( $counter_name, $counter_acomp_id )->data;
        $counter_data['modified_at']=date("Y-m-d H:i:s");
        if( $counter_data!=null ){
            $counter_data_combined=array_merge($counter_data_combined,$counter_data);
            $pref_updated['pref_value']= json_encode($counter_data_combined);
        }
        if( $counter_int!=0 ){
            $pref_updated['pref_int']=$counter_int;
        }
        return $this->update("pref_list",$pref_updated,['active_company_id'=>$counter_acomp_id,'pref_name'=>$counter_name]);
    }
    
    public function counterDelete( string $counter_name, int $counter_acomp_id ){
        $this->Hub->set_level(4);
        return $this->delete("pref_list",['active_company_id'=>$counter_acomp_id,'pref_name'=>$counter_name]);
    }
}