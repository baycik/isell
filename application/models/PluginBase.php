<?php
abstract class PluginBase extends Catalog{
    protected $plugin_data=null;
    protected $plugin_settings=null;
    protected $plugin_system_name=null;
            
    function __construct(){
        parent::__construct();
        $this->plugin_system_name=get_class($this);
    }

    protected function pluginSettingsLoad() {
        $sql = "
            SELECT
                plugin_settings,
                plugin_json_data
            FROM
                plugin_list
            WHERE plugin_system_name = '{$this->plugin_system_name}'
            ";
        $row = $this->get_row($sql);
        $this->plugin_settings=json_decode( str_replace("\n", '\n', $row->plugin_settings) );
        $this->plugin_data=json_decode( str_replace("\n", '\n', $row->plugin_json_data) );
    }
    
    protected function pluginSettingsFlush() {
        $plugin_data=$this->plugin_data;
        $this->pluginSettingsLoad();
        $this->plugin_data=(object) array_merge((array) $this->plugin_data, (array) $plugin_data);
        $encoded_settings = addslashes(json_encode($this->plugin_settings, JSON_UNESCAPED_UNICODE ));
        $encoded_data =     addslashes(json_encode($this->plugin_data));
        $sql = "
            UPDATE
                plugin_list
            SET 
                plugin_settings = '$encoded_settings',
                plugin_json_data = '$encoded_data'
            WHERE plugin_system_name = '{$this->plugin_system_name}'
            ";
        $this->query($sql);
    }
}