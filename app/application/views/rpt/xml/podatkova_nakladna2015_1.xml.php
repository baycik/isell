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
    $this->view['stay_at_seller']=true;
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



$this->view['c_reg'] = '08';
$this->view['c_raj'] = '32';
$this->view['tin'] = str_pad($this->view['a']['company_code'], 10, '0', STR_PAD_LEFT);
$this->view['c_doc'] = 'J12';
$this->view['c_doc_sub'] = '010';
$this->view['c_doc_ver'] = '7';
$this->view['c_doc_stan'] = '1';
$this->view['c_doc_type'] = '00';
$this->view['c_doc_cnt'] = str_pad($this->view['doc_num'], 7, '0', STR_PAD_LEFT);
$this->view['period_type'] = '1';
$this->view['period_month'] = substr($this->view['date'], 2, 2);
$this->view['period_year'] = substr($this->view['date'], 4, 4);
$this->view['c_sti_orig'] = $this->view['c_reg'] . $this->view['c_raj'];

$this->file_name_override = 
	  $this->view['c_reg'] 
	. $this->view['c_raj'] 
	. $this->view['tin']
	. $this->view['c_doc'] 
	. $this->view['c_doc_sub']
	. str_pad($this->view['c_doc_ver'], 2, '0', STR_PAD_LEFT) 
	. $this->view['c_doc_stan'] 
	. $this->view['c_doc_type']
	. $this->view['c_doc_cnt'] 
	. $this->view['period_type'] 
	. $this->view['period_month'] 
	. $this->view['period_year'] 
	. $this->view['c_sti_orig'] 
	. '.xml';
header('Content-Disposition: attachment;filename="' .$this->file_name_override . '"');