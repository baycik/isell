<?xml version="1.0" encoding="windows-1251"?>
<Файл  ИдФайл="<?php echo $document_name; ?>" ВерсПрог="iSell(версия 4)" ВерсФорм="5.06" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
	<Документ Индекс="0000090" НомКорр="0">
		<КнигаПрод СтПродБезНДС20="<?=$data['sub_totals']->sum_vatless; ?>"  СтПродБезНДС0="<?=$data['sub_totals']->sum_total; ?>" СумНДСВсКПр20="<?=$data['sub_totals']->sum_vat; ?>"  СтПродОсвВсКПр="0.00">
                    <?php foreach($data['rows'] as $index => $item){ ?>
                        <КнПродСтр НомерПор="<?php echo $index+1; ?>" НомСчФПрод="<?php echo $item->tax_bill_num; ?>" ДатаСчФПрод="<?php echo $item->cdate; ?>" СтоимПродСФ="<?php echo abs($item->total); ?>" СтоимПродСФ20="<?php echo abs($item->vatless); ?>" СумНДССФ20="<?php echo abs($item->vat); ?>">
                            <КодВидОпер>01</КодВидОпер>
                            <СвПокуп>
                                    <?php if(strlen($item->company_tax_id) == 12){  ?>
                                    <СведИП ИННФЛ="<?php echo $item->company_tax_id; ?>"/>
                                     <?php } else { ?>  
                                    <СведЮЛ ИННЮЛ="<?php echo $item->company_tax_id; ?>" КПП="<?php echo $item->company_tax_id2; ?>" />
                                     <?php } ?>  
                            </СвПокуп>
                        </КнПродСтр>
                    <?php } ?>    
		</КнигаПрод>
	</Документ>
</Файл>
