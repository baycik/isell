<?php
require_once 'iSellBase.php';
class ProcLogin extends iSellBase{
    public function ProcLogin(){
        $this->ProcessorBase();
    }
    public function onUserLogin(){
        $user_login=$this->request('user_login');
        $user_pass=$this->request('user_pass');
        if( $this->login($user_login,$user_pass) ){
            $this->response( 'login_successfull' );
        }
	$this->response_wrn( "Неверый пароль или логин!" );
    }
    public function onUserLogout(){
        $this->logout();
    }
    public function onUserData(){
	$this->set_level(1);
        $resp=array();
        $resp['user_id']=$this->svar('user_id');
        $resp['user_login']=$this->svar('user_login');
        $resp['user_level']=$this->svar('user_level');
        $resp['user_level_name']=$this->svar('user_level_name');
	$resp['active_company_name']=$this->acomp('company_name');
	$this->response( $resp );
    }
}
?>