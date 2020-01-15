<?php

/* Group Name: РЎРёРЅС…СЂРѕРЅРёР·Р°С†РёСЏ
 * User Level: 3
 * Plugin Name: MoedeloSync
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: MoedeloSync
 * Author: baycik 2019
 * Author URI: http://isellsoft.com
 */


class MoedeloSync extends PluginManager {
    
    function __construct() {
        parent::__construct();
        $this->settings=$this->settingsDataFetch('MoedeloSync')->plugin_settings;
        header("Content-type:text/plain");
    }
    
    public function tick(){
        $joblist=[
            'productCheckout',
            'productReplicate',
    /*        'companyCheckout',
            'companyReplicate',
            'stocksCheckout',
            'stocksReplicate',
            'billCheckout',
            'billReplicate',
            'wayBillCheckout',
            'wayBillReplicate',
            'invoiceCheckout',
            'invoiceReplicate',
            'updReplicate'    */
        ];
        
        if( isset($this->settings->lastDoneJob) ){
            
        }
        
        
        
    }
    
    public function productCheckout(){
        $MoedeloSyncProduct=$this->Hub->load_model('MoedeloSyncProduct');
        $MoedeloSyncProduct->setGateway( $this->settings->gateway_url.'stock/api/v1/' );
        $MoedeloSyncProduct->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncProduct->checkout();
        return $finished;
    }
    
    public function productReplicate(){
        $MoedeloSyncProduct=$this->Hub->load_model('MoedeloSyncProduct');
        $MoedeloSyncProduct->setGateway( $this->settings->gateway_url.'stock/api/v1/' );
        $MoedeloSyncProduct->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncProduct->replicate();
        return $finished;
    }
    
    public function companyCheckout(){
        $MoedeloSyncCompanies=$this->Hub->load_model('MoedeloSyncCompanies');
        $MoedeloSyncCompanies->setGateway( $this->settings->gateway_url.'kontragents/api/v1/' );
        $MoedeloSyncCompanies->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncCompanies->checkout();
        return $finished;
    }
    
    public function companyReplicate(){
        $MoedeloSyncCompanies=$this->Hub->load_model('MoedeloSyncCompanies');
        $MoedeloSyncCompanies->setGateway( $this->settings->gateway_url.'kontragents/api/v1/' );
        $MoedeloSyncCompanies->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncCompanies->replicate();
        return $finished;
    }
    
    
    public function stocksCheckout(){
        $MoedeloSyncStocks=$this->Hub->load_model('MoedeloSyncStocks');
        $MoedeloSyncStocks->setGateway( $this->settings->gateway_url.'stock/api/v1/stock/' );
        $MoedeloSyncStocks->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncStocks->checkout();
        return $finished;
    }
    
    public function stocksReplicate(){
        $MoedeloSyncStocks=$this->Hub->load_model('MoedeloSyncStocks');
        $MoedeloSyncStocks->setGateway( $this->settings->gateway_url.'stock/api/v1/stock/' );
        $MoedeloSyncStocks->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncStocks->replicate();
        return $finished;
    }
    
    
    public function billCheckout(){
        $MoedeloSyncBill=$this->Hub->load_model('MoedeloSyncBill');
        $MoedeloSyncBill->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
        $MoedeloSyncBill->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncBill->checkout();
        return $finished;
    }
    
    public function billReplicate(){
        $MoedeloSyncBill=$this->Hub->load_model('MoedeloSyncBill');
        $MoedeloSyncBill->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
        $MoedeloSyncBill->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncBill->replicateBills();
        return $finished;
    }
    
    public function wayBillCheckout(){
        $MoedeloSyncWayBill=$this->Hub->load_model('MoedeloSyncWayBill');
        $MoedeloSyncWayBill->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
        $MoedeloSyncWayBill->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncWayBill->checkout();
        return $finished;
    }
    
    public function wayBillReplicate(){
        $MoedeloSyncWayBill=$this->Hub->load_model('MoedeloSyncWayBill');
        $MoedeloSyncWayBill->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
        $MoedeloSyncWayBill->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncWayBill->replicateBills();
        return $finished;
    }
    
    public function invoiceCheckout(){
        $MoedeloSyncInvoice=$this->Hub->load_model('MoedeloSyncInvoice');
        $MoedeloSyncInvoice->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
        $MoedeloSyncInvoice->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncInvoice->checkout();
        return $finished;
    }
    
    public function invoiceReplicate(){
        $MoedeloSyncInvoice=$this->Hub->load_model('MoedeloSyncInvoice');
        $MoedeloSyncInvoice->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
        $MoedeloSyncInvoice->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncInvoice->replicate();
        return $finished;
    }
    
    public function updCheckout(){
        return true;
    }
    
    public function updReplicate(){
        $MoedeloSyncUpd=$this->Hub->load_model('MoedeloSyncUpd');
        $MoedeloSyncUpd->setGateway( $this->settings->gateway_url.'docs/api/v1/' );
        $MoedeloSyncUpd->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncUpd->replicate();
        return $finished;
    }
    
    
    
    
    
    protected $local_tzone='+03:00';
    protected $remote_tzone='+00:00';
    public function actSync(){
        $MoedeloSyncActSell=$this->Hub->load_model('MoedeloSyncActSell');
        $MoedeloSyncActSell->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
        $MoedeloSyncActSell->setApiKey( $this->settings->gateway_md_apikey );

        $MoedeloSyncActSell->checkout();
        $MoedeloSyncActSell->replicate();
        //$MoedeloSyncActSell->localUpdate( 26493,0,0 );
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    

    public $updateSettings = ['settings' => 'json'];

    public function updateSettings($settings) {
        $this->settings = $settings;
        $encoded = json_encode($settings, JSON_UNESCAPED_UNICODE);
        $sql = "
            UPDATE
                plugin_list
            SET 
                plugin_settings = '$encoded'
            WHERE plugin_system_name = 'CSVExporter'    
            ";
        $this->query($sql);
        return $this->getSettings();
    }

    public function getSettings() {
        $sql = "
            SELECT
                plugin_settings
            FROM 
                plugin_list
            WHERE plugin_system_name = 'CSVExporter'    
            ";
        $row = $this->get_row($sql);
        return json_decode($row->plugin_settings);
    }

}
