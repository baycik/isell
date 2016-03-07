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
