<?php
require_once 'MoedeloSyncBase.php';
class MoedeloSyncStocks extends MoedeloSyncBase{
    
    private function getDocConfig(){
        return (object)[
            'remote_function'=>'',
            'sync_destination'=>'moedelo_stocks'
        ];
    }
    
    public function checkout(){
        $doc_config=$this->getDocConfig();
        $afterDate=$this->get_value("SELECT REPLACE(MAX(remote_tstamp),' ','T') FROM plugin_sync_entries WHERE sync_destination='$doc_config->sync_destination'");
        $request=[
            'pageNo'=>1,
            'pageSize'=>100,
            'afterDate'=>$afterDate,
            'beforeDate'=>null,
            'name'=>null
        ];
        $stock_list=$this->apiExecute( $doc_config->remote_function, 'GET', $request);
        if( $request['pageNo']==1 ){
            $this->query("UPDATE plugin_sync_entries SET remote_hash=NULL,remote_tstamp=NULL WHERE sync_destination='$doc_config->sync_destination'");
        }
        foreach($stock_list->response->ResourceList as $stock){
            $local_id=1;
            $this->query("
                SET
                    @local_id:='$local_id',
                    @remote_id:='$stock->Id',
                    @remote_hash:=MD5(CONCAT('{$stock->Name}',';','{$stock->IsMain}',';','{$stock->StockType}'))
                ");
            $sql="INSERT INTO
                    plugin_sync_entries
                SET
                    sync_destination='$doc_config->sync_destination',
                    local_id=@local_id,
                    remote_id=@remote_id,
                    remote_hash=@remote_hash,
                    remote_tstamp=NOW()
                ON DUPLICATE KEY UPDATE
                    remote_hash=@remote_hash,
                    remote_tstamp=NOW()
                ";
            $this->query($sql);
        }
        if( count($stock_list)<$request['pageSize'] ){
            //$this->query("DELETE FROM plugin_sync_entries WHERE sync_destination='$doc_config->sync_destination' AND remote_hash IS NULL AND remote_tstamp IS NULL");
            return true;//down sync is finished
        }
        return false;
    }
    
    
    public function replicate(){
        $insert_list = $this->getList('INSERT');
        $update_list = $this->getList('UPDATE');
        $delete_list = $this->getList('DELETE');
        
        $rows_done=0;
        $rows_done += $this->send($insert_list, 'INSERT');
        $rows_done += $this->send($update_list, 'UPDATE');
        $rows_done += $this->send($delete_list, 'DELETE');
        return $rows_done;
    }
    
    private function getList($mode){
        $doc_config=$this->getDocConfig();
        
        $limit = 50;
        $select='';
        $table='';
        $where = '';
        $having='';

        switch( $mode ){
            case 'INSERT':
                $select='';
                $table = "    LEFT JOIN
                plugin_sync_entries pse  ON stock_id=pse.local_id AND pse.sync_destination='$doc_config->sync_destination'";
                $where= "WHERE pse.local_id IS NULL ";
                break;
            case 'UPDATE':
                $select=',pse.*';
                $table = "    LEFT JOIN
                plugin_sync_entries pse ON stock_id=pse.local_id AND pse.sync_destination='$doc_config->sync_destination'";
                $where= "WHERE pse.sync_destination='$doc_config->sync_destination'";
                $having="HAVING current_hash<>local_hash OR current_hash<>remote_hash";
                break;
            case 'DELETE':
                $select=',pse.*';
                $table = "    RIGHT JOIN
                plugin_sync_entries pse  ON stock_id=pse.local_id AND pse.sync_destination='$doc_config->sync_destination'";
                $where= "WHERE pse.sync_destination='$doc_config->sync_destination' AND stock_id IS NULL";
                break;
        }
        
        $local_stock=(object) [
            'Name'=>'Основной склад',
            'IsMain'=>1,
            'StockType'=>1
        ];
        
        
        $sql_stocklist="
            SELECT
                inner_table.*,
                MD5(CONCAT(Name,';',IsMain,';',StockType)) current_hash
                $select
            FROM 
            (SELECT
                1 stock_id,
                'Основной склад' Name,
                1 IsMain,
                1 StockType
            ) inner_table
            $table
            $where
            $having
        ";
        
        //die($sql_doclist);
        
        $stock_list=$this->get_list($sql_stocklist);
        if( !$stock_list ){
            return [];
        }
        return $stock_list;
    }
    
    private function send($stock_list, $mode){
        if( empty($stock_list) ){
            return 0;
        }
        
        print_r($stock_list);
        
        $doc_config=$this->getDocConfig();
        $rows_done = 0;
        foreach($stock_list as $stock){
            if($mode === 'INSERT'){
                $response = $this->apiExecute($doc_config->remote_function, 'POST', (array) $stock);
                if( isset($response->response) && isset($response->response->Id) ){
                    $this->logInsert($this->sync_destination,$stock->company_id,$stock->current_hash,$response->response->Id);
                    $rows_done++;
                } else {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$this->sync_destination} INSERT is unsuccessfull (HTTP CODE:$response->httpcode '$error') Name:#{$stock->Name}");
                }
            } else 
            if($mode === 'UPDATE'){
                $response = $this->apiExecute($doc_config->remote_function, 'PUT', (array) $stock, $stock->remote_id);
                if( $response->httpcode==200 ){
                    $this->logUpdate($stock->entry_id, $stock->current_hash);
                    $rows_done++;
                } else {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$this->sync_destination} UPDATE is unsuccessfull (HTTP CODE:$response->httpcode '$error') Name:#{$stock->Name}");
                }
            } else 
            if($mode === 'DELETE'){
                $response = $this->apiExecute($doc_config->remote_function, 'DELETE', null, $stock->remote_id);
                
                $this->logDelete($stock->entry_id);
                $rows_done++;
                if( $response->httpcode!=204 ) {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$this->sync_destination} DELETE is unsuccessfull (HTTP CODE:$response->httpcode '$error') Name:#{$stock->Name}");
                }
            }
        }
        return $rows_done;
    }
    
    
}