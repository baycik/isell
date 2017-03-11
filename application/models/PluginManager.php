<?php
require_once 'Catalog.php';
class PluginManager extends Catalog{
    private $plugin_folder='application/plugins/';
    
    public $listFetch=[];
    public function listFetch(){
	$plugins_folders=$this->scanFolder($this->plugin_folder);
	$plugins=[];
	foreach($plugins_folders as $plugin_folder){
	    if( strpos($plugin_folder, 'Reports')!==false ){
		continue;
	    }
	    $headers=$this->get_plugin_headers($plugin_folder);
	    if( $headers['user_level']<=$this->Hub->svar('user_level') ){
		$plugins[]=$headers;
	    }
	}
	function sort_bygroup($a,$b){
	    if( !isset($a['group_name']) || !isset($b['group_name']) || $a['group_name']==$b['group_name'] ){
		return 0;
	    }
	    return ($a['group_name']>$b['group_name'])?1:-1;
	}
	usort($plugins,'sort_bygroup');
	return $plugins;
    }
    private function scanFolder( $path ){
	$this->Hub->set_level(1);
	$files = array_diff(scandir($path), array('.', '..'));
	arsort($files);
	return array_values($files);	
    }
    private function get_plugin_headers( $plugin_system_name ){
	$plugin_data = file_get_contents($this->plugin_folder.$plugin_system_name."/models/".$plugin_system_name.".php",true); // Load the plugin we want
	preg_match ('|Group Name:(.*)$|mi', $plugin_data, $group_name);
	preg_match ('|User Level:(.*)$|mi', $plugin_data, $user_level);
	preg_match ('|Plugin Name:(.*)$|mi', $plugin_data, $name);
	preg_match ('|Plugin URI:(.*)$|mi', $plugin_data, $uri);
	preg_match ('|Version:(.*)|i', $plugin_data, $version);
	preg_match ('|Description:(.*)$|mi', $plugin_data, $description);
	preg_match ('|Author:(.*)$|mi', $plugin_data, $author_name);
	preg_match ('|Author URI:(.*)$|mi', $plugin_data, $author_uri);
	return [
	    'system_name'=>$plugin_system_name,
	    'group_name'=>isset($group_name[1])?trim($group_name[1]):null,
	    'user_level'=>isset($user_level[1])?trim($user_level[1]):2,
	    'plugin_name'=>isset($name[1])?trim($name[1]):null,
	    'plugin_uri'=>isset($uri[1])?trim($uri[1]):null,
	    'plugin_version'=>isset($version[1])?trim($version[1]):null,
	    'plugin_description'=>isset($description[1])?trim($description[1]):null,
	    'plugin_author'=>isset($author_name[1])?trim($author_name[1]):null,
	    'plugin_author_uri'=>isset($author_uri[1])?trim($author_uri[1]):null
	];
    }
    public function settingsDataFetch($plugin_system_name){
	return json_decode($this->get_value("SELECT plugin_settings FROM plugin_list WHERE plugin_system_name='$plugin_system_name'"));
    }
    public function settingsAllFetch(){
	$plugin_system_name=$this->request('system_name');
	$settings_file=$this->plugin_folder.$plugin_system_name."/settings.html";
	$settings_html=file_exists($settings_file)?file_get_contents($settings_file):'';
	$settings_data=$this->settingsDataFetch($plugin_system_name);
	return ['html'=>$settings_html,'data'=>$settings_data];
    }
    public function settingsSave(){
	$plugin_system_name=$this->request('plugin_system_name');
	$settings_json=$this->request('settings_json');
	$sql="INSERT INTO 
		plugin_list 
	    SET 
		plugin_system_name='$plugin_system_name',
		plugin_settings='$settings_json'
	    ON DUPLICATE KEY UPDATE
		plugin_settings='$settings_json'";
	$this->query($sql);
	return $this->db->affected_rows();
    }
}
