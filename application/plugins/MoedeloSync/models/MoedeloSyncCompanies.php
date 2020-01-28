<?php
require_once 'MoedeloSyncBase.php';
class MoedeloSyncCompanies extends MoedeloSyncBase{
    private $sync_destination='moedelo_companies';
    
    public function checkout(){
        $request=[
            'pageNo'=>1,
            'pageSize'=>10000,
            'afterDate'=>null,
            'beforeDate'=>null,
            'name'=>null
        ];
        if( $request['pageNo']==1 ){
            $this->query("UPDATE plugin_sync_entries SET remote_hash=NULL,remote_tstamp=NULL WHERE sync_destination='$this->sync_destination'");
        }
        $company_list=$this->apiExecute( 'kontragent', 'GET', $request);
        print_r($company_list->response->ResourceList);
        foreach($company_list->response->ResourceList as $company){
            $this->query("
                SET
                    @local_id:=
                    COALESCE(
                        (SELECT local_id FROM plugin_sync_entries WHERE sync_destination='$this->sync_destination' AND remote_id='$company->Id' LIMIT 1),
                        (SELECT company_id FROM companies_list WHERE '$company->Inn' AND company_tax_id='$company->Inn' ORDER BY company_tax_id2='$company->Kpp' DESC LIMIT 1)
                    ),
                    @remote_hash:=MD5(CONCAT(
                        '$company->Inn',
                        '$company->Ogrn',
                        '$company->Okpo',
                        '$company->Name',
                        '$company->LegalAddress',
                        '$company->ActualAddress'
                        )),
                    @remote_id:='$company->Id'
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
        if( count($company_list)<$request['pageSize'] ){
            $this->query("DELETE FROM plugin_sync_entries WHERE sync_destination='$this->sync_destination' AND remote_hash IS NULL AND remote_tstamp IS NULL");
            return true;//down sync is finished
        }
        return false;
    }
    
    public function replicate(){
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
        $company_type=1;// seller buyer
        $limit = 300;
        
        $select='';
        $table='';
        $where = '';
        $having='';

        switch( $mode ){
            case 'REMOTE_INSERT':
                $select=',cl.company_id';
                $table = 'LEFT JOIN
                    plugin_sync_entries pse ON cl.company_id=pse.local_id';
                $where= "WHERE 
                    local_id IS NULL
                    AND (LENGTH(company_tax_id)=10 OR LENGTH(company_tax_id)=12)
                    AND (NOT COALESCE(company_code,'') OR LENGTH(company_code)=8)
                    AND (NOT COALESCE(company_code_registration,'') OR LENGTH(company_code_registration)>=13)";
                break;
            case 'REMOTE_UPDATE':
                $select=',pse.*';
                $table = 'JOIN
                    plugin_sync_entries pse ON cl.company_id=pse.local_id';
                $where= "WHERE sync_destination='$this->sync_destination'";
                $having="HAVING current_hash<>local_hash OR current_hash<>remote_hash";
                break;
            case 'REMOTE_DELETE':
                $select=',pse.*';
                $table = 'RIGHT JOIN
                    plugin_sync_entries pse ON cl.company_id=pse.local_id';
                $where= "WHERE sync_destination='$this->sync_destination' AND company_id IS NULL";
                break;
        }
        $sql="
            SELECT
                inner_table.*,
                MD5(CONCAT(Inn,Ogrn,Okpo,Name,LegalAddress,ActualAddress)) current_hash
            FROM
            (SELECT
                COALESCE(company_tax_id,'') Inn,
                COALESCE(company_code_registration,'') Ogrn,
                COALESCE(company_code,'') Okpo,
                COALESCE(company_name,'') Name,
                COALESCE(company_jaddress,'') LegalAddress,
                COALESCE(company_address,'') ActualAddress,
                
                $company_type `Type`,
                IF(company_tax_id,IF(LENGTH(company_tax_id)=10,1,2),3) `Form`
                $select
            FROM
                companies_list cl
                    $table
            $where) AS inner_table
            $having
            LIMIT $limit";
        return $this->get_list($sql);
    }
    
    private function send($company_list, $mode){
        if( empty($company_list) ){
            return 0;
        }
        $rows_done = 0;
        //echo $mode;print_r($company_list);
        foreach($company_list as $company){
            $company_object = [
                "Inn" => $company->Inn,
                "Ogrn" => $company->Ogrn,
                "Okpo" => $company->Okpo,
                "Name" => $company->Name,
                "LegalAddress" => $company->LegalAddress,
                "ActualAddress" => $company->ActualAddress,
                "Type" => $company->Type,
                "Form" => $company->Form
            ];
            if($mode === 'REMOTE_INSERT'){
                $response = $this->apiExecute('kontragent', 'POST', $company_object);
                if( isset($response->response) && isset($response->response->Id) ){
                    $this->logInsert($this->sync_destination,$company->company_id,$company->current_hash,$response->response->Id);
                    $rows_done++;
                } else {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$this->sync_destination} INSERT is unsuccessfull (HTTP CODE:$response->httpcode '$error') company_name:{$company->Name}");
                }
            } else 
            if($mode === 'REMOTE_UPDATE'){
                $response = $this->apiExecute('kontragent', 'PUT', $company_object, $company->remote_id);
                if( $response->httpcode==200 ){
                    $this->logUpdate($company->entry_id, $company->current_hash);
                    $rows_done++;
                } else {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$this->sync_destination} UPDATE is unsuccessfull (HTTP CODE:$response->httpcode '$error') company_name:{$company->Name}");
                }
            } else 
            if($mode === 'REMOTE_DELETE'){
                $response = $this->apiExecute('kontragent', 'REMOTE_DELETE', null, $company->remote_id);
                $this->logDelete($company->entry_id);
                $rows_done++;
                if( $response->httpcode!=204 ) {
                    $error=$this->getValidationErrors($response);
                    $this->log("{$this->sync_destination} DELETE is unsuccessfull (HTTP CODE:$response->httpcode '$error') company_name:".($response->response->Name ?? ''));
                }
            }
        }
        return $rows_done;
    }
}