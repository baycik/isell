<?php
/**
* Codeigniter Plugin System
*
* A hook based plugin library for adding in Wordpress like plugin functionality.
*
* NOTICE OF LICENSE
*
* Licensed under the Open Software License version 3.0
*
* This source file is subject to the Open Software License (OSL 3.0) that is
* bundled with this package in the files license.txt / license.rst. It is
* also available through the world wide web at this URL:
* http://opensource.org/licenses/OSL-3.0
* If you did not receive a copy of the license and are unable to obtain it
* through the world wide web, please send an email to
* licensing@ellislab.com so we can send you a copy immediately.
*
* @package CI Plugin System
* @author Baycik, Dwayne Charrington
* @copyright Copyright (c) 2012 - 2016
* @license http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
* @since Version 1.0
*/
  
class Plugins {
    // Codeigniter instance
    protected $_ci;
    
    // Instance of this class
    public static $instance;
    
    // Action statics
    public static $actions;
    public static $current_action;
    public static $run_actions;

    //Array with already loaded plugin domains
    public static $loadedDomains=[];
    public static $loadedPlugins=[];
    
    // Directory
    private $plugins_dir='application/plugins/';
    
    public function __construct($params = array()){
        // Codeigniter instance
        $this->_ci =& get_instance();
        $this->_ci->load->database();
    }
    /**
    * Instance
    * The instance of this plugin class
    * 
    */
    public static function instance(){
        if (!self::$instance){
            self::$instance = new Plugins();
        }
        return self::$instance;
    }
    /*
     * load plugins from the same domain as action
     */
    private function lazy_load_plugins( $action_name ){
	$domain=substr($action_name,0, strpos($action_name, "_") + 1);
	
	
	//$domain=array_shift(explode('_',$action_name));
	if( in_array($domain,self::$loadedDomains) ){
	    return true;
	}
	self::$loadedDomains[]=$domain;
	$this->include_activated_plugins($domain);
    }
    
    /**
    * Get Activated Plugins from one domain
    */
    private function include_activated_plugins( $domain ){
        // Only plugins in the database are active ones
	$plugins = $this->_ci->db->query("SELECT pref_name FROM pref_list WHERE pref_name LIKE 'plugin_{$domain}%'");
        // If we have activated plugins
        if ($plugins && $plugins->num_rows() > 0){
            // For every plugin, include it
            foreach ($plugins->result_array() AS $plugin){
		$plugin_system_name=substr($plugin['pref_name'], strpos($plugin['pref_name'], "_") + 1);
		$this->include_plugin($plugin_system_name);
            }
        } else {
            return true;
        }    
    }
    /*
     * Include plugin and headers only if its not loaded already
     */
    private function include_plugin( $plugin_system_name ){
	$plugin_main_file=$this->plugins_dir.$plugin_system_name."/".$plugin_system_name.".php";
	if( !isset(self::$loadedPlugins[$plugin_system_name]) && file_exists($plugin_main_file) ){
	    self::$loadedPlugins[$plugin_system_name] = 'loaded';
	    load_class('Model', 'core');
	    include_once $plugin_main_file;
	    try{
		$plugin_class_instance=new $plugin_system_name;
		
		//do we really need this?
		if( isset($this->Base) ){
		    $plugin_class_instance->Base=$this->Base;
		}
		return $plugin_class_instance;
	    } catch (Exception $ex) {
		return false;
	    }
	}
    }
    /*
     * Get included files headers
     */
    private function get_plugin_headers( $plugin_system_name ){
	$plugin_data = file_get_contents($this->plugins_dir.$plugin_system_name."/".$plugin_system_name.".php"); // Load the plugin we want
	preg_match ('|Plugin Name:(.*)$|mi', $plugin_data, $name);
	preg_match ('|Plugin URI:(.*)$|mi', $plugin_data, $uri);
	preg_match ('|Version:(.*)|i', $plugin_data, $version);
	preg_match ('|Description:(.*)$|mi', $plugin_data, $description);
	preg_match ('|Author:(.*)$|mi', $plugin_data, $author_name);
	preg_match ('|Author URI:(.*)$|mi', $plugin_data, $author_uri);
	return [
	    'plugin_name'=>isset($name[1])?$name[1]:null,
	    'plugin_uri'=>isset($uri[1])?$uri[1]:null,
	    'plugin_version'=>isset($version[1])?$version[1]:null,
	    'plugin_description'=>isset($description[1])?$description[1]:null,
	    'plugin_author'=>isset($author_name[1])?$author_name[1]:null,
	    'plugin_author_uri'=>isset($author_uri[1])?$author_uri[1]:null
	];
    }
    /*
     * Do actions but first load needed plugins
     */
    public function do_action( $action_name, $arguments=NULL ){
	$this->lazy_load_plugins( $action_name );
        if( !self::$actions[$action_name] ){
            return [];
        }
	$returned_values=[];
	foreach (self::$actions[$action_name] as $callback) {
            $returned_values[]=$callback($action_name, $arguments);
        }
	return $returned_values;
    }
    /**
    * Add Action
    *
    * Add a new hook trigger action
    * 
    * @param mixed $name
    * @param mixed $function
    * @param mixed $priority
    */
    public function add_action( $action_name, $callback ){
        if ( !isset(self::$actions[$action_name]) ) {
            self::$actions[$action_name] = array();
        }
        self::$actions[$action_name][] = $callback;	
    }
    
    public function call_method($plugin_name, $plugin_method, $plugin_method_args){
	$Plugin=$this->include_plugin($plugin_name);
	if( method_exists($Plugin, $plugin_method) ){
	    return call_user_func_array([$Plugin,$plugin_method], $plugin_method_args);
	}
	show_error('X-isell--plugin-error: method_not_found!', 500);
	return null;
    }
}



/**
* Add a new action hook
* 
* @param mixed $name
* @param mixed $function
* @param mixed $priority
*/
function add_action($name, $function, $priority=10){
    return Plugins::instance()->add_action($name, $function, $priority);
}
/**
* Run an action
* 
* @param mixed $name
* @param mixed $arguments
* @return mixed
*/
function do_action($name, $arguments = ""){
    return Plugins::instance()->do_action($name, $arguments);
}

//print_r(do_action('stock_add_tab'));