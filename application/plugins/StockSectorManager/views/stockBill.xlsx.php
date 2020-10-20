<?php
    if( isset($this->view->head->doc_id) ){
        $trigger_before=$this->Hub->svar('trigger_before');
        $trigger_before['StockSectorManager']='StockSectorManager';
        $this->Hub->svar('trigger_before',$trigger_before);
        $StockSectorManager=$this->Hub->load_model("StockSectorManager");

        $this->view=$StockSectorManager->viewCreate($this->view->head->doc_id);
    }