
<div>
    <div class="message-header">
        <div class="message-testimonials">
            <p>Генеральному директору</br><?php echo $passive_company;?></p>
            <p>от ООО "Нильсон Крым"</p>
        </div>
        <h2>Дорогой клиент компании НИЛЬСОН КРЫМ!</h2>
    </div>
    <div class="message-body">
        <div class="message-intro">
            <p>Мы искренне благодарны Вам за сотрудничество с нашей компанией и считаем необходимым напомнить о наступлении оплаты в рамках нижеуказанных периодов.</p>
        </div>
        <div class="message-table">
            <?php echo $table;?>
        </div>
    </div>
        
    <div class="message-outro">
        <p>С уважением, директор ООО "Нильсон Крым" Байчиков Р.Э.</p>
    </div>
    <div class="message-footer"></div>
</div>

<style>
    .message-testimonials{
        text-align: right;
    }
    .message-header h2,
    .message-outro{
        text-align: center;
    }
    .message-body,
    .message-table{
        margin: 0 auto;
        width: max-content;
    }
</style>