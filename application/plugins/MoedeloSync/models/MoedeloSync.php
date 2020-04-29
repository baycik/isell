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
            'MoedeloSyncProduct/localCheckout/9 minutes/60 minutes',
            'MoedeloSyncProduct/remoteCheckout/9 minutes/1 days',
            'MoedeloSyncProduct/replicate/10 minutes/',
        
            'MoedeloSyncCompanies/localCheckout/9 minutes/60 minutes',
            'MoedeloSyncCompanies/remoteCheckout/9 minutes/1 days',
            'MoedeloSyncCompanies/replicate/10 minutes/',
            
            'MoedeloSyncBillSell/localCheckout/9 minutes/60 minutes',
            'MoedeloSyncBillSell/remoteCheckout/9 minutes/1 days',
            'MoedeloSyncBillSell/replicate/10 minutes/',
            
            'MoedeloSyncWayBillSell/localCheckout/9 minutes/60 minutes',
            'MoedeloSyncWayBillSell/remoteCheckout/9 minutes/1 days',
            'MoedeloSyncWayBillSell/replicate/10 minutes/',
            
            'MoedeloSyncInvoiceSell/localCheckout/9 minutes/60 minutes',
            'MoedeloSyncInvoiceSell/remoteCheckout/9 minutes/1 days',
            'MoedeloSyncInvoiceSell/replicate/10 minutes/',
            
            'MoedeloSyncUPDSell/localCheckout/9 minutes/60 minutes',
            'MoedeloSyncUPDSell/replicate/10 minutes/',
        
            'MoedeloSyncUPDBuy/localCheckout/9 minutes/60 minutes',
            'MoedeloSyncUPDBuy/replicate/10 minutes/',
        
            'MoedeloSyncActSell/localCheckout/9 minutes/60 minutes',
            'MoedeloSyncActSell/remoteCheckout/9 minutes/1 days',
            'MoedeloSyncActSell/replicate/10 minutes/',
        
            'MoedeloSyncActBuy/localCheckout/9 minutes/60 minutes',
            'MoedeloSyncActBuy/remoteCheckout/9 minutes/1 days',
            'MoedeloSyncActBuy/replicate/10 minutes/',
        
            'MoedeloSyncWayBillBuy/localCheckout/9 minutes/60 minutes',
            'MoedeloSyncWayBillBuy/remoteCheckout/9 minutes/1 days',
            'MoedeloSyncWayBillBuy/replicate/10 minutes/',
            
            'MoedeloSyncInvoiceBuy/localCheckout/9 minutes/60 minutes',
            'MoedeloSyncInvoiceBuy/remoteCheckout/9 minutes/1 days',
            'MoedeloSyncInvoiceBuy/replicate/10 minutes/'
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
    
    public function downloadFile( string $doc_type, int $doc_view_id, int $view_type_id, string $file_type, string $file_name ){
        $key="{$doc_type}_{$view_type_id}";
        $handlers=[
            '1_136'=>'MoedeloSyncBillSell',
            '1_133'=>'MoedeloSyncWayBillSell',
            '1_140'=>'MoedeloSyncInvoiceSell',
            '1_143'=>'MoedeloSyncUPDSell'
        ];
        $Handler=$this->Hub->load_model($handlers[$key]);
        $remote_id=$Handler->remotePush($doc_view_id,true);
        $file_path=$Handler->doc_config->remote_function."/$remote_id/$file_type";
        $file_data=$Handler->apiExecute($file_path,"DOWNLOAD");
        http_response_code($file_data->httpcode);
        if($file_data->httpcode==200){
            $file_types=[
                'pdf'=>"Content-type:application/pdf",
                'xls'=>"Content-Type:application/vnd.ms-excel; charset=utf-8",
                'doc'=>"Content-Type:application/vnd.ms-word; charset=utf-8"
            ];
            $full_filename=rawurlencode("$file_name.$file_type");
            header($file_types[$file_type]);
            header("Content-Disposition:attachment;filename=$full_filename");
            header("Content-Disposition:attachment;filename*=UTF-8''$full_filename");
            echo $file_data->response;
        } else {
            die($file_data->response);
        }
    }
}
