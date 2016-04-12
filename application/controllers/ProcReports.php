<?php
include 'iSellBase.php';
class ProcReports extends iSellBase{
    
    public function index(){
	header("X-isell-type:OK");
	include 'views/reports/reports_main.html';
    }
}
