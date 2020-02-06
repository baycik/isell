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
    public $settings;
    public $plugin_data;
    
    function init() {
        $this->getSettings();
        //print_r($this->plugin_data);
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

    //'MoedeloSyncStocks/replicate/1 years/','MoedeloSyncStocks/checkout/1 years/',
    private $joblist=[
            'MoedeloSyncProduct/localCheckout/10 minutes/1 days',
            'MoedeloSyncProduct/remoteCheckout/1 days/1 days',
            'MoedeloSyncProduct/replicate/10 minutes/',
        
            'MoedeloSyncCompanies/localCheckout/10 minutes/1 days',
            'MoedeloSyncCompanies/remoteCheckout/1 hours/1 days',
            'MoedeloSyncCompanies/replicate/10 minutes/',
            
            'MoedeloSyncBillSell/localCheckout/10 minutes/60 minutes',
            'MoedeloSyncBillSell/remoteCheckout/10 minutes/1 days',
            'MoedeloSyncBillSell/replicate/10 minutes/',
            
        /*   
            'wayBillCheckout',
            'wayBillReplicate',
            'invoiceCheckout',
            'invoiceReplicate',
            'updReplicate'    */
        ];
    
    public function tick( $iterations_left ){
        header("Content-type:text/plain");
        if( empty($this->settings->gateway_url) || empty($this->settings->gateway_md_apikey) ){
            throw new Exception('Gateway or API key is not set');
        }
        if( !$iterations_left ){
            $iterations_left=count($this->joblist)+1;
        }
        if( $iterations_left<=1 ){
            echo 'iterations finished';
            return false;
        }
        
        $currentJob=$this->joblist[0];        
        if( isset($this->plugin_data->lastDoneJob) ){
            $last_done_key=array_search($this->plugin_data->lastDoneJob,$this->joblist)??0;
            if( is_numeric($last_done_key) &&  $last_done_key+1<count($this->joblist) ){
                $currentJob=$this->joblist[$last_done_key+1];
            }
        }
        $jobParts=explode('/',$currentJob);
        $last_launch=$this->plugin_data->{"$jobParts[0]_$jobParts[1]_Last"}??"2019-01-01";
        $last_full_launch=$this->plugin_data->{"$jobParts[0]_$jobParts[1]_LastFull"}??"2019-01-01";
        $is_short=$jobParts[2] && strtotime("$last_launch + $jobParts[2]")<time()?1:0;
        $is_full= $jobParts[3] && strtotime("$last_full_launch + $jobParts[3]")<time()?1:0;
        if( !$is_short && !$is_full ){
            echo "$iterations_left:skipped $currentJob\n";
            $this->plugin_data->lastDoneJob=$currentJob;
            $this->updateSettings();
            $this->tick( $iterations_left-1 );
            return true;
        }
        $this->jobExecute($currentJob,$jobParts,$is_full);
    }
    
    private function jobExecute($currentJob,$jobParts,$is_full){
        try{
            echo "starting $currentJob is_full=$is_full\n";
            $SyncModel=$this->Hub->load_model($jobParts[0]);
            $finished=$SyncModel->{$jobParts[1]}($is_full);
            if( $finished ){
                if( $is_full ){
                    $this->plugin_data->{"$jobParts[0]_$jobParts[1]_LastFull"}=date("Y-m-d H:i:s");
                }
                $this->plugin_data->{"$jobParts[0]_$jobParts[1]_Last"}=date("Y-m-d H:i:s");
                $this->plugin_data->lastDoneJob=$currentJob;
                $this->updateSettings();
                echo "done $currentJob\n";
                return true;
            } else {
                echo "undone. continue on next tick: $currentJob\n";
            }
        } catch (Exception $ex) {
            $this->log($ex);
            echo $ex;
        }
        return false;
    }
    
    
    public function updateSettings() {
        $settings=$this->settings;
        $plugin_data=$this->plugin_data;
        $this->getSettings();
        $plugin_data=(object) array_merge((array) $this->plugin_data, (array) $plugin_data);
        $encoded_settings = json_encode($settings);
        $encoded_data =     json_encode($plugin_data);
        $this->settings=    $settings;
        $this->plugin_data= $plugin_data;
        $sql = "
            UPDATE
                plugin_list
            SET 
                plugin_settings = '$encoded_settings',
                plugin_json_data = '$encoded_data'
            WHERE plugin_system_name = 'MoedeloSync'    
            ";
        $this->query($sql);
    }

    public function getSettings() {
        $sql = "
            SELECT
                plugin_settings,
                plugin_json_data
            FROM 
                plugin_list
            WHERE plugin_system_name = 'MoedeloSync'    
            ";
        $row = $this->get_row($sql);
        $this->settings=json_decode($row->plugin_settings);
        $this->plugin_data=json_decode($row->plugin_json_data);
    }
//    public function productSync(){
//        $MoedeloSyncProduct=$this->Hub->load_model('MoedeloSyncProduct');
//        $MoedeloSyncProduct->setGateway( $this->settings->gateway_url.'stock/api/v1/' );
//        $MoedeloSyncProduct->setApiKey( $this->settings->gateway_md_apikey );
//
//        $MoedeloSyncProduct->checkout(1);
//        $MoedeloSyncProduct->replicate();
//    }
    
    
    
//    public function productCheckout( $is_full ){
//        $MoedeloSyncProduct=$this->Hub->load_model('MoedeloSyncProduct');
//        $MoedeloSyncProduct->setGateway( $this->settings->gateway_url.'stock/api/v1/' );
//        $MoedeloSyncProduct->setApiKey( $this->settings->gateway_md_apikey );
//        $finished=$MoedeloSyncProduct->checkout($is_full);
//        return $finished;
//    }
//    
//    public function productReplicate(){
//        $MoedeloSyncProduct=$this->Hub->load_model('MoedeloSyncProduct');
//        $MoedeloSyncProduct->setGateway( $this->settings->gateway_url.'' );
//        $MoedeloSyncProduct->setApiKey( $this->settings->gateway_md_apikey );
//        $finished=$MoedeloSyncProduct->replicate();
//        return $finished;
//    }
    
//    public function companyCheckout( $is_full ){
//        $MoedeloSyncCompanies=$this->Hub->load_model('MoedeloSyncCompanies');
//        $MoedeloSyncCompanies->setGateway( $this->settings->gateway_url.'' );
//        $MoedeloSyncCompanies->setApiKey( $this->settings->gateway_md_apikey );
//        $finished=$MoedeloSyncCompanies->checkout( $is_full );
//        return $finished;
//    }
//    
//    public function companyReplicate(){
//        $MoedeloSyncCompanies=$this->Hub->load_model('MoedeloSyncCompanies');
//        $MoedeloSyncCompanies->setGateway( $this->settings->gateway_url.'kontragents/api/v1/' );
//        $MoedeloSyncCompanies->setApiKey( $this->settings->gateway_md_apikey );
//        $finished=$MoedeloSyncCompanies->replicate();
//        return $finished;
//    }
    
    
//    public function stocksCheckout(){
//        $MoedeloSyncStocks=$this->Hub->load_model('MoedeloSyncStocks');
//        $MoedeloSyncStocks->setGateway( $this->settings->gateway_url.'stock/api/v1/stock/' );
//        $MoedeloSyncStocks->setApiKey( $this->settings->gateway_md_apikey );
//        $finished=$MoedeloSyncStocks->checkout();
//        return $finished;
//    }
//    
//    public function stocksReplicate(){
//        $MoedeloSyncStocks=$this->Hub->load_model('MoedeloSyncStocks');
//        $MoedeloSyncStocks->setGateway( $this->settings->gateway_url.'stock/api/v1/stock/' );
//        $MoedeloSyncStocks->setApiKey( $this->settings->gateway_md_apikey );
//        $finished=$MoedeloSyncStocks->replicate();
//        return $finished;
//    }
//    
//    
//    public function billCheckout(){
//        $MoedeloSyncBill=$this->Hub->load_model('MoedeloSyncBill');
//        $MoedeloSyncBill->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
//        $MoedeloSyncBill->setApiKey( $this->settings->gateway_md_apikey );
//        $finished=$MoedeloSyncBill->checkout();
//        return $finished;
//    }
//    
//    public function billReplicate(){
//        $MoedeloSyncBill=$this->Hub->load_model('MoedeloSyncBill');
//        $MoedeloSyncBill->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
//        $MoedeloSyncBill->setApiKey( $this->settings->gateway_md_apikey );
//        $finished=$MoedeloSyncBill->replicateBills();
//        return $finished;
//    }
//    
//    public function wayBillCheckout(){
//        $MoedeloSyncWayBill=$this->Hub->load_model('MoedeloSyncWayBill');
//        $MoedeloSyncWayBill->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
//        $MoedeloSyncWayBill->setApiKey( $this->settings->gateway_md_apikey );
//        $finished=$MoedeloSyncWayBill->checkout();
//        return $finished;
//    }
//    
//    public function wayBillReplicate(){
//        $MoedeloSyncWayBill=$this->Hub->load_model('MoedeloSyncWayBill');
//        $MoedeloSyncWayBill->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
//        $MoedeloSyncWayBill->setApiKey( $this->settings->gateway_md_apikey );
//        $finished=$MoedeloSyncWayBill->replicateBills();
//        return $finished;
//    }
//    
//    public function invoiceCheckout(){
//        $MoedeloSyncInvoice=$this->Hub->load_model('MoedeloSyncInvoice');
//        $MoedeloSyncInvoice->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
//        $MoedeloSyncInvoice->setApiKey( $this->settings->gateway_md_apikey );
//        $finished=$MoedeloSyncInvoice->checkout();
//        return $finished;
//    }
//    
//    public function invoiceReplicate(){
//        $MoedeloSyncInvoice=$this->Hub->load_model('MoedeloSyncInvoice');
//        $MoedeloSyncInvoice->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
//        $MoedeloSyncInvoice->setApiKey( $this->settings->gateway_md_apikey );
//        $finished=$MoedeloSyncInvoice->replicate();
//        return $finished;
//    }
//    
//    public function updCheckout(){
//        return true;
//    }
//    
//    public function updReplicate(){
//        $MoedeloSyncUpd=$this->Hub->load_model('MoedeloSyncUpd');
//        $MoedeloSyncUpd->setGateway( $this->settings->gateway_url.'docs/api/v1/' );
//        $MoedeloSyncUpd->setApiKey( $this->settings->gateway_md_apikey );
//        $finished=$MoedeloSyncUpd->replicate();
//        return $finished;
//    }
//    
//    
//    
//    
//    
//    protected $local_tzone='+03:00';
//    protected $remote_tzone='+00:00';
//    public function actSync(){
//        $MoedeloSyncActSell=$this->Hub->load_model('MoedeloSyncActSell');
//        $MoedeloSyncActSell->setGateway( $this->settings->gateway_url.'accounting/api/v1/' );
//        $MoedeloSyncActSell->setApiKey( $this->settings->gateway_md_apikey );
//
//        $MoedeloSyncActSell->checkout();
//        $MoedeloSyncActSell->replicate();
//        //$MoedeloSyncActSell->localUpdate( 26493,0,0 );
//    }
}
