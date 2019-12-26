<?php
require_once 'MoedeloSyncBase.php';
class MoedeloSyncAct extends MoedeloSyncBase{
    function __construct(){
        $this->doc_config=(object) [
            'remote_function'=>'sales/act',
            'local_view_type_id'=>137,//Act
            'sync_destination'=>'moedelo_doc_act_sell',
            'doc_type'=>3
        ];
    }
    
    protected function checkoutCalcRemote( $item ){
        $item->DocDate= substr($item->DocDate, 0, 10);
        $item->Sum=number_format($item->Sum, 2, '.', '');
        echo $sql_find_local="
            SELECT
                doc_view_id local_id
            FROM
                document_list dl
                    JOIN
                document_view_list dvl USING(doc_id)
                    LEFT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='{$this->doc_config->sync_destination}'
                    LEFT JOIN
                plugin_sync_entries comp_pse ON passive_company_id=comp_pse.local_id AND comp_pse.remote_id='{$item->KontragentId}' AND comp_pse.sync_destination='moedelo_companies'
            WHERE
                doc_pse.remote_id='$item->Id'
                    OR
                comp_pse.local_id IS NOT NULL
                AND active_company_id='{$this->acomp_id}'
                AND doc_type='{$this->doc_config->doc_type}'
                AND view_num='{$item->Number}'
                AND view_type_id='{$this->doc_config->local_view_type_id}'
                AND SUBSTRING(tstamp,1,10)='{$item->DocDate}'";
        $local_id=$this->get_value($sql_find_local);
        
        return (object) [
            'local_id'=>$local_id,
            'remote_id'=>$item->Id,
            'remote_hash'=>md5("{$item->Number};{$item->DocDate};{$item->KontragentId};{$item->Sum};"),
            'remote_tstamp'=>date("Y-m-d H:i:s")
        ];
    }
    
    
    
    public function replicate(){
        $rows_done=0;
        
        $get_list    = $this->getList('LOCAL_INSERT');
        $rows_done += $this->recieve($get_list);
        
        
        print_r($get_list);die;
        
        
        $remote_insert_list = $this->getList('REMOTE_INSERT');
        $remote_update_list = $this->getList('REMOTE_UPDATE');
        $remote_delete_list = $this->getList('REMOTE_DELETE');
        
        $rows_done += $this->send($remote_insert_list, 'REMOTE_INSERT');
        $rows_done += $this->send($remote_update_list, 'REMOTE_UPDATE');
        //$rows_done += $this->send($remote_delete_list, 'REMOTE_DELETE');
        return $rows_done;
    }
    
    private function getList($mode){
        $limit = 50;
        $select='';
        $table='';
        $where = '';
        $having='';

        switch( $mode ){
            case 'LOCAL_INSERT':
                $select=',doc_pse.entry_id,doc_pse.remote_id';
                $table = "    RIGHT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='{$this->doc_config->sync_destination}'";
                $where= "WHERE doc_pse.sync_destination='{$this->doc_config->sync_destination}' AND doc_view_id IS NULL";
                break;
            case 'REMOTE_INSERT':
                $select='';
                $table = "    LEFT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='{$this->doc_config->sync_destination}'";
                $where= "WHERE doc_pse.local_id IS NULL 
                    AND active_company_id='{$this->acomp_id}'
                    AND view_type_id='{$this->doc_config->local_view_type_id}'";
                break;
            case 'REMOTE_UPDATE':
                $select=',doc_pse.entry_id,doc_pse.remote_id,doc_pse.remote_hash,doc_pse.local_hash';
                $table = "    LEFT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='{$this->doc_config->sync_destination}'";
                $where= "WHERE doc_pse.sync_destination='{$this->doc_config->sync_destination}'";
                $having="HAVING current_hash<>COALESCE(local_hash,'') OR current_hash<>COALESCE(remote_hash,'')";
                break;
            case 'REMOTE_DELETE':
                $select=',doc_pse.entry_id,doc_pse.remote_id';
                $table = "    RIGHT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='{$this->doc_config->sync_destination}'";
                $where= "WHERE doc_pse.sync_destination='{$this->doc_config->sync_destination}' AND doc_view_id IS NULL";
                break;
        }
         $sql_doclist="
            SELECT
                inner_table.*,
                MD5(CONCAT(Number,';',SUBSTRING(DocDate,1,10),';',KontragentId,';',Sum,';')) current_hash
            FROM 
            (SELECT
                dl.doc_id,
                dvl.doc_view_id local_id,
                dl.vat_rate,
                
                view_num Number,
                dvl.tstamp DocDate,
                '' PaymentNumber,
                '' PaymentDate,
                dl.cstamp ContextCreateDate,
                NOW() ContextModifyDate,
                user_sign ContextModifyUser,
                SUM(ROUND(invoice_price*product_quantity*(1+dl.vat_rate/100),2)) Sum,
                2 NdsPositionType,                
                Kontragent_pse.remote_id KontragentId,
                Stock_pse.remote_id StockId
                
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
                plugin_sync_entries Kontragent_pse ON passive_company_id=Kontragent_pse.local_id AND Kontragent_pse.sync_destination='moedelo_companies'
                $table
            $where
            GROUP BY doc_view_id
            LIMIT $limit) inner_table
            $having";
        
        $doc_list=$this->get_list($sql_doclist);
        if( !$doc_list ){
            return [];
        }
        foreach($doc_list as &$document){
            if( !$document->local_id ){
                continue;
            }
            $sql_entry="
                SELECT
                    ru Name,
                    product_quantity Count,
                    product_unit Unit,
                    IF(is_service=1,2,1) Type,
                    ROUND(invoice_price*(1+{$document->vat_rate}/100),2) Price,
                    {$document->vat_rate} NdsType,
                    prod_pse.remote_id StockProductId,
                    ROUND(invoice_price*product_quantity*(1+{$document->vat_rate}/100),2) SumWithNds
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
        $rows_done=$this->apiSend( $this->doc_config->sync_destination, $this->doc_config->remote_function, $document_list, $mode );
        return $rows_done;
    }
    
    private function recieve($document_list){
        if( empty($document_list) ){
            return 0;
        }
        $rows_done=$this->apiRecieve( $this->doc_config->sync_destination, $this->doc_config->remote_function, $document_list );
        return $rows_done;
    }
    
    protected function apiRecieve( $sync_destination, $remote_function, $document_list ){
        foreach($document_list as $document){
            $response = $this->apiExecute($remote_function, 'GET', NULL, $document->remote_id);
            
            
            print_r($response);die;
            
            
            if( isset($response->response) && isset($response->response->Id) ){
                $this->logInsert($sync_destination,$document->local_id,$document->current_hash,$response->response->Id);
                $rows_done++;
            } else {
                $error=$this->getValidationErrors($response);
                $this->log("{$sync_destination} INSERT is unsuccessfull (HTTP CODE:$response->httpcode '$error') Number:#{$document->Number}");
            }
        }
    }
    
    protected function localInsert( $document ){
        return true;
    }
    
    protected function localDelete( $local_id ){
        return true;
    }
    
    protected function localUpdate( $document ){
        return true;
    }
    
}