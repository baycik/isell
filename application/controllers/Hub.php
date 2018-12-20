<?php
//date_default_timezone_set('Europe/Kiev');
spl_autoload_register(function ($class_name) {
    $filename=APPPATH.'models/'.$class_name . '.php';
    if( file_exists($filename) ){
	require_once $filename;
    }
});


class Hub  extends CI_Controller{
    public $level_names=["Нет доступа","Ограниченный","Менеджер","Бухгалтер","Администратор"];
    private $rtype='OK';
    private $msg='';
    public $log_output_messages=false;
    function __construct(){
	session_name('baycikSid');
	session_start();
	parent::__construct();
    }
    private function checkAnonymousAccess($model_name,$method){
	if( !$this->svar('user_id') ){
	    header("HTTP/1.1 401 Unauthorized");
	    if( empty($_SERVER['HTTP_X_REQUESTED_WITH']) ){
		$this->loginform();
	    } else {
		if( $model_name=='User' && $method=='SignIn' ){
		    return;
		}
		die('X-isell-error: Access denied!!!');
	    }
	}
    }
    
    private function loginform(){
	$user_login=$this->request('user_login');
	$user_pass=$this->request('user_pass');
	$User=$this->load_model('User');
	if( $user_phone=$this->request('user_phone','^[\d]+',false) ){
            $status=$User->sendPassword($user_phone);
        } else if( $user_login && $user_pass ){
            if( $User->SignIn($user_login,$user_pass) ){
                header("Location: ./");
                return;
            }
	    $status="login_or_pasword_wrong";
	} else {
            $status="please_login";
        }
	include APPPATH.'views/login.html';
	exit;
    }
    
    
    public function index(){
	include "index.html";
    }
    
    public function on( $model_name, $method='index' ){
	$this->checkAnonymousAccess($model_name,$method);
	$route_args =array_slice(func_get_args(), 2);
	$this->pluginTrigger($model_name,$method,$route_args);
	$this->execute($model_name, $method, $route_args);
    }
    
    private function execute( $model_name, $method, $route_args ){
	try{
	    $Model=$this->load_model($model_name);
	    if( !method_exists($model_name, $method) ){
		show_error("X-isell-error: No such method '$method' in $model_name", 500);
	    }
	    if( !isset($Model->$method) ){
		show_error("X-isell-error: '$method' config for arguments is not set. Access denied", 500);
	    }
	    $method_args_config=$Model->$method;
	    $method_args=$this->parseMethodArguments($method_args_config, $route_args);
	    $response=call_user_func_array([$Model, $method],$method_args);
	    if( !is_null($response) ){
		$this->response($response);
	    }
	} catch(Exception $e){
	    show_error("X-isell-error: ".$e->getMessage(), 500);
	}
    }

    private function parseMethodArguments($method_args_config,$route_args){
	if( is_array($method_args_config) ){
	    $method_args=[];
	    foreach( $method_args_config as $var_name=>$var_type ){
		if( is_numeric($var_name) && isset($route_args[$var_name]) ){
		    $arg_value=rawurldecode($route_args[$var_name]);
		    $this->check($arg_value, $var_type);
		    $method_args[]=$arg_value;
		} else {
		    $method_args[]=is_array($var_type)?$this->request($var_name,$var_type[0],$var_type[1]):$this->request($var_name,$var_type);
		}
	    }
	    return $method_args;
	}
    }
    
    public function page( $parent_folder=null ){
	$modules_allowed=$this->svar('modules_allowed');
	if( isset($modules_allowed[$parent_folder]) && $modules_allowed[$parent_folder]->level > $this->svar('user_level') ){
	    $this->set_level($modules_allowed[$parent_folder]->level);
	}
	$file_name = implode('/',func_get_args());
	$this->load->view($file_name);
    }

    private function pluginTrigger($model_name,$method,$route_args){
	$trigger_before=$this->svar('trigger_before');
	if( isset($trigger_before[$model_name]) ){
	    $this->pluginCheckIfPublicFile($model_name,$method,$route_args);
	    $model_override=$trigger_before[$model_name];
	    $this->load->add_package_path(APPPATH.'plugins/'.$model_override, FALSE);
	    if( $model_override===$model_name ){//if plugin ovverides it self then adding package is enough
		return false;
	    }
	    $this->execute($model_override, $method, $route_args);
	}
    }
    private function pluginCheckIfPublicFile($model_name,$method,$route_args){
	$public_file_path=APPPATH."plugins/{$model_name}/public/$method".($route_args?"/".implode("/",$route_args):"");
	if (file_exists($public_file_path)) {
            header("X-isell-type: OK");
	    $this->load->helper('download');
	    force_download($public_file_path, null, true);
	    exit;
	}
    }
    /*
     * bridgeLoad function to load and use legacy iSell2 class files
     */
    public function bridgeLoad( $class_name ){
	!defined('BAY_OMIT_CONTROLLER_CONSTRUCT') && define('BAY_OMIT_CONTROLLER_CONSTRUCT',true);
	if( !isset($this->bridge) ){
	    require_once 'iSellBase.php';
	    $this->bridge=new iSellBase();
	}
	return $this->bridge->LoadClass($class_name);
    }
    /*
     * HUB BASE FUNCTIONS
     * 
     * 
     * 
     */

    public function acomp($name){/*@TODO move to lazy loading of pcomp/acomp in v4.0*/
	$acomp=$this->svar('acomp');
	return isset($acomp->$name)?$acomp->$name:NULL;
    }
    
    public function pcomp($name){/*@TODO move to lazy loading of pcomp/acomp in v4.0*/
	$pcomp=$this->svar('pcomp');
	return isset($pcomp->$name)?$pcomp->$name:NULL;
    }
    
    public function pref($name){
	if( !isset($this->pref) ){
	    $Pref=$this->load_model('Pref');
	    $this->pref=$Pref->getPrefs();
	}
	return isset($this->pref->$name)?$this->pref->$name:NULL;
    }
    
    public function svar($name, $value = NULL) {
	if (isset($value)) {
	    $_SESSION[$name] = $value;
	}
	return isset($_SESSION[$name])?$_SESSION[$name]:NULL;
    }
    
    public function load_model( $name ){
	$this->load->model($name,null,true);
	if( isset($this->{$name}->min_level) ){
	    $this->set_level($this->{$name}->min_level);
	}
	$this->{$name}->Hub=$this;
	if( method_exists($this->{$name}, 'init') ){
	    $this->{$name}->init();
	}

	return $this->{$name};
    }
    
    public function set_level($allowed_level) {
	if ($this->svar('user_level') < $allowed_level) {
	    if ($this->svar('user_level') == 0) {
		$this->msg("Текущий уровень " . $this->level_names[$this->svar('user_level') * 1]);
		$this->msg("Необходим уровень доступа " . $this->level_names[$allowed_level]);
		$this->kick_out();
	    } else {
		$this->msg("Необходим мин. уровень доступа '{$this->level_names[$allowed_level]}'");
		$this->response(0);
	    }
	}
    }
    public function check( &$var, $type=null ){
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
		$var=$this->db->escape_identifiers($var);
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
    public function request( $name, $type=null, $default=null ){
	$value=$this->input->get_post($name);
	if( $value!==null ){
	    $this->check($value,$type);
	    return $value;
	}
	return $default;
    }
    
    public function kick_out() {
	header("HTTP/1.1 401 Unauthorized");
	die();
    }
    
    public function msg($msg) {
	$this->msg.="$msg\n";
    }
    

    public function db_msg(){
	$error = $this->db->error();
	switch( $error['code'] ){
	    case 1451:
		$this->msg('Элемент ипользуется, поэтому не может быть изменен или удален!');
		break;
	    case 1452:
		$this->msg('Новое значение отсутствует в вышестоящей таблице!');
		break;
	    case 1062:
		$this->msg('Запись с таким ключем уже есть!');
		break;
	    default:
		header("X-isell-type:error");
		show_error($this->msg." ".$error['message'].' '.$this->db->last_query(), 500);
		break;
	}
    }
    
    public function response( $response ){
	if( isset($this->bridge) && $this->bridge->msg ){
	    $this->msg.=$this->bridge->msg;
	}
        if( $this->log_output_messages ){
            $this->load_model('Catalog')->log($this->svar('user_login').': '.$this->msg);
        } else {
            $this->output->set_header("X-isell-msg:".urlencode($this->msg));
        }
        
	$this->output->set_header("X-isell-type:".$this->rtype);
	
	if( is_array($response) || is_object($response) ){
	    $this->output->set_header("Content-type:text/plain;charset=utf8"); 
	    $this->output->set_output(json_encode($response,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));	    
	}
	else{
	    if( is_bool($response) ){
		$response*=1;
	    }
	    $this->output->set_header("Content-type:text/html;charset=utf8"); 
	    $this->output->set_output($response);	    
	}
	$this->output->_display();
	exit;
    }
}
