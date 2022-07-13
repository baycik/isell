<?php

$this->setPageOrientation('landscape');


$this->view->doc_id = 
"ON_NSCHFDOPPR_{$this->view->buyer->company_tax_id}-{$this->view->buyer->company_tax_id2}_{$this->view->seller->company_tax_id}-{$this->view->seller->company_tax_id2}"
."_20200729_1BAFD6E5-9CFF-4C74-82A0-3864F3AFF475"
.'.xml';
header('Content-Disposition: attachment;filename="' .$this->view->doc_id. '"');















/////////////////////////////////////////////////
//REQUISITES PREPARING
/////////////////////////////////////////////////
function getAll( $comp ) {
    $all ="$comp->company_name";
    $all.=$comp->company_jaddress?", $comp->company_jaddress":'';
    return $all;
}

if( isset($this->view->doc_view->extra->reciever_company_id) ){
    $this->Hub->load_model("Company");
    $this->view->reciever=$this->Hub->Company->companyGet($this->view->doc_view->extra->reciever_company_id);
} else {
    $this->view->reciever=$this->view->buyer;
}
if( isset($this->view->doc_view->extra->supplier_company_id) ){
    $this->Hub->load_model("Company");
    $this->view->supplier=$this->Hub->Company->companyGet($this->view->doc_view->extra->supplier_company_id);
} else {
    $this->view->supplier=$this->view->seller;
}

$torg12_view=null;
$view_list=$this->Hub->load_model("DocumentView")->viewListFetch( $this->view->doc_view->doc_id );
foreach($view_list as $view){
    if($view->view_role=='sell_bill'){
        $torg12_view=$view;
        break;
    }
}
$this->view->sell_bill_reference="1-".count($this->view->rows)." №{$torg12_view->view_num} от ".todmy($torg12_view->view_date);

$this->view->seller->all=getAll($this->view->seller);
$this->view->buyer->all=getAll($this->view->buyer);
$this->view->supplier->all=getAll($this->view->supplier);
$this->view->reciever->all=getAll($this->view->reciever);

$this->view->buyer->company_name=htmlentities($this->view->buyer->company_name);
$this->view->seller->company_name=htmlentities($this->view->seller->company_name);
$this->view->reciever->company_name=htmlentities($this->view->reciever->company_name);
$this->view->supplier->company_name=htmlentities($this->view->supplier->company_name);






if( isset($this->view->doc_view->extra->reason_date) ){
    $this->view->doc_view->extra->reason_date=todmy( $this->view->doc_view->extra->reason_date );
}
if( isset($this->view->doc_view->extra->transport_bill_date) ){
    $this->view->doc_view->extra->transport_bill_date=todmy( $this->view->doc_view->extra->transport_bill_date );
}
if( isset($this->view->doc_view->extra->goods_reciever_okpo) ){
    $this->view->goods_reciever_okpo=$this->view->doc_view->extra->goods_reciever_okpo;
}
if( isset($this->view->doc_view->extra->goods_reciever) ){
    $this->view->goods_reciever=$this->view->doc_view->extra->goods_reciever;
}

/////////////////////////////////////////////////
//ROW CALCULATION
/////////////////////////////////////////////////
include 'BlankDatatables.php';
$this->view->footer->total_qty=0;
$this->view->footer->vatless=0;
$this->view->footer->vat=0;
$i = 0;



$subcount = 0;
$subvatless = 0;
$subvat = 0;
$subtotal = 0;
foreach ($this->view->rows as &$row) {
    $row->num = ++$i;
    $row->product_sum_vat = $row->product_sum_total-$row->product_sum_vatless;
    $row->product_vat_rate=$this->view->head->vat_rate.'%';
    $row->product_excise='без акциза';

    $unit=unit_code($row->product_unit);
    $row->product_unit=$unit['name'];
    $row->product_unit_code=$unit['code'];

    $country= country_code($row->analyse_origin);
    $row->origin_name=$country['name'];
    $row->origin_code=$country['code'];

    if( !$row->party_label || strlen($row->party_label)<23 ){
        $row->party_label='-';
        $row->origin_name='-';
        $row->origin_code='-';
    }

    $subcount+=$row->product_quantity;
    $subvatless+=$row->product_sum_vatless;
    $subvat+=$row->product_sum_vat;
    $subtotal+=$row->product_sum_total;
}
$this->view->footer->total_qty+=$subcount;
$this->view->footer->vatless+=$subvatless;
$this->view->footer->vat+=$subvat;




///////////////////////////////////////////////
//TEMPLATE MULTIPAGE
///////////////////////////////////////////////


//print_r($this->view->tables);die;
/////////////////////////////////////////////////
//UTIL FUNCTIONS
/////////////////////////////////////////////////
function todmy( $iso ){
   $ymd= explode('-', $iso);
   return "$ymd[2].$ymd[1].$ymd[0]";
}
function format($num){
    return number_format($num, 2,'.','');
}
function daterus($dmy) {
    $dmy = explode('.', $dmy);
    $months = array('ноября', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
    return ' <' . $dmy[0] . '> ' . $months[$dmy[1] * 1] . ' ' . $dmy[2] . ' года';
}

/**
 * Возвращает сумму прописью
 * @author runcore
 * @uses morph(...)
 */
function num2str($num, $only_number = false) {
    $nul = 'ноль';
    $ten = array(
	array('', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
	array('', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
    );
    $a20 = array('десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать');
    $tens = array(2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто');
    $hundred = array('', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот');
    $unit = array(// Units
	array('копейка', 'копейки', 'копеек', 1),
	array('рубль', 'рубля', 'рублей', 0),
	array('тысяча', 'тысячи', 'тысяч', 1),
	array('миллион', 'миллиона', 'миллионов', 0),
	array('миллиард', 'милиарда', 'миллиардов', 0),
    );
    //
    list($rub, $kop) = explode('.', sprintf("%015.2f", floatval($num)));
    $out = array();
    if (intval($rub) > 0) {
	foreach (str_split($rub, 3) as $uk => $v) { // by 3 symbols
	    if (!intval($v))
		continue;
	    $uk = sizeof($unit) - $uk - 1; // unit key
	    $gender = $unit[$uk][3];
	    list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
	    // mega-logic
	    $out[] = $hundred[$i1]; # 1xx-9xx
	    if ($i2 > 1)
		$out[] = $tens[$i2] . ' ' . $ten[$gender][$i3];# 20-99
	    else
		$out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3];# 10-19 | 1-9
	    // units without rub & kop
	    if ($uk > 1)
		$out[] = morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
	} //foreach
    } else {
	$out[] = $nul;
    }
    if ($only_number) {
	return join(' ', $out);
    }
    $out[] = morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]); // rub
    $out[] = $kop . ' ' . morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]); // kop
    return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
}

/**
 * Склоняем словоформу
 * @ author runcore
 */
function morph($n, $f1, $f2, $f5) {
    $n = abs(intval($n)) % 100;
    if ($n > 10 && $n < 20)
	return $f5;
    $n = $n % 10;
    if ($n > 1 && $n < 5)
	return $f2;
    if ($n == 1)
	return $f1;
    return $f5;
}
