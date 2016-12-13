<?php
$countries=[
    'Турция'=>'792',
    'Китай'=>'156',
    'Беларусь'=>'112',
    'ГЕРМАНИЯ'=>'276',
    'ФРАНЦИЯ'=>'250'    
];
$okei = [
    'шт' => '796',
    'руб'=>'383',
    '1000 руб'=>'384',
    'компл'=>'839',
    'л'=>'112',
    'усл. ед'=>'876',
    'кг'=>'166',
    'т'=>'168',
    'ч'=>'356',
    'м' => '006',
    'м2'=>'055',
    'пог. м'=>'018',
    'упак'=>'778'
];
foreach($this->view->rows as &$row){
    $row->vat_rate=$this->view->head->vat_rate/100;
    $row->product_unit_code = $okei[$row->product_unit];
    $row->product_sum_vat = $row->product_sum_total-$row->product_sum_vatless;
    $row->product_accis='без акциза';
    
    $row->origin_name=$row->product_uktzet;
    $row->origin_code=$countries[$row->origin_name];
    
    //$row->product_price=format($row->product_price*$vat_ratio);
    //$row->product_sum=format($row->product_price*$row->product_quantity);
}
    function format($num){
        return number_format($num, 2,'.','');
    }    
