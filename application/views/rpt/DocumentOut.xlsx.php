<?php

foreach($this->view->rows as $row){
    $row->product_price=$row->product_sum/$row->product_quantity;
}