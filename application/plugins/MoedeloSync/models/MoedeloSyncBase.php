<?php

class MoedeloSyncBase extends Catalog{
    protected $acomp_id=2;
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
                $query=http_build_query($data);
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
            if($mode === 'INSERT'){
                $response = $this->apiExecute($remote_function, 'POST', (array) $document);
                if( isset($response->response) && isset($response->response->Id) ){
                    $this->logInsert($sync_destination,$document->local_id,$document->current_hash,$response->response->Id);
                    $rows_done++;
                } else {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$sync_destination} INSERT is unsuccessfull (HTTP CODE:$response->httpcode '$error') Number:#{$document->Number}");
                }
            } else 
            if($mode === 'UPDATE'){
                $response = $this->apiExecute($remote_function, 'PUT', (array) $document, $document->remote_id);
                if( $response->httpcode==200 ){
                    $this->logUpdate($document->entry_id, $document->current_hash);
                    $rows_done++;
                } else {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$sync_destination} UPDATE is unsuccessfull (HTTP CODE:$response->httpcode '$error') Number:#{$document->Number}");
                }
            } else 
            if($mode === 'DELETE'){
                $response = $this->apiExecute($remote_function, 'DELETE', null, $document->remote_id);
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
            
            print_r($response->response->ValidationErrors);
            
            
            foreach( $response->response->ValidationErrors as $errors ){
                foreach($errors as $key=>$err){
                    $error_text.="$key : $err;";
                }
            }
        }
        return $error_text;
    }
}