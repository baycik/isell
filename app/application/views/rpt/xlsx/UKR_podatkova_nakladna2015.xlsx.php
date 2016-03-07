<?php

if( empty($this->view['p']['company_vat_id']) ){
    $this->view['p']['company_name']="Неплатник податку";
    $this->view['p']['company_jaddress']="-";
    if( empty($this->view['extra']->type_of_reason) ){
	$this->view['extra']->type_of_reason=" 02";
    }
    $this->view['p']['company_vat_id']="400000000000";
}
if( $this->view['extra']->type_of_reason ){
    $this->view['stay_at_seller']="X";
}
else{
    $this->view['is_original']="X";
}
if( $this->view['inernn']==1 ){
   $this->view['inernn']="X";
}
else{
    $this->view['inernn']="";
}
$this->view['footer']['vatless']=''.str_replace(',','.',$this->view['footer']['vatless']);
$this->view['footer']['vat']=str_replace(',','.',$this->view['footer']['vat']);
$this->view['footer']['total']=str_replace(',','.',$this->view['footer']['total']);

$this->post_processor=function( $page ){
    $page= stripslashes($page);
    return $page.str_replace(array("&nbsp;X   ","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"), array(" ","X"), $page);
};
