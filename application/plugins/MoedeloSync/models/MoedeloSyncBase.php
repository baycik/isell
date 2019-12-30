<?php

class MoedeloSyncBase extends Catalog{
    protected $acomp_id=2;
    protected $sync_since="2019-11-01 00:00:00";
    protected $sync_time_window=365;
    
    private $gateway_url=null;
    private $gateway_md_apikey=null;
    
    function __construct() {
        session_write_close();
        set_time_limit(300);
    }
    
    public function setGateway( $url ){
        $this->gateway_url=$url;
    }
    public function setApiKey( $key ){
        $this->gateway_md_apikey=$key;
    }
    
    
    protected function apiExecute( string $function, string $method, array $data = null, int $remote_id = null){
        $url = "{$this->gateway_url}$function/$remote_id";
        
        $curl = curl_init(); 
        switch( $method ){
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            case 'GET':
                $query=$data?http_build_query($data):'';
                $url .= "?$query";
                break;
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["md-api-key: {$this->gateway_md_apikey}","Content-Type: application/json"]);

        $result = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if( curl_error($curl) ){
            $this->log("{$this->sync_destination} API Execute error: ".curl_error($curl));
            die(curl_error($curl));
        }
        curl_close($curl);
        return (object)[
            'httpcode'=>$httpcode,
            'response'=>json_decode($result)
            ];
    }
    
    protected function apiSend( $sync_destination, $remote_function, $document_list, $mode ){
        $rows_done=0;
        foreach($document_list as $document){
            if($mode === 'REMOTE_INSERT'){
                $response = $this->apiExecute($remote_function, 'POST', (array) $document);
                if( isset($response->response) && isset($response->response->Id) ){
                    $this->logInsert($sync_destination,$document->local_id,$document->current_hash,$response->response->Id);
                    $rows_done++;
                } else {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$sync_destination} INSERT is unsuccessfull (HTTP CODE:$response->httpcode '$error') Number:#{$document->Number}");
                }
            } else 
            if($mode === 'REMOTE_UPDATE'){
                $response = $this->apiExecute($remote_function, 'PUT', (array) $document, $document->remote_id);
                if( $response->httpcode==200 ){
                    $this->logUpdate($document->entry_id, $document->current_hash);
                    $rows_done++;
                } else {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$sync_destination} UPDATE is unsuccessfull (HTTP CODE:$response->httpcode '$error') Number:#{$document->Number}");
                }
            } else 
            if($mode === 'REMOTE_DELETE'){
                $response = $this->apiExecute($remote_function, 'REMOTE_DELETE', null, $document->remote_id);
                $this->logDelete($document->entry_id);
                $rows_done++;
                if( $response->httpcode!=204 ) {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$sync_destination} DELETE is unsuccessfull (HTTP CODE:$response->httpcode '$error') Number:#{$document->Number}");
                }
            }
        }
        return $rows_done;
    }
    
    
    
    
    
    protected function logInsert( $sync_destination, $local_id, $local_hash, $remote_id ){
        $sql = "
            INSERT INTO 
                plugin_sync_entries 
            SET
                sync_destination = '$sync_destination',
                local_id = '$local_id', 
                local_hash = '$local_hash', 
                local_tstamp = NOW(), 
                remote_id = '$remote_id'";
        return $this->query($sql);
    }
    
    protected function logUpdate($entry_id,$local_hash){
        $sql = "
            UPDATE 
                plugin_sync_entries 
            SET
                remote_hash = '$local_hash',
                local_hash = '$local_hash',
                local_tstamp=NOW()
            WHERE entry_id = '$entry_id'";
        return $this->query($sql);
    }
    
    protected function logDelete($entry_id){
        $sql = "
            DELETE FROM 
                plugin_sync_entries 
            WHERE entry_id = '$entry_id'";
        return $this->query($sql);
    }
    
    protected function getValidationErrors( $response ){
        $error_text='';
        if( isset($response->response->ValidationErrors) ){
            foreach( $response->response->ValidationErrors as $errors ){
                foreach($errors as $key=>$err){
                    $error_text.="$key : $err;";
                }
            }
        }
        return $error_text;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    protected function remoteCheckout( bool $is_full=false ){
        $sync_destination=$this->doc_config->sync_destination;
        $remote_function=$this->doc_config->remote_function;
        $is_finished=false;
        if( $is_full ){
            $afterDate=null;
        } else {
            $afterDate=$this->get_value("SELECT MAX(remote_tstamp) FROM plugin_sync_entries WHERE sync_destination='$sync_destination'");
        }
        $result=$this->remoteCheckoutGetList( $sync_destination, $remote_function, $afterDate );
        $nextPageNo=$result->pageNo+1;
        
        $this->query("START TRANSACTION");
        if( $is_full && $result->pageNo==1 ){
            $this->query("UPDATE plugin_sync_entries SET remote_hash=NULL,remote_tstamp=NULL WHERE sync_destination='$sync_destination'");
        }        
        foreach( $result->list as $item ){
            $calculatedItem=$this->remoteCheckoutCalculateItem( $item );
            $sql="INSERT INTO
                    plugin_sync_entries
                SET
                    sync_destination='$sync_destination',
                    local_id='$calculatedItem->local_id',
                    remote_id='$calculatedItem->remote_id',
                    remote_hash='$calculatedItem->remote_hash'
                ON DUPLICATE KEY UPDATE
                    remote_hash='$calculatedItem->remote_hash'
                ";
            $this->query($sql);
        }
        if( $result->pageIsLast ){//last page
            $is_finished=true;
            $nextPageNo=1;
        }
        $this->query("UPDATE
                plugin_list
            SET 
                plugin_json_data=JSON_SET(plugin_json_data,'$.{$this->doc_config->sync_destination}.checkoutPage','$nextPageNo'),
                plugin_json_data=JSON_SET(plugin_json_data,'$.{$this->doc_config->sync_destination}.checkoutLastFinished',NOW())
            WHERE 
                plugin_system_name='MoedeloSync'");
        $this->query("COMMIT");
        return $is_finished;
    }
    /**
     * 
     * @param string $sync_destination
     * @param string $remote_function
     * @param timestamp $afterDate
     * @return responseobject
     *  
     */
    protected function remoteCheckoutGetList( $sync_destination, $remote_function, $afterDate=null ){
        $pageNo=$this->get_value("SELECT COALESCE(JSON_EXTRACT(plugin_json_data,'$.{$sync_destination}.checkoutPage'),1) FROM plugin_list WHERE plugin_system_name='MoedeloSync'");
        $pageSize=1000;
        $request=[
            'pageNo'=>$pageNo,
            'pageSize'=>$pageSize,
            'afterDate'=>$afterDate,
            'beforeDate'=>null,
            'name'=>null
        ];
        $response=$this->apiExecute( $remote_function, 'GET', $request);
        $list=[];
        if( isset($response->response->ResourceList) ){
            $list=$response->response->ResourceList;
        }
        return (object) [
            'pageNo'=>$pageNo,
            'pageIsLast'=>count($list)<$pageSize?1:0,
            'list'=>$list
        ];
    }

}