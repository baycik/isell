<?php

/* Group Name: Работа с клиентами
 * User Level: 2
 * Plugin Name: Календарь платежей
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: Календарь платежей 
 * Author: baycik 2019
 * Author URI: http://isellsoft.com
 */


class DebtManager extends Catalog {
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
    
    

}
