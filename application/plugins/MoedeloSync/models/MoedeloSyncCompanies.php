<?php
require_once 'MoedeloSyncBase.php';
class MoedeloSyncCompanies extends MoedeloSyncBase{
    private $sync_destination='moedelo_companies';
    
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
        $company_list=$this->apiExecute( 'kontragent', 'GET', $request);
        foreach($company_list->response->ResourceList as $company){
            $this->query("
                SET
                    @local_id:=(SELECT local_id FROM plugin_sync_entries WHERE sync_destination='$this->sync_destination' AND remote_id='$company->Id'),
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
        $company_type=1;// seller buyer
        
        $limit = 50;
        
        $select='';
        $table='';
        $where = '';
        $having='';

        switch( $mode ){
            case 'INSERT':
                $select=',cl.company_id';
                $table = 'LEFT JOIN
                    plugin_sync_entries pse ON cl.company_id=pse.local_id';
                $where= "WHERE local_id IS NULL AND company_id>1040";
                break;
            case 'UPDATE':
                $select=',pse.*';
                $table = 'JOIN
                    plugin_sync_entries pse ON cl.company_id=pse.local_id';
                $where= "WHERE sync_destination='$this->sync_destination'";
                $having="HAVING current_hash<>local_hash OR current_hash<>remote_hash";
                break;
            case 'DELETE':
                $select=',pse.*';
                $table = 'RIGHT JOIN
                    plugin_sync_entries pse ON cl.company_id=pse.local_id';
                $where= "WHERE sync_destination='$this->sync_destination' AND company_id IS NULL";
                break;
        }
        echo $sql="
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
                IF(company_tax_id,IF(company_tax_id2,1,2),3) `Form`
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
            if($mode === 'INSERT'){
                $response = $this->apiExecute('kontragent', 'POST', $company_object)->response;
                if( isset($response->Id) ){
                    $this->logInsert($this->sync_destination,$company->company_id,$company->current_hash,$response->Id);
                    $rows_done++;
                } else {
                    $this->log("{$this->sync_destination} INSERT is unsuccessfull company_name:{$company->Name}");
                }
            } else 
            if($mode === 'UPDATE'){
                $httpcode = $this->apiExecute('kontragent', 'PUT', $company_object, $company->remote_id)->httpcode;
                if( $httpcode==200 ){
                    $this->logUpdate($company->entry_id, $company->current_hash);
                    $rows_done++;
                } else {
                    $this->log("{$this->sync_destination} UPDATE is unsuccessfull company_name:{$company->Name}");
                }
            } else 
            if($mode === 'DELETE'){
                $httpcode = $this->apiExecute('kontragent', 'DELETE', null, $company->remote_id)->httpcode;
                $this->logDelete($company->entry_id);
                $rows_done++;
                if( $httpcode!=204 ) {
                    $this->log("{$this->sync_destination} DELETE is unsuccessfull code:$httpcode company_name:{$company->Name}");
                }
            }
        }
        return $rows_done;
    }
    
    
}