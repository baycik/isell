<?php

class Pref {

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
        return mysql_affected_rows() ? true : false;
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
        return mysql_affected_rows() ? true : false;
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
}

?>