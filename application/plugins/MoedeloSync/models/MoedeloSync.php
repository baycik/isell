<?php

/* Group Name: Синхронизация
 * User Level: 3
 * Plugin Name: MoedeloSync
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: MoedeloSync
 * Author: baycik 2019
 * Author URI: http://isellsoft.com
 */


class MoedeloSync extends Catalog {
    
    function __construct() {
        parent::__construct();
        $this->settings=$this->getSettings();
    }
    
    private function updateSettings($settings) {
        $this->settings = $settings;
        $encoded = json_encode($settings, JSON_UNESCAPED_UNICODE);
        $sql = "
            UPDATE
                plugin_list
            SET 
                plugin_settings = '$encoded'
            WHERE plugin_system_name = 'MoedeloSync'    
            ";
        $this->query($sql);
        return $this->getSettings();
    }

    private function getSettings() {
        $sql = "
            SELECT
                plugin_settings
            FROM 
                plugin_list
            WHERE plugin_system_name = 'MoedeloSync'    
            ";
        $row = $this->get_row($sql);
        return json_decode($row->plugin_settings);
    }
    
    public function install(){
        $this->Hub->set_level(4);
	$install_file=__DIR__."/../install/install.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($install_file);
    }
    
    public function uninstall(){
        $this->Hub->set_level(4);
	$uninstall_file=__DIR__."/../install/uninstall.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($uninstall_file);
    }
    
    public function activate(){
        
    }
    
    public function deactivate(){
        
    }

    public function tick(){
        header("Content-type:text/plain");
        if( empty($this->settings->gateway_url) || empty($this->settings->gateway_md_apikey) ){
            throw new Exception('Gateway or API key is not set');
        }
        $full_checkout_lap=" + 1 days";
        $joblist=[
            'productCheckout',
            'productReplicate',
            'companyCheckout',
            'companyReplicate',
    /*      'stocksCheckout',
            'stocksReplicate',
            'billCheckout',
            'billReplicate',
            'wayBillCheckout',
            'wayBillReplicate',
            'invoiceCheckout',
            'invoiceReplicate',
            'updReplicate'    */
        ];
        

        $currentJob=$joblist[0];        
        if( isset($this->settings->lastDoneJob) ){
            $last_done_key=array_search($this->settings->lastDoneJob,$joblist);
            if( is_numeric($last_done_key) &&  $last_done_key+1<count($joblist) ){
                $currentJob=$joblist[$last_done_key+1];
            }
        } 
        try{
            echo "starting $currentJob\n";
            $is_checkout_job=strpos($currentJob, 'Checkout')>0;
            
            
            if( $is_checkout_job ){
                /*
                 * This is checkout function so we need to trigger it as full checkout in $full_checkout_lap intervals
                 */
                $next_full_launch=1;
                if( isset($this->settings->{$currentJob.'LastFull'}) ){
                    $next_full_launch=strtotime($this->settings->{$currentJob.'LastFull'}. ' + 1 days');
                }
                $is_full=$next_full_launch<time()?1:0;
            }
            
            
            
            
            $finished=$this->{$currentJob}();
            if( $finished ){
                $this->settings->lastDoneJob=$currentJob;
                $this->updateSettings($this->settings);
                echo "done $currentJob\n";
            }
        } catch (Exception $ex) {
            $this->log($ex);
            echo $ex;
        }
    }
    
//    public function productSync(){
//        $MoedeloSyncProduct=$this->Hub->load_model('MoedeloSyncProduct');
//        $MoedeloSyncProduct->setGateway( $this->settings->gateway_url.'stock/api/v1/' );
//        $MoedeloSyncProduct->setApiKey( $this->settings->gateway_md_apikey );
//
//        $MoedeloSyncProduct->checkout(1);
//        $MoedeloSyncProduct->replicate();
//    }
    
    
    
    public function productCheckout( $is_full ){
        $MoedeloSyncProduct=$this->Hub->load_model('MoedeloSyncProduct');
        $MoedeloSyncProduct->setGateway( $this->settings->gateway_url.'stock/api/v1/' );
        $MoedeloSyncProduct->setApiKey( $this->settings->gateway_md_apikey );
        $finished=$MoedeloSyncProduct->checkout($is_full);
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
    

    
    
    
    
    
    
    
    
    
    
    
    
    
    




}
