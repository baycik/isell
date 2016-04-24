<script>
    /* global App */
    StockJs={
	init:function(){
	    App.initTabs('stock_main_tabs');
	}
    };
</script>

<div id="stock_main_tabs" class="slim-tab">
    <div title="Остатки товара" href="page/stock/leftovers.html" style="min-height: 500px;min-width: 950px;"></div>
    <div title="Движения товара" href="page/stock/movements.html" style="min-height: 500px;min-width: 900px;"></div>
    <?php foreach( do_action('stock_add_tab') as $tab ):?>
	<div title="<?=$tab['title']?>" href="<?=$tab['href']?>" style="min-height: 500px;min-width: 900px;"></div>
    <?php endforeach; ?>
</div>
