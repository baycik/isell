<?xml version="1.0" encoding="windows-1251"?>
<Файл ИдФайл="<?php echo $document_name; ?>" ВерсПрог="iSell(версия 4)" ВерсФорм="5.06" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
	<Документ Индекс="0000080" НомКорр="<?php echo $data['correction_num']; ?>">
		<КнигаПокуп СумНДСВсКПк="<?php echo $data['sub_totals']->sum_total; ?>">
                    <?php foreach($data['rows'] as $index => $item){ ?>
			<КнПокСтр НомерПор="<?php echo $index+1; ?>" НомСчФПрод="<?php echo $item->tax_bill_num; ?>" ДатаСчФПрод="<?php echo $item->cdate; ?>" СтоимПокупВ="<?php echo abs($item->total); ?>" СумНДСВыч="<?php echo abs($item->vat); ?>">
				<КодВидОпер>01</КодВидОпер>
				<ДатаУчТов><?php echo $item->cdate; ?></ДатаУчТов>
				<СвПрод>
                                        <?php if(strlen($item->company_tax_id) == 12){ ?>
					<СведИП ИННФЛ="<?php echo $item->company_tax_id; ?>"/>
                                         <?php } else { ?>  
                                        <СведЮЛ ИННЮЛ="<?php echo $item->company_tax_id; ?>" КПП="<?php echo $item->company_tax_id2; ?>" />
                                         <?php } ?>    
				</СвПрод>
                                <?php if($item->party_labels){ ?>
                                    <?php foreach(explode(',', $item->party_labels) as $party_label){ ?>
                                        <?php if(!empty($party_label)){ ?>
                                    <РегНомТД><?php echo trim(preg_replace('/[\t|\s{2,}]/', '', $party_label)); ?></РегНомТД>
                                    <?php }} ?>  
                                <?php } ?>   
			</КнПокСтр>
                    <?php } ?>    
		</КнигаПокуп>
	</Документ>
</Файл>
