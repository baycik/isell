<?php

class Catalog extends CI_Model {
    public $min_level=1;
    protected function check( &$var, $type=null ){
	switch( $type ){
	    case 'raw':
		break;
	    case 'int':
		$var=(int) $var;
		break;
	    case 'double':
		$var=(float) $var;
		break;
	    case 'bool':
		$var=(bool) $var;
		break;
	    case 'escape':
		$var=$this->db->escape($var);
		break;
	    case 'string':
                $var=  addslashes( $var );
                break;
	    case 'json':
                $var= json_decode( $var ,true);
                break;
	    default:
		if( $type ){
		    $matches=[];
		    preg_match('/'.$type.'/u', $var, $matches);
		    $var=  isset($matches[0])?$matches[0]:null;
		} else {
		    $var=  addslashes( $var );
		}
	}
    }
    protected function request( $name, $type=null, $default=null ){
	$value=$this->input->get_post($name);
	if( $value!==null ){
	    $this->check($value,$type);
	    return $value;
	}
	return $default;
    }
    
    ////////////////////////////////////////////////////
    // CORE LIST FUNCTIONS
    ////////////////////////////////////////////////////
    private function check_error(){
	$error = $this->db->error();
	if( $error['code'] ){
	    $this->Base->db_msg();
	    return true;
	}
        return false;
    }
    protected function query( $query ){
	if(is_string($query)){
	    $query=$this->db->query($query);
	}
        if( $this->check_error() ){
            return NULL;
        }
        return $query;
    }
    
    protected function get_list( $query ){
	$list=array();
	$result=$this->query($query);
	foreach( $result->result() as $row ){
	    $list[]=$row;
	}
	$result->free_result();
	return $list;
    }
    protected function get_row( $query ){
	$result=$this->query($query);
        if( $result && $result->num_rows()>0 ){
            $row=$result->row();
            $result->free_result();
            return $row;
        }
	return null;
    }
    protected function get_value( $query ){
	$row = $this->query($query)->row_array();
	if( $row ){
	    foreach( $row as $value ){
		return $value;
	    }
	}
	return null;
    }
    
    protected function get( $table, $key ){
	return $this->db->get_where( $table, $key )->row();
    }
    protected function create($table,$data) {
	$this->db->insert($table, $data);
	$newid=$this->db->insert_id();
	$ok=!!$this->db->affected_rows();
        $this->check_error();
	return $newid?$newid:$ok;
    }
    protected function update($table, $data, $key) {
	$this->db->update($table, $data, $key);
	$ok=$this->db->affected_rows();
        $this->check_error();
	return $ok;
    }
    protected function delete($table, $key, $key_values=null) {
	if( $key_values ){
	    $this->db->where_in($key, $key_values);
	    $this->db->delete($table);	    
	} else {
	    $this->db->delete($table, $key);
	}
	$ok=$this->db->affected_rows();
        $this->check_error();
	return $ok;
    }
    protected function rowUpdate( $table, $data, $key ){
	return $this->update($table,$data,$key);
    }
    protected function rowUpdateField( $table, $key_field, $id, $field, $value ){
	$key=array($key_field=>$id);
	$data=array($field=>$value);
	return $this->update($table,$data,$key);
    }
    ////////////////////////////////////////////////////
    // CORE TREE FUNCTIONS
    ////////////////////////////////////////////////////
    protected function treeFetch( $table, $parent_id = null, $depth = 'all', $super_path='', $level=0 ) {
	$where=array("level IS NULL OR level<=$level");
	if( $super_path!=='' ){
	    $where[]="path LIKE '$super_path".($parent_id===null?'':'%')."'";
	}
	if( $parent_id!==null ){
	    $where[]="parent_id=$parent_id";
	}
	$where=  implode(' AND ',$where);
	$res = $this->db->query("SELECT * FROM $table WHERE $where ORDER BY is_leaf,label");
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
    protected function treeCreate($table,$type,$parent_id,$label='',$calc_top_id=false){
	if( $this->treeisLeaf($table,$parent_id) || !$label ){
	    return false;
	}
        $parent_top_id=$this->get_value("SELECT top_id FROM $table WHERE branch_id='$parent_id'");
	$branch_id=$this->create($table,[
	    'parent_id'=>$parent_id,
	    'is_leaf'=>($type=='leaf'),
	    'path'=>'/-newbranch-/',
            'top_id'=>$parent_top_id
	    ]);
	$this->treeUpdate($table, $branch_id, 'label', $label, $calc_top_id);
	return $branch_id;
    }
    protected function treeUpdate($table,$branch_id,$field,$value,$calc_top_id=false) {
	if( $field=='parent_id' && $this->treeisLeaf($table,$value) || $field=='label' && !$value ){
	    /*parent must be not leaf and label should not be empty*/
            $this->Base->msg($field=='parent_id'?"Not folder":"Label should not be empty");
	    return false;
	}
	$this->update($table, [$field => $value],['branch_id' => $branch_id]);
	$this->treeRecalculate($table);
	return true;
    }
    private function treeRecalculate( $table, $parent_id = 0, $parent_path='/', $top_id=0 ) {
	$res = $this->db->query("SELECT * FROM $table WHERE parent_id='$parent_id'");
	foreach ($res->result() as $row) {
            $current_path=$parent_path."{$row->label}/";
            if($parent_id == 0){
                $top_id=$row->branch_id;
            }
	    $this->update($table,['path'=>$current_path,'top_id'=>$top_id],['branch_id'=>$row->branch_id]);
            $this->treeRecalculate($table, $row->branch_id,$current_path,$top_id);
	}
	$res->free_result();
    }
    protected function treeDelete($table,$branch_id){
	$branch_ids=$this->treeGetSub($table, $branch_id);
	$in=implode(',', $branch_ids);
	$this->query("START TRANSACTION");
	$this->query("DELETE FROM $table WHERE branch_id IN ($in)");
	$deleted=$this->db->affected_rows();
	$this->query("COMMIT");
	return $deleted;
    }
    protected function treeGetSub($table_name, $branch_id) {
        $branch_ids = [$branch_id];
	$result=$this->query("SELECT branch_id FROM $table_name WHERE parent_id='$branch_id'");
	foreach( $result->result() as $row ){
	    $sub_branch_ids = $this->treeGetSub($table_name, $row->branch_id);
	    $branch_ids=array_merge($branch_ids, $sub_branch_ids);
	}
	$result->free_result();
	return $branch_ids;
    }
    private function treeisLeaf($table,$branch_id){
	$row = $this->db->get_where($table, array('branch_id' => $branch_id))->row();
	if ( $row && $row->is_leaf) {
	    return true;
	}
	return false;
    }
    ////////////////////////////////////////////////////
    // EASYUI DATAGRID FUNCTIONS
    ////////////////////////////////////////////////////    
    protected function decodeFilterRules(){
	$raw=$this->input->get_post('filterRules');
	$filter=json_decode($raw);
	if( !is_array($filter) || count($filter)===0 ){
	    return 1;
	}
	$having=array();
	foreach( $filter as $rule ){
	    $having[]="$rule->field LIKE '%$rule->value%'";
	}
	return implode(' AND ',$having);
    }
}