<?php
date_default_timezone_set('Europe/Kiev');
//set_include_path('.'.PATH_SEPARATOR.'application/');
spl_autoload_register(function ($class_name) {
    $filename=APPPATH.'models/'.$class_name . '.php';
    if( file_exists($filename) ){
	require_once $filename;
    }
});
include APPPATH.'libraries/Plugins.php';


class Hub  extends CI_Controller{
    public $level_names=["Нет доступа","Ограниченный","Менеджер","Бухгалтер","Администратор"];
    private $rtype='OK';
    private $msg='';
    function __construct(){
	session_set_cookie_params(36000, '/');
	session_name('baycikSid' . BAY_COOKIE_NAME);
	session_start();
	parent::__construct();
    }
    public function index(){
	include "index.html";
    }
    
//    public function on1( $model, $method='index' ){
//	if( !$model ){
//	    show_error('X-isell-error: Model is not set!', 500);
//	}
//	try {
//	    $this->load_model($model);
//	    $Model=$this->{$model};
//	    $method_config=$method;
//	    if( isset($Model->$method_config) ){
//		/*input config array is exists*/
//		$this->load_model('Catalog');
//		$method_args=[];
//		foreach( $Model->$method_config as $var_name=>$var_type ){
//		    $method_args[]=$this->Catalog->request($var_name,$var_type);
//		}
//	    } else {
//		$method_args = array_map("rawurldecode",array_slice(func_get_args(), 2));
//	    }
//	    $response=call_user_func_array([$Model, $method],$method_args);
//	    $this->response($response);
//	} catch (Exception $ex) {
//	    show_error("X-isell-error: Such module function '$model->$method' not found or other error occured!", 500);
//	}
//    }
    
    public function on( $model_name, $method='index' ){
	$route_args =array_slice(func_get_args(), 2);
	$this->pluginTrigger($model_name,$method,$route_args);
	$this->execute($model_name, $method, $route_args);
    }
    
    private function execute( $model_name, $method, $route_args ){
	try{
	    $Model=$this->load_model($model_name);
	    $method_args_config=isset($Model->$method)?$Model->$method:NULL;
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
	if( isset($method_args_config) ){
	    $method_args=[];
	    foreach( $method_args_config as $var_name=>$var_type ){
		if( is_numeric($var_name) && $route_args[$var_name] ){
		    $arg_value=rawurldecode($route_args[$var_name]);
		    $this->check($arg_value, $var_type);
		    $method_args[]=$arg_value;
		} else {
		    $method_args[]=$this->request($var_name,$var_type);
		}
	    }
	    return $method_args;
	} 
	$method_args=array_map("rawurldecode",$route_args);//this behavior is deprecated
	return $method_args;
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
	Plugins::instance()->Hub=$this;
	$response=Plugins::instance()->call_method($plugin_name, $plugin_method, $plugin_method_args);
	$this->response($response);
    }    
    public function pluginInitTriggers(){
	$before=[];
	$after=[];
	$sql="SELECT 
		plugin_system_name,trigger_before
	    FROM 
		plugin_list 
	    WHERE 
		is_activated AND (trigger_before IS NOT NULL)";
	$active_plugin_triggers=$this->db->query($sql);
	if($active_plugin_triggers){
	    foreach( $active_plugin_triggers->result() as $trigger ){
		if( $trigger->trigger_before ){
		    $this->pluginParseTriggers($before, $trigger->trigger_before, $trigger->plugin_system_name);
		}
//		if( $trigger->trigger_after ){
//		    $this->pluginParseTriggers($after, $trigger->trigger_after, $trigger->plugin_system_name);
//		}
	    }
	    $active_plugin_triggers->free_result();
	}
	$this->svar('trigger_before',$before);
	$this->svar('trigger_after',$after);
    }
    private function pluginParseTriggers( &$registry, $triggers, $plugin_system_name ){
	$trigger_list=explode(',',$triggers);
	foreach($trigger_list as $trigger){
	    if( !isset($registry[$trigger]) ){
		$registry[$trigger]=[];
	    }
	    $registry[$trigger]=$plugin_system_name;
	}
    }
    private function pluginTrigger($model_name,$method,$route_args){
	$trigger_before=$this->svar('trigger_before');
	if( isset($trigger_before[$model_name]) ){
	    $model_override=$trigger_before[$model_name];
	    $this->load->add_package_path(APPPATH.'plugins/'.$model_override, FALSE);
	    if( $model_override===$model_name ){//if plugin ovverides it self then adding package is enough
		return false;
	    }
	    $this->execute($model_override, $method, $route_args);
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
	return $this->{$name};
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
    private function check( &$var, $type=null ){
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
    public function request( $name, $type=null, $default=null ){
	$value=$this->input->get_post($name);
	if( $value!==null ){
	    $this->check($value,$type);
	    return $value;
	}
	return $default;
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
