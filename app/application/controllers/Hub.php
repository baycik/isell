<?php
//include 'HubBase.php';
date_default_timezone_set('Europe/Kiev');
class Hub extends HubBase{

    public function index(){
	include "index.html";
    }
    
    public function on( $model, $method ){
	if( $model ){
	    $this->load_model($model);
	    if( method_exists($this->{$model},$method) ){// && stripos($method,'core')===false
		$this->{$model}->Base=$this;
		$method_args = array_map("rawurldecode",array_slice(func_get_args(), 2));
		$response=call_user_func_array(array($this->{$model}, $method),$method_args);
		$this->response($response);
	    }
	    else{
		show_error("X-isell-error: Such module function '$model->$method' not found!", 500);
	    }
	}
	else {
	    show_error('X-isell-error: Model is not set!', 500);
	}
    }
    
    public function page(){
	$file_name = "application/views/".implode('/',func_get_args());
	if( file_exists($file_name) ){
	    header("X-isell-type:OK");
	    include $file_name;
	}
	else{
	    show_error('X-isell-error: File not found!', 404);
	}
	exit;
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
}
class HubBase extends CI_Controller{
    public $level_names=array("Нет доступа","Ограниченный","Менеджер","Бухгалтер","Администратор");
    private $rtype='OK';
    private $msg='';
    function HubBase(){
	$this->Session();
	parent::__construct();
    }
    private function Session() {
	session_set_cookie_params(36000, '/');
	session_name('baycikSid' . BAY_COOKIE_NAME);
	session_start();
    }
    
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
	$this->{$name}->Base=$this;
	return $this->{$name};
    }
    
    public function load_plugin( $subfolder, $name ){
	require_once "application/views/plugins/$subfolder/$name/$name.php";
	$Plugin=new $name();
	$Plugin->Base=$this;
	return $Plugin;
    }
    
    public function set_level($allowed_level) {
	if ($this->svar('user_level') < $allowed_level) {
	    if ($this->svar('user_level') == 0) {
		$this->msg("Текущий уровень <b>" . $this->level_names[$this->svar('user_level') * 1] . "</b><br>");
		$this->msg("Необходим уровень доступа <b>" . $this->level_names[$allowed_level] . "</b>");
		$this->kick_out();
	    } else {
		$this->msg("Необходим мин. уровень доступа '{$this->level_names[$allowed_level]}'");
		$this->response(0);
	    }
	}
    }
    
    private function kick_out() {
	$this->rtype = 'DIALOG';
	$this->response('page/dialog/loginform.html');
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
	$this->output->set_header("X-isell-msg:".urlencode($this->msg));
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