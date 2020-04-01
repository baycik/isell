<?php

/* Group Name: Работа с клиентами
 * User Level: 2
 * Plugin Name: Массовые рассылки
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: Массовые рассылки
 * Author: baycik 2020
 * Author URI: http://isellsoft.com
 */


class MailingManager extends Catalog {
    public $settings = [];
    private $default_settings = [
        'reciever_blacklist' => [],
        'reciever_list' => []
    ];
    
    public function index(){
        $this->Hub->set_level(3);
        $this->load->view('mailing_manager.html');
    }
    
     public function install(){
        $this->Hub->set_level(4);
	$install_file=__DIR__."/../install/install.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($install_file);
    }
    
    public function uninstall(){
        $this->Hub->set_level(4);
	$uninstall_file=__DIR__."/../install/uninstall.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($uninstall_file);
    }
    
    public function activate(){
        $this->Hub->set_level(4);
    }
    
    public function deactivate(){
        $this->Hub->set_level(4);
    }
    
    public function init(){
        $this->pluginSettingsLoad();
        if(!$this->settings){
            $this->settings = $this->default_settings;
            $this->pluginSettingsFlush();
        }
    }

    private function pluginSettingsFlush() {
        $settings=$this->settings;
        $plugin_data=$this->plugin_data;
        $this->pluginSettingsLoad();
        $plugin_data=(object) array_merge((array) $this->plugin_data, (array) $plugin_data);
        $encoded_settings = json_encode($settings, JSON_UNESCAPED_UNICODE );
        $encoded_data =     json_encode($plugin_data, JSON_UNESCAPED_UNICODE );
        $this->settings=    $settings;
        $this->plugin_data= $plugin_data;
        $sql = "
            UPDATE
                plugin_list
            SET 
                plugin_settings = '$encoded_settings',
                plugin_json_data = '$encoded_data'
            WHERE plugin_system_name = 'MailingManager'    
            ";
        $this->query($sql);
    }

    private function pluginSettingsLoad() {
        $sql = "
            SELECT
                plugin_settings,
                plugin_json_data
            FROM 
                plugin_list
            WHERE plugin_system_name = 'MailingManager'    
            ";
        $row = $this->get_row($sql);
        $this->settings=json_decode($row->plugin_settings);
        $this->plugin_data=json_decode($row->plugin_json_data);
    }
    
    
    public function settingsUpdate(array $settings){
        $this->settings = $settings;
        $this->pluginSettingsFlush();
        return true;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    

    public function messageCreate( string $handler, array $message ){
        $user_id=$this->Hub->svar('user_id');
        $message_record=[
            'message_handler'=>$handler,
            'message_status'=>'created',
            'message_reason'=>$message['message_reason'],
            'message_note'=>$message['message_note'],
            'message_recievers'=>$message['message_recievers'],
            'message_subject'=>$message['message_subject'],
            'message_body'=>$message['message_body'],
            'created_by'=>$user_id,
            'modified_by'=>$user_id
        ];
        return $this->create('plugin_message_list',$message_record);
    }
    
    
    public function customMessageCreate(array $message){
        $user_id=$this->Hub->svar('user_id');
        $reciever_list = $this->customMessageComposeRecievers($message);
        $message_template = $message['message_body'];
        foreach($reciever_list as $reciever){
            if(empty( $reciever->{$message['message_handler']} )){
                continue;
            }
            $message['message_recievers'] = $reciever->{$message['message_handler']};
            $message['message_reason'] = $message['message_subject'];
            $message['message_note'] = '';
            $message['message_body'] = $this->customMessageBodyMarkUp($message['message_recievers'],$message_template);
            $this->messageCreate($message['message_handler'], $message);
        }
        return true;
    }
    
    private function customMessageComposeRecievers( $message ){
        $reciever_list = [];
        if((int) $message['message_reciever_list']){
            $reciever_list += array_merge($reciever_list,$this->recieverGet($message['message_reciever_list']));
        }
        if(!empty($message['message_recievers'])){
            $custom_recievers = explode(',', $message['message_recievers']);
            foreach($custom_recievers as $custom_reciever){
                $custom_reciever_object = new StdClass();
                $custom_reciever_object->{$message['message_handler']} = $custom_reciever;
                $reciever_list[] = $custom_reciever_object;
            }
        }
        return $reciever_list;
    }
    
    private $default_markup_values = [
        'company_name' => 'Ваша компания',
        'company_person' => 'Клиент нашей компании',
        'company_director' => 'Клиент нашей компании',
        'company_email' => 'Ваш электронный адрес',
        'company_web' => 'Адрес вашего сайта',
        'company_mobile' => 'Ваш номер телефона',
        'company_address' => 'Ваш физический адрес',
        'company_bank_name' => 'Ваш банк'
    ];
    
    private function customMessageBodyMarkUp($message_recievers, $message_template){
        $passive_company = $this->passiveCompanyGet($message_recievers);
        foreach($this->default_markup_values as $key=>$property){
            $search = '{{'.$key.'}}';
            if(strpos($message_template, $search) > -1){
                if(!empty($passive_company->{$key})){
                    $replace = $passive_company->{$key};
                } else {
                    $replace = $property;
                }
                $message_template = str_replace($search, $replace, $message_template);
            } 
        }
        return $message_template;
    }
    
    private function messageListFilterGet( $filter ){
        if( empty($filter) ){
            return '1';
        }
        $signature="CONCAT(message_handler,' ',message_reason,' ',message_note,' ',message_recievers,' ',message_subject)";
        $parts=explode(" ",trim($filter));
        $having=" $signature LIKE '%".implode("%' OR $signature LIKE '%",$parts)."%'";
        return $having;
    }
    
    public function messageListGet( string $filter='', string $filter_handler='', string $filter_reason='', string $filter_date='' ){
        $where = $this->messageListFilterGet( $filter );
        $msg_list_msg="
            SELECT
                *,
                CONCAT(message_handler,' ',message_reason,' ',message_note,' ',message_recievers,' ',message_subject) signature
            FROM
                plugin_message_list
            WHERE
                message_handler LIKE '%$filter_handler%'
                AND message_reason LIKE '%$filter_reason%'
                AND created_at LIKE '%$filter_date%'
                AND $where
            ";
        return $this->get_list($msg_list_msg);
    }
    
    public function messageGroupListGet( string $filter ){
        $where = $this->messageListFilterGet( $filter );
        $msg_list_msg="
            SELECT
                message_handler,
                message_reason,
                SUBSTRING(created_at, 1, 13) group_created_at,
                created_at,
                COUNT(*) message_count
            FROM
                plugin_message_list
            WHERE
                $where
            GROUP BY
                CONCAT(message_handler,message_reason,SUBSTRING(created_at, 1, 13))
            ";
        return $this->get_list($msg_list_msg);        
    }
    
    public function messageGet( int $message_id ){
        $sql="
            SELECT 
                message_handler, 
                message_reason, 
                message_note, 
                message_recievers, 
                message_subject, 
                message_body
            FROM
                plugin_message_list
            WHERE 
                message_id = $message_id    
            ";
        return $this->get_row($sql);        
    }
    
    public function settingsGet(){
        $sql="
            SELECT 
                plugin_settings
            FROM
                plugin_list
            WHERE
                plugin_system_name = 'MailingManager'
            ";
        return [
            'settings' => json_decode($this->get_row($sql)->plugin_settings, true),
            'staff_list' => $this->Hub->load_model("Pref")->getStaffList(),
            'editor_markup' => $this->default_markup_values
        ];        
    }
    
    
    public function recieverGet( int $reciever_list_id ){
        if($reciever_list_id == '-1'){
            return [];
        }
        $where = $this->recieverGetComposeWhere($reciever_list_id);
        $sql="
            SELECT 
                label,
                path,
                company_email as email,
                company_mobile as sms
            FROM
                companies_list
                    JOIN
                companies_tree USING(branch_id)
            WHERE $where
            ORDER BY label ASC";
        return $this->get_list($sql);       
    }
    
    public function recieverGetComposeWhere( int $reciever_list_id ){
        $this->Hub->set_level(2);
        $settings = $this->settingsGet()['settings'];
        if(empty($settings)){
            return 0;
        }
        $reciever_list_settings = $settings['reciever_list'][$reciever_list_id];
        if(!empty($settings['reciever_blacklist'])){
            $reciever_blacklist = $settings['reciever_blacklist'];
        }
        $assigned_path=  $this->Hub->svar('user_assigned_path');
        $user_level=     $this->Hub->svar('user_level');
        $or_case=[];
        $and_case=[];
        //$and_case[]=" level<= $user_level";
        if( $assigned_path ){
            $and_case[]=" path LIKE '%".str_replace(",", "%' OR path LIKE '%", $assigned_path)."%'";
        }
        if( !empty($reciever_list_settings['subject_path_include']) ){
            $or_case[]=" path LIKE '%".str_replace(",", "%' OR path LIKE '%", $reciever_list_settings['subject_path_include'])."%'";
        }
        if( !empty($reciever_list_settings['subject_path_exclude']) ){
            $and_case[]=" path NOT LIKE '%".str_replace(",", "%' AND path NOT LIKE '%", $reciever_list_settings['subject_path_exclude'])."%'";
        }
        if( !empty($reciever_blacklist) ){
            $blacklist = [];
            $exploded_blacklist = explode(',',$reciever_blacklist);
            foreach ($exploded_blacklist as $item){
                $blacklist[] = "path NOT LIKE '%".trim($item)."%'";
            }
            $and_case[]=" path NOT IN ( ".implode(',',$blacklist)." )";
        }
        if( $reciever_list_settings['subject_manager_include']){
            $or_case[]=" manager_id IN (".implode(',',$reciever_list_settings['subject_manager_include']).")";
        }
        if( $reciever_list_settings['subject_manager_exclude']){
            $and_case[]=" manager_id NOT IN (".implode(',', $reciever_list_settings['subject_manager_exclude']).")";
        }
        $where="";
        if( count($or_case) ){
            $where="(".implode(' OR ',$or_case).")";
        }
        if( count($and_case) ){
            if( count($or_case) ){
                $where.=" AND ";
            }
            $where.=implode(' AND ', $and_case);
        }
        return $where?$where." AND level<= $user_level":0;       
    }
    
    
    public function recieverSaveList( string $settings ){
        $this->settings = $settings;
        return $this->pluginSettingsFlush();    
    }
    
    private function passiveCompanyGet($reciever){
        $sql = "
            SELECT 
                cl.*, ct.label as company_label
            FROM 
                companies_list cl
                    JOIN 
                companies_tree ct ON (cl.branch_id = ct.branch_id)
            WHERE 
                company_email = '$reciever' OR company_mobile = '$reciever'
            ";
        return $this->get_row($sql);
    }
}
