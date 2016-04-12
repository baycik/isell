<?php
    $okei=[
        'шт'=>['okei'=>'796','univ'=>'PCE'],
        'м'=>['okei'=>'006','univ'=>'M']
    ];
    foreach( $this->view->rows as &$row ){
        $row->product_unit_code=$okei[$row->product_unit]['okei'];
        $row->product_unit_code_univ=$okei[$row->product_unit]['univ'];
    }