<?php
/* Group Name: Документ
 * User Level: 1
 * Plugin Name: История документа
 * Plugin URI: http://isellsoft.net
 * Version: 1.0
 * Description: История изменений в документе
 * Author: baycik 2021
 * Author URI: http://isellsoft.net
 */

class DocumentHistory extends PluginBase{
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
        $Events=$this->Hub->load_model('Events');
        $Events->Topic('documentEntryChanged')->subscribe('DocumentHistory','onEntryChanged');
    }
    
    /**
     * Do action when plugin deactivated
     */
    public function deactivate(){
        $Events=$this->Hub->load_model('Events');
        $Events->Topic('documentEntryChanged')->unsubscribe('DocumentHistory','onEntryChanged');
    }
    
    public function onEntryChanged( $doc_entry_id, $doc, $arguments ){
        $user_label=$this->Hub->svar('user_sign');
        $history_sql="
            INSERT INTO plugin_doc_history_list (active_company_label,passive_company_label,user_label,entry_type,entry_doc_id,entry_doc_num,entry_change_qty,entry_change_name)
            SELECT
                acl.company_name active_company_label,
                pcl.company_name passive_company_label,
                '$user_label' user_label,
                IF(doc_type=1,'Расход','Приход') entry_type,
                dl.doc_id entry_doc_id,
                dl.doc_num entry_doc_num,
                de.product_quantity-SUM(pdhl.entry_change_qty) entry_change_qty,
                CONCAT(pl.product_code,' ',pl.ru) entry_change_name
            FROM
                document_entries de
                    JOIN
                document_list dl USING(doc_id)
                    JOIN
                companies_list pcl ON passive_company_id=pcl.company_id
                    JOIN
                companies_list acl ON active_company_id=acl.company_id
                    JOIN
                prod_list pl USING(product_code)
                    LEFT JOIN
                plugin_doc_history_list pdhl ON pdhl.entry_doc_id=dl.doc_id
            WHERE
                de.doc_entry_id='$doc_entry_id'
            GROUP BY dl.doc_id
            ";
        $this->query($history_sql);
    }

}