<?php
/* User Level: 2
 * Group Name: Продажи
 * Plugin Name: CampaignManager
 * Version: 2019-01-05
 * Description: Расчет планов и бонусов менеджеров
 * Author: baycik 2019
 * Author URI: 
 * Trigger After: CampaignManager
 */
class CampaignManager extends Catalog{
    public function index(){
        $this->load->view('campaign_manager.html');
    }
    
    public function campaignGet( int $campaign_id ){
        $settings=$this->get_row("SELECT * FROM plugin_campaign_list WHERE campaign_id='$campaign_id'");
        $Pref=$this->Hub->load_model("Pref");
        $staff=$Pref->getStaffList();
        return [
            'settings'=>$settings,
            'staff_list'=>$staff
        ];
    }
    
    public function campaignAdd(){
        return $this->create('plugin_campaign_list');
    }
    
    public function campaignRemove(int $campaign_id){
        return $this->delete('plugin_campaign_list',['campaign_id'=>$campaign_id]);
    }
    
    public function campaignUpdate(int $campaign_id,string $field,string $value){
        return $this->update('plugin_campaign_list',[$field=>$value],['campaign_id'=>$campaign_id]);
    }
    
    private function clientListFilterGet($campaign_id){
        $settings=$this->get_row("SELECT * FROM plugin_campaign_list WHERE campaign_id='$campaign_id'");
        $or_case=[];
        $and_case=[];
        if( $settings->subject_path_include ){
            $or_case[]=" path LIKE '".str_replace(",", "%' OR path LIKE '", $settings->subject_path_include)."%'";
        }
        if( $settings->subject_path_exclude ){
            $and_case[]=" path NOT LIKE '".str_replace(",", "%' AND path NOT LIKE '", $settings->subject_path_include)."%'";
        }
        if( $settings->subject_manager_include ){
            $or_case[]=" manager_id = '".str_replace(",", "' OR manager_id = '", $settings->subject_manager_include)."'";
        }
        if( $settings->subject_path_exclude ){
            $and_case[]=" manager_id <> '".str_replace(",", "' OR manager_id <> '", $settings->subject_manager_include)."'";
        }
        if( count($or_case) ){
            $where="(".implode(' OR ',$or_case).")";
        }
        if( count($and_case) ){
            if( count($or_case) ){
                $where.=" AND ";
            }
            $where.=implode(' AND ', $and_case);
        }
        return $where?$where:1;
    }
    
    
    
    public function clientListFetch(int $campaign_id, int $offset,int $limit,string $sortby='label',string $sortdir='ASC',array $filter){
        $having=$this->makeFilter($filter);
        $where=$this->clientListFilterGet($campaign_id);
        $sql="
            SELECT 
                label,
                path
            FROM
                companies_list
                    JOIN
                companies_tree USING(branch_id)
            WHERE $where
            HAVING $having
            ORDER BY $sortby $sortdir
	    LIMIT $limit OFFSET $offset";
        return $this->get_list($sql);
    }
}