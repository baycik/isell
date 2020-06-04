<?php
ini_set('html_errors', false);
require_once 'MoedeloSyncActSell.php';
class MoedeloSyncActBuy extends MoedeloSyncActSell{
    ///////////////////////////////////////////////////////////////
    // COMMON SECTION
    ///////////////////////////////////////////////////////////////
    function __construct(){
        parent::__construct();
        $this->doc_config=(object) [
            'remote_function'=>'accounting/api/v1/purchases/act',
            'local_view_type_id'=>137,//Act
            'sync_destination'=>'moedelo_doc_act_buy',
            'doc_type'=>4
        ];
    }
}