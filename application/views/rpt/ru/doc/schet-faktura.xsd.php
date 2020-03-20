<?php


foreach($this->view->rows as &$row){
    $row->vat_rate=$this->view->head->vat_rate/100;
    $row->product_sum_vat = sprintf("%.2f", $row->product_sum_total-$row->product_sum_vatless);
    $row->product_accis='без акциза';
    
    $row->origin_name=$row->analyse_origin;
    
    //$row->product_price=format($row->product_price*$vat_ratio);
    //$row->product_sum=format($row->product_price*$row->product_quantity);
}



$xsdMainSetting = [ 
    
    'xsdChoices' => [
        'СвПрод-СвЮЛУч' => '',
        'СвПрод-АдрРФ' => '',
        'ГрузОт-ОнЖе' => '',
        'ГрузОтпр-СвЮЛУч'  => '',
        'ГрузОтпр-АдрРФ' => '',
        'ГрузПолуч-СвЮЛУч' => '',
        'ГрузПолуч-АдрРФ' => '',
        'СвПокуп-СвЮЛУч' => '',
        'СвПокуп-АдрРФ' => '',
        'Акциз-БезАкциз' => '',
        'СумНал-СумНал' => '',
        'СумНалВсего-СумНал' => '',
        'СвЛицПер-ИнЛицо' => '',
        'ИнЛицо-ПредОргПер' => '',
        'Перевозчик-СвЮЛУч' => '',
        'Перевозчик-АдрРФ' => '',
        'Подписант-ЮЛ' => ''
    ],

    /*=================================================
     * 
     * Custom syntax:
     * '@'      - get default value from xsd file
     * '#...'   - set custom string value
     * '...'    - get value from input data object
    */

    'xsdRelations' => [
        // HEAD OF THE DOC
        'ВерсФорм' => '@',
        'ВерсПрог' => '#iSell(версия 5.0.1.0)' ,


        'ИдЭДО'=>'#000',
        'КНД'=>'#1115125',


        //DOC MAIN INFO


        'Функция'=>'#СЧФДОП',
        'ПоФактХЖ'=>'#Документ об отгрузке товаров (выполнении работ), передаче имущественных прав (документ об оказании услуг)',
        'НаимДокОпр'=>'#Счет-фактура и документ об отгрузке товаров (выполнении работ), передаче имущественных прав (документ об оказании услуг)',
        'НаимЭконСубСост'=>'active_company_label',

        //SCHET-FAKTURA INFO

        'НомерСчФ'=>'view_num',
        'ДатаСчФ'=>'doc_date',

        //CURRENCY CODE

        'КодОКВ'=>'#643',


        // COMPANY INFO

        'НаимОрг'=>'company_name',
        'Тлф'=>'company_phone',
        'ЭлПочта'=>'company_email',
        'НомерСчета'=>'company_bank_account',
        'НаимБанк'=>'company_bank_name',
        'БИК'=>'company_bank_id',
        'КорСчет'=>'company_bank_corr_account',
        'Фамилия'=>'company_director_surname',
        'Имя'=>'company_director_name',
        'Отчество'=>'company_director_secondname',
        'ИННЮЛ'=>'company_tax_id',
        'ИННФЛ'=>'company_tax_id',
        'КПП'=>'company_tax_id2',

        // COMPANY ADDRESS

        'Индекс'=>'company_jaddress_index',
        'КодСтр'=>'company_jaddress_country_code',
        'КодРегион'=>'#82',
        'АдрТекст='=>'company_jaddress',
        'Район'=>'company_jaddress_district',
        'Город'=>'company_jaddress_city',
        'НаселПункт'=>'company_jaddress_locality',
        'Улица'=>'company_jaddress_street',
        'Дом'=>'company_jaddress_house',
        'Корпус'=>'company_jaddress_housing',
        'Кварт'=>'company_jaddress_apartment',

        //DOCUMENT ENTRIES

        'НомСтр'=>'',
        'НаимТов'=>'product_name',
        'ОКЕИ_Тов'=>'#796',
        'КолТов'=>'product_quantity',
        'НалСт'=>'vat_rate',
        'ЦенаТов'=>'product_price',
        'СтТовБезНДС'=>'product_sum_vatless',
        'СтТовУчНал'=>'product_sum_total',
        'СумНал'=>'product_sum_vat',
        'НеттоВс'=>'weight',

        'КодПроисх'=>'#156',
        'НомерТД'=>'#00009162/180418/0013548/01',
        'ПрТовРаб'=>'#1',
        'КодТов'=>'product_code',
        'НаимЕдИзм'=>'#шт',

        //DOCUMENT FOOTER

        'СтТовБезНДСВсего'=>'vatless',
        'СтТовУчНалВсего'=>'total',
        'НеттоВс'=>'total_weight',
        'СумНалВсего'=>'vat',

        //DOCUMENT ADDITIONAL INFO

        'СодОпер'=>'#Товары переданы',
        'ДатаПер'=>'doc_date',

        'НаимОсн'=>'#Отсутствует',
        'НомОсн'=>'doc_id',
        'ДатаОсн'=>'doc_date',

        'НомТранНакл'=>'doc_id',
        'ДатаТранНакл'=>'doc_date',

        //SIGNER

        'ОблПолн'=>'#5',
        'Статус'=>'#1',
        'ОснПолн'=>'#Основные полномочия',
        'ОснПолнОрг'=>'#Полномочия организации',
        'Должн'=>'company_director_title'
    ],

    'xsdSettings' => [
        'СвОЭДОтпр' => 'p',
        'СвПрод' => 'p',
        'ГрузОт' => 'p',
        'ГрузОтпр' => 'p',
        'ГрузПолуч' => 'a',
        'СвПокуп' => 'a',
        'СвПродПер' => 'p',
        'СведТов' => 'rows',
        'ВсегоОпл' => 'footer',
        'Подписант' => 'p'
    ],
    
    'xsdExceptions' => [
        'ВремФайлИнфПр' => '',
        'Значен' => '',
        'СумНалВсего' => '',
        'ДатаИспрСчФ' => '',
        'ДатаПРД' => '',
    ]
];   
$this->xsdtoxml->loadSettings($xsdMainSetting);
