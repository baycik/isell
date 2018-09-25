<?php
    define("BAY_STORAGE_FOLDER",'../storage/modificator_backup');
class PluginManager extends Catalog{
    public $min_level=1;
    private $plugin_folder='application/plugins/';
    
    public $listFetch=['mode'=>'string'];
    public function listFetch($mode=null){
	$plugins_folders=$this->scanFolder($this->plugin_folder);
	$plugins=[];
	foreach($plugins_folders as $plugin_folder){
	    if( $mode=='ReportsOnly' && strpos($plugin_folder, 'Reports')===false ){
		continue;
	    }
	    $headers=$this->get_plugin_headers($plugin_folder);
	    if( isset($headers['user_level']) && $headers['user_level']<=$this->Hub->svar('user_level') ){
                if( !isset($headers['plugin_name']) ){
                    $headers['plugin_name']=$plugin_folder;
                }
		$plugins[]=$headers;
	    }
	}
	function sort_bygroup($a,$b){
	    return strcmp($a['group_name'],$b['group_name']);
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
    protected function get_plugin_headers( $plugin_system_name ){
	$path=$this->plugin_folder.$plugin_system_name."/models/".$plugin_system_name.".php";
	if( !file_exists($path) ){
	    $path=$this->plugin_folder.$plugin_system_name."/".$plugin_system_name.".php";// Support for older plugins
	    if( !file_exists($path) ){
		return [];
	    }
	}
	$plugin_data = file_get_contents($path,true);
	
	preg_match ('|Group Name:(.*)$|mi', $plugin_data, $group_name);
	preg_match ('|User Level:(.*)$|mi', $plugin_data, $user_level);
	preg_match ('|Plugin Name:(.*)$|mi', $plugin_data, $name);
	preg_match ('|Plugin URI:(.*)$|mi', $plugin_data, $uri);
	preg_match ('|Trigger before:(.*)$|mi', $plugin_data, $trigger_before);
	preg_match ('|Trigger after:(.*)$|mi', $plugin_data, $trigger_after);
	preg_match ('|Version:(.*)|i', $plugin_data, $version);
	preg_match ('|Description:(.*)$|mi', $plugin_data, $description);
	preg_match ('|Author:(.*)$|mi', $plugin_data, $author_name);
	preg_match ('|Author URI:(.*)$|mi', $plugin_data, $author_uri);
	preg_match ('|Plugin Template:(.*)$|mi', $plugin_data, $plugin_template);
	return [
	    'system_name'=>$plugin_system_name,
	    'trigger_before'=>isset($trigger_before[1])?trim($trigger_before[1]):$plugin_system_name,
	    'trigger_after'=>isset($trigger_after[1])?trim($trigger_after[1]):'',
	    'group_name'=>isset($group_name[1])?trim($group_name[1]):null,
	    'user_level'=>isset($user_level[1])?trim($user_level[1]):2,
	    'plugin_name'=>isset($name[1])?trim($name[1]):null,
	    'plugin_uri'=>isset($uri[1])?trim($uri[1]):null,
	    'plugin_version'=>isset($version[1])?trim($version[1]):null,
	    'plugin_description'=>isset($description[1])?trim($description[1]):null,
	    'plugin_author'=>isset($author_name[1])?trim($author_name[1]):null,
	    'plugin_author_uri'=>isset($author_uri[1])?trim($author_uri[1]):null,
	    'plugin_template'=>isset($plugin_template[1])?trim($plugin_template[1]):null
	];
    }
    public $settingsDataFetch=[];
    public function settingsDataFetch($plugin_system_name){
	$settings_data=$this->get_row("SELECT * FROM plugin_list WHERE plugin_system_name='$plugin_system_name'");
	if( $settings_data ){
	    $settings_data->plugin_settings=  $settings_data->plugin_settings?json_decode($settings_data->plugin_settings):new stdClass;
	} else {
	    $settings_data=new stdClass;
	}
	return $settings_data;
    }
    public $settingsAllFetch=['system_name'=>'string'];
    public function settingsAllFetch($plugin_system_name){
	$settings_data=$this->settingsDataFetch($plugin_system_name);
	
	$settings_file=$this->plugin_folder.$plugin_system_name."/settings.html";
	$settings_data->html=file_exists($settings_file)?file_get_contents($settings_file):'';
	return $settings_data;
    }
    
    public $settingsSave=['plugin_system_name'=>'string','settings_json'=>'string'];
    public function settingsSave($plugin_system_name,$settings_json){
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
    
    public $activate_plugin=['plugin_system_name'=>'string'];
    public function activate_plugin($plugin_system_name){
	$this->Hub->set_level(4);
	$data=[
	    'plugin_system_name'=>$plugin_system_name,
	    'is_activated'=>1
	];
	$ok=$this->pluginUpdate($plugin_system_name,$data);
	$this->pluginInitTriggers();
	$this->plugin_do($plugin_system_name, 'activate');
        $this->mod_scan();
	return $ok;
    }
    
    public $deactivate_plugin=['plugin_system_name'=>'string'];
    public function deactivate_plugin($plugin_system_name){
	$this->Hub->set_level(4);
	$data=[
	    'plugin_system_name'=>$plugin_system_name,
	    'is_activated'=>0
	];
	$ok=$this->pluginUpdate($plugin_system_name,$data);
	$this->pluginInitTriggers();
	$this->plugin_do($plugin_system_name, 'deactivate');
        $this->mod_scan();
	return $ok;
    }
    
    public $install_plugin=['plugin_system_name'=>'string'];
    public function install_plugin($plugin_system_name){
	$this->Hub->set_level(4);
	$headers=$this->get_plugin_headers( $plugin_system_name );
	$sql="REPLACE INTO 
		plugin_list 
	    SET
		plugin_system_name='$plugin_system_name',
		trigger_before='{$headers['trigger_before']}',
		is_installed=1,
		is_activated=0";
	$ok=$this->query($sql);
	$this->pluginInitTriggers();
	$this->plugin_do($plugin_system_name, 'install');
	return $ok;
    }
    
    public $uninstall_plugin=['plugin_system_name'=>'string'];
    public function uninstall_plugin($plugin_system_name){
	$this->Hub->set_level(4);
	$ok=$this->delete('plugin_list',['plugin_system_name'=>$plugin_system_name]);
	$this->plugin_do($plugin_system_name, 'uninstall');
	$this->pluginInitTriggers();
	return $ok;
    }
    
    public function pluginInitTriggers(){
	$before=[];
	$after=[];
	$sql="SELECT 
		plugin_system_name,trigger_before
	    FROM 
		plugin_list 
	    WHERE 
		is_activated AND (trigger_before IS NOT NULL)";
	$active_plugin_triggers=$this->db->query($sql);
	if($active_plugin_triggers){
	    foreach( $active_plugin_triggers->result() as $trigger ){
		if( $trigger->trigger_before ){
		    $this->pluginParseTriggers($before, $trigger->trigger_before, $trigger->plugin_system_name);
		}
//		if( $trigger->trigger_after ){
//		    $this->pluginParseTriggers($after, $trigger->trigger_after, $trigger->plugin_system_name);
//		}
	    }
	    $active_plugin_triggers->free_result();
	}
	$this->Hub->svar('trigger_before',$before);
	//$this->Hub->svar('trigger_after',$after);
    }
    private function pluginParseTriggers( &$registry, $triggers, $plugin_system_name ){
	$trigger_list=explode(',',$triggers);
	foreach($trigger_list as $trigger){
	    if( !isset($registry[$trigger]) ){
		$registry[$trigger]=[];
	    }
	    $registry[$trigger]=$plugin_system_name;
	}
    }
    private function pluginUpdate($plugin_system_name,$data){
	return $this->update('plugin_list',$data,['plugin_system_name'=>$plugin_system_name]);
    }
    
    public function plugin_do($plugin_system_name, $plugin_method, $plugin_method_args=[]){
	$path=$this->plugin_folder.$plugin_system_name."/models/".$plugin_system_name.".php";
	if( !file_exists($path) ){
	    $path=$this->plugin_folder.$plugin_system_name."/".$plugin_system_name.".php";// Support for older plugins
	    if( !file_exists($path) ){
		return [];
	    }
	}
	
	require_once $path;
	$Plugin=$this->Hub->load_model($plugin_system_name);
	if( method_exists($Plugin, $plugin_method) ){
	    return call_user_func_array([$Plugin,$plugin_method], $plugin_method_args);
	}
	return null;
    }
    
    public function getActivePlugins(){
        $sql="SELECT 
            plugin_system_name
        FROM 
            plugin_list 
        WHERE 
            is_activated";
	return ($this->get_list($sql));
    }
     
//    public $mod_reset = [];
//    public function mod_reset2 (){
//        if (!is_dir(BAY_STORAGE_FOLDER)){
//            mkdir(BAY_STORAGE_FOLDER);
//        }
//        foreach (glob(BAY_STORAGE_FOLDER.'/'.$this->backup_prefix.'*') as $filename) {
//            if (!is_file($filename)){
//                continue;
//            }
//            $backup_file_info = pathinfo($filename);
//            $backup_file_name = $backup_file_info['basename'].'<br/>';
//            $backup_file_path = $backup_file_info['dirname'].'<br/>';
//            $view_file_name = substr($backup_file_name, 3);
//            $view_file_path = substr($backup_file_path, strlen(BAY_STORAGE_FOLDER));
//            copy($backup_file_path.'/'.$backup_file_name , APPPATH.$view_file_path.'/'.$view_file_name);
//        }
//        $this->mod_scan();
//        return true;
//    }
    
    public $mod_reset=[];
    public function mod_reset($path='') {
        $backup_folder='../storage/modificator_backup/';
        $app_folder=APPPATH;
        if (!file_exists($backup_folder.$path)){
            return true;
        }
	$files = array_diff(scandir($backup_folder.$path), array('.', '..'));
	foreach ($files as $file) {
	    if(is_dir($backup_folder."$path/$file")){
                $this->mod_reset("$path/$file");
            } else if (is_file ($backup_folder."$path/$file")) {
                copy($backup_folder."$path/$file", $app_folder."$path/$file");
            }
	}
    }

    public $mod_scan=[];
    public function mod_scan (){
        $this->mod_reset();
        $array = $this->getActivePlugins();
        foreach ($array as $row) {
            $plugin_folder = APPPATH.'plugins/'.$row->plugin_system_name;
            $this->mod_modificate($plugin_folder.'/plugmod.php');
        }
    }
    
    public function mod_modificate ($plugmod){
        if( !file_exists($plugmod) ){
            return true;
        }
        $filename=[];
        $search = [];
        $replace = [];
        $before = [];
        $after = [];
        include $plugmod;
        if ( is_array($filename) ){
            foreach ($filename as $row=>$val){
                $replace_ex=isset($replace[$row])?$replace[$row]:'';
                $before_ex=isset($before[$row])?$before[$row]:'';
                $after_ex=isset($after[$row])?$after[$row]:'';
                $this->mod_submodificate($filename[$row], $search[$row], $replace_ex, $before_ex, $after_ex);
            }
        } else {
            $this->mod_submodificate($filename, $search, $replace, $before, $after);
        }
        
    }
    
    public function mod_submodificate($filename='', $search='', $replace='', $before='', $after=''){
        $this->mod_backup_file($filename);
        $altered_html = '';
        $source_html = file_get_contents(APPPATH.$filename);  
        if ($replace){
            $trans = array($search => $replace, $replace => $search);
            $altered_html = strtr($source_html, $trans);
        }else if ($before){
            $position = strpos($source_html, $search);
            $altered_html = substr_replace($source_html, $before, $position, 0);
        }else if ($after){
            $position = strpos($source_html, $search)+ strlen($search);
            $altered_html = substr_replace($source_html, $after, $position, 0);
        }
        file_put_contents(APPPATH.$filename, $altered_html);
    }
    
    public function mod_backup_file ($file){
        $backup_folder='../storage/modificator_backup/';
        $app_folder=APPPATH;
        if ( !file_exists (dirname($backup_folder.$file)) ){
            mkdir( dirname($backup_folder.$file), 0777, true);
        }
        if ( !file_exists ($backup_folder.$file) ){
            copy( $app_folder.$file, $backup_folder.$file );
        }
    }
    
    public $mod_clear_backup = [];
    public function mod_clear_backup (){
        $Storage=$this->Hub->load_model('Storage');
        $Storage->dir_remove('modificator_backup');
    }
}


