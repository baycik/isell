<?php
    $this->setPageOrientation('landscape');
    $this->view->doc_view->total_spell=  num2str($this->view->footer->total);
    $this->view->doc_view->date_spell= daterus($this->view->doc_view->date_dot);
    
    function getAll( $comp ) {
        $all ="$comp->company_name";
        $all.=$comp->company_jaddress?", $comp->company_jaddress":'';
        return $all;
    }
    
    $this->view->supplier=$this->view->seller;
    $this->view->reciever=$this->view->buyer;
    if( isset($this->view->doc_view->extra->reciever_company_id) ){
        $this->Hub->load_model("Company");
        $this->view->reciever=$this->Hub->Company->companyGet($this->view->doc_view->extra->reciever_company_id);
    }
    if( isset($this->view->doc_view->extra->supplier_company_id) ){
        $this->Hub->load_model("Company");
        $this->view->supplier=$this->Hub->Company->companyGet($this->view->doc_view->extra->supplier_company_id);
    }
    $this->view->seller->all=getAll($this->view->seller);
    $this->view->buyer->all=getAll($this->view->buyer);
    $this->view->supplier->all=getAll($this->view->supplier);
    $this->view->reciever->all=getAll($this->view->reciever);
    
    if( $this->view->supplier->company_id==$this->view->seller->company_id ){
        $this->view->supplier->all='он же';
    }
    if( isset($this->view->doc_view->extra->reason_date) ){
        $this->view->doc_view->extra->reason_date=todmy( $this->view->doc_view->extra->reason_date );
    }
    
    //print_r($this->view);die;
    
    
    $countries=[
        'ТУРЦИЯ'=>'792',
        'КИТАЙ'=>'156',
        'БЕЛАРУСЬ'=>'112',
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
    $this->view->total_qty=0;
    foreach( $this->view->rows as &$row ){
        $row->product_unit_code=$okei[$row->product_unit];
        $row->product_sum_vat=$row->product_sum_total-$row->product_sum_vatless;
        $row->product_vat_rate=$this->view->head->vat_rate/100;
        $this->view->total_qty+=$row->product_quantity;
        
        $row->product_excise='без акциза';
        $row->skip='-';
        $row->origin_name='-';
        $row->origin_code='-';
        
        $upper_origin=mb_strtoupper($row->analyse_origin);
        if( !empty($countries[$upper_origin]) ){
            $row->origin_name=$upper_origin;
            $row->origin_code=$countries[$upper_origin];
        }
        if( empty($row->party_label) ){
            $row->party_label='-';
        }
        //print_r($row);die;
    }
    $this->view->row_count=count($this->view->rows);
    $this->view->row_count_spell= num2str($this->view->row_count,false);
    $this->view->place_count_spell= num2str($this->view->doc_view->extra->place_count,false);
    

   function todmy( $iso ){
       $ymd= explode('-', $iso);
       return "$ymd[2].$ymd[1].$ymd[0]";
   }

    function daterus($dmy) {
        $dmy=  explode('.', $dmy);
        $months = array('ноября', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
        return ' ' .$dmy[0] . ' ' . $months[$dmy[1]*1] . ' ' . $dmy[2] . ' года';
    }


    /**
     * Возвращает сумму прописью
     * @author runcore
     * @uses morph(...)
     */
    function num2str($num, $include_ruble=true) {
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
        if( $include_ruble ){
            $out[] = morph(intval($rub), $unit[1][0],$unit[1][1],$unit[1][2]); // rub
            $out[] = $kop.' '.morph($kop,$unit[0][0],$unit[0][1],$unit[0][2]); // kop
        }
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