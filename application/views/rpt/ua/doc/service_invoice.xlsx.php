<?php

$this->view->a->all=getAll($this->view->a);
$this->view->p->all=getAll($this->view->p);

if( $this->view->head->doc_type==3 ){
    /*we are selling the service*/
    $this->view->ag_num=$this->view->p->company_agreement_num;
    $this->view->ag_date=$this->view->p->company_agreement_date;
    $this->view->sign_performer= $this->view->doc_view->user_position.$this->view->doc_view->user_sign;
} else {
    /*we are buying the service*/
    $this->view->ag_num=$this->view->a->company_agreement_num;
    $this->view->ag_date=$this->view->a->company_agreement_date;
    $this->view->sign_customer= $this->view->doc_view->user_position.$this->view->doc_view->user_sign;
}


$this->view->ag_date_dot=date('d.m.Y',  strtotime($this->view->ag_date));

function getAll( $comp ) {
    $all ="$comp->company_name \n$comp->company_jaddress";
    $all.=$comp->company_phone?", тел.:{$comp->company_phone}":'';
    $all.=$comp->company_bank_account?", Р/р:{$comp->company_bank_account}":'';
    $all.=$comp->company_bank_name?" в {$comp->company_bank_name}":'';
    $all.=$comp->company_bank_id?", МФО:{$comp->company_bank_id}":'';
    $all.=$comp->company_vat_id?", IПН:{$comp->company_vat_id}":'';
    $all.=$comp->company_code?", ЄДРПОУ:{$comp->company_code}":'';
    $all.=$comp->company_email?", E-mail:{$comp->company_email}":'';
    $all.=$comp->company_web?",{$comp->company_web}":'';
    return $all;
}
