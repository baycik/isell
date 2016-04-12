<?php 
if( empty($this->view->p->company_vat_id) ){
    $this->view->p->company_name="Неплатник податку";
    $this->view->p->company_jaddress="-";
    if( empty($this->view->doc_view->extra->type_of_reason) ){
	$this->view->doc_view->extra->type_of_reason="02";
    }
    $this->view->p->company_vat_id="100000000000";
}
if( $this->view->doc_view->extra->type_of_reason ){
    $this->view->doc_view->stay_at_seller='X';
}

$this->view->doc_view->agreement_type='договір поставки';
$this->view->doc_view->payment_type='оплата з поточного рахунка';
$this->view->doc_view->rows_num=count($this->view->rows);

if (!$this->view->p->company_agreement_date && !$this->view->p->company_agreement_num) {
    $this->view->p->company_agreement_date = $this->view->doc_view->tstamp;
    $this->view->p->company_agreement_num = '-';
}
$this->view->p->ag_date_dot=date('d.m.Y',  strtotime($this->view->p->company_agreement_date));

$unit_codes=[
    'шт'=>'2009',
    'м'=>'0101',
    'кг'=>'0301',
    'г'=>'0303'
];
foreach( $this->view->rows as &$row ){
    $row->product_unit_code=$unit_codes[$row->product_unit];
}

$this->view->c_reg = '08';
$this->view->c_raj = '32';
$this->view->tin = str_pad($this->view->a->company_code, 10, '0', STR_PAD_LEFT);
$this->view->c_doc = 'J12';
$this->view->c_doc_sub = '010';
$this->view->c_doc_ver = '8';
$this->view->c_doc_stan = '1';
$this->view->c_doc_type = '00';
$this->view->c_doc_cnt = str_pad($this->view->doc_view->view_num, 7, '0', STR_PAD_LEFT);
$this->view->period_type = '1';
$this->view->period_month = substr($this->view->doc_view->date, 2, 2);
$this->view->period_year = substr($this->view->doc_view->date, 4, 4);
$this->view->c_sti_orig = $this->view->c_reg . $this->view->c_raj;

$this->file_name_override = 
	  $this->view->c_reg 
	. $this->view->c_raj 
	. $this->view->tin
	. $this->view->c_doc 
	. $this->view->c_doc_sub
	. str_pad($this->view->c_doc_ver, 2, '0', STR_PAD_LEFT) 
	. $this->view->c_doc_stan 
	. $this->view->c_doc_type
	. $this->view->c_doc_cnt 
	. $this->view->period_type 
	. $this->view->period_month 
	. $this->view->period_year 
	. $this->view->c_sti_orig 
	. '.xml';
header('Content-Disposition: attachment;filename="' .$this->file_name_override . '"');