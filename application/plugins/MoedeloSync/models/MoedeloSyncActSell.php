<?php
require_once 'MoedeloSyncBase.php';
class MoedeloSyncActSell extends MoedeloSyncBase{
    function __construct(){
        $this->doc_config=(object) [
            'remote_function'=>'sales/act',
            'local_view_type_id'=>137,//Act
            'sync_destination'=>'moedelo_doc_act_sell',
            'doc_type'=>3
        ];
    }
    
    /**
     * 
     * @param bool $is_full
     * Checks for updates on remote
     */
    public function remoteCheckout( bool $is_full=false ){
        parent::remoteCheckout( $is_full );
    }    
    /**
     * 
     * @param object $item
     * @return calculated item
     */
    protected function remoteCheckoutCalculateItem( $item ){
        $item->DocDate= substr($item->DocDate, 0, 10);
        $item->Sum=number_format($item->Sum, 2, '.', '');
        $sql_find_local="
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
            'remote_tstamp'=>''
        ];
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * Finds changes that needs to be made on remote
     */
    public function remoteReplicate(){
        
    }
    
    /**
     * Inserts new record on remote
     */
    private function remoteInsert(){
        
    }
    
    /**
     * Updates existing record on remote
     */
    private function remoteUpdate(){
        
    }
    
    /**
     * Deletes existing record on remote
     */
    private function remoteDelete(){
        
    }
    
    /**
     * Gets existing remord from remote
     */
    private function remoteGet( $remote_id ){
        $response=$this->apiExecute($this->doc_config->remote_function, 'GET', null, $remote_id);
        if( $response->httpcode==200 ){
            return $response->response;
        }
        $this->log("Can't get document".$this->doc_config->remote_function);
    }
    
    
    
    
    
    /**
     * 
     * @param bool $is_full
     * Checks for updates on local
     */
    public function localCheckout( bool $is_full=false ){
        $sql_local_docs="
            SELECT
                '{$this->doc_config->sync_destination}',
                local_id,
                MD5(CONCAT(Number,';',DocDate,';',KontragentId,';',Sum,';')) local_hash,
                local_tstamp,
                0 local_deleted,
                remote_id
            FROM 
            (SELECT
                dvl.doc_view_id local_id,
                doc_pse.remote_id,
                view_num Number,
                SUBSTRING(dvl.tstamp,1,10) DocDate,
                SUM(ROUND(invoice_price*product_quantity*(1+dl.vat_rate/100),2)) Sum,
                Kontragent_pse.remote_id KontragentId,
                GREATEST(dl.modified_at,MAX(de.modified_at)) local_tstamp
            FROM
                document_list dl
                    JOIN
                document_entries de USING(doc_id)
                    JOIN
                document_view_list dvl USING(doc_id)
                    JOIN
                plugin_sync_entries Kontragent_pse ON passive_company_id=Kontragent_pse.local_id AND Kontragent_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='{$this->doc_config->sync_destination}'
            WHERE
                active_company_id='{$this->acomp_id}'
                AND doc_type='{$this->doc_config->doc_type}'
                AND view_type_id='{$this->doc_config->local_view_type_id}'
            GROUP BY doc_view_id) inner_table";
        if( $is_full ){
            $afterDate='';
        } else {
            $afterDate='';
        }
        $sql_update_local_docs="
            INSERT INTO
                plugin_sync_entries
            (sync_destination,local_id,local_hash,local_tstamp,local_deleted,remote_id)
            SELECT * FROM ($sql_local_docs) local_sync_list
            ON DUPLICATE KEY UPDATE 
                local_hash=local_sync_list.local_hash,local_tstamp=local_sync_list.local_tstamp,local_deleted=0
            ";
        $this->query("$sql_update_local_docs");
    }
    
    /**
     * Finds changes that needs to be made on local
     */
    public function replicate(){
        $sql_action_list="
            SELECT
                local_id,
                remote_id,
                IF( local_id=0, 'localInsert',
                IF( remote_id=0, 'remoteInsert',
                IF( local_deleted=1, 'localDelete',
                IF( remote_deleted=1, 'remoteDelete',
                IF( COALESCE(local_hash,'')<>COALESCE(remote_hash,''),
                IF( local_tstamp<remote_tstamp, 'localUpdate', 'remoteUpdate'),
                'SKIP'))))) sync_action
            FROM
                plugin_sync_entries doc_pse
            WHERE 
                sync_destination='{$this->doc_config->sync_destination}'
            ";
        $action_list=$this->get_list($sql_action_list);
        
        
        print_r($action_list);
        foreach( $action_list as $action ){
            if( method_exists( $this, $action->sync_action) ){
                $this->{$action->sync_action}($action->local_id,$action->remote_id);
            }
        }
    }
    
    /**
     * Inserts new record on local
     */
    private function localInsert( $local_id, $remote_id ){
        $remoteDoc=$this->remoteGet($remote_id);
        if( !$remoteDoc ){
            return false;
        }
        $passive_company_id=$this->localFind($remoteDoc->KontragentId, 'moedelo_companies');
        $doc_date= substr($remoteDoc->DocDate, 0, 10);
        $doc_sum=number_format($remoteDoc->Sum, 2, '.', '');
        $doc_num=$remoteDoc->Number;
        /**
         * searching for existing documents with same date & sum
         */
        
        $this->localFindDocumentId( $passive_company_id, $doc_num, $doc_date, $doc_sum );
        
        
        
        
        
        $DocumentItems=$this->Hub->load_model("DocumentItems");
        $new_doc_id=$DocumentItems->createDocument($this->doc_config->doc_type);
        
        foreach( $remoteDoc->Items as $Item ){
            $sql_get_product_code="
                SELECT 
                    product_code 
                FROM 
                    prod_list
                JOIN
                    plugin_sync_entries doc_pse ON local_id=product_id
                WHERE 
                    sync_destination='moedelo_products'
                    AND remote_id='$Item->Id'
                    ";
            $product_code=$this->get_value($sql_get_product_code);
            $DocumentItems->entryAdd( $new_doc_id, $product_code, $Item->Count, $Item->Price );
        }
        
        
        print_r($remoteDoc);
    }
    
    private function localFindDocumentId( $passive_company_id, $doc_num, $doc_date, $doc_sum=0 ){
        $sql_find_doc="";
        $doc_date= substr($doc_date, 0, 10);
        echo $sql_find_local="
            SELECT
                dl.doc_id,
                SUM(ROUND(invoice_price*product_quantity*(1+dl.vat_rate/100),2))=$doc_sum*1 sum_equals,
                doc_num='$doc_num' doc_num_equals,
                view_num='$doc_num' view_num_equals
            FROM
                document_list dl
                    JOIN
                document_entries de USING(doc_id)
                    JOIN
                plugin_sync_entries comp_pse ON passive_company_id=comp_pse.local_id AND comp_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                document_view_list dvl ON dl.doc_id=dvl.doc_id AND view_type_id='{$this->doc_config->local_view_type_id}'
                    LEFT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='{$this->doc_config->sync_destination}'
            WHERE
                active_company_id='{$this->acomp_id}'
                AND passive_company_id='$passive_company_id'
                AND doc_type='{$this->doc_config->doc_type}'
                AND SUBSTRING(cstamp,1,10)='$doc_date'
            GROUP BY dl.doc_id";
        $local_id=$this->get_value($sql_find_local);
    }
    private function localFind( $remote_id, $sync_destination ){
        $sql="SELECT
                local_id
            FROM
                plugin_sync_entries
            WHERE 
                sync_destination='$sync_destination'
                AND remote_id='$remote_id'";
        return $this->get_value($sql);
    }
    
    /**
     * Updates existing record on remote
     */
    private function localUpdate( $local_id, $remote_id ){
        
    }
    
    /**
     * Deletes existing record on remote
     */
    private function localDelete( $local_id, $remote_id ){
        
    }
}