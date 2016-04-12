<?php 
if( empty($this->view['p']['company_vat_id']) ){
    $this->view['p']['company_name']="Неплатник податку";
    $this->view['p']['company_jaddress']="-";
    if( empty($this->view['extra']->type_of_reason) ){
	$this->view['extra']->type_of_reason="02";
    }
    $this->view['p']['company_vat_id']="100000000000";
}
if( $this->view['extra']->type_of_reason ){
    $this->view['stay_at_seller']='X';
}

$this->view['agreement_type']='договір поставки';
$this->view['payment_type']='оплата з поточного рахунка';

$unit_codes=array(
    'шт'=>2009,
    'м'=>0101,
    'кг'=>0301,
    'г'=>0303
);
foreach( $this->view['entries'] as &$entry ){
    $entry['product_unit_code']=$unit_codes[$entry['product_unit']];
}
