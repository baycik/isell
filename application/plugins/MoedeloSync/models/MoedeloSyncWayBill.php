<?php
require_once 'MoedeloSyncBase.php';
class MoedeloSyncWayBill extends MoedeloSyncBase{
    private function getDocConfig(){
        return (object)[
            'remote_function'=>'sales/waybill',
            'local_view_type_id'=>133,//torg12
            'sync_destination'=>'moedelo_doc_waybill'
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
        $document_list=$this->apiExecute( $doc_config->remote_function, 'GET', $request);
        if( $request['pageNo']==1 ){
            $this->query("UPDATE plugin_sync_entries SET remote_hash=NULL,remote_tstamp=NULL WHERE sync_destination='$doc_config->sync_destination'");
        }
        foreach($document_list->response->ResourceList as $document_head){
            $document_head->DocDate= substr($document_head->DocDate, 0, 10);
            $sql_find_local="
                SELECT
                    doc_view_id local_id
                FROM
                    document_list dl
                        JOIN
                    document_view_list USING(doc_id)
                        JOIN
                    document_entries USING(doc_id)
                        LEFT JOIN
                    plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='$doc_config->sync_destination'
                WHERE
                    doc_pse.remote_id='$document_head->Id'
                        OR
                    passive_company_id=(SELECT local_id FROM plugin_sync_entries WHERE sync_destination='moedelo_companies' AND remote_id='{$document_head->KontragentId}')
                    AND active_company_id='$this->acomp_id'
                    AND view_num='{$document_head->Number}'
                    AND view_type_id='$doc_config->local_view_type_id'
                    AND SUBSTRING(tstamp,1,10)='{$document_head->DocDate}'";
            $local_id=$this->get_value($sql_find_local);
            $this->query("
                SET
                    @local_id:='$local_id',
                    @remote_id:='$document_head->Id',
                    @remote_hash:=MD5(CONCAT({$document_head->Number},';','{$document_head->DocDate}',';',{$document_head->KontragentId},';',REPLACE(FORMAT({$document_head->Sum}, 2),',',''),';'))
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
        if( count($document_list)<$request['pageSize'] ){
            $this->query("DELETE FROM plugin_sync_entries WHERE sync_destination='$doc_config->sync_destination' AND remote_hash IS NULL AND remote_tstamp IS NULL");
            return true;//down sync is finished
        }
        return false;
    }
    
    
    public function replicateBills(){
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
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='$doc_config->sync_destination'";
                $where= "WHERE doc_pse.local_id IS NULL 
                    AND active_company_id='$this->acomp_id'
                    AND view_type_id='$doc_config->local_view_type_id'";
                break;
            case 'UPDATE':
                $select=',doc_pse.*';
                $table = "    LEFT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='$doc_config->sync_destination'";
                $where= "WHERE doc_pse.sync_destination='$doc_config->sync_destination'";
                $having="HAVING current_hash<>local_hash OR current_hash<>remote_hash";
                break;
            case 'DELETE':
                $select=',doc_pse.*';
                $table = "    RIGHT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='$doc_config->sync_destination'";
                $where= "WHERE doc_pse.sync_destination='$doc_config->sync_destination' AND doc_view_id IS NULL";
                break;
        }
         $sql_doclist="
            SELECT
                inner_table.*,
                MD5(CONCAT(Number,';',SUBSTRING(DocDate,1,10),';',KontragentId,';',Sum,';')) current_hash
            FROM 
            (SELECT
                dl.doc_id,
                dvl.doc_view_id,
                dl.vat_rate,
                view_num Number,
                dvl.tstamp DocDate,
                SUM(ROUND(invoice_price*product_quantity*(1+dl.vat_rate/100),2)) Sum,
                
                Payer_pse.remote_id KontragentId,
                Sender_pse.remote_id SenderId,
                Supplier_pse.remote_id SupplierId,
                Receiver_pse.remote_id ReceiverId,
                Payer_pse.remote_id PayerId,
                Stock_pse.remote_id StockId,


                1 Type,
                2 NdsPositionType,
                NOW() ModifyDate,
                user_sign ModifyUser
                $select
            FROM
                document_list dl
                    JOIN
                document_entries USING(doc_id)
                    JOIN
                document_view_list dvl USING(doc_id)
                    JOIN
		user_list ON dl.modified_by=user_id
                    JOIN
                plugin_sync_entries Stock_pse ON 1=Stock_pse.local_id AND Stock_pse.sync_destination='moedelo_stocks'
                    JOIN
                plugin_sync_entries Payer_pse ON passive_company_id=Payer_pse.local_id AND Payer_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries Sender_pse ON '$this->acomp_id'=Sender_pse.local_id AND Sender_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries Supplier_pse ON JSON_UNQUOTE(JSON_EXTRACT(view_efield_values,'$.supplier_company_id'))=Supplier_pse.local_id AND Supplier_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries Receiver_pse ON JSON_UNQUOTE(JSON_EXTRACT(view_efield_values,'$.reciever_company_id'))=Receiver_pse.local_id AND Receiver_pse.sync_destination='moedelo_companies'
                $table
            $where
            GROUP BY doc_view_id
            LIMIT $limit) inner_table
            $having";
        
        //die($sql_doclist);
        
        $doc_list=$this->get_list($sql_doclist);
        if( !$doc_list ){
            return [];
        }
        foreach($doc_list as &$document){
            if( !$document->doc_view_id ){
                continue;
            }
            $sql_entry="
                SELECT
                    0 DiscountRate,
                    ru Name,
                    product_quantity Count,
                    product_unit Unit,
                    IF(is_service,2,1) Type,
                    {$document->vat_rate} NdsType,
                    ROUND(invoice_price*(1+{$document->vat_rate}/100),2) Price,
                    ROUND(invoice_price*product_quantity*(1+{$document->vat_rate}/100),2) SumWithNds,
                    prod_pse.remote_id StockProductId
                FROM
                    document_entries
                        JOIN
                    prod_list USING(product_code)
                        LEFT JOIN
                    plugin_sync_entries prod_pse ON sync_destination='moedelo_products' AND local_id=product_id
                WHERE
                    doc_id={$document->doc_id}";
            $document->Items=$this->get_list($sql_entry);
        }
        return $doc_list;
    }
    
    private function send($document_list, $mode){
        if( empty($document_list) ){
            return 0;
        }
        
        //echo $mode."  ";print_r($document_list);
        
        $doc_config=$this->getDocConfig();
        $rows_done = 0;
        foreach($document_list as $document){
            if($mode === 'INSERT'){
                $response = $this->apiExecute($doc_config->remote_function, 'POST', (array) $document);
                if( isset($response->response) && isset($response->response->Id) ){
                    $this->logInsert($doc_config->sync_destination,$document->doc_view_id,$document->current_hash,$response->response->Id);
                    $rows_done++;
                } else {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$doc_config->sync_destination} INSERT is unsuccessfull (HTTP CODE:$response->httpcode '$error') Number:#{$document->Number}");
                }
            } else 
            if($mode === 'UPDATE'){
                $response = $this->apiExecute($doc_config->remote_function, 'PUT', (array) $document, $document->remote_id);
                if( $response->httpcode==200 ){
                    $this->logUpdate($document->entry_id, $document->current_hash);
                    $rows_done++;
                } else {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$doc_config->sync_destination} UPDATE is unsuccessfull (HTTP CODE:$response->httpcode '$error') Number:#{$document->Number}");
                }
            } else 
            if($mode === 'DELETE'){
                $response = $this->apiExecute($doc_config->remote_function, 'DELETE', null, $document->remote_id);
                
                $this->logDelete($document->entry_id);
                $rows_done++;
                if( $response->httpcode!=204 ) {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$doc_config->sync_destination} DELETE is unsuccessfull (HTTP CODE:$response->httpcode '$error') Number:#{$document->Number}");
                }
            }
        }
        return $rows_done;
    }
    
    
}