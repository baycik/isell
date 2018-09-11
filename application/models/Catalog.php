<?php

abstract class Catalog extends CI_Model {

    public $min_level = 1;

    protected function check(&$var, $type = null) {
        switch ($type) {
            case 'raw':
                break;
            case 'int':
                $var = (int) $var;
                break;
            case 'double':
                $var = (float) $var;
                break;
            case 'bool':
                $var = (bool) $var;
                break;
            case 'escape':
                $var = $this->db->escape_identifiers($var);
                break;
            case 'string':
                $var = addslashes($var);
                break;
            case 'json':
                $var = json_decode($var, true);
                break;
            default:
                if ($type) {
                    $matches = [];
                    preg_match('/' . $type . '/u', $var, $matches);
                    $var = isset($matches[0]) ? $matches[0] : null;
                } else {
                    $var = addslashes($var);
                }
        }
    }

    public function request($name, $type = null, $default = null) {
        $value = $this->input->get_post($name);
        if ($value !== null) {
            $this->check($value, $type);
            return $value;
        }
        return $default;
    }
    
    protected function transliterate($input,$direction='fromlatin'){
        $latin="qwertyuiop[]asdfghjkl;'zxcvbnm,./";
        $cyrilic="йцукенгшщзхъфывапролджэячсмитьбю.";
        $output="";
        for($i=0;$i<mb_strlen($input);$i++){
            $letter=mb_substr($input,$i,1);
            if( $direction=='fromlatin' ){
                $pos=mb_strpos($latin, $letter);
                $output.=($pos===false)?$letter:mb_substr($cyrilic,$pos,1);
            } else {
                $pos=mb_strpos($cyrilic, $letter);
                $output.=($pos===false)?$letter:mb_substr($latin,$pos,1);
            }
        }
        return $output;
    }

    ////////////////////////////////////////////////////
    // CORE LIST FUNCTIONS
    ////////////////////////////////////////////////////
    private function check_error() {
        $error = $this->db->error();
        if ($error['code']) {
            $this->Hub->db_msg();
            return true;
        }
        return false;
    }

    protected function query($query, $error_warn = true) {
        if (is_string($query)) {
            $query = $this->db->query($query);
        }
        if ($error_warn && $this->check_error()) {
            return NULL;
        }
        return $query;
    }

    protected function get_list($query) {
        $list = array();
        $result = $this->query($query);
        foreach ($result->result() as $row) {
            $list[] = $row;
        }
        $result->free_result();
        return $list;
    }

    protected function get_row($query) {
        $result = $this->query($query);
        if ($result && $result->num_rows() > 0) {
            $row = $result->row();
            $result->free_result();
            return $row;
        }
        return null;
    }

    protected function get_value($query) {
        $row = $this->query($query)->row_array();
        if ($row) {
            foreach ($row as $value) {
                return $value;
            }
        }
        return null;
    }

    protected function get($table, $key) {
        return $this->db->get_where($table, $key)->row();
    }

    protected function create($table, $data) {
        $this->db->insert($table, $data);
        $newid = $this->db->insert_id();
        $ok = $this->db->affected_rows() > 0;
        $this->check_error();
        return $newid ? $newid : $ok;
    }

    protected function update($table, $data, $key) {
        $this->db->update($table, $data, $key);
        $ok = $this->db->affected_rows();
        $this->check_error();
        return $ok;
    }

    protected function delete($table, $key, $key_values = null) {
        if ($key_values) {
            $this->db->where_in($key, $key_values);
            $this->db->delete($table);
        } else {
            $this->db->delete($table, $key);
        }
        $ok = $this->db->affected_rows();
        $this->check_error();
        return $ok;
    }

    protected function rowUpdate($table, $data, $key) {
        return $this->update($table, $data, $key);
    }

    protected function rowUpdateField($table, $key_field, $id, $field, $value) {
        $key = array($key_field => $id);
        $data = array($field => $value);
        return $this->update($table, $data, $key);
    }
    
    protected function log($message){
	$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$this->create('log_list',['message'=>$message,'url'=>$url]);
    }

    ////////////////////////////////////////////////////
    // CORE TREE FUNCTIONS
    ////////////////////////////////////////////////////
    protected function treeFetch($table, $parent_id = null, $depth = 'all', $super_path = '', $level = 0, $order = "is_leaf,label") {
        $where = array("level IS NULL OR level<=$level");
        if ($super_path !== '') {
            $where[] = "path LIKE '$super_path" . ($parent_id === null ? '' : '%') . "'";
        }
        if ($parent_id !== null) {
            $where[] = "parent_id=$parent_id";
        }
        $where = implode(' AND ', $where);
        $res = $this->db->query("SELECT * FROM $table WHERE $where ORDER BY $order");
        $branches = array();
        foreach ($res->result() as $row) {
            //$this->treeUpdatePath($table, $row->branch_id);
            if ($depth == 'top') {
                $row->state = $row->is_leaf ? '' : 'closed';
            } else {
                $row->children = $this->treeFetch($table, $row->branch_id, 'all');
            }
            $branches[] = $row;
        }
        $res->free_result();
        return $branches;
    }

    protected function treeCreate($table, $type, $parent_id, $label = '', $calc_top_id = false) {
        if ($this->treeisLeaf($table, $parent_id) || !$label) {
            return false;
        }
        $parent_top_id = 0;
        if ($parent_id != 0) {
            $parent_top_id = $this->get_value("SELECT top_id FROM $table WHERE branch_id='$parent_id'");
        }
        $branch_id = $this->create($table, [
            'parent_id' => $parent_id,
            'is_leaf' => ($type == 'leaf'),
            'path' => '/-newbranch-/',
            'top_id' => $parent_top_id
        ]);
        $this->treeUpdate($table, $branch_id, 'label', $label, $calc_top_id);
        return $branch_id;
    }

    protected function treeUpdate($table, $branch_id, $field, $value, $calc_top_id = false) {
        if ($field == 'parent_id' && $this->treeisLeaf($table, $value) || $field == 'label' && !$value) {
            /* parent must be not leaf and label should not be empty */
            $this->Hub->msg($field == 'parent_id' ? "Not folder" : "Label should not be empty");
            return false;
        }
        if ($field == 'parent_id' && $branch_id == $value) {
            //move into self
            return false;
        }
        $this->update($table, [$field => $value], ['branch_id' => $branch_id]);
//	$this->treeUpdatePath($table, $branch_id);
//        if( $calc_top_id ){
//            $this->treeUpdateTopId($table, $branch_id);
//        }
        $this->treeRecalculate($table);
        return true;
    }

//    private function treeTopRecalculate($table){
//        $res = $this->db->query("SELECT branch_id,path FROM $table WHERE parent_id=0");
//	foreach ($res->result() as $row) {
//            $this->db->query("UPDATE $table SET top_id='{$row->branch_id}' WHERE path LIKE '{$row->path}%'");
//	}
//	$res->free_result();        
//    }
    private function treeRecalculate($table, $parent_id = 0, $parent_path = '/', $top_id = 0) {
        $res = $this->db->query("SELECT * FROM $table WHERE parent_id='$parent_id'");
        foreach ($res->result() as $row) {
            $current_path = $parent_path . "{$row->label}/";
            if ($parent_id == 0) {
                $top_id = $row->branch_id;
            }
            $this->update($table, ['path' => $current_path, 'top_id' => $top_id], ['branch_id' => $row->branch_id]);
            $this->treeRecalculate($table, $row->branch_id, $current_path, $top_id);
        }
        $res->free_result();
    }

//    protected function treeUpdatePath($table, $branch_id) {
//	$this->query("SET @old_path:='',@new_path:='';");
//	$this->query(
//		"SELECT @old_path:=COALESCE(t1.path, ''),@new_path:=CONCAT(COALESCE(t2.path, '/'), t1.label, '/')
//		FROM (SELECT * FROM $table) t1
//			LEFT JOIN
//		    (SELECT * FROM $table) t2 ON t1.parent_id = t2.branch_id 
//		WHERE
//		    t1.branch_id = $branch_id");
//	$this->query(
//		"UPDATE $table 
//		SET 
//		    path = IF(@old_path<>'',REPLACE(path, @old_path, @new_path),@new_path)
//		WHERE
//		    IF(@old_path<>'',path LIKE CONCAT(@old_path, '%'),branch_id=$branch_id)");
//    }
//    private function treeUpdateTopId($table_name, $branch_id){
//	$branch_ids=implode(',',$this->treeGetSub($table_name, $branch_id));
//	$this->query("UPDATE $table_name SET top_id='$branch_id' WHERE branch_id IN ($branch_ids)");
//        return $this->db->affected_rows();
//    }
    protected function treeDelete($table, $branch_id) {
        $branch_ids = $this->treeGetSub($table, $branch_id);
        $in = implode(',', $branch_ids);
        $this->query("START TRANSACTION");
        $this->query("DELETE FROM $table WHERE branch_id IN ($in)");
        $deleted = $this->db->affected_rows();
        $this->query("COMMIT");
        return $deleted;
    }

    protected function treeGetSub($table_name, $branch_id) {
        $branch_ids = [$branch_id];
        $result = $this->query("SELECT branch_id FROM $table_name WHERE parent_id='$branch_id'");
        foreach ($result->result() as $row) {
            $sub_branch_ids = $this->treeGetSub($table_name, $row->branch_id);
            $branch_ids = array_merge($branch_ids, $sub_branch_ids);
        }
        $result->free_result();
        return $branch_ids;
    }

    private function treeisLeaf($table, $branch_id) {
        $row = $this->db->get_where($table, array('branch_id' => $branch_id))->row();
        if ($row && $row->is_leaf) {
            return true;
        }
        return false;
    }

    ////////////////////////////////////////////////////
    // EASYUI DATAGRID FUNCTIONS
    ////////////////////////////////////////////////////    
    protected function decodeFilterRules() {
        $raw = $this->input->get_post('filterRules');
        $filter = json_decode($raw);
        if (!is_array($filter) || count($filter) === 0) {
            return 1;
        }
        $having = [];
        foreach ($filter as $rule) {
            $having[] = "$rule->field LIKE '%$rule->value%'";
        }
        return implode(' AND ', $having);
    }

    protected function makeFilter($filter) {
        if (!$filter) {
            return 1;
        }
        $having = [];
        foreach ($filter as $field => $value) {
            if( strpos($value,'|')===false ){
                $having[] = "$field LIKE '%$value%'";
            } else {
                $having[] = "($field = '".str_replace('|', "' OR $field = '", $value)."')";
            }
        }
        return  implode(' AND ', $having);
    }

}
