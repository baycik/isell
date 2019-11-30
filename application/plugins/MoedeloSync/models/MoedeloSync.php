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
    
    public function sync(){
        
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


    private function getCategories($category_id) {
        $branches = $this->treeGetSub('stock_tree', $category_id);
        return $branches;
    }

    
    
    public $syncCompanies = [];
    public function syncCompanies(){
        $modes = ['POST'];
        $limit = 10;
        return;
        foreach($modes as $mode){
            $product_list = $this->productGetList($mode, $limit);
            
            
            
            
            
            if(!empty($product_list)){
                $rows_done = $this->productSync($product_list, $mode);
            }
        }
    }
    

}
