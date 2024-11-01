<?php

function pl( $data ){
    log_message('error', json_encode($data,JSON_UNESCAPED_UNICODE & JSON_PRETTY_PRINT & JSON_UNESCAPED_SLASHES));
}

function ql( $context ){
    log_message('error', $context->last_query());
}