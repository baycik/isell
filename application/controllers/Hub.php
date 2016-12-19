<?php
set_include_path('.'.PATH_SEPARATOR.'application/');
include 'libraries/Plugins.php';


date_default_timezone_set('Europe/Kiev');
class Hub  extends CI_Controller{
    public function index(){
	include "index.html";
    }
    
    public function on( $model, $method='index' ){
	if( $model ){
	    try {
		$this->load_model($model);
		$method_args = array_map("rawurldecode",array_slice(func_get_args(), 2));
		$response=call_user_func_array(array($this->{$model}, $method),$method_args);
		$this->response($response);
	    } catch (Exception $ex) {
		show_error("X-isell-error: Such module function '$model->$method' not found or other error occured!", 500);
	    }
	}
	else {
	    show_error('X-isell-error: Model is not set!', 500);
	}
    }
    
    public function page( $parent_folder=null ){
	if( $parent_folder=='plugins' ){
	    $file_name = "application/".implode('/',func_get_args());
	} else {
	    $file_name = "application/views/".implode('/',func_get_args());
	}
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
     * Here the url call to plugin goes as /plugin/plugin_name/plugin_method/args/...
     * So to method comes the plugin_name in $args[0] and method name in $args[1]
     */
    public function plugin(){
	$args=func_get_args();
	$plugin_name=$args[0];
	if( !$plugin_name ){
	    return;
	}
	$plugin_method=isset($args[1])?$args[1]:'index';
	$plugin_method_args = array_slice($args, 2);
	Plugins::instance()->Base=$this;
	$response=Plugins::instance()->call_method($plugin_name, $plugin_method, $plugin_method_args);
	$this->response($response);
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

    public $level_names=["Нет доступа","Ограниченный","Менеджер","Бухгалтер","Администратор"];
    private $rtype='OK';
    private $msg='';
    function __construct(){
	session_set_cookie_params(36000, '/');
	session_name('baycikSid' . BAY_COOKIE_NAME);
	session_start();
	parent::__construct();
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
    
    public function load_plugin( $plugin_name ){
	require_once "application/plugins/$plugin_name/$plugin_name.php";
	$Plugin=new $plugin_name();
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
    
    public function kick_out() {
	$this->rtype = 'kickout';
	$this->response('');
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