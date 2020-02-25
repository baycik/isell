<?php
require_once 'MoedeloSyncBase.php';
class MoedeloSyncWayBillSell extends MoedeloSyncBase{
    function __construct(){
        parent::__construct();
        $this->doc_config=(object) [
            'remote_function'=>'accounting/api/v1/sales/waybill',
            'local_view_type_id'=>133,//torg12
            'sync_destination'=>'moedelo_doc_waybillsell',
            'doc_type'=>1
        ];
    }
    
    /**
     * Finds changes that needs to be made on local and remote
     */
    public function checkout( $is_full ){
        return false;
    }
    /**
     * Executes needed sync operations
     */
    public function replicate(){
        return parent::replicate();
    }
    
    
    ///////////////////////////////////////////////////////////////
    // REMOTE SECTION
    ///////////////////////////////////////////////////////////////
    
    /**
     * @param bool $is_full
     * Checks for updates on remote
     */
    public function remoteCheckout( bool $is_full=false ){
        return parent::remoteCheckout( $is_full );
    }
    /**
     * Inserts new record on remote
     */
    public function remoteInsert( $local_id, $remote_id, $entry_id ){
        return parent::remoteInsert($local_id, $remote_id, $entry_id);
    }
    /**
     * Updates existing record on remote
     */
    public function remoteUpdate( $local_id, $remote_id, $entry_id ){
        return parent::remoteUpdate($local_id, $remote_id, $entry_id);
    }
    
    /** 
     * Deletes existing record on remote
     */
    public function remoteDelete( $local_id, $remote_id, $entry_id ){
        return parent::remoteDelete($local_id, $remote_id, $entry_id);
    }
    
    /**
     * 
     * @param int $remote_id
     * @return type
     * Gets existing record from remote
     */
    public function remoteGet( $remote_id ){
        return parent::remoteGet($remote_id);
    }
    /**
     * 
     * @param object $entity
     * @return type md5 hash
     * Calculates remote entity hash
     */
    public function remoteHashCalculate( $entity ){
        $DocDate=substr( $this->toTimezone($entity->DocDate,'local') , 0, 10);
        $entity->Sum*=1;
        $check="{$entity->Number};{$DocDate};{$entity->KontragentId};{$entity->Sum};";
        //echo "remote check-$check";
        return md5($check);
    }
    /**
     * 
     * @param type $local_id
     * @param type $remote_id
     * @param type $entry_id
     * Gets remote document and fetches its modify date. This function resolves locks when hashes different but tstamps same.
     */
    public function remoteInspect( $local_id, $remote_id, $entry_id ){
        $this->remoteUpdate( $local_id, $remote_id, $entry_id );
    }
    
    
    
    
    
    ///////////////////////////////////////////////////////////////
    // LOCAL SECTION
    ///////////////////////////////////////////////////////////////
    
    /**
     * 
     * @param bool $is_full
     * Checks for updates on local
     */
    public function localCheckout( bool $is_full=false ){
        return parent::localCheckout($is_full);
    }
    /**
     * @param bool $is_full
     * Create local doc list to sync
     */    
    protected function localCheckoutGetList( $is_full, $afterDate ){
        $local_sync_list_sql="
            SELECT
                '{$this->doc_config->sync_destination}' sync_destination,
                local_id,
                MD5(CONCAT(
                    Number,';',
                    DocDate,';',
                    TRIM(Sum)*1,';',
                    KontragentId,';',
                    COALESCE(SenderId,0),';',
                    COALESCE(SupplierId,0),';',
                    COALESCE(ReceiverId,0),';',
                    PayerId,';'
                )) local_hash,
                local_tstamp,
                0 local_deleted
            FROM 
            (SELECT
                dvl.doc_view_id local_id,
                dl.vat_rate,
                view_num Number,
                SUBSTRING(dvl.tstamp,1,10) DocDate,
                SUM(ROUND(invoice_price*product_quantity*(1+dl.vat_rate/100),2)) Sum,
                1 Type,
                2 NdsPositionType,
                GREATEST(dl.modified_at,MAX(de.modified_at),dvl.modified_at) local_tstamp,
                
                Payer_pse.remote_id KontragentId,
                Sender_pse.remote_id SenderId,
                Supplier_pse.remote_id SupplierId,
                Receiver_pse.remote_id ReceiverId,
                Payer_pse.remote_id PayerId                
            FROM
                document_list dl
                    JOIN
                document_entries de USING(doc_id)
                    JOIN
                document_view_list dvl USING(doc_id)
                    JOIN
                plugin_sync_entries Payer_pse ON passive_company_id=Payer_pse.local_id AND Payer_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries Sender_pse ON '$this->acomp_id'=Sender_pse.local_id AND Sender_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries Supplier_pse ON JSON_UNQUOTE(JSON_EXTRACT(view_efield_values,'$.supplier_company_id'))=Supplier_pse.local_id AND Supplier_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries Receiver_pse ON JSON_UNQUOTE(JSON_EXTRACT(view_efield_values,'$.reciever_company_id'))=Receiver_pse.local_id AND Receiver_pse.sync_destination='moedelo_companies'
            WHERE 
                active_company_id='{$this->acomp_id}'
                AND doc_type='{$this->doc_config->doc_type}'
                AND view_type_id='{$this->doc_config->local_view_type_id}'
                AND dvl.tstamp>'{$this->sync_since}'
            GROUP BY doc_view_id) inner_table";
        return $local_sync_list_sql;
    }
    
    
    /**
     * Inserts new record on local
     */
    public function localInsert( $local_id, $remote_id, $entry_id ){
        $this->remoteDelete( $local_id, $remote_id, $entry_id );
    }
    
    /**
     * Updates existing record on local
     */
    public function localUpdate( $local_id, $remote_id, $entry_id ){
        $this->remoteUpdate( $local_id, $remote_id, $entry_id );
    }
    
    /**
     * Deletes existing record on local
     */
    public function localDelete( $local_id, $remote_id, $entry_id ){
        $this->remoteInsert( $local_id, $remote_id, $entry_id );
    }

//    protected function localHashCalculate( $entity ){
//        $check="{$entity->Article};{$entity->UnitOfMeasurement};".round($entity->SalePrice,5).";{$entity->Producer};";
//        echo "local check-$check";
//        return md5($check);
//    }
    
    
    public function localGet( $local_id ){
        $sql_dochead="
            SELECT
                dl.doc_id,
                dvl.doc_view_id local_id,
                dl.vat_rate,
                view_num Number,
                REPLACE(dvl.tstamp,' ','T') DocDate,
                '' PaymentNumber,
                '' PaymentDate,
                dl.cstamp ContextCreateDate,
                GREATEST(dl.modified_at,MAX(de.modified_at),dvl.modified_at) ContextModifyDate,
                user_sign ContextModifyUser,
                SUM(ROUND(invoice_price*product_quantity*(1+dl.vat_rate/100),2)) Sum,
                1 Type,
                2 NdsPositionType,
                
                Payer_pse.remote_id KontragentId,
                Sender_pse.remote_id SenderId,
                Supplier_pse.remote_id SupplierId,
                Receiver_pse.remote_id ReceiverId,
                Payer_pse.remote_id PayerId,
                (SELECT remote_id FROM plugin_sync_entries Bill_pse JOIN document_view_list dvl2 ON Bill_pse.local_id=dvl2.doc_view_id WHERE dvl2.doc_id=dl.doc_id AND view_type_id=136 AND Bill_pse.sync_destination='moedelo_doc_billsell' ) BillId
            FROM
                document_list dl
                    JOIN
                document_entries de USING(doc_id)
                    JOIN
                document_view_list dvl USING(doc_id)
                    JOIN
		user_list ON dl.modified_by=user_id 
                    JOIN
                plugin_sync_entries Payer_pse ON passive_company_id=Payer_pse.local_id AND Payer_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries Sender_pse ON '$this->acomp_id'=Sender_pse.local_id AND Sender_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries Supplier_pse ON JSON_UNQUOTE(JSON_EXTRACT(view_efield_values,'$.supplier_company_id'))=Supplier_pse.local_id AND Supplier_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries Receiver_pse ON JSON_UNQUOTE(JSON_EXTRACT(view_efield_values,'$.reciever_company_id'))=Receiver_pse.local_id AND Receiver_pse.sync_destination='moedelo_companies'
            WHERE 
                doc_view_id='$local_id'";
        $document=$this->get_row($sql_dochead);
        if( $document->doc_id ){
            $sql_entry="
                SELECT
                    0 DiscountRate,
                    ru Name,
                    product_quantity Count,
                    product_unit Unit,
                    IF(is_service=1,2,1) Type,
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
        $document->Context=(object)[
            'CreateDate'=>$this->toTimezone($document->ContextCreateDate,'remote'),
            'ModifyDate'=>$this->toTimezone($document->ContextModifyDate,'remote'),
            'ModifyUser'=>$document->ContextModifyUser
        ];
        
        
        print_r($document);//die;
        
        return $document;
    }    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    private function getDocConfig(){
        return (object)[
            'remote_function'=>'sales/waybill',
            'local_view_type_id'=>133,//torg12
            'sync_destination'=>'moedelo_doc_waybill'
        ];
    }
    
    public function checkout11111(){
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
                    document_view_list dvl USING(doc_id)
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
        $remote_insert_list = $this->getList('REMOTE_INSERT');
        $remote_update_list = $this->getList('REMOTE_UPDATE');
        $remote_delete_list = $this->getList('REMOTE_DELETE');
        
        $rows_done=0;
        $rows_done += $this->send($remote_insert_list, 'REMOTE_INSERT');
        $rows_done += $this->send($remote_update_list, 'REMOTE_UPDATE');
        $rows_done += $this->send($remote_delete_list, 'REMOTE_DELETE');
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
            case 'REMOTE_INSERT':
                $select='';
                $table = "    LEFT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='$doc_config->sync_destination'";
                $where= "WHERE doc_pse.local_id IS NULL 
                    AND active_company_id='$this->acomp_id'
                    AND view_type_id='$doc_config->local_view_type_id'";
                break;
            case 'REMOTE_UPDATE':
                $select=',doc_pse.*';
                $table = "    LEFT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='$doc_config->sync_destination'";
                $where= "WHERE doc_pse.sync_destination='$doc_config->sync_destination'";
                $having="HAVING current_hash<>COALESCE(local_hash,'') OR current_hash<>COALESCE(remote_hash,'')";
                break;
            case 'REMOTE_DELETE':
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
            if($mode === 'REMOTE_INSERT'){
                $response = $this->apiExecute($doc_config->remote_function, 'POST', (array) $document);
                if( isset($response->response) && isset($response->response->Id) ){
                    $this->logInsert($doc_config->sync_destination,$document->doc_view_id,$document->current_hash,$response->response->Id);
                    $rows_done++;
                } else {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$doc_config->sync_destination} INSERT is unsuccessfull (HTTP CODE:$response->httpcode '$error') Number:#{$document->Number}");
                }
            } else 
            if($mode === 'REMOTE_UPDATE'){
                $response = $this->apiExecute($doc_config->remote_function, 'PUT', (array) $document, $document->remote_id);
                if( $response->httpcode==200 ){
                    $this->logUpdate($document->entry_id, $document->current_hash);
                    $rows_done++;
                } else {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$doc_config->sync_destination} UPDATE is unsuccessfull (HTTP CODE:$response->httpcode '$error') Number:#{$document->Number}");
                }
            } else 
            if($mode === 'REMOTE_DELETE'){
                $response = $this->apiExecute($doc_config->remote_function, 'REMOTE_DELETE', null, $document->remote_id);
                
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