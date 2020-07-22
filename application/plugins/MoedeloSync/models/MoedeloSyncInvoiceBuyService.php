<?php
require_once 'MoedeloSyncInvoiceBuy.php';
class MoedeloSyncInvoiceBuyService extends MoedeloSyncInvoiceBuy{
    function __construct(){
        parent::__construct();
        $this->doc_config=(object) [
            'remote_function'=>'accounting/api/v1/purchases/invoice/common',
            'local_view_type_id'=>140,//invoice
            'sync_destination'=>'moedelo_doc_invoice_buy_service',
            'doc_type'=>4
        ];
    }
}