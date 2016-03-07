<?php
    $this->view->doc_view->total_spell=  spellAmount($this->view->footer->total);

    function spellAmount($number, $unit = NULL, $return_cents = true) {
	if (!$unit) {
	    $unit[0] = array('копейка', 'копейки', 'копеек');
	    $unit[1] = array('рубль', 'рубля', 'рублей');
	    $unit[2] = array('тысяча', 'тысячи', 'тысяч');
	    $unit[3] = array('миллион', 'миллиона', 'миллионов');
	}
	$millions = getNumberPosition($number, 1000000, 100);
	$thousands = getNumberPosition($number, 1000, 100);
	$ones = getNumberPosition($number, 1, 100);
	$cents = getNumberPosition($number * 100, 1, 10);

	$str = spellNumber($millions, $unit[3]) . ' ' . spellNumber($thousands, $unit[2]) . ' ' . spellNumber($ones, $unit[1]) . ' ' . spellNumber($cents, $unit[0], $return_cents);
	$str = trim($str);
	return mb_strtoupper(mb_substr($str, 0, 1, 'utf-8'), 'utf-8') . mb_substr($str, 1, mb_strlen($str) - 1, 'utf-8');
    }

    function spellNumber($number, $units=null, $ret_number = false) {
	$hundreds_i = getNumberPosition($number, 100, 1);
	$tens_i = getNumberPosition($number, 10, 1);
	$ones_i = getNumberPosition($number, 1, 1);
	if (!($hundreds_i || $tens_i || $ones_i) && !$ret_number){
	    return '';
	}
	if( $units ){
	    if ($ones_i === 1 && $tens_i != 1){
		$unit = $units[0];
	    } else 
	    if ($ones_i > 1 && $ones_i < 5){
		$unit = $units[1];
	    } else {
		$unit = $units[2];
	    }
	    if ($ret_number) {
		if ($number < 10)
		    return "0$number $unit";
		return "$number $unit";
	    }
	} else {
	    $unit='';
	}

	$ones = array("", "один", "два", "три", "четыре", "пять", "шесть", "сем", "восем", "девять");
	$tens = array("", "десять", "двадцать", "тридцать", "сорок", "пятдесят", "шестдесят", "семдесят", "восемдесят", "девяносто");
	$teens = array("", "одинадцать", "двенадцать", "тринадцать", "четырнадцать", "пятнадцать", "шестнадцать", "семнадцать", "восемнадцать", "девятнадцать");
	$hundreds = array("", "сто", "двести", "триста", "четыреста", "пятсот", "шестсот", "семсот", "восемсот", "девятсот");

	if ($tens_i == 1){
	    return "$hundreds[$hundreds_i] $teens[$ones_i] $unit";
	}
	else{
	    return "$hundreds[$hundreds_i] $tens[$tens_i] $ones[$ones_i] $unit";
	}
    }

    function getNumberPosition($number, $position, $range = 1) {//DEPRECATED
	$number-=$position * 10 * $range * floor($number / $position / 10 / $range);
	return floor($number / $position);
    }