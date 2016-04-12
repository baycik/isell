<?php

require_once('iSellBase.php');

class ProcHome extends iSellBase {

    public function ProcHome() {
        $this->ProcessorBase(1);
    }

    public function onDefault() {
        $this->response_tpl('home/home_main.html');
    }

    public function onGetStockStats() {
        $this->LoadClass('Stock');
        $this->response($this->Stock->getStockStats());
    }

    public function onExpiredDebts() {
        $grid_query = $this->getGridQuery();
        $this->LoadClass('Home');
        $this->response(
                $this->Home->fetchExpiredDebts($grid_query)
        );
    }

    public function onExpListOut() {
        $grid_query = $this->getGridQuery();
        $out_type = $this->request('out_type', 0, '.print');
        $this->LoadClass('Home');
        $grid_data = $this->Home->fetchExpiredDebts($grid_query);
        $grid_structure = $this->Home->getGridStructure('expired_list');
        $this->Home->getGridOut($grid_structure, $grid_data['grid'], $out_type);
        exit;
    }

    public function onManagerPayments() {
	$period=$this->request('period');
        $grid_query = $this->getGridQuery();
        $this->LoadClass('Home');
        $this->response(
                $this->Home->fetchManagerPayments($grid_query,$period)
        );
    }

    public function onManagerPaymentsOut() {
	$period=$this->request('period');
        $grid_query = $this->getGridQuery();
        $out_type = $this->request('out_type', 0, '.print');
        $this->LoadClass('Home');
        $grid_data = $this->Home->fetchManagerPayments($grid_query,$period);
        $grid_structure = $this->Home->getGridStructure('manager_payments');
        $this->Home->getGridOut($grid_structure, $grid_data['grid'], $out_type);
        exit;
    }
     public function onFetchAvgRate(){
         $year=$this->request('year');
         $month=$this->request('month');
         $this->LoadClass('Home');
         $avg=$this->Home->fetchAvgRate($month,$year);
         $this->response($avg);
     }

}

?>