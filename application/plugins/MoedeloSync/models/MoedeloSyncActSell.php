<?php
ini_set('html_errors', false);
require_once 'MoedeloSyncBase.php';
class MoedeloSyncActSell extends MoedeloSyncBase{
    ///////////////////////////////////////////////////////////////
    // COMMON SECTION
    ///////////////////////////////////////////////////////////////
    function __construct(){
        parent::__construct();
        $this->doc_config=(object) [
            'remote_function'=>'sales/act',
            'local_view_type_id'=>137,//Act
            'sync_destination'=>'moedelo_doc_act_sell',
            'doc_type'=>3
        ];
    }
    /**
     * Finds changes that needs to be made on local and remote
     */
    public function checkout(){
        $is_full=0;
        $this->remoteCheckout($is_full);
        $this->localCheckout(1);
    }
    /**
     * Executes needed sync operations
     */
    public function replicate(){
        $sql_action_list="
            SELECT
                entry_id,
                local_id,
                remote_id,
                IF( local_id IS NULL, 'localInsert',
                IF( remote_id IS NULL, 'remoteInsert',
                IF( local_deleted=1, 'remoteDelete',
                IF( remote_deleted=1, 'localDelete',
                IF( COALESCE(local_hash,'')<>COALESCE(remote_hash,''),
                IF( local_tstamp=remote_tstamp, 'remoteInspect',
                IF( local_tstamp<remote_tstamp, 'localUpdate', 'remoteUpdate')),
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
                $this->{$action->sync_action}($action->local_id,$action->remote_id,$action->entry_id);
            }
        }
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
        $DocDate=substr( $this->toTimezone($entity->DocDate,'local') , 0, 10);
        $entity->Sum*=1;
        echo $check="{$entity->Number};{$DocDate};{$entity->KontragentId};{$entity->Sum};";
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
        $remoteDoc=$this->remoteGet( $remote_id );
        $remote_tstamp=$this->toTimezone($remoteDoc->Context->ModifyDate,'local');
        $this->query("UPDATE plugin_sync_entries SET remote_tstamp='$remote_tstamp' WHERE entry_id='$entry_id'");
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
        $sql_local_docs="
            SELECT
                '{$this->doc_config->sync_destination}',
                local_id,
                MD5(CONCAT(Number,';',DocDate,';',KontragentId,';',TRIM(Sum)*1,';')) local_hash,
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
                GREATEST(dl.modified_at,MAX(de.modified_at),dvl.modified_at) local_tstamp
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
    }
    /**
     * Inserts new record on local
     */
    public function localInsert( $local_id, $remote_id, $entry_id ){
        $remoteDoc=$this->remoteGet($remote_id);
        if( !$remoteDoc || $this->Hub->svar( 'user_level' )<2 ){
            return false;
        }
        $remoteDoc->DocDate=$this->toTimezone($remoteDoc->DocDate,'local');
        $passive_company_id=$this->localFind($remoteDoc->KontragentId, 'moedelo_companies');
        $localDoc=$this->localFindDocument( $passive_company_id, $remoteDoc->Number, $remoteDoc->DocDate, $remoteDoc->Sum );
        print_r($remoteDoc);
        if( !$localDoc ){
            $DocumentItems=$this->Hub->load_model("DocumentItems");
            $new_doc_id=$DocumentItems->createDocument($this->doc_config->doc_type);
            $sql_doc_update="
                    UPDATE 
                        document_list 
                    SET 
                        cstamp='$remoteDoc->DocDate',
                        doc_num='$remoteDoc->Number'
                    WHERE doc_id='$new_doc_id'";
            $this->query($sql_doc_update);
            foreach( $remoteDoc->Items as $Item ){
                $sql_get_product_code="
                    SELECT 
                        product_code 
                    FROM 
                        prod_list
                    WHERE 
                        is_service=1
                        AND product_unit='$Item->Unit'
                        AND ru='$Item->Name'
                        ";
                $product_code=$this->get_value($sql_get_product_code);
                if( !$product_code ){
                    $product_code= mb_substr($Item->Name, 0, 5).rand(100,999);
                    $sql_insert_service="
                        INSERT INTO
                            prod_list
                        SET
                            is_service=1,
                            product_unit='$Item->Unit',
                            ru='$Item->Name',
                            product_code='$product_code'
                        ";
                    $this->query($sql_insert_service);
                }
                $DocumentItems->entryAdd( $new_doc_id, $product_code, $Item->Count, $Item->SumWithoutNds/$Item->Count );
            }
            $DocumentItems->entryDocumentCommit($new_doc_id);
            $localDoc=(object)[
                'doc_id'=>$new_doc_id,
                'modified_at'=>date("Y-m-d H:i:s")
            ];
        }
        
        $DocumentView=$this->Hub->load_model("DocumentView");
        if( empty($localDoc->doc_view_id) ){
            $new_doc_view_id=$DocumentView->viewCreate($this->doc_config->local_view_type_id);
            $localDoc->doc_view_id=$new_doc_view_id;
        }
        $DocumentView->viewUpdate($localDoc->doc_view_id,false,'view_num',$remoteDoc->Number);
        $DocumentView->viewUpdate($localDoc->doc_view_id,false,'view_date',$remoteDoc->DocDate);
        $remoteDoc->DocDate= substr($remoteDoc->DocDate, 0, 10);
        $local_hash=md5("{$remoteDoc->Number};{$remoteDoc->DocDate};{$remoteDoc->KontragentId};{$remoteDoc->Sum};");
        
        $remote_tstamp=$this->toTimezone($remoteDoc->Context->ModifyDate, 'local');
        $sql_save_insert="
            UPDATE 
                plugin_sync_entries
            SET
                local_id='$localDoc->doc_view_id',
                local_hash='$local_hash',
                local_tstamp='$localDoc->modified_at',
                remote_tstamp='$remote_tstamp'
            WHERE
                sync_destination='{$this->doc_config->sync_destination}'
                AND remote_id='$remote_id'
            ";
        $this->query($sql_save_insert);
        return $localDoc->doc_view_id;
    }
    
    private function localFindDocument( $passive_company_id, $doc_num, $doc_date, $doc_sum=0 ){
        echo $sql_find_local="
            SELECT
                dl.doc_id,
                SUM(ROUND(invoice_price*product_quantity*(1+dl.vat_rate/100),2)) doc_sum,
                GREATEST(dl.modified_at,MAX(de.modified_at),dvl.modified_at) modified_at,
                dvl.doc_view_id,
                doc_num='$doc_num' doc_num_equals,
                view_num='$doc_num' view_num_equals
            FROM
                document_list dl
                    JOIN
                document_entries de USING(doc_id)
                    LEFT JOIN
                document_view_list dvl ON dl.doc_id=dvl.doc_id AND view_type_id='{$this->doc_config->local_view_type_id}'
                    LEFT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='{$this->doc_config->sync_destination}'
            WHERE
                active_company_id='{$this->acomp_id}'
                AND passive_company_id='$passive_company_id'
                AND is_commited
                AND doc_type='{$this->doc_config->doc_type}'
                AND DATEDIFF(cstamp,'$doc_date')=0
            GROUP BY dl.doc_id
            HAVING doc_sum=$doc_sum*1
            ORDER BY doc_num_equals DESC,view_num_equals DESC
            LIMIT 1";
        return $this->get_row($sql_find_local);
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
     * Updates existing record on local
     */
    public function localUpdate( $local_id, $remote_id, $entry_id ){
//        $remoteDoc=$this->remoteGet($remote_id);
//        if( !$remoteDoc || $this->Hub->svar( 'user_level' )<2 ){
//            return false;
//        }
//        $remoteDoc->DocDate=$this->toTimezone($remoteDoc->DocDate,'local');
        $localDoc=$this->localGet($remote_id);
        print_r($localDoc);
    }
    
    /**
     * Deletes existing record on local
     */
    public function localDelete( $local_id, $remote_id, $entry_id ){
        
    }
    
    
    public function localGet( $local_id ){
        $sql_dochead="
            SELECT
                doc_pse.entry_id,
                doc_pse.remote_id,
                dl.doc_id,
                dl.vat_rate,
                
                view_num Number,
                dvl.tstamp DocDate,
                '' PaymentNumber,
                '' PaymentDate,
                dl.cstamp ContextCreateDate,
                GREATEST(dl.modified_at,MAX(de.modified_at),dvl.modified_at) ContextModifyDate,
                user_sign ContextModifyUser,
                SUM(ROUND(invoice_price*product_quantity*(1+dl.vat_rate/100),2)) Sum,
                2 NdsPositionType,                
                Kontragent_pse.remote_id KontragentId,
                Stock_pse.remote_id StockId
            FROM
                document_list dl
                    JOIN
                document_entries de USING(doc_id)
                    JOIN
                document_view_list dvl USING(doc_id)
                    JOIN
		user_list ON dl.modified_by=user_id
                    JOIN
                plugin_sync_entries Stock_pse ON 1=Stock_pse.local_id AND Stock_pse.sync_destination='moedelo_stocks'
                    JOIN
                plugin_sync_entries Kontragent_pse ON passive_company_id=Kontragent_pse.local_id AND Kontragent_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries doc_pse ON dvl.doc_view_id=doc_pse.local_id AND doc_pse.sync_destination='{$this->doc_config->sync_destination}'
            WHERE doc_view_id='$local_id'";
        $document=$this->get_row($sql_dochead);
        if( $document->doc_id ){
            $sql_entry="
                SELECT
                    prod_pse.remote_id Id,
                    ru Name,
                    product_quantity Count,
                    product_unit Unit,
                    IF(is_service=1,2,1) Type,
                    ROUND(invoice_price*(1+{$document->vat_rate}/100),2) Price,
                    {$document->vat_rate} NdsType,
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
        $document->Context=(object)[
            'CreateDate'=>$this->toTimezone($document->ContextCreateDate,'remote'),
            'ModifyDate'=>$this->toTimezone($document->ContextModifyDate,'remote'),
            'ModifyUser'=>$document->ContextModifyUser
        ];
        //print_r($document);
        return $document;
    }
    
    protected function localHashCalculate( $entity ){
        $DocDate=substr( $entity->DocDate, 0, 10);
        $entity->Sum*=1;
        echo $check="{$entity->Number};{$DocDate};{$entity->KontragentId};{$entity->Sum};";
        return md5($check);
    }
}