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
            $this->settings = [
                'reciever_list' => new stdClass()
            ];
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

    public function settingsUpdate(array $settings){
        $this->settings = $settings;
        $this->pluginSettingsFlush();
        return true;
    }    
    /*
     * Message CRUD functions
     */
    public function messageCreate( array $message ){
        $user_id=$this->Hub->svar('user_id');
        $message_record=[
            'message_handler'=>$message['message_handler'],
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
    public function messageUpdate( int $message_id, array $message ){
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
        return $this->update('plugin_message_list',$message_record,['message_id'=>$message_id]);        
    }
    
    public function messageDelete( $message_id ){
        return $this->delete('plugin_message_list',['message_id'=>$message_id]);  
    }
    
    private function messageRenderTpl( string $message_template, $context ){
        foreach ($context as $key=>$val){
            $message_template = str_replace('{{'.$key.'}}', $val, $message_template);
        }
        return $message_template;
    }
    
    
    private $default_markup_values = [
        'company_name' => 'Ваша компания',
        'company_person' => 'Клиент нашей компании',
        'company_director' => 'Клиент нашей компании',
        'company_email' => 'Ваш электронный адрес',
        'company_web' => 'Адрес вашего сайта',
        'company_mobile' => 'Ваш номер телефона',
        'company_address' => 'Ваш физический адрес'
    ];
    
    /*
     * Message batches CRUD functions
     */
    public function messageBatchCreate( array $message_batch ){
        $batch_context = $this->messageBatchContextGet( $message_batch['reciever_list_id'] );
        $message=[];
        foreach($batch_context as $context){
            if( empty($context->company_email) && empty($context->company_mobile) ){
                continue;
            }
            $message['message_recievers'] = "{$context->company_email} | {$context->company_mobile}";
            $message['message_handler'] = $message_batch['handler'];
            $message['message_reason'] = $message_batch['subject'];
            $message['message_subject'] = $this->messageRenderTpl($message_batch['subject'],$context);
            $message['message_note'] = '';
            $message['message_body'] = $this->messageRenderTpl($message_batch['body'],$context);
            $this->messageCreate($message);
        }
        return true;
    }
    private function messageBatchContextGet( string $reciever_list_id ){
        $where = $this->recieverListFilterGet($reciever_list_id);
        $sql="
            SELECT 
                label,
                path,
                company_name,
                company_person,
                company_director,
                company_address,
                company_email,
                company_mobile
            FROM
                companies_list
                    JOIN
                companies_tree USING(branch_id)
            WHERE $where
            ORDER BY label ASC";
        return $this->get_list($sql);       
    }

    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    public function messageBatchDelete( string $message_batch_label ){
        return $this->delete("plugin_message_list",['message_batch_label'=>$message_batch_label]);
    }
    
    private function messageBatchRecieversGet( $message ){
        $reciever_list = [];
        if((int) $message['message_reciever_list']){
            $reciever_list += array_merge($reciever_list,$this->messageBatchRecieverListGet($message['message_reciever_list']));
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
    
    public function messageBatchListGet( string $filter=null ){
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
    
    private function recieverListFilterGet( string $reciever_list_id ){
        $this->Hub->set_level(2);
        $settings = $this->settingsGet();
        if( empty($settings['settings']['reciever_list'][$reciever_list_id]) ){
            return 0;
        }
        $reciever_list_settings=$settings['settings']['reciever_list'][$reciever_list_id];
        $assigned_path=  $this->Hub->svar('user_assigned_path');
        $user_level=     $this->Hub->svar('user_level');
        $or_case=[];
        $and_case=[];
        if( $assigned_path ){
            $and_case[]=" path LIKE '%".str_replace(",", "%' OR path LIKE '%", $assigned_path)."%'";
        }
        if( !empty($reciever_list_settings['subject_path_include']) ){
            $or_case[]=" path LIKE '%".str_replace(",", "%' OR path LIKE '%", $reciever_list_settings['subject_path_include'])."%'";
        }
        if( !empty($reciever_list_settings['subject_path_exclude']) ){
            $and_case[]=" path NOT LIKE '%".str_replace(",", "%' AND path NOT LIKE '%", $reciever_list_settings['subject_path_exclude'])."%'";
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
    
    
    public function recieverListFetch(string $reciever_list_id, int $offset=0,int $limit=30,string $sortby='label',string $sortdir='ASC',array $filter){
        $this->Hub->set_level(3);
        $having=$this->makeFilter($filter);
        $where=$this->recieverListFilterGet($reciever_list_id);
        $sql="
            SELECT 
                label,
                company_name,
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

    
//    private function passiveCompanyGet($reciever){
//        $sql = "
//            SELECT 
//                cl.*, ct.label as company_label
//            FROM 
//                companies_list cl
//                    JOIN 
//                companies_tree ct ON (cl.branch_id = ct.branch_id)
//            WHERE 
//                company_email = '$reciever' OR company_mobile = '$reciever'
//            ";
//        return $this->get_row($sql);
//    }
}
