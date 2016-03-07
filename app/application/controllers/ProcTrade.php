<?php
set_include_path('.'.PATH_SEPARATOR.'application/');
class ProcTrade{
    public function index(){
	header("X-isell-type:OK");
	include 'views/trade/trade_main.html';
    }
}
