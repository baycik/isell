<?php

require_once('iSellBase.php');

class ProcPref extends iSellBase {

    function ProcPref() {
        $this->ProcessorBase(2);
    }

    public function onDefault() {
        $this->response_tpl('pref/pref_main.html');
    }

    public function onDetails() {
        $this->LoadClass('Companies');
        $company_data = $this->Companies->getCompanyDetails(true /* Active Company */);
        //$this->LoadClass('Pref');
        //$company_data['user_list']=$this->Pref->getUserList();
        $this->response($company_data);
    }

    public function onDetailUpdate() {
        $field_name = $this->request('field_name');
        $field_value = $this->request('field_value');
        $this->LoadClass('Companies');
        $this->Companies->updateDetail($field_name, $field_value, true /* Active Company */);
        if ($field_name == "dollar_ratio") {
            $this->LoadClass('Pref');
            $this->Pref->setPrefs(array('dollar_ratio' => $field_value));
        }
    }

    public function onStaffList() {
        $direct = $this->request('direct_response', 1);
        $this->LoadClass('Pref');
        $list = $this->Pref->getStaffList();
        if ($direct)
            $this->response($list);
        else
            $this->response($list);
    }

    public function onUserListGet() {
        $this->LoadClass('Pref');
        $user_list = $this->Pref->userListGet();
        $this->response($user_list);
    }

    public function onUserDetailUpdate() {
        $user_id = $this->request('user_id', 1);
        $field_name = $this->request('field_name');
        $field_value = $this->request('field_value');
        $this->LoadClass('Pref');
        $ok = $this->Pref->userDetailUpdate($user_id, $field_name, $field_value);
        $this->response($ok);
    }

    public function onUserAdd() {
        $this->LoadClass('Pref');
        $ok = $this->Pref->userAdd();
        $this->response($ok);
    }

    public function onUserDelete() {
        $user_id = $this->request('user_id', 1);
        $this->LoadClass('Pref');
        $ok = $this->Pref->userDelete($user_id);
        $this->response($ok);
    }

    public function onUserChangePass() {
        $user_id = $this->request('user_id', 1);
        $curr_pass = $this->request('curr_pass');
        $new_pass = $this->request('new_pass');
        $this->LoadClass('Pref');
        $ok = $this->Pref->userPassChange($user_id, $curr_pass, $new_pass);
        $this->response($ok);
    }

    public function onPrefGet(){
        $this->LoadClass('Pref');
        $this->response($this->Pref->prefGet());
    }
    
    public function onPrefUpdate(){
        $field=$this->request('field');
        $value=$this->request('value');
        $this->LoadClass('Pref');
        $this->response($this->Pref->prefUpdate($field,$value));
    }
    
}

?>