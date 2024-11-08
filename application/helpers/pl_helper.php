<?php

function pl( $data ){
    if( is_array($data) || is_object($data) ){
        log_message('error', json_encode($data,JSON_UNESCAPED_UNICODE & JSON_PRETTY_PRINT & JSON_UNESCAPED_SLASHES));
    } else {
        log_message('error', $data);
    }
}

function ql( $context ){
    log_message('error', $context->last_query());
}