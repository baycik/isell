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
    }

    private function pluginSettingsFlush() {
        $settings=$this->settings;
        $plugin_data=$this->plugin_data;
        $this->pluginSettingsLoad();
        $plugin_data=(object) array_merge((array) $this->plugin_data, (array) $plugin_data);
        $encoded_settings = json_encode($settings);
        $encoded_data =     json_encode($plugin_data);
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
        $user_id=$this->Hub->svar('user_id');
        return $this->settings->users[$user_id];
    }
    
    public function settingsUpdate(){
        $user_id=$this->Hub->svar('user_id');
        $this->pluginSettingsLoad();
        if( $this->settings->users??0 ){
            $this->settings->users=[];
        }
        $this->settings->users[$user_id];
        $this->pluginSettingsFlush();
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    public function messageCreate( string $handler, object $message ){
        $user_id=$this->Hub->svar('user_id');
        $message_record=[
            'message_handler'=>$handler,
            'message_status'=>'created',
            'message_note'=>$message->note,
            'message_recievers'=>$message->recievers,
            'message_subject'=>$message->subject,
            'message_body'=>$message->body,
            'created_by'=>$user_id,
            'modified_by'=>$user_id
        ];
        return $this->create('plugin_message_list',$message_record);
    }
    
    

}
