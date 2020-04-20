<?php
require_once 'MoedeloSyncBase.php';
class MoedeloSyncCompanies extends MoedeloSyncBase{
    function __construct(){
        parent::__construct();
        $this->doc_config=(object) [
            'remote_function'=>'kontragents/api/v1/kontragent',
            'sync_destination'=>'moedelo_companies'
        ];
    }
    
    /**
     * Finds changes that needs to be made on local and remote
     */
    public function checkout( $is_full ){
        //return $this->remoteCheckout($is_full) && $this->localCheckout($is_full);
    }
    /**
     * Executes needed sync operations
     */
    public function replicate( $filter_local_id=null ){
        return parent::replicate( $filter_local_id );
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
        $check="{$entity->Inn};{$entity->Ogrn};{$entity->Okpo};{$entity->Name};{$entity->LegalAddress};{$entity->ActualAddress};";
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
    public function localCheckout( bool $is_full=false, $filter_local_id=null ){
        return parent::localCheckout($is_full,$filter_local_id);
    }
    /**
     * 
     * @param bool $is_full
     * Create local doc list to sync
     */    
    protected function localCheckoutGetList( $is_full ){
        $local_sync_list_sql="
            SELECT
                '{$this->doc_config->sync_destination}' sync_destination,
                local_id,
                MD5(CONCAT(Inn,';',Ogrn,';',Okpo,';',Name,';',LegalAddress,';',ActualAddress,';')) local_hash,
                local_tstamp,
                0 local_deleted
            FROM
            (SELECT
                company_id local_id,
                NOW() local_tstamp,

                COALESCE(company_tax_id,'') Inn,
                COALESCE(company_code_registration,'') Ogrn,
                COALESCE(company_code,'') Okpo,
                COALESCE(company_name,'') Name,
                COALESCE(company_jaddress,'') LegalAddress,
                COALESCE(company_address,'') ActualAddress
            FROM
                companies_list cl
            WHERE
                (LENGTH(company_tax_id)=10 OR LENGTH(company_tax_id)=12)
                AND (COALESCE(company_code,'')='' OR LENGTH(company_code)=8)
                AND (COALESCE(company_code_registration,'')='' OR LENGTH(company_code_registration)=13 OR LENGTH(company_code_registration)=15)
                AND COALESCE(company_name,'')<>''
            ) inner_table";  
        return $local_sync_list_sql;
    }
    /**
     * Inserts new record on local
     */
    public function localInsert( $local_id, $remote_id, $entry_id ){
        $this->remoteDelete($local_id, $remote_id, $entry_id);
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

    protected function localHashCalculate( $entity ){
        $check="{$entity->Inn};{$entity->Ogrn};{$entity->Okpo};{$entity->Name};{$entity->LegalAddress};{$entity->ActualAddress};";
        //echo "local check-$check";
        return md5($check);
    }
    
    
    public function localGet( $local_id ){
        $sql_local="
            SELECT
                COALESCE(company_tax_id,'') Inn,
                COALESCE(company_code_registration,'') Ogrn,
                COALESCE(company_code,'') Okpo,
                COALESCE(company_name,'') Name,
                COALESCE(company_jaddress,'') LegalAddress,
                COALESCE(company_address,'') ActualAddress,
                
                CONCAT(company_name,company_id) ErrorTitle,
                
                '1' `Type`,
                IF(LENGTH(company_tax_id)=10,1,
                IF(LENGTH(company_tax_id)=12,'2'
                ,'3')) `Form`
            FROM
                companies_list cl
            WHERE company_id='$local_id'
           ";
        return $this->get_row($sql_local);
    }
}