<style type="text/css">
	body, html, td{
		font-family:Arial;
		font-size:10px;
	}
	.s1{
		font-size:16px;
		font-weight:bold;
	}
	.s2{
		font-size:13px;
		font-weight:bold;
	}
	.s3{
		font-size:15px;
	}
	.inn{
		width:15px;
	}
	.table_border{
		border-collapse: collapse;
	}
	.table_border td {
		border-top:#000 1px solid;
		border-right:#000 1px solid;
		border-left:#000 1px solid;
		border-bottom:#000 1px solid;
		padding:1px;
	}

	.noborder{
		border:none;
	}
	.tiny{
		font-size:8px;
	}
	.small{
		font-size:9px;
	}
	.medium{
		font-size:11px;
	}
	.big{
		font-size:14px;
	}
	.cell{
		width:16px;
		height:21px;
		text-align:center;
		font-size:14px;
		font-family:"Courier New", Courier, monospace;
	}
	
	.page{
		page-break-after: always;
		//page-break-before: always;
	}

</style>
<table width="718" border="0" cellpadding="0" cellspacing="0" class="page">
  <tr>
    <td align="center"><table width="700" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="350">
        <table border="0" cellpadding="0" cellspacing="0" class="table_border">
          <tr>
            <td rowspan="4">Оригінал</td>
            <td>Видається покупцю</td>
            <td height="10" colspan="2" align="center">X</td>
            </tr>
          <tr>
            <td>Включено до ЄРПН</td>
            <td height="10" colspan="2" class="tiny">&nbsp;</td>
            </tr>
          <tr>
            <td rowspan="2">Залишається у продавця <br />(тип причини)</td>
            <td height="10" colspan="2" class="tiny">&nbsp;</td>
            </tr>
          <tr>
            <td width="14" height="10" class="tiny">&nbsp;</td>
            <td width="14" height="10" class="tiny">&nbsp;</td>
            </tr>
          <tr>
            <td colspan="2">Копія (залишається у продавця)</td>
            <td height="10" colspan="2" class="tiny">&nbsp;</td>
          </tr>
        </table>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Потрібне виділити поміткою "Х")
        </td>
        <td width="350" align="right" valign="top">
        <table>
          <tr>
            <td align="left" valign="middle" class="medium">ЗАТВЕРДЖЕНО<br />
              Наказ Державної податкової адміністрації  України <br />
              21.12.2010 №  969</td>
          </tr>
        </table>
</td>
      </tr>
      <tr>
        <td height="30" colspan="2" align="center" valign="top" class="s1">ПОДАТКОВА НАКЛАДНА</td>
        </tr>
      <tr>
        <td valign="top"><table width="340" cellpadding="0" cellspacing="0">
          <tr>
            <td class="medium">Дата виписки податкової накладної</td>
            <td>
            <table class="table_border"><tr><td class="cell">{$v.date.0}</td><td class="cell">{$v.date.1}</td><td class="cell">{$v.date.2}</td><td class="cell">{$v.date.3}</td><td class="cell">{$v.date.4}</td><td class="cell">{$v.date.5}</td><td class="cell">{$v.date.6}</td><td class="cell">{$v.date.7}</td></tr></table>
            </td>
          </tr>
        </table></td>
        <td align="right" valign="top"><table width="340" cellpadding="0" cellspacing="0">
          <tr>
            <td class="medium">Порядковий номер</td>
            <td rowspan="2" align="right">
            
            <table border="0" cellpadding="0" cellspacing="0"><tr>
            <td><table class="table_border"><tr><td class="cell">{$v.view_num_fill.0}</td><td class="cell">{$v.view_num_fill.1}</td><td class="cell">{$v.view_num_fill.2}</td><td class="cell">{$v.view_num_fill.3}</td><td class="cell">{$v.view_num_fill.4}</td><td class="cell">{$v.view_num_fill.5}</td><td class="cell">{$v.view_num_fill.6}</td></tr></table>
            </td><td class="cell">/</td><td>
            <table class="table_border"><tr><td class="cell">&nbsp;</td><td class="cell">&nbsp;</td><td class="cell">&nbsp;</td><td class="cell">&nbsp;</td></tr></table>
            </td></tr>
              <tr style="margin:0px;padding:0px">
                <td>&nbsp;</td>
                <td class="cell">&nbsp;</td>
                <td align="center">(номер філії)</td>
              </tr>
            
            
            </table>
            
            </td>
          </tr>
          <tr>
            <td class="medium">&nbsp;</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table width="350" cellpadding="0" cellspacing="0">
          <tr>
            <td width="106">&nbsp;</td>
            <td width="246" align="center"><b>Продавець</b></td>
            </tr>
          <tr>
            <td>Особа (платник<br />податку) - продавець</td>
            <td height="45" align="center" class="medium" style="border:1px #000 solid">{$v.a.company_name} &nbsp;</td>
            </tr>
          <tr>
            <td>&nbsp;</td>
            <td align="center" class="tiny">(найменування; прізвище, ім'я, по батькові - для фізичної особи - підприємця)</td>
            </tr>
          </table></td>
        <td align="right"><table width="350" cellpadding="0" cellspacing="0">
          <tr>
            <td width="106">&nbsp;</td>
            <td width="246" align="center"><b>Покупець</b></td>
            </tr>
          <tr>
            <td>Особа (платник<br />податку) - покупець</td>
            <td height="45" align="center" class="medium" style="border:1px #000 solid">{$v.p.company_name} &nbsp;</td>
            </tr>
          <tr>
            <td>&nbsp;</td>
            <td align="center" class="tiny">(найменування; прізвище, ім'я, по батькові - для фізичної особи - підприємця)</td>
            </tr>
          </table></td>
      </tr>
      <tr>
        <td><table width="345" cellpadding="0" cellspacing="0">
          <tr>
            <td>&nbsp;</td>
            <td width="222" align="center">
            <table class="table_border" cellpadding="0" cellspacing="0" width="210">
              <tr>
                <td align="center" class="cell">{$v.a.cvi.0}</td>
                <td align="center" class="cell">{$v.a.cvi.1}</td>
                <td align="center" class="cell">{$v.a.cvi.2}</td>
                <td align="center" class="cell">{$v.a.cvi.3}</td>
                <td align="center" class="cell">{$v.a.cvi.4}</td>
                <td align="center" class="cell">{$v.a.cvi.5}</td>
                <td align="center" class="cell">{$v.a.cvi.6}</td>
                <td align="center" class="cell">{$v.a.cvi.7}</td>
                <td align="center" class="cell">{$v.a.cvi.8}</td>
                <td align="center" class="cell">{$v.a.cvi.9}</td>
                <td align="center" class="cell">{$v.a.cvi.10}</td>
                <td align="center" class="cell">{$v.a.cvi.11}</td>
              </tr>
            </table>
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td align="center" class="tiny">(індивідуалний податковий номер продавця)</td>
          </tr>
        </table></td>
        <td><table width="345" cellpadding="0" cellspacing="0">
          <tr>
            <td>&nbsp;</td>
            <td width="222" align="center">
            <table class="table_border" cellpadding="0" cellspacing="0" width="210">
              <tr>
                <td align="center" class="cell">{$v.p.cvi.0}</td>
                <td align="center" class="cell">{$v.p.cvi.1}</td>
                <td align="center" class="cell">{$v.p.cvi.2}</td>
                <td align="center" class="cell">{$v.p.cvi.3}</td>
                <td align="center" class="cell">{$v.p.cvi.4}</td>
                <td align="center" class="cell">{$v.p.cvi.5}</td>
                <td align="center" class="cell">{$v.p.cvi.6}</td>
                <td align="center" class="cell">{$v.p.cvi.7}</td>
                <td align="center" class="cell">{$v.p.cvi.8}</td>
                <td align="center" class="cell">{$v.p.cvi.9}</td>
                <td align="center" class="cell">{$v.p.cvi.10}</td>
                <td align="center" class="cell">{$v.p.cvi.11}</td>
              </tr>
            </table>
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td align="center" class="tiny">(індивідуалний податковий номер покупця)</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="40"><table width="340" border="0" cellspacing="0">
          <tr>
            <td width="101">Місцезнаходження (податкова адреса) продавця</td>
            <td valign="bottom" class="medium" style="border-bottom:1px #000 solid">{$v.a.company_jaddress} &nbsp;</td>
          </tr>
        </table></td>
        <td align="right"><table width="340" border="0" cellspacing="0">
          <tr>
            <td width="101">Місцезнаходження (податкова адреса) покупця</td>
            <td valign="bottom" class="medium" style="border-bottom:1px #000 solid">{$v.p.company_jaddress} &nbsp;</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="40"><table width="345" border="0" cellspacing="0">
          <tr>
            <td width="90">Номер телефону</td>
            <td align="right">
            <table class="table_border"><tr>
            <td class="cell">{$v.a.company_phone.0}</td>
            <td class="cell">{$v.a.company_phone.1}</td>
            <td class="cell">{$v.a.company_phone.2}</td>
            <td class="cell">{$v.a.company_phone.3}</td>
            <td class="cell">{$v.a.company_phone.4}</td>
            <td class="cell">{$v.a.company_phone.5}</td>
            <td class="cell">{$v.a.company_phone.6}</td>
            <td class="cell">{$v.a.company_phone.7}</td>
            <td class="cell">{$v.a.company_phone.8}</td>
            <td class="cell">{$v.a.company_phone.9}</td>
            </tr></table>
            </td>
          </tr>
        </table></td>
        <td align="right"><table width="345" border="0" cellspacing="0">
          <tr>
            <td width="90">Номер телефону</td>
            <td align="right">
            
            <table class="table_border"><tr>
            <td class="cell">{$v.p.company_phone.0}</td>
            <td class="cell">{$v.p.company_phone.1}</td>
            <td class="cell">{$v.p.company_phone.2}</td>
            <td class="cell">{$v.p.company_phone.3}</td>
            <td class="cell">{$v.p.company_phone.4}</td>
            <td class="cell">{$v.p.company_phone.5}</td>
            <td class="cell">{$v.p.company_phone.6}</td>
            <td class="cell">{$v.p.company_phone.7}</td>
            <td class="cell">{$v.p.company_phone.8}</td>
            <td class="cell">{$v.p.company_phone.9}</td>
            </tr></table>
            </td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="50"><table width="345" border="0" cellspacing="0">
          <tr>
            <td width="162" height="70">Номер свідоцтва про реєстрацію платника податку на додану вартість (продавця)</td>
            <td align="center">
            
            <table class="table_border"><tr>
            <td class="cell">{$v.a.cvli.0}</td>
            <td class="cell">{$v.a.cvli.1}</td>
            <td class="cell">{$v.a.cvli.2}</td>
            <td class="cell">{$v.a.cvli.3}</td>
            <td class="cell">{$v.a.cvli.4}</td>
            <td class="cell">{$v.a.cvli.5}</td>
            <td class="cell">{$v.a.cvli.6}</td>
            <td class="cell">{$v.a.cvli.7}</td>
            <td class="cell">{$v.a.cvli.8}</td>
            <td class="cell">{$v.a.cvli.9}</td>
            </tr></table>
            
            </td>
          </tr>
        </table></td>
        <td align="right"><table width="340" border="0" cellspacing="0">
          <tr>
            <td width="162">Номер свідоцтва про реєстрацію платника податку на додану вартість (покупця)</td>
            <td align="center">

            <table class="table_border"><tr>
            <td class="cell">{$v.p.cvli.0}</td>
            <td class="cell">{$v.p.cvli.1}</td>
            <td class="cell">{$v.p.cvli.2}</td>
            <td class="cell">{$v.p.cvli.3}</td>
            <td class="cell">{$v.p.cvli.4}</td>
            <td class="cell">{$v.p.cvli.5}</td>
            <td class="cell">{$v.p.cvli.6}</td>
            <td class="cell">{$v.p.cvli.7}</td>
            <td class="cell">{$v.p.cvli.8}</td>
            <td class="cell">{$v.p.cvli.9}</td>
            </tr></table>

            
            </td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="40" colspan="2">
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="170">Вид цивільно-правового договору</td>
            <td width="200" style="border-bottom:#000 1px solid" class="medium" valign="bottom">Договір поставки</td>
            <td>
            
            <table class="table_border"><tr>
            <td style="border:none;">від</td>
            <td class="cell">{$v.p.ag_date.0}</td>
            <td class="cell">{$v.p.ag_date.1}</td>
            <td class="cell">{$v.p.ag_date.2}</td>
            <td class="cell">{$v.p.ag_date.3}</td>
            <td class="cell">{$v.p.ag_date.4}</td>
            <td class="cell">{$v.p.ag_date.5}</td>
            <td class="cell">{$v.p.ag_date.6}</td>
            <td class="cell">{$v.p.ag_date.7}</td>
            <td style="border:none">№</td>
            <td class="cell">{$v.p.ag_no.0}</td>
            <td class="cell">{$v.p.ag_no.1}</td>
            <td class="cell">{$v.p.ag_no.2}</td>
            <td class="cell">{$v.p.ag_no.3}</td>
            <td class="cell">{$v.p.ag_no.4}</td>
            <td class="cell">{$v.p.ag_no.5}</td>
            <td class="cell">{$v.p.ag_no.6}</td>
            <td class="cell">{$v.p.ag_no.7}</td>
            <td class="cell">{$v.p.ag_no.8}</td> 
            </tr></table>
            
            
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td align="center" class="tiny">(вид договору)</td>
            <td align="center" class="tiny">&nbsp;</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="35" colspan="2"><table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="160">Форма проведених розрахунків</td>
            <td class="medium" style="border-bottom:#000 1px solid">оплата з поточного рахунку</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td align="center" class="tiny">(бартер, готівка, оплата з поточного рахунку, чек тощо)</td>
          </tr>
        </table></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td align="center">
    <table width="700" border="0" cellpadding="1" cellspacing="0" class="table_border">
      <tr>
        <td rowspan="2" align="center">Роз- діл</td>
        <td rowspan="2" align="center">Дата відвантаження (виконання, постачання (оплати*) товарів/ послуг</td>
        <td width="210" rowspan="2" align="center">Номенклатура постачання товарів/ послуг продавця</td>
        <td rowspan="2" align="center">Оди- ниця виміру товару</td>
        <td rowspan="2" align="center">Кількість (об'єм, обсяг)</td>
        <td rowspan="2" align="center">Ціна постачання одиниці товару/ послуги без урахування ПДВ</td>
        <td colspan="4" align="center">Обсяги постачання (база оподаткування) без урахування ПДВ, що підлягають оподаткуванню за ставками</td>
        <td rowspan="2" align="center">Загальна сума коштів, що підлягає сплаті</td>
      </tr>
      <tr>
        <td align="center" width="40">20%</td>
        <td align="center">0 % (постачання на митній території України)</td>
        <td align="center">0 % (експорт)</td>
        <td align="center">звільнення від ПДВ**</td>
      </tr>
      <tr>
        <td align="center">1</td>
        <td align="center">2</td>
        <td align="center">3</td>
        <td align="center">4</td>
        <td align="center">5</td>
        <td align="center">6</td>
        <td align="center">7</td>
        <td align="center">8</td>
        <td align="center">9</td>
        <td align="center">10</td>
        <td align="center">11</td>
      </tr>
      <tr>
        <td rowspan="{$v.entries_num+2}" align="center" valign="top">I</td>
      </tr>
      {loop name="v.entries"}
      <tr>
        <td align="center">{$v.date_dot}</td>
        <td align="left">{$value.2}</td>
        <td align="center">{$value.4}</td>
        <td align="center">{$value.3}</td>
        <td align="right">{$value.vatless_price}</td>
        <td align="right">{$value.vatless_sum}</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        {if condition="$counter==0"}
        <td align="right" valign="bottom" rowspan="{$v.entries_num+1}">{$v.footer.total_vatless}</td>
        {/if}
      </tr>
      {/loop}
      <tr>
        <td colspan="2" align="center"><b>Всього по розділу І</b></td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="right">{$v.footer.total_vatless}</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        </tr>
      <tr>
        <td align="center">II</td>
        <td colspan="2" align="center">Зворотна (заставна) тара</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">&nbsp;</td>
      </tr>
      <tr>
        <td align="center">III</td>
        <td colspan="2" align="center">Податок на додану вартість</td>
        <td align="center">X</td>
        <td align="center">X</td>
        <td align="center">X</td>
        <td align="right">{$v.footer.total_vat}</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="right">{$v.footer.total_vat}</td>
      </tr>
      <tr>
        <td align="center">IV</td>
        <td colspan="2" align="center">Загальна сума з ПДВ</td>
        <td align="center">X</td>
        <td align="center">X</td>
        <td align="center">X</td>
        <td align="right">{$v.footer.total_price}</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="right">{$v.footer.total_price}</td>
        </tr>
    </table></td>
  </tr>
  <tr>
    <td align="center">&nbsp;</td>
  </tr>
  <tr>
    <td align="center"><table width="700" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td height="40" colspan="2" valign="top" class="small">Суми ПДВ, нараховані (сплачені) в зв'язку з постачанням товарів/послуг, зазначених у цій накладній, визначені правильно, відповідають сумі податкових зобов'язань продавця і включені до реєстру виданих та отриманих податкових накладних.</td>
        </tr>
      <tr>
        <td width="420" class="small">&nbsp;</td>
        <td width="280" align="right" valign="bottom">&nbsp;</td>
      </tr>
      <tr>
        <td height="80"><table border="0">
            <tr>
            <td width="50">&nbsp;</td>
            <td width="50" height="50" align="center" valign="middle" style="border:1px solid #000">М.П.</td>
            </tr>
        </table></td>
        <td><table width="270" border="0" align="right" cellspacing="0">
          <tr>
            <td align="center" style="border-bottom:#000 solid 1px">{$v.user_sign}</td>
          </tr>
          <tr>
            <td align="center" class="tiny">(підпис і прізвище особи, яка склала податкову накладну)</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="2" class="small"><em>______________________<br />
          * Дата оплати ставиться у разі попередньої оплати постачання, на яку виписується податкова накладна, для операцій з постачання товарів/послуг відповідно до пункту 187.10 статті 187 розділу V Податкового кодексу України.<br />
** ___________________________________________________________________________________ <br />
(відповідні пункти (підпункти), статті, підрозділи, розділи Податкового кодексу України, якими передбачено звільнення від оподаткування)</em></td>
        </tr>
    </table></td>
  </tr>
</table> 





<!--------------------------------------------------------------------------------------------->

<br />

<table width="718" border="0" cellpadding="0" cellspacing="0" class="page">
  <tr>
    <td align="center"><table width="700" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="350">
        <table border="0" cellpadding="0" cellspacing="0" class="table_border">
          <tr>
            <td rowspan="4">Оригінал</td>
            <td>Видається покупцю</td>
            <td height="10" colspan="2" align="center">&nbsp;</td>
            </tr>
          <tr>
            <td>Включено до ЄРПН</td>
            <td height="10" colspan="2" class="tiny">&nbsp;</td>
            </tr>
          <tr>
            <td rowspan="2">Залишається у продавця <br />(тип причини)</td>
            <td height="10" colspan="2" class="tiny">&nbsp;</td>
            </tr>
          <tr>
            <td width="14" height="10" class="tiny">&nbsp;</td>
            <td width="14" height="10" class="tiny">&nbsp;</td>
            </tr>
          <tr>
            <td colspan="2">Копія (залишається у продавця)</td>
            <td height="10" colspan="2" align="center" class="table_border">X</td>
          </tr>
        </table>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Потрібне виділити поміткою "Х")
        </td>
        <td width="350" align="right" valign="top">
        <table>
          <tr>
            <td align="left" valign="middle" class="medium">ЗАТВЕРДЖЕНО<br />
              Наказ Державної податкової адміністрації  України <br />
              21.12.2010 №  969</td>
          </tr>
        </table>
</td>
      </tr>
      <tr>
        <td height="30" colspan="2" align="center" valign="top" class="s1">ПОДАТКОВА НАКЛАДНА</td>
        </tr>
      <tr>
        <td valign="top"><table width="340" cellpadding="0" cellspacing="0">
          <tr>
            <td class="medium">Дата виписки податкової накладної</td>
            <td>
            <table class="table_border"><tr><td class="cell">{$v.date.0}</td><td class="cell">{$v.date.1}</td><td class="cell">{$v.date.2}</td><td class="cell">{$v.date.3}</td><td class="cell">{$v.date.4}</td><td class="cell">{$v.date.5}</td><td class="cell">{$v.date.6}</td><td class="cell">{$v.date.7}</td></tr></table>
            </td>
          </tr>
        </table></td>
        <td align="right" valign="top"><table width="340" cellpadding="0" cellspacing="0">
          <tr>
            <td class="medium">Порядковий номер</td>
            <td rowspan="2" align="right">
            
            <table border="0" cellpadding="0" cellspacing="0"><tr>
            <td><table class="table_border"><tr><td class="cell">{$v.view_num_fill.0}</td><td class="cell">{$v.view_num_fill.1}</td><td class="cell">{$v.view_num_fill.2}</td><td class="cell">{$v.view_num_fill.3}</td><td class="cell">{$v.view_num_fill.4}</td><td class="cell">{$v.view_num_fill.5}</td><td class="cell">{$v.view_num_fill.6}</td></tr></table>
            </td><td class="cell">/</td><td>
            <table class="table_border"><tr><td class="cell">&nbsp;</td><td class="cell">&nbsp;</td><td class="cell">&nbsp;</td><td class="cell">&nbsp;</td></tr></table>
            </td></tr>
              <tr style="margin:0px;padding:0px">
                <td>&nbsp;</td>
                <td class="cell">&nbsp;</td>
                <td align="center">(номер філії)</td>
              </tr>
            
            
            </table>
            
            </td>
          </tr>
          <tr>
            <td class="medium">&nbsp;</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table width="350" cellpadding="0" cellspacing="0">
          <tr>
            <td width="106">&nbsp;</td>
            <td width="246" align="center"><b>Продавець</b></td>
            </tr>
          <tr>
            <td>Особа (платник<br />податку) - продавець</td>
            <td height="45" align="center" class="medium" style="border:1px #000 solid">{$v.a.company_name} &nbsp;</td>
            </tr>
          <tr>
            <td>&nbsp;</td>
            <td align="center" class="tiny">(найменування; прізвище, ім'я, по батькові - для фізичної особи - підприємця)</td>
            </tr>
          </table></td>
        <td align="right"><table width="350" cellpadding="0" cellspacing="0">
          <tr>
            <td width="106">&nbsp;</td>
            <td width="246" align="center"><b>Покупець</b></td>
            </tr>
          <tr>
            <td>Особа (платник<br />податку) - покупець</td>
            <td height="45" align="center" class="medium" style="border:1px #000 solid">{$v.p.company_name} &nbsp;</td>
            </tr>
          <tr>
            <td>&nbsp;</td>
            <td align="center" class="tiny">(найменування; прізвище, ім'я, по батькові - для фізичної особи - підприємця)</td>
            </tr>
          </table></td>
      </tr>
      <tr>
        <td><table width="345" cellpadding="0" cellspacing="0">
          <tr>
            <td>&nbsp;</td>
            <td width="222" align="center">
            <table class="table_border" cellpadding="0" cellspacing="0" width="210">
              <tr>
                <td align="center" class="cell">{$v.a.cvi.0}</td>
                <td align="center" class="cell">{$v.a.cvi.1}</td>
                <td align="center" class="cell">{$v.a.cvi.2}</td>
                <td align="center" class="cell">{$v.a.cvi.3}</td>
                <td align="center" class="cell">{$v.a.cvi.4}</td>
                <td align="center" class="cell">{$v.a.cvi.5}</td>
                <td align="center" class="cell">{$v.a.cvi.6}</td>
                <td align="center" class="cell">{$v.a.cvi.7}</td>
                <td align="center" class="cell">{$v.a.cvi.8}</td>
                <td align="center" class="cell">{$v.a.cvi.9}</td>
                <td align="center" class="cell">{$v.a.cvi.10}</td>
                <td align="center" class="cell">{$v.a.cvi.11}</td>
              </tr>
            </table>
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td align="center" class="tiny">(індивідуалний податковий номер продавця)</td>
          </tr>
        </table></td>
        <td><table width="345" cellpadding="0" cellspacing="0">
          <tr>
            <td>&nbsp;</td>
            <td width="222" align="center">
            <table class="table_border" cellpadding="0" cellspacing="0" width="210">
              <tr>
                <td align="center" class="cell">{$v.p.cvi.0}</td>
                <td align="center" class="cell">{$v.p.cvi.1}</td>
                <td align="center" class="cell">{$v.p.cvi.2}</td>
                <td align="center" class="cell">{$v.p.cvi.3}</td>
                <td align="center" class="cell">{$v.p.cvi.4}</td>
                <td align="center" class="cell">{$v.p.cvi.5}</td>
                <td align="center" class="cell">{$v.p.cvi.6}</td>
                <td align="center" class="cell">{$v.p.cvi.7}</td>
                <td align="center" class="cell">{$v.p.cvi.8}</td>
                <td align="center" class="cell">{$v.p.cvi.9}</td>
                <td align="center" class="cell">{$v.p.cvi.10}</td>
                <td align="center" class="cell">{$v.p.cvi.11}</td>
              </tr>
            </table>
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td align="center" class="tiny">(індивідуалний податковий номер покупця)</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="40"><table width="340" border="0" cellspacing="0">
          <tr>
            <td width="101">Місцезнаходження (податкова адреса) продавця</td>
            <td valign="bottom" class="medium" style="border-bottom:1px #000 solid">{$v.a.company_jaddress} &nbsp;</td>
          </tr>
        </table></td>
        <td align="right"><table width="340" border="0" cellspacing="0">
          <tr>
            <td width="101">Місцезнаходження (податкова адреса) покупця</td>
            <td valign="bottom" class="medium" style="border-bottom:1px #000 solid">{$v.p.company_jaddress} &nbsp;</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="40"><table width="345" border="0" cellspacing="0">
          <tr>
            <td width="90">Номер телефону</td>
            <td align="right">
            <table class="table_border"><tr>
            <td class="cell">{$v.a.company_phone.0}</td>
            <td class="cell">{$v.a.company_phone.1}</td>
            <td class="cell">{$v.a.company_phone.2}</td>
            <td class="cell">{$v.a.company_phone.3}</td>
            <td class="cell">{$v.a.company_phone.4}</td>
            <td class="cell">{$v.a.company_phone.5}</td>
            <td class="cell">{$v.a.company_phone.6}</td>
            <td class="cell">{$v.a.company_phone.7}</td>
            <td class="cell">{$v.a.company_phone.8}</td>
            <td class="cell">{$v.a.company_phone.9}</td>
            </tr></table>
            </td>
          </tr>
        </table></td>
        <td align="right"><table width="345" border="0" cellspacing="0">
          <tr>
            <td width="90">Номер телефону</td>
            <td align="right">
            
            <table class="table_border"><tr>
            <td class="cell">{$v.p.company_phone.0}</td>
            <td class="cell">{$v.p.company_phone.1}</td>
            <td class="cell">{$v.p.company_phone.2}</td>
            <td class="cell">{$v.p.company_phone.3}</td>
            <td class="cell">{$v.p.company_phone.4}</td>
            <td class="cell">{$v.p.company_phone.5}</td>
            <td class="cell">{$v.p.company_phone.6}</td>
            <td class="cell">{$v.p.company_phone.7}</td>
            <td class="cell">{$v.p.company_phone.8}</td>
            <td class="cell">{$v.p.company_phone.9}</td>
            </tr></table>
            </td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="50"><table width="345" border="0" cellspacing="0">
          <tr>
            <td width="162" height="70">Номер свідоцтва про реєстрацію платника податку на додану вартість (продавця)</td>
            <td align="center">
            
            <table class="table_border"><tr>
            <td class="cell">{$v.a.cvli.0}</td>
            <td class="cell">{$v.a.cvli.1}</td>
            <td class="cell">{$v.a.cvli.2}</td>
            <td class="cell">{$v.a.cvli.3}</td>
            <td class="cell">{$v.a.cvli.4}</td>
            <td class="cell">{$v.a.cvli.5}</td>
            <td class="cell">{$v.a.cvli.6}</td>
            <td class="cell">{$v.a.cvli.7}</td>
            <td class="cell">{$v.a.cvli.8}</td>
            <td class="cell">{$v.a.cvli.9}</td>
            </tr></table>
            
            </td>
          </tr>
        </table></td>
        <td align="right"><table width="340" border="0" cellspacing="0">
          <tr>
            <td width="162">Номер свідоцтва про реєстрацію платника податку на додану вартість (покупця)</td>
            <td align="center">

            <table class="table_border"><tr>
            <td class="cell">{$v.p.cvli.0}</td>
            <td class="cell">{$v.p.cvli.1}</td>
            <td class="cell">{$v.p.cvli.2}</td>
            <td class="cell">{$v.p.cvli.3}</td>
            <td class="cell">{$v.p.cvli.4}</td>
            <td class="cell">{$v.p.cvli.5}</td>
            <td class="cell">{$v.p.cvli.6}</td>
            <td class="cell">{$v.p.cvli.7}</td>
            <td class="cell">{$v.p.cvli.8}</td>
            <td class="cell">{$v.p.cvli.9}</td>
            </tr></table>

            
            </td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="40" colspan="2">
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="170">Вид цивільно-правового договору</td>
            <td width="200" style="border-bottom:#000 1px solid" class="medium" valign="bottom">Договір поставки</td>
            <td>
            
            <table class="table_border"><tr>
            <td style="border:none;">від</td>
            <td class="cell">{$v.p.ag_date.0}</td>
            <td class="cell">{$v.p.ag_date.1}</td>
            <td class="cell">{$v.p.ag_date.2}</td>
            <td class="cell">{$v.p.ag_date.3}</td>
            <td class="cell">{$v.p.ag_date.4}</td>
            <td class="cell">{$v.p.ag_date.5}</td>
            <td class="cell">{$v.p.ag_date.6}</td>
            <td class="cell">{$v.p.ag_date.7}</td>
            <td style="border:none">№</td>
            <td class="cell">{$v.p.ag_no.0}</td>
            <td class="cell">{$v.p.ag_no.1}</td>
            <td class="cell">{$v.p.ag_no.2}</td>
            <td class="cell">{$v.p.ag_no.3}</td>
            <td class="cell">{$v.p.ag_no.4}</td>
            <td class="cell">{$v.p.ag_no.5}</td>
            <td class="cell">{$v.p.ag_no.6}</td>
            <td class="cell">{$v.p.ag_no.7}</td>
            <td class="cell">{$v.p.ag_no.8}</td> 
            </tr></table>
            
            
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td align="center" class="tiny">(вид договору)</td>
            <td align="center" class="tiny">&nbsp;</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="35" colspan="2"><table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="160">Форма проведених розрахунків</td>
            <td class="medium" style="border-bottom:#000 1px solid">оплата з поточного рахунку</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td align="center" class="tiny">(бартер, готівка, оплата з поточного рахунку, чек тощо)</td>
          </tr>
        </table></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td align="center">
    <table width="700" border="0" cellpadding="1" cellspacing="0" class="table_border">
      <tr>
        <td rowspan="2" align="center">Роз- діл</td>
        <td rowspan="2" align="center">Дата відвантаження (виконання, постачання (оплати*) товарів/ послуг</td>
        <td width="210" rowspan="2" align="center">Номенклатура постачання товарів/ послуг продавця</td>
        <td rowspan="2" align="center">Оди- ниця виміру товару</td>
        <td rowspan="2" align="center">Кількість (об'єм, обсяг)</td>
        <td rowspan="2" align="center">Ціна постачання одиниці товару/ послуги без урахування ПДВ</td>
        <td colspan="4" align="center">Обсяги постачання (база оподаткування) без урахування ПДВ, що підлягають оподаткуванню за ставками</td>
        <td rowspan="2" align="center">Загальна сума коштів, що підлягає сплаті</td>
      </tr>
      <tr>
        <td align="center" width="40">20%</td>
        <td align="center">0 % (постачання на митній території України)</td>
        <td align="center">0 % (експорт)</td>
        <td align="center">звільнення від ПДВ**</td>
      </tr>
      <tr>
        <td align="center">1</td>
        <td align="center">2</td>
        <td align="center">3</td>
        <td align="center">4</td>
        <td align="center">5</td>
        <td align="center">6</td>
        <td align="center">7</td>
        <td align="center">8</td>
        <td align="center">9</td>
        <td align="center">10</td>
        <td align="center">11</td>
      </tr>
      <tr>
        <td rowspan="{$v.entries_num+2}" align="center" valign="top">I</td>
      </tr>
      {loop name="v.entries"}
      <tr>
        <td align="center">{$v.date_dot}</td>
        <td align="left">{$value.2}</td>
        <td align="center">{$value.4}</td>
        <td align="center">{$value.3}</td>
        <td align="right">{$value.vatless_price}</td>
        <td align="right">{$value.vatless_sum}</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        {if condition="$counter==0"}
        <td align="right" valign="bottom" rowspan="{$v.entries_num+1}">{$v.footer.total_vatless}</td>
        {/if}
      </tr>
      {/loop}
      <tr>
        <td colspan="2" align="center"><b>Всього по розділу І</b></td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="right">{$v.footer.total_vatless}</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        </tr>
      <tr>
        <td align="center">II</td>
        <td colspan="2" align="center">Зворотна (заставна) тара</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">Х</td>
        <td align="center">&nbsp;</td>
      </tr>
      <tr>
        <td align="center">III</td>
        <td colspan="2" align="center">Податок на додану вартість</td>
        <td align="center">X</td>
        <td align="center">X</td>
        <td align="center">X</td>
        <td align="right">{$v.footer.total_vat}</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="right">{$v.footer.total_vat}</td>
      </tr>
      <tr>
        <td align="center">IV</td>
        <td colspan="2" align="center">Загальна сума з ПДВ</td>
        <td align="center">X</td>
        <td align="center">X</td>
        <td align="center">X</td>
        <td align="right">{$v.footer.total_price}</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="right">{$v.footer.total_price}</td>
        </tr>
    </table></td>
  </tr>
  <tr>
    <td align="center">&nbsp;</td>
  </tr>
  <tr>
    <td align="center"><table width="700" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td height="40" colspan="2" valign="top" class="small">Суми ПДВ, нараховані (сплачені) в зв'язку з постачанням товарів/послуг, зазначених у цій накладній, визначені правильно, відповідають сумі податкових зобов'язань продавця і включені до реєстру виданих та отриманих податкових накладних.</td>
        </tr>
      <tr>
        <td width="420" class="small">&nbsp;</td>
        <td width="280" align="right" valign="bottom">&nbsp;</td>
      </tr>
      <tr>
        <td height="80"><table border="0">
            <tr>
            <td width="50">&nbsp;</td>
            <td width="50" height="50" align="center" valign="middle" style="border:1px solid #000">М.П.</td>
            </tr>
        </table></td>
        <td><table width="270" border="0" align="right" cellspacing="0">
          <tr>
            <td align="center" style="border-bottom:#000 solid 1px">{$v.user_sign}.</td>
          </tr>
          <tr>
            <td align="center" class="tiny">(підпис і прізвище особи, яка склала податкову накладну)</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td colspan="2" class="small"><em>______________________<br />
          * Дата оплати ставиться у разі попередньої оплати постачання, на яку виписується податкова накладна, для операцій з постачання товарів/послуг відповідно до пункту 187.10 статті 187 розділу V Податкового кодексу України.<br />
** ___________________________________________________________________________________ <br />
(відповідні пункти (підпункти), статті, підрозділи, розділи Податкового кодексу України, якими передбачено звільнення від оподаткування)</em></td>
        </tr>
    </table></td>
  </tr>
</table>