<?php
require_once 'MoedeloSyncBase.php';
class MoedeloSyncProduct extends MoedeloSyncBase{
    private $sync_destination='moedelo_products';
    
    public function checkout(){
        $request=[
            'pageNo'=>1,
            'pageSize'=>100000,
            'afterDate'=>null,
            'beforeDate'=>null,
            'name'=>null
        ];
        if( $request['pageNo']==1 ){
            $this->query("UPDATE plugin_sync_entries SET remote_hash=NULL,remote_tstamp=NULL WHERE sync_destination='$this->sync_destination'");
        }
        $product_list=$this->apiExecute( 'good', 'GET', $request);
        foreach($product_list->response->ResourceList as $product){
            $this->query("
                SET
                    @local_id:=(SELECT product_id FROM prod_list WHERE product_code='$product->Article'),
                    @remote_hash:=MD5(CONCAT(
                        '$product->Name',
                        '$product->Article',
                        '$product->UnitOfMeasurement',
                        ROUND('$product->SalePrice',5),
                        '$product->Producer'
                        )),
                    @remote_id:='$product->Id'
                ");
            $sql="INSERT INTO
                    plugin_sync_entries
                SET
                    sync_destination='$this->sync_destination',
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
        if( count($product_list)<$request['pageSize'] ){
            $this->query("DELETE FROM plugin_sync_entries WHERE sync_destination='$this->sync_destination' AND remote_hash IS NULL AND remote_tstamp IS NULL");
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
        $nomenclature_id = '11780959';
        $usd_rate=$this->Hub->pref('usd_ratio');
        $vat_rate = 20;
        $vat_position = 2;
        $product_type=0;
        
        $limit = 50;
        
        $select='';
        $table='';
        $where = '';
        $having='';

        switch( $mode ){
            case 'INSERT':
                $select=',pl.product_id';
                $table = 'LEFT JOIN
                    plugin_sync_entries pse ON pl.product_id=pse.local_id';
                $where= "WHERE local_id IS NULL";
                break;
            case 'UPDATE':
                $select=',pse.*';
                $table = 'JOIN
                    plugin_sync_entries pse ON pl.product_id=pse.local_id';
                $where= "WHERE sync_destination='$this->sync_destination'";
                $having="HAVING current_hash<>local_hash OR current_hash<>remote_hash";
                break;
            case 'DELETE':
                $select=',pse.*';
                $table = 'RIGHT JOIN
                    plugin_sync_entries pse ON pl.product_id=pse.local_id';
                $where= "WHERE sync_destination='$this->sync_destination' AND product_id IS NULL";
                break;
        }
        $sql="
            SELECT
                inner_table.*,
                MD5(CONCAT(Name,Article,UnitOfMeasurement,SalePrice,Producer)) current_hash
            FROM
            (SELECT
                $nomenclature_id NomenclatureId,
                ru Name,
                se.product_code Article,
                product_unit UnitOfMeasurement,
                $vat_rate Nds,
                ROUND(IF(pre.curr_code='USD',$usd_rate,1)*sell, 2) SalePrice,
                $product_type Type,
                $vat_position NdsPositionType,
                analyse_brand Producer
                $select
            FROM
                stock_entries se
                    JOIN
                prod_list pl ON se.product_code=pl.product_code
                    JOIN
                price_list pre ON se.product_code=pre.product_code AND label=''
                    $table
            $where) AS inner_table
            $having
            LIMIT $limit";
        return $this->get_list($sql);
    }
    
    private function send($product_list, $mode){
        if( empty($product_list) ){
            return 0;
        }
        $rows_done = 0;
        foreach($product_list as $product){
            $product_object = [
                "NomenclatureId" => $product->NomenclatureId,
                "Name" => $product->Name,
                "Article" => $product->Article,
                "UnitOfMeasurement" => $product->UnitOfMeasurement,
                "Nds" => $product->Nds,
                "SalePrice" => $product->SalePrice,
                "Type" => $product->Type,
                "NdsPositionType" => $product->NdsPositionType,
                "Producer" => $product->Producer
            ];
            if($mode === 'INSERT'){
                $response = $this->apiExecute('good', 'POST', $product_object)->response;
                if( isset($response->Id) ){
                    $this->logInsert($this->sync_destination,$product->product_id,$product->current_hash,$response->Id);
                    $rows_done++;
                } else {
                    $this->log("{$this->sync_destination} INSERT is unsuccessfull product_code:{$product->Article}");
                }
            } else 
            if($mode === 'UPDATE'){
                $httpcode = $this->apiExecute('good', 'PUT', $product_object, $product->remote_id)->httpcode;
                if( $httpcode==200 ){
                    $this->logUpdate($product->entry_id, $product->current_hash);
                    $rows_done++;
                } else {
                    $this->log("{$this->sync_destination} UPDATE is unsuccessfull product_code:{$product->Article}");
                }
            } else 
            if($mode === 'DELETE'){
                $httpcode = $this->apiExecute('good', 'DELETE', null, $product->remote_id)->httpcode;
                $this->logDelete($product->entry_id);
                $rows_done++;
                if( $httpcode!=204 ) {
                    $this->log("{$this->sync_destination} DELETE is unsuccessfull code:$httpcode product_code:{$product->Article}");
                }
            }
        }
        return $rows_done;
    }
    
    
}