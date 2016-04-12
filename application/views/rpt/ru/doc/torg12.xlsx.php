<?php
    $this->view->doc_view->total_spell=  num2str($this->view->footer->total);
    $this->view->doc_view->date_spell= daterus($this->view->doc_view->date_dot);
    $this->view->p->all=getAll($this->view->p);
    $this->view->a->all=getAll($this->view->a);
    
    $okei=[
        'шт'=>'796',
        'м'=>'006'
    ];
    $this->view->total_qty=0;
    foreach( $this->view->rows as &$row ){
        $row->product_unit_code=$okei[$row->product_unit];
        $this->view->total_qty+=$row->product_quantity;
        
        
        $row->product_sum_vat=round($row->product_sum*0.18,2);
        $row->product_sum_total=$row->product_sum+$row->product_sum_vat;
    }
    $this->view->row_count=count($this->view->rows);
    
    

    function getAll( $comp ) {
        $all ="$comp->company_name";
        $all.=$comp->company_vat_id?", ИНН/КПП:{$comp->company_vat_id}/{$comp->company_vat_licence_id}":'';
        $all.=$comp->company_jaddress?", $comp->company_jaddress":'';
        $all.=$comp->company_phone?", тел.:{$comp->company_phone}":'';
        return $all;
    }
    

    function daterus($dmy) {
        $dmy=  explode('.', $dmy);
        $months = array('ноября', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
        return ' <' .$dmy[0] . '> ' . $months[$dmy[1]*1] . ' ' . $dmy[2] . ' года';
    }


/**
 * Возвращает сумму прописью
 * @author runcore
 * @uses morph(...)
 */
function num2str($num) {
	$nul='ноль';
	$ten=array(
		array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),
		array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять'),
	);
	$a20=array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать');
	$tens=array(2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто');
	$hundred=array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот');
	$unit=array( // Units
		array('копейка' ,'копейки' ,'копеек',	 1),
		array('рубль'   ,'рубля'   ,'рублей'    ,0),
		array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
		array('миллион' ,'миллиона','миллионов' ,0),
		array('миллиард','милиарда','миллиардов',0),
	);
	//
	list($rub,$kop) = explode('.',sprintf("%015.2f", floatval($num)));
	$out = array();
	if (intval($rub)>0) {
		foreach(str_split($rub,3) as $uk=>$v) { // by 3 symbols
			if (!intval($v)) continue;
			$uk = sizeof($unit)-$uk-1; // unit key
			$gender = $unit[$uk][3];
			list($i1,$i2,$i3) = array_map('intval',str_split($v,1));
			// mega-logic
			$out[] = $hundred[$i1]; # 1xx-9xx
			if ($i2>1) $out[]= $tens[$i2].' '.$ten[$gender][$i3]; # 20-99
			else $out[]= $i2>0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
			// units without rub & kop
			if ($uk>1) $out[]= morph($v,$unit[$uk][0],$unit[$uk][1],$unit[$uk][2]);
		} //foreach
	}
	else $out[] = $nul;
	$out[] = morph(intval($rub), $unit[1][0],$unit[1][1],$unit[1][2]); // rub
	$out[] = $kop.' '.morph($kop,$unit[0][0],$unit[0][1],$unit[0][2]); // kop
	return trim(preg_replace('/ {2,}/', ' ', join(' ',$out)));
}

/**
 * Склоняем словоформу
 * @ author runcore
 */
function morph($n, $f1, $f2, $f5) {
	$n = abs(intval($n)) % 100;
	if ($n>10 && $n<20) return $f5;
	$n = $n % 10;
	if ($n>1 && $n<5) return $f2;
	if ($n==1) return $f1;
	return $f5;
}