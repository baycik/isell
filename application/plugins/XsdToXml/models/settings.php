<?php
$xsd_choices = [
    'СвПрод-СвЮЛУч' => '',
    'СвПрод-АдрРФ' => '',
    'ГрузОт-ГрузОтпр' => '',
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
];

$xsd_skip_tags = [
    'СвПрод-СвЮЛУч' => '',
    'СвПрод-АдрРФ' => ''
];        
$xsd_relations = [
    // HEAD OF THE DOC
    'ИдФайл' => '',
    'ВерсФорм' => '5.01',
    'ВерсПрог' => 'Инфо-Предприятие(версия 4.5.981)' ,
    
 
    
    
   'ИдОтпр' => 'doc_id',
    'ИдПол' => '000123123',
   
    
   
    'ИдЭДО'=>'000',
    'КНД'=>'1115125',
    
    
    //DOC MAIN INFO
    
    
   'Функция'=>'СЧФДОП',
   'ПоФактХЖ'=>'Документ об отгрузке товаров (выполнении работ), передаче имущественных прав (документ об оказании услуг)',
   'НаимДокОпр'=>'Счет-фактура и документ об отгрузке товаров (выполнении работ), передаче имущественных прав (документ об оказании услуг)',
   'НаимЭконСубСост'=>'active_company_label',
    
    //SCHET-FAKTURA INFO
    
   'НомерСчФ'=>'view_num',
   'ДатаСчФ'=>'doc_date',
    
    //CURRENCY CODE
    
   'КодОКВ'=>'643',
    
    
    // COMPANY INFO

   'НаимОрг'=>'company_name',
   'Тлф'=>'company_phone',
   'ЭлПочта'=>'company_email',
   'НомерСчета'=>'company_bank_account',
   'НаимБанк'=>'company_bank_name',
   'БИК'=>'company_bank_id',
   'КорСчет'=>'company_bank_corr_account',
   'Фамилия'=>'company_director',
   'Имя'=>'company_director',
   'Отчество'=>'company_director',
   'ИННЮЛ'=>'company_tax_id',
   'ИННФЛ'=>'company_tax_id',
   'КПП'=>'company_tax_id2',
    
    // COMPANY ADDRESS

   'Индекс'=>'company_jaddress',
   'КодСтр'=>'company_jaddress',
   'КодРегион'=>'company_jaddress',
   'АдрТекст='=>'company_jaddress',
   'Район'=>'company_jaddress',
   'Город'=>'company_jaddress',
   'НаселПункт'=>'company_jaddress',
   'Улица'=>'company_jaddress',
   'Дом'=>'company_jaddress',
   'Корпус'=>'company_jaddress',
   'Кварт'=>'company_jaddress',

    //DOCUMENT ENTRIES
    
   'НомСтр'=>'',
   'НаимТов'=>'product_name',
   'ОКЕИ_Тов'=>'796',
   'КолТов'=>'product_quantity',
   'НалСт'=>'vat_rate',
   'ЦенаТов'=>'product_price',
   'СтТовБезНДС'=>'product_sum_vatless',
   'СтТовУчНалВсего'=>'product_sum_total',
   'СумНДС'=>'',
   'НеттоВс'=>'weight',

   'КодПроисх'=>' ',
   'НомерТД'=>' ',
   'ПрТовРаб'=>'1',
   'КодТов'=>'product_code',
   'НаимЕдИзм'=>'шт',
    
    //DOCUMENT FOOTER
    
   'СтТовБезНДСВсего'=>'vatless',
   'СтТовУчНалВсего'=>'total',
   'НеттоВс'=>'total_weight',
   'СумНал'=>'vat',

    //DOCUMENT ADDITIONAL INFO
    
   'СодОпер'=>'Товары переданы',
   'ДатаПер'=>'doc_date',
    
   'НаимОсн'=>'Отсутствует',
   'НомОсн'=>'doc_id',
   'ДатаОсн'=>'doc_date',
    
   'НомТранНакл'=>'doc_id',
   'ДатаТранНакл'=>'doc_date',
   
    //SIGNER
    
   'ОблПолн'=>'5',
   'Статус'=>'1',
   'ОснПолн'=>'Основные полномочия',
   'ОснПолнОрг'=>'Полномочия организации',
   'Должн'=>'company_director_title'
];

$xsd_settings = [
    'СвОЭДОтпр' => 'a',
    'СвПрод' => 'a',
    'ГрузОт' => 'a',
    'ГрузОтпр' => 'a',
    'ГрузПолуч' => 'p',
    'СвПокуп' => 'p',
    'СвПродПер' => 'a',
    'СведТов' => 'rows',
    'ВсегоОпл' => 'footer',
    'Подписант' => 'a'
];

