<?php
//extends CI_Controller
class InputOutput extends CI_Controller{
    function __construct(){
	if( defined('BAY_OMIT_CONTROLLER_CONSTRUCT') ){
	    return;
	}
	parent::__construct();
    }
    
    public $rq;
    public $rtype = 'OK';
    public $rmethod = 'xhr';
    public $msg = '';

    public function request($var, $type = 0, $def = NULL) {
	if (!isset($_REQUEST[$var]) || $_REQUEST[$var] === NULL) {
	    return $def;
	}
	$RAWVAR = $_REQUEST[$var];
	if ($type === 0)
	    return addslashes($RAWVAR);
	if ($type === 1)
	    return intval($RAWVAR);
	if ($type === 2)
	    return doubleval($RAWVAR);
	if ($type === 3)
	    return json_decode(stripslashes($RAWVAR), true);
	if ($type === 4)
	    return $RAWVAR;
	else {
	    preg_match("/$type/u", $RAWVAR, $matches);
	    return $matches[0];
	}
    }

    public function response($data = '', $pretty = false) {
	if ($this->rmethod == 'alert') {
	    header('Content-type: text/html; charset=utf-8;');
	    $data = json_encode($data);
	    echo "<script>text=" . $data . ";parent?parent.msg(text):msg(text);</script>";
	}
	else {
	    header('Content-type: text/plain; charset=utf-8;');
	    header('X-isell-type: '.$this->rtype);
	    header('X-isell-msg: '.rawurlencode($this->msg));
	    if( is_array($data) ){
		header('X-isell-format: json');
		echo json_encode( $data, $pretty?(JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE):JSON_UNESCAPED_UNICODE );
	    }
	    else{
		echo $data;
	    }
	}
	exit();
    }

    public function response_confirm($msg='') {
	$this->rtype = 'confirm';
	$this->response("$msg");
    }

    public function response_dialog($msg='') {
	$this->rtype = 'dialog';
	$this->response($msg);
    }

    public function response_error($msg='') {
	$this->rtype = 'error';
	$this->response("On " . $this->request('fn') . $this->request('mod') . ":$this->rq \n\n$msg");
    }

    public function response_wrn($msg='') {
	$this->rtype = 'wrn';
	$this->response($msg);
    }

    public function response_tpl($path) {
	//$this->rtype = 'tpl';
	//echo var_dump(stream_resolve_include_path ( "application/views/$path" ));
	$this->response(file_get_contents("application/views/$path", true));
    }

    public function response_alert($msg='') {
	$this->rtype = 'alert';
	$this->response($msg);
    }

    public function msg($msg='') {
	$this->msg.="$msg\n";
    }

    public function trace($obj) {
	$this->msg(json_encode($obj));
	$this->response();
    }
}

class DataBase extends InputOutput {

    public $field_list = array();
    public $db_link;

    public function __construct() {
	parent::__construct();
    }
    
    private function db_connect(){
	$this->db_link = mysqli_connect(BAY_DB_HOST, BAY_DB_USER, BAY_DB_PASS, BAY_DB_NAME);
	if ($this->db_link) {
	    mysqli_query($this->db_link,'SET NAMES utf8');
	} else {
	    $this->response_error('COULD NOT CONNECT TO MYSQL '.mysqli_connect_error());
	}
    }
    
//    public function db_free_result( $res ){
//	mysql_free_result($res);
//    }
//    
//    public function db_errno(){
//	return mysqli_errno($this->Base->db_link);
//    }

    public function query($sql, $throw_error = true) {
	if( !$this->db_link ){
	    $this->db_connect();
	}
	$res = mysqli_query($this->db_link,$sql);
	if ($throw_error && mysqli_errno($this->db_link)) {
	    if ($this->svar('user_level') > 2 || 1)
		$this->response_error('SQL ERROR: ' . "\n$sql\n\n" . mysqli_error($this->db_link));
	    else
		$this->response_wrn('Упс! Ошибочка вышла!');
	}
	return $res;
    }

    public function get_list($sql) {
	$tmplist = array();
	$tmpres = $this->query($sql);
	while ($row = mysqli_fetch_assoc($tmpres)) {
	    $tmplist[] = $row;
	}
	mysqli_free_result($tmpres);
	return $tmplist;
    }

    public function get_row($sql, $index = NULL) {
	$tmpres = $this->query($sql);
	if ($index === NULL) {
	    $row = mysqli_fetch_assoc($tmpres);
	    mysqli_free_result($tmpres);
	    return $row;
	} else {
	    $row = mysqli_fetch_array($tmpres);
	    mysqli_free_result($tmpres);
	    return $row[$index]??NULL;
	}
    }
    public function get_field_list($table_name, $hide_tstamp = true) {
	if (!$this->field_list[$table_name]) {
	    $fields = array();
	    $fields['full'] = array();
	    $fields['keys'] = array();
	    $fields['columns'] = array();
	    $res = $this->query("SHOW COLUMNS FROM $table_name");
	    while ($row = mysqli_fetch_assoc($res)) {
		if ($hide_tstamp && $row['Default'] == 'CURRENT_TIMESTAMP')
		    continue;
		$fields['full'][] = $row;
		$fields['columns'][] = $row['Field'];
		if ($row['Key'] == 'PRI') {
		    $fields['keys'][] = $row['Field'];
		}
	    }
	    mysqli_free_result($res);
	    $fields['count'] = count($fields['columns']);

	    $this->field_list[$table_name] = $fields;
	}
	return $this->field_list[$table_name];
    }
}

class Session extends DataBase {

    public $level_names = array("Нет доступа", "Ограниченный", "Менеджер", "Бухгалтер", "Администратор");

    public function __construct() {
	session_set_cookie_params(36000, '/');
	session_name('baycikSid' . BAY_COOKIE_NAME);
	@session_start();
	if (method_exists($this, 'initApplication')) {
	    $this->initApplication();
	}
	parent::__construct();
    }

    public function svar($name, $value = NULL) {
	if (isset($value)) {
	    $_SESSION[$name] = $value;
	}
	return isset($_SESSION[$name])?$_SESSION[$name]:'';
    }

    public function set_level($allowed_level) {
	if ($this->svar('user_level') < $allowed_level) {
	    if ($this->svar('user_level') == 0) {
		$this->msg("Текущий уровень <b>" . $this->level_names[$this->svar('user_level') * 1] . "</b><br>");
		$this->msg("Необходим уровень доступа <b>" . $this->level_names[$allowed_level] . "</b>");
            die;
		$this->kick_out();
	    } else {
		$this->msg("Текущий уровень '" . $this->level_names[$this->svar('user_level') * 1] . "'\nНеобходим мин. уровень доступа '" . $this->level_names[$allowed_level] . "'");
	    }
	}
    }

    public function login($usr, $pass) {
	if (!preg_match('/^[a-zA-Z_0-9]*$/', $usr) || !preg_match('/^[a-zA-Z_0-9]*$/', $pass)) {
	    $this->kick_out();
	}
	$pass_hash = md5($pass);
	$user_data = $this->get_row("SELECT * FROM " . BAY_DB_MAIN . ".user_list WHERE user_login='$usr' AND user_pass='$pass_hash'");
	if ($user_data['user_id']) {
	    $this->svar('user_id', $user_data['user_id']);
	    $this->svar('user_level', $user_data['user_level']);
	    $this->svar('user_level_name', $this->level_names[$user_data['user_level']]);
	    $this->svar('user_login', $user_data['user_login']);
	    if ( method_exists($this, 'initLoggedUser') ) {
		$this->initLoggedUser($user_data);
	    }
	    return true;
	}
	return false;
    }

    public function logout() {
	$this->svar('user_id', 0);
	$this->svar('user_level', 0);
	$this->svar('user_login', '');
	$this->svar('user_sign', '');
	$this->svar('user_position', '');
    }

    private function kick_out() {
	include 'views/dialog/loginform.html';
	$this->response_dialog();
    }

}

class ProcessorBase extends Session {

    public function __construct($allowed_user_level = 0) {
	return;
        parent::__construct();
	$this->set_level($allowed_user_level);

	if ($this->request('mod')) {
	    $this->rq = $this->request('rq', 0, 'Default');
	    //$this->onRequest($this->rq);
	}
	
    }

//    public function onRequest($rq) {
//	$rq = 'on' . $rq;
//	if (method_exists($this, $rq))
//	    $this->$rq();
//	else
//	    $this->response_error("Processor ERROR: Requested command is not found \n $rq");
//	$this->response();
//    }

    public function index(){
	$this->onDefault();
    }
    
    public function LoadClass($class_name) {
	if ( isset($this->$class_name) ){
	    return $this->$class_name;
	}
	require_once "application/models/proc/$class_name.php";
	$this->$class_name = new $class_name();
	$this->$class_name->Base = $this;
	if (method_exists($this->$class_name, 'Init'))
	    $this->$class_name->Init();
	return $this->$class_name;
    }

//    public function LoadPlugin($plugin_name) {
//	if (!file_exists("plugins/$plugin_name/$plugin_name.php"))
//	    $this->response_wrn("Plugin $plugin_name not found!");
//	include_once "plugins/$plugin_name/$plugin_name.php";
//	$this->$plugin_name = new $plugin_name();
//	$this->$plugin_name->Base = $this;
//	if (method_exists($this->$plugin_name, 'Init'))
//	    $this->$plugin_name->Init();
//	return $this->$plugin_name;
//    }

//    public function execClassFn($Class, $fn_name = 'html') {
//	if (array_key_exists($fn_name, $Class->fns) && method_exists($Class, $fn_name)) {
//	    $args = explode(',', $Class->fns[$fn_name]);
//	    foreach ($args as &$arg) {
//		$arg_parts = explode(" ", trim($arg));
//		$arg_type = str_replace(array('(string)', '(int)', '(float)', '(json)', '(raw)'), array(0, 1, 2, 3, 4), $arg_parts[0]);
//		$arg_name = $arg_parts[1];
//		$arg_default = $arg_parts[2];
//		if (is_numeric($arg_type))
//		    $arg_type = (int) $arg_type;
//		$arg = $this->request($arg_name, $arg_type, $arg_default);
//	    }
//	    $resp = call_user_func_array(array($Class, $fn_name), $args);
//	    if ($resp)
//		$this->response($resp);
//	}
//	else {
//	    $this->response_error("Class " . get_class($Class) . " don't have function $fn_name!");
//	}
//    }

    protected function get_table_query() {//Deprecated
	$table_query = array();
	$table_query['limit'] = $this->request('limit', 1);
	$table_query['page'] = $this->request('page', 1);
	$table_query['cols'] = $this->request('cols', 3);
	$table_query['vals'] = $this->request('vals', 3);
	return $table_query;
    }

    protected function getGridQuery() {
	$gridQuery = array();
	$gridQuery['limit'] = $this->request('limit', 1);
	$gridQuery['page'] = $this->request('page', 1);
	$gridQuery['filter'] = $this->request('filter', 3);
	return $gridQuery;
    }

}

?>
