<?php

class PrefOld {

    public function getStaffList() {
        $list = array('identifier' => 'full_name', 'label' => 'full_name', 'items' => array());
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
                    CONCAT(last_name,' ',first_name,' ',middle_name) AS full_name 
                    FROM " . BAY_DB_MAIN . ".user_list
                    WHERE first_name IS NOT NULL AND last_name IS NOT NULL";
        $list['items'] = $this->Base->get_list($sql);
        return $list;
    }

    public function getPrefs($pref_names) {// "pref1,pref2,pref3"
	$active_company_id=$this->Base->acomp('company_id');
        $where = "pref_name='" . str_replace(',', "' OR pref_name='", $pref_names) . "'";
        $prefs = $this->Base->get_row("SELECT GROUP_CONCAT(pref_value) pvals,GROUP_CONCAT(pref_name) pnames FROM pref_list WHERE active_company_id='$active_company_id' AND ($where)");
        return array_combine(explode(',', $prefs['pnames']), explode(',', $prefs['pvals']));
    }

    public function setPrefs($pref_list) {
	$active_company_id=$this->Base->acomp('company_id');
        foreach ($pref_list as $pref_name => $pref_value) {
            if (preg_match('/^[^,]*$/', $pref_value))//Should not contain comma
                $this->Base->query("REPLACE pref_list SET active_company_id='$active_company_id', pref_name='$pref_name', pref_value='$pref_value'");
            else
                $this->Base->response_error("Не дожно содержать запятую $pref_value ");
        }
        return true;
    }

    public function userListGet() {
        $user_id = $this->Base->svar('user_id');
        $where = ($this->Base->svar('user_level') < 4) ? "WHERE user_id='$user_id'" : "";
        $sql = "SELECT * FROM " . BAY_DB_MAIN . ".user_list $where ORDER BY user_id<>'$user_id', user_level DESC";
        return $this->Base->get_list($sql);
    }

    public function userDetailUpdate($user_id, $field_name, $field_value) {
        $where = "user_id='$user_id'";
        $set = "$field_name='$field_value'";
        $fields = array(
            1 => array('user_login', 'user_pass', 'user_sign'),
            2 => array('user_sign', 'first_name', 'middle_name', 'last_name', 'id_type', 'id_serial', 'id_number', 'id_given_by', 'id_date'),
            4 => array('user_level', 'user_position', 'user_assigned_path', 'user_assigned_stat', 'user_permissions')
        );
        if (in_array($field_name, $fields[1])) {
            $this->Base->set_level(1);
            if ($field_name === 'user_login') {
                $current_user_id = $this->Base->svar('user_id');
                if ($current_user_id !== $user_id) {
                    $this->Base->set_level(4);
                }
            }
        } else if (in_array($field_name, $fields[2])) {
            $this->Base->set_level(2);
        } else if (in_array($field_name, $fields[4])) {
            $this->Base->set_level(4);
            if ($field_name === 'user_level') {
                if ($field_value < 4 && !$this->userAdminCountCheck($user_id)) {
                    return false;
                }
                if ($field_value == 0) {
                    $set.=",user_pass='" . md5('') . "'";
                    $this->Base->msg("Пароль пользователя был сброшен!");
                } else {
                    $pass_notset = $this->Base->get_row("SELECT 1 FROM " . BAY_DB_MAIN . ".user_list WHERE user_id='$user_id' AND (user_pass='" . md5('') . "' OR user_login='')");
                    if ($pass_notset) {
                        $this->Base->msg("Для открытия доступа установите логин и пароль пользователя!");
                        return false;
                    }
                }
            }
        } else
            return false;
        $this->Base->query("UPDATE " . BAY_DB_MAIN . ".user_list SET $set,nick=CONCAT(SUBSTR(last_name,1,1),SUBSTR(first_name,1,1),SUBSTR(middle_name,1,1)) WHERE $where");
        return mysqli_affected_rows($this->Base->db_link) ? true : false;
    }

    private function userAdminCountCheck($user_id) {
        $admin_count = $this->Base->get_row("SELECT COUNT(*) FROM " . BAY_DB_MAIN . ".user_list WHERE user_level=4 AND user_id<>'$user_id'", 0);
        if ($admin_count < 1) {
            $this->Base->msg("Среди пользователей должен остаться хотябы один администратор!\n Изменение не сохранено!");
            return false;
        }
        return true;
    }

    public function userAdd() {
        $this->Base->set_level(3);
        $this->Base->query("INSERT INTO " . BAY_DB_MAIN . ".user_list SET user_level=0,user_pass='" . md5('') . "'");
        return true;
    }

    public function userDelete($user_id) {
        $this->Base->set_level(4);
        if (!$this->userAdminCountCheck($user_id)) {
            return false;
        }
        $this->Base->query("DELETE FROM " . BAY_DB_MAIN . ".user_list WHERE user_id='$user_id'");
        return true;
    }

    public function userPassChange($user_id, $curr_pass, $new_pass) {
        if ($new_pass === '')
            return false;
        $this->Base->set_level(1);
        $curr_hash = md5($curr_pass);
        $new_hash = md5($new_pass);
        $this->Base->query("UPDATE " . BAY_DB_MAIN . ".user_list SET user_pass='$new_hash' WHERE user_id='$user_id' AND user_pass='$curr_hash'"); //,user_level=IF(user_level=0,1,user_level)
        return mysqli_affected_rows($this->Base->db_link) ? true : false;
    }

    public $fns = array(
        'prefGet' => '',
        'prefUpdate' => '[a-z_]+ field,(string) value'
    );

    public function prefGet() {
	$active_company_id=$this->Base->acomp('company_id');
        $ratios = $this->Base->get_list("SELECT * FROM pref_list WHERE active_company_id='$active_company_id'");
        $resp = array();
        foreach ($ratios as $val) {
            $resp[$val['pref_name']] = $val['pref_value'];
        }
        return $resp;
    }

    public function prefUpdate($field, $value) {
	$active_company_id=$this->Base->acomp('company_id');
	if( $field=='default_debt_limit' ){
	    $this->Base->set_level(4);
	}
	$this->Base->set_level(2);
        $this->Base->query("REPLACE pref_list SET active_company_id='$active_company_id', pref_value='$value', pref_name='$field'");
    }
    
    
    
    
    
    //////////////////////////////////////////////
    //COUNTER SECTION
    //////////////////////////////////////////////
    public function counterNumGet(  string $counter_name, int $counter_acomp_id=null, bool $counter_increase=false ){
        if( $counter_acomp_id===null ){
            $counter_acomp_id=$this->Base->acomp('company_id');
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
        $counter=$this->Base->get_row("SELECT * FROM pref_list WHERE pref_name='$counter_name' AND active_company_id='$counter_acomp_id'");
        if( !$counter ){
            return null;
        }
        $counter['data']= json_decode($counter['pref_value'],true);
        return (object) $counter;
    }
    
    
    public function counterCreate(  string $counter_name, int $counter_acomp_id=null, string $counter_title ){
        if( $counter_acomp_id===null ){
            $counter_acomp_id=$this->Base->acomp('company_id');
        }
        $this->Base->query("INSERT pref_list SET active_company_id=$counter_acomp_id,pref_name='$counter_name',pref_int=1");
        $counter_data=[
            'counter_title'=>$counter_title
        ];
        return $this->counterUpdate($counter_name, $counter_acomp_id, $counter_data, 1);
    }
    
    public function counterUpdate( string $counter_name, int $counter_acomp_id=0, array $counter_data=null, int $counter_int=0 ){
        $set='';
        $set_delimeter='';
        $counter_data_combined=$this->counterGet( $counter_name, $counter_acomp_id )->data;
        $counter_data_combined['modified_at']=date("Y-m-d H:i:s");
        if( $counter_data!=null ){
            $counter_data_combined=array_merge($counter_data_combined,$counter_data);
            $set.="pref_value='". addslashes(json_encode($counter_data_combined))."'";
            $set_delimeter=',';
        }
        if( $counter_int!=0 ){
            $set.=$set_delimeter."pref_int='$counter_int'";
        }
        $this->Base->query("UPDATE pref_list SET $set WHERE active_company_id='$counter_acomp_id' AND pref_name='$counter_name'");
        return true;
    }
}