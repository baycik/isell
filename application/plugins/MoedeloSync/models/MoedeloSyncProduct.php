<?php
require_once 'MoedeloSyncBase.php';
class MoedeloSyncProduct extends MoedeloSyncBase{
    function __construct(){
        parent::__construct();
        $this->doc_config=(object) [
            'remote_function'=>'good',
            'sync_destination'=>'moedelo_products',
            
            'nomenclature_id'=>'11780959',
            'usd_rate'=>0,
            'vat_rate'=>20,
            'vat_position'=>2,
            'product_type'=>0
            
        ];
    }
    
    /**
     * Finds changes that needs to be made on local and remote
     */
    public function checkout( $is_full ){
        $this->remoteCheckout($is_full);
        $this->localCheckout($is_full);
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
        parent::remoteCheckout( $is_full );
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
        $entity->SalePrice= number_format($entity->SalePrice, 5,'.','');
        $check="{$entity->Article};{$entity->UnitOfMeasurement};{$entity->SalePrice};{$entity->Producer};";
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
        $this->doc_config->usd_rate=$this->Hub->pref('usd_ratio');
        $sql_local_docs="
            SELECT
                '{$this->doc_config->sync_destination}' sync_destination,
                local_id,
                MD5(CONCAT(Article,';',UnitOfMeasurement,';',ROUND(SalePrice,5),';',Producer,';')) local_hash,
                local_tstamp,
                0 local_deleted,
                remote_id
            FROM 
            (SELECT
                {$this->doc_config->nomenclature_id} NomenclatureId,
                ru Name,
                se.product_code Article,
                product_unit UnitOfMeasurement,
                {$this->doc_config->vat_rate} Nds,
                ROUND(IF(pre.curr_code='USD',{$this->doc_config->usd_rate},1)*sell, 2) SalePrice,
                {$this->doc_config->product_type} Type,
                {$this->doc_config->vat_position} NdsPositionType,
                analyse_brand Producer,
                
                pl.product_id local_id,
                pse.remote_id,
                GREATEST(se.modified_at,pl.modified_at,pre.modified_at) local_tstamp
            FROM
                stock_entries se
                    JOIN
                prod_list pl ON se.product_code=pl.product_code
                    JOIN
                price_list pre ON se.product_code=pre.product_code AND label=''
                    LEFT JOIN
                plugin_sync_entries pse ON pl.product_id=pse.local_id AND pse.sync_destination='{$this->doc_config->sync_destination}'
            ) inner_table";
        if( $is_full ){
            $afterDate='';
            $this->query("UPDATE plugin_sync_entries SET local_deleted=1 WHERE sync_destination='{$this->doc_config->sync_destination}'");
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
        //print_r($this->get_list($sql_local_docs));
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
        $this->doc_config->usd_rate=$this->Hub->pref('usd_ratio');
        $sql_local="
            SELECT
                {$this->doc_config->nomenclature_id} NomenclatureId,
                ru Name,
                se.product_code Article,
                product_unit UnitOfMeasurement,
                {$this->doc_config->vat_rate} Nds,
                ROUND(IF(pre.curr_code='USD',{$this->doc_config->usd_rate},1)*sell, 2) SalePrice,
                {$this->doc_config->product_type} Type,
                {$this->doc_config->vat_position} NdsPositionType,
                analyse_brand Producer,
                
                se.product_code Number,
                pl.product_id local_id,
                pse.remote_id,
                GREATEST(se.modified_at,pl.modified_at,pre.modified_at) local_tstamp
            FROM
                stock_entries se
                    JOIN
                prod_list pl ON se.product_code=pl.product_code
                    JOIN
                price_list pre ON se.product_code=pre.product_code AND label=''
                    LEFT JOIN
                plugin_sync_entries pse ON pl.product_id=pse.local_id AND pse.sync_destination='{$this->doc_config->sync_destination}'
            WHERE
                product_id='$local_id'
           ";
        return $this->get_row($sql_local);
    }
}