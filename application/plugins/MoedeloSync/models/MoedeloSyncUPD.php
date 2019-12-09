<?php
require_once 'MoedeloSyncBase.php';
class MoedeloSyncUPD extends MoedeloSyncBase{
    
    private function getDocConfig(){
        return (object)[
            'remote_function'=>'Sales/Upd',
            'local_view_type_id'=>143,//UPD
            'sync_destination'=>'moedelo_doc_upd'
        ];
    }
    
    public function checkout(){
        return true;
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
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='$doc_config->sync_destination'";
                $where= "WHERE doc_pse.local_id IS NULL 
                    AND active_company_id='$this->acomp_id'
                    AND view_type_id='$doc_config->local_view_type_id'";
                break;
            case 'UPDATE':
                $select=',doc_pse.entry_id,doc_pse.remote_id,doc_pse.remote_hash,doc_pse.local_hash';
                $table = "    LEFT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='$doc_config->sync_destination'";
                $where= "WHERE doc_pse.sync_destination='$doc_config->sync_destination'";
                $having="HAVING current_hash<>COALESCE(local_hash,'') OR current_hash<>COALESCE(remote_hash,'')";
                break;
            case 'DELETE':
                $select=',doc_pse.entry_id,doc_pse.remote_id';
                $table = "    RIGHT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='$doc_config->sync_destination'";
                $where= "WHERE doc_pse.sync_destination='$doc_config->sync_destination' AND doc_view_id IS NULL";
                break;
        }
         $sql_doclist="
            SELECT
                inner_table.*,
                MD5(CONCAT(Number,';',SUBSTRING(Date,1,10),';',KontragentId,';',Sum,';')) current_hash
            FROM 
            (SELECT
                dl.doc_id,
                dvl.doc_view_id local_id,
                dl.vat_rate,
                
                view_num Number,
                dvl.tstamp Date,
                1 Status,
                Payer_pse.remote_id KontragentId,
                2 NdsPositionType,
                Stock_pse.remote_id StockId,
                
                SUM(ROUND(invoice_price*product_quantity*(1+dl.vat_rate/100),2)) Sum
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
            if( !$document->local_id ){
                continue;
            }
            $sql_entry="
                SELECT
                    IF(is_service=1,2,1) Type,
                    ru Name,
                    product_unit Unit,
                    product_quantity Count,
                    ROUND(invoice_price*(1+{$document->vat_rate}/100),2) Price,
                    IF({$document->vat_rate},5,1) NdsType,
                    prod_pse.remote_id StockProductId,
                    ROUND(invoice_price*product_quantity*(1+{$document->vat_rate}/100),2) SumWithNds,
                    analyse_origin Country,
                    party_label Declaration
                FROM
                    document_entries
                        JOIN
                    prod_list USING(product_code)
                        LEFT JOIN
                    plugin_sync_entries prod_pse ON sync_destination='moedelo_products' AND local_id=product_id
                WHERE
                    doc_id={$document->doc_id}";
            $document->Items=$this->get_list($sql_entry);
            foreach($document->Items as &$item){
                if( !$item->Declaration ){
                    unset($item->Declaration);
                }
            }
        }
        return $doc_list;
    }
    
    private function send($document_list, $mode){
        if( empty($document_list) ){
            return 0;
        }
        
        //echo $mode;print_r($document_list);
        
        
        $doc_config=$this->getDocConfig();
        $rows_done=$this->apiSend( $doc_config->sync_destination, $doc_config->remote_function, $document_list, $mode );
        return $rows_done;
    }
    
    
}