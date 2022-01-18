<?php

ini_set('zlib.output_compression', 0); 
ini_set('implicit_flush', 1);

function finishHere1($content){
        ob_start();
        echo "json_encode()";
        header('Connection: close');
        header('Content-Length: '.ob_get_length());
        ob_end_flush();
        ob_flush();
        flush();
}

finishHere1('{}');

sleep(2);