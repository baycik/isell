<?php
set_include_path('.'.PATH_SEPARATOR.'application/');
class ProcEvents{
    public function index(){
	header("X-isell-type:OK");
	include 'views/events/events_main.html';
    }
}
