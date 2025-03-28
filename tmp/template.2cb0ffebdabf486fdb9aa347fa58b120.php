<?php if(!class_exists('raintpl')){exit;}?><style>
    .product_code{
	width:90px;
	color:#999;
	padding: 3px;
    }
    .product_name{
        padding: 3px;
    }
    .product_price{
	width:60px;
	text-align: right;
    }
    .product_imgs{
	text-align: center;
	width:140px;
	vertical-align: top;
    }
    .price_h1{
	background-color: #fc0;
	font-size: 18px;
	padding: 5px;
        font-weight: bold;
    }
    .price_h2{
	background-color: #cdf;
	font-size: 14px;
	padding: 3px;
        font-weight: bold;
    }
    .price_page{
	padding: 0px;
    }
    table{
	border-collapse: collapse;
	width:100%;
	max-width:18.5cm;
	font-size: 16px;
	font-family: Calibri,Arial;
    }
    .promo ,.promo .product_code,.promo .product_name,.promo .product_price{
        background-color: #6f6;
    }
</style>
<div class="page">
    <div>
        <div style="font-size:24px;text-align: left;float: left;">ПРАЙС ЛИСТ ОТ <?php echo $v->date;?></div>  <div style="float:right"><?php echo $v->pcomp_label;?> <?php echo $v->dollar_ratio;?>$</div>        
    </div>
    <table border>
        <tbody>
        <?php $counter1=-1; if( isset($v->price_blocks) && is_array($v->price_blocks) && sizeof($v->price_blocks) ) foreach( $v->price_blocks as $key1 => $value1 ){ $counter1++; ?>
            <?php if( $value1->type=='category' ){ ?>
            <?php if( $value1->catalog_view ){ ?>
            <tr><td colspan="4">
                <?php if( $num=count($value1->rows) ){ ?>
                <?php $counter2=-1; if( isset($value1->rows) && is_array($value1->rows) && sizeof($value1->rows) ) foreach( $value1->rows as $key2 => $value2 ){ $counter2++; ?>
                <div style="display: inline-block;width:32%">
                    <div>
                        <img src="../../Storage/image_flush/?size=200x200&path=/dynImg/<?php echo $value2->product_img;?>"
                             onerror="this.onerror=null;this.src='../dynImg/<?php echo $value2->product_img;?>_200x200.png';" width="100%" />
                    </div>
                    <div style="border-bottom: 1px solid black" <?php if( $value2->product_price!=$value2->promo_product_price ){ ?>class="promo"<?php } ?>>
                        <div>
                            <span class="product_code" <?php if( $value2->in_stock ){ ?>style="color:black"<?php } ?> >
                                Код:<b><?php echo $value2->product_code;?></b>
                            </span>
                            Цена:
                            <?php if( $value2->product_price!=$value2->promo_product_price ){ ?>
                                <s><?php echo $value2->product_price;?></s>
                                <b><?php echo $value2->promo_product_price;?></b>
                            <?php }else{ ?>
                                <b><?php echo $value2->product_price;?></b>
                            <?php } ?>
                        </div>
                        <i><?php echo $value2->product_name;?></i>
                    </div>
                    
                </div>
                <?php } ?>
                <?php } ?>
            </td></tr>
            <?php }else{ ?>
                    <?php if( $num=count($value1->rows) ){ ?>
                    <?php $counter2=-1; if( isset($value1->rows) && is_array($value1->rows) && sizeof($value1->rows) ) foreach( $value1->rows as $key2 => $value2 ){ $counter2++; ?>
                        <tr style="height: <?php echo $value1->rows_height;?>px;" <?php if( $value2->product_price!=$value2->promo_product_price ){ ?>class="promo"<?php } ?>>
                            <td class="product_code" <?php if( $value2->in_stock ){ ?>style="color:black"<?php } ?>><?php echo $value2->product_code;?></td>
                            <td class="product_name"><?php echo $value2->product_name;?></td>
                            <td class="product_price"> 
                                <?php if( $value2->product_price!=$value2->promo_product_price ){ ?>
                                    <s><?php echo $value2->product_price;?></s>
                                    <b><?php echo $value2->promo_product_price;?></b>
                                <?php }else{ ?>
                                    <?php echo $value2->product_price;?>
                                <?php } ?>
                            </td>
                            <?php if( $value1->allimg ){ ?>
                            <td class="product_imgs" style="page-break-inside: avoid;">
                                <div style="position: relative;margin: 1px;">
                                    <img src="../../Storage/image_flush/?size=140x140&path=/dynImg/<?php echo $value2->product_img;?>"
                                         onerror="this.onerror=null;this.src='../dynImg/<?php echo $value2->product_img;?>_140x140.png';" />
                                </div>
                            </td>
                            <?php }else{ ?>
                                <?php if( $counter2==0 ){ ?>
                                <td class="product_imgs" rowspan="<?php echo $num;?>">
                                    <div style="overflow: hidden;">
                                    <?php $counter3=-1; if( isset($value1->imgs) && is_array($value1->imgs) && sizeof($value1->imgs) ) foreach( $value1->imgs as $key3 => $value3 ){ $counter3++; ?>
                                    <div style="position: relative;margin: 1px;">
                                        <div style="position:absolute;left:3px;top:3px;background-color: rgba(255,255,255,.5)"><?php echo $value3->product_code;?></div>
                                        <img src="../../Storage/image_flush/?size=140x<?php echo $value1->img_height;?>&path=/dynImg/<?php echo $value3->product_img;?>"
                                             onerror="this.onerror=null;this.src='../dynImg/<?php echo $value3->product_img;?>_140x<?php echo $value1->img_height;?>.png';" />
                                    </div>
                                    <?php } ?>
                                    </div>
                                </td>
                                <?php } ?>
                            <?php } ?>
                        </tr>	
                    <?php } ?>
                    <?php } ?>
                <?php } ?>
            <?php }else{ ?>
            </tbody></table>
                <?php if( $value1->type=='page' ){ ?>
                    <table style="border-collapse: collapse"><tbody>
                    <tr>
                        <td colspan="4" class="price_page"><?php echo $value1->text;?></td>
                    </tr>
                <?php } ?>
                <?php if( $value1->type=='h1' ){ ?>
                    <table border><tbody>
                    <tr>
                        <td colspan="4" class="price_h1"><?php echo $value1->text;?></td>
                    </tr>
                <?php } ?>
                <?php if( $value1->type=='h2' ){ ?>
                    <table border><tbody>
                    <tr>
                        <td colspan="4" class="price_h2"><?php echo $value1->text;?></td>
                    </tr>
                    <tr>
                        <th>Код</th>
                        <th>Название</th>
                        <th>Цена</th>
                        <th>Изображение</th>
                    </tr>                
                <?php } ?>
            <?php } ?>
        <?php } ?>
        </tbody>
    </table>
</div>