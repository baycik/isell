<?php 
    //print_r($list);
    //die;
?>

<table class="debt-table-body" >
    <tr class="debt-header-row">
        <td class="">Дата</td>
        <td class="">Компания</td>
        <td class="">Документ</td>
        <td class="">Сумма документа</td>
        <td class="">Неоплаченная сумма</td>
        <td class="">Дата оплаты по договору</td>
    </tr>
    <?php foreach($list as $row){ ?> 
        <tr class="debt_empty-row" ><td colspan="6"></td></tr>
        <tr class="debt-row" >
            <td class="" colspan="6">
                <?php if($row['date']){ ?>
                <div class="date">
                    <span class="day">
                        <?php echo $row['date']['day']; ?>
                    </span>
                    <span class="month">
                        <?php if($row['date']['month']>0){ ?>
                            <?php echo ['', 'января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'][$row['date']['month']]; ?>
                        <?php } ?>

                        <?php if($row['date']['quarter']){ ?>
                            <?php echo $row['date']['quarter']; ?> Квартал
                        <?php } ?>
                    </span>
                    <span class="year">
                        <?php echo $row['date']['year']; ?>
                    </span>
                </div>
                <?php } else { ?>
                <div></div>
                <?php }  ?>
            </td>
        </tr>
        <?php foreach($row['list'] as $document){ ?>
        <tr class="debt-row">
            <td class=""><?php echo $document->doc_date;?></td>
            <td class=""><?php echo $document->company_name;?></td>
            <td class=""><?php echo $document->description;?></td>
            <td class=""><?php echo $document->amount_sell;?></td>
            <td class=""><?php echo $document->total_amount;?></td>
            <td class=""><?php echo $document->pay_date;?></td>
        </tr>
        <?php } ?>
        <tr class="debt-row">
            <td class="" colspan="2"></td>
            <td class="">Итого</td>
            <td class=""><?php echo $row['total']['sell'];?></td>
            <td class=""></td>
        </tr>
    <?php } ?>
</table>
<style>
    table{
        border-collapse: collapse;
    }
    td, tr, debt-table-body{
        border: 1px solid black;
        padding: 10px;
    }
    .debt-header-row td{
        font-weight: bold;
    }
</style>