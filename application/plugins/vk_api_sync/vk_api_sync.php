<?php
/* Group Name: 1Синхронизация
 * User Level: 2
 * Plugin Name: VK market sync
 * Plugin URI: http://isellsoft.com
 * Version: 0.1
 * Description: Tool for syncing with VK
 * Author: baycik 2016
 * Author URI: http://isellsoft.com
 */
include 'models/Catalog.php';
class vk_api_sync extends Catalog{
    function __construct(){
	add_action('settings_form', function(){
	    return $this->get_admin_settings();
	});
    }
    private function get_admin_settings(){
	return [
	    ['name'=>'client_id','text'=>'номер приложения'],
	    ['name'=>'secret_key','text'=>'Секрет'],
	    ['name'=>'user_id','text'=>'номер пользователя'],
	    ['name'=>'scope','text'=>'права доступа'],
	    ['name'=>'access_token','text'=>'access_token'],
	    ['text'=>"<b>Hello</b>"]
	];
    }
}