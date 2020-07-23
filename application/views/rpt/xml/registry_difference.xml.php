<?xml version="1.0" encoding="windows-1251"?>
<Файл  ИдФайл="<?php echo $document_name; ?>" ВерсПрог="Инфо-Предприятие(версия 4.5.942)" ВерсФорм="5.06" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ПризнНал8-12="1" ПризнНал8="1" ПризнНал81="0" ПризнНал9="1" ПризнНал91="0" ПризнНал10="0" ПризнНал11="0" ПризнНал12="0">
	<Документ КНД="1151001" ДатаДок="<?= date('d.m.Y'); ?>" Период="<?=$handled_period['date'] ?>" ОтчетГод="<?=$handled_period['year'] ?>" КодНО="9102" НомКорр="0" ПоМесту="214">
		<СвНП ОКВЭД="<?=$fields->OKVED; ?>" Тлф="<?=str_replace('+','',$acomp_info->company_phone); ?>">
			<НПЮЛ НаимОрг="<?=htmlspecialchars($acomp_info->company_name); ?>" ИННЮЛ="<?=$acomp_info->company_tax_id; ?>" КПП="<?=$acomp_info->company_tax_id2; ?>" />
		</СвНП>
		<Подписант ПрПодп="1">
                <ФИО Фамилия="<?=explode(' ', strtoupper($acomp_info->company_director))[0]; ?>" Имя="<?=explode(' ', strtoupper($acomp_info->company_director))[1]; ?>" Отчество="<?=explode(' ', strtoupper($acomp_info->company_director))[2]; ?>" />
		</Подписант>
		<НДС>
			<СумУплНП ОКТМО="<?=$fields->OKTMO; ?>" КБК="<?=$fields->KBK; ?>" СумПУ_173.1="<?=ceil($data['difference']->sum_vat) ?>"/>
			<СумУпл164 НалПУ164="<?=ceil($data['difference']->sum_vat) ?>">
				<СумНалОб НалВосстОбщ="<?=ceil($data['sell']->sum_vat) ?>">
					<РеалТов20 НалБаза="<?=ceil($data['sell']->sum_vatless) ?>" СумНал="<?=ceil($data['sell']->sum_vat) ?>"/>
				</СумНалОб>
				<СумНалВыч НалПредНППриоб="<?=ceil($data['buy']->sum_vat) ?>" НалВычОбщ="<?=ceil($data['buy']->sum_vat) ?>"/>
			</СумУпл164>
			<КнигаПокуп НаимКнПок="<?php echo $this->generateUniqueFileName($acomp_info, 'buy', $period) ?>.XML"/>
			<КнигаПрод НаимКнПрод="<?php echo $this->generateUniqueFileName($acomp_info, 'sell', $period) ?>.XML"/>
		</НДС>
	</Документ>
</Файл>
