<?php
require_once 'MoedeloSyncUPDSell.php';
class MoedeloSyncUPDBuy extends MoedeloSyncUPDSell{
    function __construct(){
        parent::__construct();
        $this->doc_config=(object) [
            'remote_function'=>'docs/api/v1/Purchases/Upd',
            'local_view_type_id'=>143,//upd
            'sync_destination'=>'moedelo_doc_upd_buy',
            'doc_type'=>2
        ];
    }
    
    /**
     * @param bool $is_full
     * Create local doc list to sync
     */    
    protected function localCheckoutGetList( $is_full, $afterDate, $filter_local='' ){
        $local_sync_list_sql="
            SELECT
                '{$this->doc_config->sync_destination}' sync_destination,
                local_id,
                MD5(CONCAT(Number,';',DocDate,';',KontragentId,';',TRIM(Sum)*1,';')) local_hash,
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
                
                Sender_pse.remote_id KontragentId,
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
                plugin_sync_entries Sender_pse ON passive_company_id=Sender_pse.local_id AND Sender_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries Payer_pse ON '$this->acomp_id'=Payer_pse.local_id AND Payer_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries Supplier_pse ON JSON_UNQUOTE(JSON_EXTRACT(view_efield_values,'$.supplier_company_id'))=Supplier_pse.local_id AND Supplier_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries Receiver_pse ON JSON_UNQUOTE(JSON_EXTRACT(view_efield_values,'$.reciever_company_id'))=Receiver_pse.local_id AND Receiver_pse.sync_destination='moedelo_companies'
            WHERE 
                active_company_id='{$this->acomp_id}' 
                AND doc_type='{$this->doc_config->doc_type}'
                AND view_type_id='{$this->doc_config->local_view_type_id}'
                AND dvl.tstamp>'{$this->sync_since}'
                $filter_local
            GROUP BY doc_view_id) inner_table";
        return $local_sync_list_sql;
    }
    
    
    
    public function localGet( $local_id ){
        $sql_dochead="
            SELECT
                dl.doc_id,
                dvl.doc_view_id local_id,
                dl.vat_rate,
                view_num Number,
                REPLACE(dvl.tstamp,' ','T') Date,
                1 Status,
                
                2 TaxSystem,
                2 NdsPositionType,
                
                CONCAT('УПД ',view_num,dvl.tstamp) ErrorTitle,
                
                SUM(ROUND(invoice_price*product_quantity*(1+dl.vat_rate/100),2)-ROUND(invoice_price*product_quantity,2)) NdsTotal,
                SUM(ROUND(invoice_price*product_quantity*(1+dl.vat_rate/100),2)) Sum,
                {$this->remote_stock_id} StockId,
                Sender_pse.remote_id KontragentId,
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
                plugin_sync_entries Sender_pse ON passive_company_id=Sender_pse.local_id AND Sender_pse.sync_destination='moedelo_companies'
                    LEFT JOIN
                plugin_sync_entries Payer_pse ON '$this->acomp_id'=Payer_pse.local_id AND Payer_pse.sync_destination='moedelo_companies'
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
                    IF({$this->doc_config->doc_type}=1 OR {$this->doc_config->doc_type}=2,1,2) Type,
                    IF({$document->vat_rate},5,1) NdsType,
                    ROUND(invoice_price,2) Price,
                    ROUND(invoice_price*product_quantity,2) SumWithoutNds,
                    ROUND(invoice_price*product_quantity*(1+{$document->vat_rate}/100),2) SumWithNds,
                    ROUND(invoice_price*product_quantity*(1+{$document->vat_rate}/100),2)-ROUND(invoice_price*product_quantity,2) NdsSum,
                    prod_pse.remote_id StockProductId,
                    party_label Declaration,
                    CONCAT(UCASE(LEFT(analyse_origin, 1)), LCASE(SUBSTRING(analyse_origin, 2))) Country
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
        $document->NdsDeductions=[
            ["Date"=>$document->Date,"Sum"=>$document->NdsTotal]
        ];
        return $document;
    }    
    
}