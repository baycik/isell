<?php

class Maintain extends CI_Model {

    private $dirDbBackup = BAY_STORAGE."db_backup/";
    private $dirWork;

    function __construct() {
	$this->dirWork = realpath('.');
    }

    public $getCurrentVersionStamp = [];

    public function getCurrentVersionStamp() {
	if (file_exists($this->dirWork . '/.git')) {
	    return ['stamp' => date("Y-m-d\TH:i:s\Z", time()), 'branch' => $this->getGitBranch()];
	}
	return ['stamp' => date("Y-m-d\TH:i:s\Z", filemtime($this->dirWork)), 'branch' => $this->getGitBranch()];
    }

    private function getGitBranch() {
	$matches = [];
	preg_match("/\/(\w+).zip/", BAY_UPDATE_URL, $matches);
	return $matches[1];
    }

    public $updateInstall = [];

    public function updateInstall() {
	$this->updateConfigurator();
	$this->updateDb();
	return true;
    }
    
    public $updatePluginRefresh=[];
    public function updatePluginRefresh(){
        $PluginManager=$this->Hub->load_model('PluginManager');
	return $PluginManager->mod_scan();
	
    }
    
    private function updateDb(){
	$result=$this->db->query("SELECT pref_value FROM pref_list WHERE pref_name='db_applied_patches'");
	$db_applied_patches=$result->row()?$result->row()->pref_value:'';
	$directory = str_replace("\\", "/", $this->dirWork.'/install/db_update/');
	$patches = array_diff(scandir($directory), array('..', '.'));
	foreach ($patches as $patch) {
	    $patch_version = str_replace('.sql', '', $patch);
	    if (strpos($db_applied_patches, $patch_version) === false) {
		$this->backupImportExecute($directory . $patch);
		$db_applied_patches.="|" . $patch_version;
		$this->db->query("REPLACE pref_list SET pref_name='db_applied_patches', pref_value='$db_applied_patches'");
	    }
	}
    }

    private function updateConfigurator() {
	$this->xcopy($this->dirWork . '/install/configurator', realpath('../'));
    }

    private function xcopy($src, $dst) {
	$dir = opendir($src);
	!file_exists($dst) && mkdir($dst);
	while (false !== ($file = readdir($dir))) {
	    if ($file == '.' || $file == '..') {
		continue;
	    }
	    if (is_dir($src . '/' . $file)) {
		$this->xcopy($src . '/' . $file, $dst . '/' . $file);
	    } else {
		copy($src . '/' . $file, $dst . '/' . $file);
	    }
	}
	closedir($dir);
    }

    private function setupConf() {
	$conf_file = $this->dirWork . "/conf" . rand(1, 1000);
	$conf = '[client]
	    user="' . BAY_DB_USER . '"
	    password="' . BAY_DB_PASS . '"
	    default-character-set=utf8
	    ';
	file_put_contents($conf_file, $conf);
	return $conf_file;
    }

    public function backupImportExecute($file) {
	if( pathinfo($file, PATHINFO_EXTENSION )=='zip' ){
	    $file=pathinfo($file, PATHINFO_DIRNAME ).'/'.$this->backupDumpUnzip($file);
	}
	$output = [];
	$conf_file = $this->setupConf();
	$path_to_mysql = $this->db->query("SHOW VARIABLES LIKE 'basedir'")->row()->Value;
	exec("$path_to_mysql/bin/mysql --defaults-file=$conf_file " . BAY_DB_NAME . " <" . $file . " 2>&1", $output);
	unlink($conf_file);
	if (count($output)) {
	    file_put_contents($this->dirDbBackup . date('Y-m-d_H-i-s') . '-IMPORT.log', implode("\n", $output));
	    return false;
	}
	return true;
    }

    public $backupImport = ['filename' => 'string'];

    public function backupImport($file) {
	$this->Hub->set_level(4);
	if (file_exists($this->dirDbBackup . $file)) {
	    return $this->backupImportExecute($this->dirDbBackup . $file);
	}
	return false;
    }

    public $backupDump = [];

    public function backupDump() {
	$this->Hub->set_level(4);
	$path_to_mysql = $this->db->query("SHOW VARIABLES LIKE 'basedir'")->row()->Value;
	if (!file_exists($this->dirDbBackup)) {
	    mkdir($this->dirDbBackup,0777 ,true);
	}
	$output = [];
	$filename = $this->dirDbBackup . date('Ymd_His') . "_" . BAY_DB_NAME . '_BACKUP.sql';
	exec("$path_to_mysql/bin/mysqldump --user=" . BAY_DB_USER . " --password=" . BAY_DB_PASS . "  --default-character-set=utf8 --single-transaction=TRUE --routines --events  " . BAY_DB_NAME . " >" . $filename, $output);
	if (count($output)) {
	    file_put_contents($filename . '.log', implode("\n", $output));
	    return false;
	}
	return $filename;
    }
    
    private function backupDumpUnzip($zipfile){
	$filename=null;
	$zip = new ZipArchive() ;
	if ( $zip->open($zipfile) === true ) {
	    $first_filename = $zip->getNameIndex(0);
	    $extracted=$zip->extractTo($this->dirDbBackup,$first_filename);
	    if( $extracted ){
		$filename=$first_filename;
	    }
	}
	$zip->close();
	return $filename;
    }

    public function backupDumpZip($filename) {
	$zip = new ZipArchive;
	if ($zip->open("$filename.zip", ZipArchive::CREATE) === TRUE) {
	    $zip->addFile($filename);
	    $zip->close();
	    file_exists($filename) && unlink($filename);
	    return "$filename.zip";
	}
	return false;
    }
    
    public function backupDumpFtpUpload($filename){
	if( !file_exists($filename) ){
	    return false;
	}
	$this->load->library('ftp');
	$config['hostname'] = $this->Hub->pref('FTP_SERVER');
	$config['username'] = $this->Hub->pref('FTP_USER');
	$config['password'] = $this->Hub->pref('FTP_PASS');
	$config['debug']        = TRUE;
	$this->ftp->connect($config);
	$path_info=pathinfo($filename);
	$remote_name=  $path_info['basename'];
	$ok=$this->ftp->upload($filename, $remote_name, 'auto', 0600);
	$this->backupDumpFtpCleanup();
	$this->ftp->close();
	return $ok;
    }
    
    private function backupDumpFtpCleanup(){
	$list = $this->ftp->list_files();
	if( !$list ){
	    return false;
	}
	rsort($list);
	$old_files=array_slice ($list,10);
	foreach($old_files as $filename){
	    $this->ftp->delete_file($filename);
	}
    }

    public $backupList = [];

    public function backupList() {
	$this->Hub->set_level(4);
	$files = array_diff(scandir($this->dirDbBackup), array('.', '..'));
	arsort($files);
	return array_values($files);
    }

    public $backupListNamed = [];

    public function backupListNamed() {
	$this->Hub->set_level(4);
	$named = [];
	$list = $this->backupList();
	foreach ($list as $file) {
	    $named[] = ['file' => $file];
	}
	return $named;
    }

    public $backupDown = ['string'];

    public function backupDown($file) {
	$this->Hub->set_level(4);
	if (file_exists($this->dirDbBackup . $file)) {
	    header('Content-type: application/force-download');
	    header('Content-Disposition: attachment; filename="' . $file . '"');
            header('Content-Length: '.filesize($this->dirDbBackup.$file));
            $fp = fopen($this->dirDbBackup.$file, 'rb');
            fpassthru($fp);
            exit;
	} else {
	    show_error('X-isell-error: File not found!' . $this->dirDbBackup . $file, 404);
	}
    }

    public $backupUp = [];

    public function backupUp() {
	if (!file_exists($this->dirDbBackup)) {
	    mkdir($this->dirDbBackup);
	}
	if ($_FILES['upload_file'] && !$_FILES['upload_file']['error']) {
	    return 'uploaded' . move_uploaded_file($_FILES['upload_file']["tmp_name"], $this->dirDbBackup . $_FILES['upload_file']['name']);
	}
	return 'error' . $_FILES['upload_file']['error'];
    }

    public $backupDelete = [];

    public function backupDelete() {
	$this->Hub->set_level(4);
	$file = $this->input->post('filename');
	return unlink($this->dirDbBackup . $file);
    }

}
