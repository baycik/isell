<?php
/* Group Name: Test
 * User Level: 1
 * Plugin Name: Sample plugin
 * Plugin URI: http://isellsoft.net
 * Version: 1.0
 * Description: Just template for cool plugin
 * Author: baycik 2021
 * Author URI: http://isellsoft.net
 */

class _SamplePlugin extends PluginBase{
    /*
     * Min user level to use this plugin
     */
    public $min_level=1;
    /**
     * plugin settings accessible from plugin admin panel. Stores as text JSON
     * @var object 
     */
    protected $plugin_settings;
    /**
     * plugin data for plugins need. Uses MySql JSON col type
     * @var object 
     */
    protected $plugin_data;
    
    
    function __construct() {
        parent::__construct();
        //Loads $this->plugin_settings and $this->plugin_data
        //To save changes use $this->pluginSettingsFlush();
        $this->pluginSettingsLoad();
    }
    
    /**
     * execute db installation script
     * @return bool
     */
    public function install(){
        $this->Hub->set_level(4);
	$install_file=__DIR__."/../install/install.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($install_file);
    }
    
    /**
     * execute db uninstallation script
     * @return bool
     */
    public function uninstall(){
        $this->Hub->set_level(4);
	$uninstall_file=__DIR__."/../install/uninstall.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($uninstall_file);
    }
    
    /**
     * Do action when plugin activated
     */
    public function activate(){
        
    }
    
    /**
     * Do action when plugin deactivated
     */
    public function deactivate(){
        
    }

}