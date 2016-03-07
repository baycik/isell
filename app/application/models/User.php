<?php

require_once 'Catalog.php';
class User extends Catalog {
    public $min_level=0;
    public function SignIn(){
	$login=$this->input->post('login');
	$pass=$this->input->post('pass');
	$this->check($login,'^[a-zA-Z_0-9]*$');
	$this->check($pass,'^[a-zA-Z_0-9]*$');
	//if( !$login || !$pass ){
	    //allow empty pass
	    //$this->Base->kick_out();
	//}
	$pass_hash = md5($pass);
	$user_data = $this->get_row("SELECT * FROM " . BAY_DB_MAIN . ".user_list WHERE user_login='$login' AND user_pass='$pass_hash'");
	if ($user_data && $user_data->user_id) {
	    $this->Base->svar('user_id', $user_data->user_id);
	    $this->Base->svar('user_level', $user_data->user_level);
	    $this->Base->svar('user_level_name', $this->Base->level_names[$user_data->user_level]);
	    $this->Base->svar('user_login', $user_data->user_login);
	    $this->Base->svar('user_sign', $user_data->user_sign);
	    $this->Base->svar('user_position', $user_data->user_position);
            $this->Base->svar('user_assigned_stat',$user_data->user_assigned_stat);
            $this->Base->svar('user_assigned_path',$user_data->user_assigned_path);
            
            $Company=$this->Base->load_model("Company");
            $Company->selectActiveCompany($user_data->company_id);
	    return $this->getUserData();
	}
	return false;
    }
    public function SignOut(){
	$this->Base->svar('user_id', 0);
	$this->Base->svar('user_level', 0);
	$this->Base->svar('user_login', '');
	$this->Base->svar('user_sign', '');
	$this->Base->svar('user_position', '');
	return true;
    }
    public function getUserData(){
	return [
	    'user_id'=>$this->Base->svar('user_id'),
	    'user_login'=>$this->Base->svar('user_login'),
	    'user_level'=>$this->Base->svar('user_level'),
	    'user_level_name'=>$this->Base->svar('user_level_name'),
	    'acomp'=>$this->Base->svar('acomp'),
	    'module_list'=>$this->getModuleList()
	];
    }
    private function getModuleList(){
	$mods=json_decode(file_get_contents('application/config/modules.json',true));//not very reliable way to check, modules can be loaded anyway by hand
	$alowed=array();
	foreach( $mods as $mod ){
	    if( $this->Base->svar('user_level')>=$mod->level ){// && strpos(BAY_ACTIVE_MODULES, "/{$mod->name}/")!==false 
		$alowed[]=$mod;
	    }
	}
	return $alowed;
    }
    private function initLoggedUser($user_data){
	$Company=$this->Base->load_model("Company");
	$Company->selectActiveCompany($user_data->company_id);
        $this->Base->svar('user_assigned_stat',$user_data->user_assigned_stat);
        $this->Base->svar('user_assigned_path',$user_data->user_assigned_path);
    }
    public function userFetch(){
	$user_id = $this->Base->svar('user_id');
        $sql="SELECT
		user_id,user_login,user_level,user_sign,user_position,
		first_name,middle_name,last_name,nick,
		id_type,id_serial,id_number,id_given_by,id_date,
		user_assigned_path,
		CONCAT(last_name,' ',first_name,' ',middle_name) AS full_name 
	    FROM ".BAY_DB_MAIN.".user_list
		WHERE user_id='$user_id'";
        return $this->get_row($sql);
    }
    public function listFetch(){
	$user_id = $this->Base->svar('user_id');
        $where = ($this->Base->svar('user_level') < 4) ? "WHERE user_id='$user_id'" : "";
        $sql="SELECT
		user_id,user_login,user_level,user_sign,user_position,
		first_name,middle_name,last_name,nick,
		id_type,id_serial,id_number,id_given_by,id_date,
		user_assigned_path,
		CONCAT(last_name,' ',first_name,' ',middle_name) AS full_name 
	    FROM ".BAY_DB_MAIN.".user_list
		$where 
	    ORDER BY user_id<>'$user_id', user_level DESC";
        return $this->get_list($sql);
    }
    public function save(){
	$fields=[];
	$user_id=$this->request('user_id','int');
	$current_level=$this->Base->svar('user_level');
	if( $current_level>=1 && $this->Base->svar('user_id')==$user_id || $current_level>=4){
	    $fields['user_login']=$this->request('user_login','^[a-zA-Z_0-9]*$');
	    $new_pass=$this->request('new_pass','^[a-zA-Z_0-9]*$',false);
	    if( $new_pass ){
		$fields['user_pass']=md5($new_pass);
	    }
	}
	if( $current_level>=3 ){
	    $fields['user_sign']=$this->request('user_sign');
	    $fields['user_position']=$this->request('user_position');	    
	    $fields['first_name']=$this->request('first_name');
	    $fields['middle_name']=$this->request('middle_name');	    
	    $fields['last_name']=$this->request('last_name');
            $fields['nick']=mb_substr($fields['last_name'],0,1).mb_substr($fields['first_name'],0,1).mb_substr($fields['middle_name'],0,1);
	    $fields['id_type']=$this->request('id_type');
	    $fields['id_serial']=$this->request('id_serial');	    
	    $fields['id_number']=$this->request('id_number');
	    $fields['id_given_by']=$this->request('id_given_by');	    
	    $fields['id_date']=$this->request('id_date');	    
	}
	if( $current_level>=4 ){
	    $fields['user_level']=$this->request('user_level','int');
	    $fields['user_assigned_path']=$this->request('user_assigned_path');
	    
	    $admin=$this->adminLastCheck($user_id);
	    if( $admin==='last' && $fields['user_level']<4 ){
		return 'LAST_ADMIN';
	    }
	}
	if( $user_id===0 ){
	    return $this->create(BAY_DB_MAIN.".user_list", $fields);
	} else {
	    return $this->update(BAY_DB_MAIN.".user_list", $fields,['user_id'=>$user_id]);
	}
    }
    public function remove( $user_id ){
	$this->Base->set_level(4);
	$this->check($user_id,'int');
	$admin=$this->adminLastCheck($user_id);
	if( $admin==='last' ){
	    return 'LAST_ADMIN';
	}
	return $this->delete(BAY_DB_MAIN.".user_list", ['user_id'=>$user_id]);
    }
    private function adminLastCheck($user_id){
	return $this->get_value("SELECT IF(user_level=4 AND (SELECT COUNT(*)=1 FROM user_list WHERE user_level=4),'last','not_last') FROM user_list WHERE user_id='$user_id'");
    }
}
