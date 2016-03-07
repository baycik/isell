<?php
    $this->view->a->all=  getAll($this->view->a);
    $this->view->doc_view->total_spell=  num2str($this->view->footer->total);
    $this->view->doc_view->vat_spell=  num2str($this->view->footer->vat);
    $this->view->doc_view->loc_date=  russian_date($this->view->doc_view->date_dot);

    function getAll( $comp ) {
        $all =$comp->company_name." ".$comp->company_jaddress;
        $all.=$comp->company_phone?", тел.:{$comp->company_phone}":'';
        $all.=$comp->company_bank_account?", р/с:{$comp->company_bank_account}":'';
        $all.=$comp->company_bank_corr_account?", к/с:{$comp->company_bank_corr_account}":'';
        $all.=$comp->company_bank_name?" в {$comp->company_bank_name}":'';
        $all.=$comp->company_bank_id?", БИК:{$comp->company_bank_id}":'';
        $all.=$comp->company_vat_id?", ИНН:{$comp->company_vat_id}":'';
        $all.=$comp->company_code?", ОКПО:{$comp->company_code}":'';
        $all.=$comp->company_email?", E-mail:{$comp->company_email}":'';
        $all.=$comp->company_web?",{$comp->company_web}":'';
        return $all;
    }
    function russian_date( $date_dot ){
        $date=explode(".", $date_dot);
        switch ($date[1]){
            case 1: $m='января'; break;
            case 2: $m='февраля'; break;
            case 3: $m='марта'; break;
            case 4: $m='апреля'; break;
            case 5: $m='мая'; break;
            case 6: $m='июня'; break;
            case 7: $m='июля'; break;
            case 8: $m='августа'; break;
            case 9: $m='сентября'; break;
            case 10: $m='октября'; break;
            case 11: $m='ноября'; break;
            case 12: $m='декабря'; break;
        }
        return $date[0].' '.$m.' '.$date[2];
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