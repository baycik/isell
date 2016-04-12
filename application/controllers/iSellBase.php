<?php
//date_default_timezone_set('Europe/Kiev');
set_include_path('.'.PATH_SEPARATOR.'application/');
error_reporting(E_ERROR | E_PARSE);
//ini_set('html_errors','off');


include_once 'ProcessorBase.php';
class iSellBase extends ProcessorBase{
    public $_acomp=false;
    public $_pcomp=false;
    public $level_names=array("Нет доступа","Ограниченный","Менеджер","Бухгалтер","Администратор");
    
    public function initApplication(){
        $this->loadSessionData();
    }
    public function initLoggedUser($user_data){
        $this->svar('user_sign',$user_data['user_sign']);
        $this->svar('user_position',$user_data['user_position']);
        //$this->svar('user_only_assigned',$user_data['user_only_assigned']);
        $this->svar('user_assigned_stat',$user_data['user_assigned_stat']);
        $this->svar('user_assigned_path',$user_data['user_assigned_path']);
        $this->selectActiveCompany( $user_data['company_id'] );
        $this->selectPassiveCompany( $user_data['company_id'] );//WTF
    }
    public function selectActiveCompany( $active_company_id ){
        $acomp_data=$active_company_id?(object)$this->get_row("SELECT * FROM companies_list LEFT JOIN curr_list USING(curr_code) WHERE company_id='$active_company_id'"):NULL;
        $this->_acomp=$_SESSION['acomp']= $acomp_data;
    }
    public function selectPassiveCompany( $passive_company_id ){
        if( $this->acomp('company_id') === $passive_company_id )
            return;
        if( !$this->checkAssignedPassiveCompany( $passive_company_id ) )
            return;
        $this->_pcomp=$_SESSION['pcomp']=$passive_company_id?$this->get_row("SELECT * FROM companies_list JOIN companies_tree USING(branch_id) LEFT JOIN curr_list USING(curr_code) WHERE company_id='$passive_company_id'"):NULL;
    }
    private function checkAssignedPassiveCompany( $passive_company_id ){
        $branch_id=$this->get_row("SELECT branch_id FROM companies_list WHERE company_id='$passive_company_id'",0);
        return $this->checkAssignedBranch( $branch_id );
    }
    public function checkAssignedBranch( $branch_id ){
        $assigned_path=$this->svar('user_assigned_path');
        if( $assigned_path ){
            $where="path LIKE '".str_replace(',',"%' OR path LIKE '",$assigned_path)."%'";
            return $this->get_row("SELECT 1 FROM companies_tree WHERE branch_id=$branch_id AND ($where)",0);
        }
        return true;
    }
    public function reloadPassiveCompany(){
        $this->selectPassiveCompany( $this->pcomp('company_id') );
    }
    public function reloadActiveCompany(){
        $this->selectActiveCompany( $this->acomp('company_id') );
    }
    public function acomp($name,$warn=true){
        if( is_array($this->_acomp) && isset($this->_acomp[$name]) ){
	    return $this->_acomp[$name];
	}
	else if( is_object($this->_acomp) && isset($this->_acomp->$name) ){
	     return $this->_acomp->$name;
	}
        return NULL;
    }
    public function pcomp($name,$warn=true){
        if( is_array($this->_pcomp) && isset($this->_pcomp[$name]) ){
	    return $this->_pcomp[$name];
	}
	else if( is_object($this->_pcomp) && isset($this->_pcomp->$name) ){
	     return $this->_pcomp->$name;
	}
        return NULL;
    }
    public function loadSessionData(){
        if( isset($_SESSION['acomp']) )
            $this->_acomp=$_SESSION['acomp'];
        if( isset($_SESSION['pcomp']) )
            $this->_pcomp=$_SESSION['pcomp'];
        if( $this->svar('session_msg') && $this->svar('user_level')>3 ){
            $this->msg( $this->svar('session_msg') );
            $this->svar( 'session_msg', '');
        }
    }
//    public function checkPendingTasks(){
//        if( time()-$this->svar('task_last_exec')*1>3*60 && $_REQUEST['mod']!='SyncInit' ){
//            $this->postImpulse('/bay/',array('mod'=>'SyncInit'),array('name'=>session_name(),'value'=>session_id()),false);
//            $this->svar('task_last_exec',time());
//        }
//    }
//    public function postImpulse( $url, $params, $cookie=null ){
//        $parts=parse_url($url);
//        $post_string=http_build_query($params);
//        $fp = fsockopen($parts['host'],80,$errno, $errstr, 1);
//        if (!$fp)
//            $this->svar('session_msg',"postImpulse socket error:$errstr ($errno)");
//        else {
//            $out = "POST {$parts['path']} HTTP/1.1\r\n";
//            $out.= "Host: {$parts['host']}\r\n";
//            $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
//            $out.= "Content-Length: ".strlen($post_string)."\r\n";
//            if( $cookie )
//                    $out.= "Cookie:	{$cookie['name']}={$cookie['value']}\r\n";
//            $out.= "Connection: Close\r\n\r\n";
//            $out.= $post_string;
//            fwrite($fp, $out);
//            fclose($fp);
//        }
//        header("SyncInit: sended");
//    }
}
?>