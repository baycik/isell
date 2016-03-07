<?php

if( empty($this->view['p']['company_vat_id']) ){
    $this->view['p']['company_name']="Неплатник податку";
    $this->view['p']['company_jaddress']="-";
    if( empty($this->view['extra']->type_of_reason) ){
	$this->view['extra']->type_of_reason="02";
    }
    $this->view['p']['cvi']="400000000000";
}
if( $this->view['extra']->type_of_reason ){
    $this->view['stay_at_seller']="X";
}
