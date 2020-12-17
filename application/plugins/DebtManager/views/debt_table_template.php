<?php 
    if( !function_exists('expired') ){
        function expired( $due_date_dmy ){
            $due = new DateTime($due_date_dmy);
            $now = new DateTime();
            $interval = $due->diff($now);
            if($due>$now){
                return '';
            }
            $text='';
            if( $interval->y ){
                $text.="{$interval->y}г ";
            }
            if( $interval->m ){
                $text.="{$interval->m}м ";
            }
            if( $interval->d ){
                $text.="{$interval->d}д";
            }
            return $text;
        }
    }
?>
<table class="debt-table-body" >
    <tr class="debt-header-row">
        <th style="width:50px">Период</th>
        <th style="width:300px">Документ задолженности</th>
        <th style="width:80px">Сумма</th>
        <th style="width:80px">Неоплачено</th>
        <th style="width:80px">Срок погашения</th>
        <th style="width:80px">Просрочка</th>
    </tr>
    <?php foreach($block_list['list'] as $row){ ?>
        <?php if($row['date']){ ?>
        <tr class="debt-row" >
            <td rowspan="<?php echo count($row['list'])+2?>" class="date <?php echo count($row['list'])>0?'':'empty' ?> <?php echo $row['date']=='expired'?'expired':'' ?>">
                <?php if($row['date']=='expired'){ ?>
                    
                <?php } else { ?>
                <span class="day">
                    <?php echo $row['date']['day']; ?>
                </span><br>
                <span class="month">
                    <?php if($row['date']['month']>0){ ?>
                        <?php echo ['', 'января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'][$row['date']['month']]; ?>
                    <?php } ?>

                    <?php if($row['date']['quarter']){ ?>
                        <?php echo $row['date']['quarter']; ?> Квартал
                    <?php } ?>
                </span><br>
                <span class="year">
                    <?php echo $row['date']['year']; ?>
                </span><br>
                <?php } ?>
            </td>
        </tr>
        <?php }  ?>
        <?php foreach($row['list'] as $document){ ?>
        <tr class="debt-row">
            <td style="text-align: left"><?php echo $document->description;?> от <?php echo $document->doc_date_dmy;?></td>
            <td><?php echo $document->total_amount;?></td>
            <td><?php echo $document->amount_sell;?></td>
            <td><?php echo $document->due_date_dmy;?></td>
            <td style="color:red"><?php echo expired($document->due_date_dmy);?></td>
        </tr>
        <?php } ?>
        <?php if( count($row['list'])>0 ){ ?>
        <tr class="debt-row">
            <td colspan="2">Итого</td>
            <td><?php echo $row['total']['sell'];?></td>
            <td></td>
            <td></td>
        </tr>
        <?php } else { ?>
        <tr>
            <td colspan="5"></td>
        </tr>
        <?php } ?>
    <?php } ?>
        <tr class="debt-row">
            <td colspan="3">Общая неоплаченная задолженность</td>
            <td><b><?php echo $block_list['grand_total_sell'];?></b></td>
            <td></td>
            <td></td>
        </tr>
</table>
<style>
    .debt-table-body{
        border-collapse: collapse;
        font-family:Calibri,Arial;
        border: 1px solid #999;
    }
    .debt-table-body tr{
        border: 1px solid #ccc;
    }
    .debt-table-body td{
        padding: 5px;
        text-align: right
    }
    .debt-table-body th{
        background-color: #ccc;
        padding: 10px;
    }
    .debt-table-body .date{
        text-align: left;
    }
    .debt-table-body .date{
        border-right: 4px solid #036;
        background-color: #37c;
        padding: 5px;
        font-size: 20px;
        color: white;
        font-weight: bold;
        font-family: Arial Black, Arial Bold, Gadget, sans-serif;
    }
    .debt-table-body .empty{
        background: #ccc;
        font-size: 14px;
        border-right: 4px solid #777;
    }
    .debt-table-body .expired{
        background: #fdd;
        font-size: 16px;
        border-right: 4px solid #f00;
    }
    .debt-table-body .date .year{
        font-weight: normal;
        font-family:Arial;
    }
</style>