<?php

include 'config.php';

class UpdateInstaller {
    private $link;
    private $dirParent;
    private $dirWork;
    private $dirUnpack;
    private $dirBackup;
    private $zipPath;
    private $zipSubFolder;
    private $dirDbBackup;

    function __construct() {
	$this->dirParent = realpath('.');
	$this->dirWork = $this->dirParent . '/isell';
	$this->dirDbBackup=$this->dirParent.'/DB_BACKUP/';
	if (file_exists($this->dirWork . '/.git')) {
	    exit("Work folder contains .git folder. Update may corrupt your work!");
	}
	$git_branch_name = $this->getGitBranch();
	$this->dirUnpack = $this->dirParent . '/isell_update';
	$this->dirBackup = $this->dirParent . '/isell_backup';
	$this->zipPath = $this->dirUnpack . '/isell_update.zip';
	$this->zipSubFolder = $this->dirUnpack . "/isell-$git_branch_name/";
    }

    private function getGitBranch() {
	$matches = [];
	preg_match("/\/(\w+).zip/", BAY_UPDATE_URL, $matches);
	return $matches[1];
    }

    public function appUpdate($action = 'download') {
	switch( $action ){
	    case 'is_installed':
		return $this->checkInstalled();
	    case 'download':
		return $this->updateDownload(BAY_UPDATE_URL, $this->zipPath);
	    case 'unpack':
		return $this->updateUnpack();
	    case 'swap':
		return $this->updateSwap();
	    case 'install':
		return $this->freshDbInstall();
	    case 'signup':
		return $this->signup();
	}
    }

    private function updateDownload($updateUrl, $updateFile) {
	set_time_limit(240);
	if (!file_exists($this->dirUnpack)) {
	    mkdir($this->dirUnpack);
	}
	return copy($updateUrl, $updateFile);
    }

    private function updateUnpack() {
	$this->delTree($this->zipSubFolder);
	$zip = new ZipArchive;
	if ($zip->open($this->zipPath) === TRUE) {
	    $zip->extractTo($this->dirUnpack);
	    $zip->close();
	    return true;
	} else {
	    return false;
	}
    }

    private function updateSwap() {
	if (file_exists($this->dirBackup)) {
	    $this->delTree($this->dirBackup);
	}
	if (file_exists($this->dirWork)) {
	    $this->loopRename($this->dirWork, $this->dirBackup);
	}
	if (!file_exists($this->dirWork) && file_exists($this->zipSubFolder)) {
	    return rename($this->zipSubFolder, $this->dirWork) &&
		   $this->delTree($this->dirUnpack) && $this->delTree($this->dirBackup);
	}
	return false;
    }

    private function freshDbInstall() {
	if( $this->checkAdminExists() ){
	    return 'admin_exists';
	}
	$this->dirWork = realpath('.');
	$file = str_replace("\\", "/", $this->dirWork . '/isell/install/fresh_db_dump.sql');
	$this->query("CREATE DATABASE IF NOT EXISTS " . BAY_DB_NAME);
	return $this->backupImportExecute($file);
    }
    
    public function request($index){
	return isset($_REQUEST[$index])?addslashes($_REQUEST[$index]):null;
    }
    
    private function signup(){
	if( $this->checkAdminExists() ){
	    return 'admin_exists';
	}
	$first_name=  $this->request('first_name');
	$last_name=  $this->request('last_name');
	$user_login=  $this->request('user_login');
	$user_pass=   $this->request('user_pass');
	if( preg_match('/^[a-zA-Z_0-9]{3,}$/',$user_login) && preg_match('/^[a-zA-Z_0-9]{3,}$/',$user_pass) ){
	    $pass_hash = md5($user_pass);
	    $this->query("INSERT INTO " . BAY_DB_NAME . ".user_list SET first_name='$first_name',last_name='$last_name',user_login='$user_login',user_pass='$pass_hash',user_level=4");
	    return mysqli_errno($this->link)?mysqli_errno($this->link):'admin_added';
	} else {
	    return 'login_pass_not_match';
	}
    }

    private function checkAdminExists() {
	$row = $this->query("SELECT user_id FROM " . BAY_DB_NAME . ".user_list WHERE user_level=4");
	return $row;
    }

    private function checkInstalled(){
	$status='';
	if( file_exists($this->dirWork) && file_exists($this->dirWork.'/index.php') ){
	    $status.=' files_ok';
	}
	if( $this->query("SHOW DATABASES LIKE '" . BAY_DB_NAME . "'") ){
	    $status.=' db_ok';
	}
	if( $this->checkAdminExists() ){
	    $status.=' admin_ok';
	}
	return $status;
    }
    
    private function setupConf() {
	$this->dirWork = realpath('.');
	$conf_file = $this->dirWork . "/conf" . rand(1, 1000);
	$conf = '[client]
	    user="' . BAY_DB_USER . '"
	    password="' . BAY_DB_PASS . '"';
	file_put_contents($conf_file, $conf);
	return $conf_file;
    }

    private function query($query) {
	if (!isset($this->link)) {
	    $this->link = mysqli_connect('localhost', BAY_DB_USER, BAY_DB_PASS);
	}
	mysqli_query($this->link, "SET NAMES utf8");
	$result = mysqli_query($this->link, $query);
	if (is_bool($result)) {
	    return $result;
	}
	return mysqli_fetch_object($result);
    }
    
    private function backupImportExecute($file) {
	$output = [];
	$conf_file = $this->setupConf();
	$path_to_mysql = $this->query("SHOW VARIABLES LIKE 'basedir'")->Value;
	exec("$path_to_mysql/bin/mysql --defaults-file=$conf_file " . BAY_DB_NAME . " <" . $file . " 2>&1", $output);
	unlink($conf_file);
	if (count($output)) {
	    if( !file_exists($this->dirDbBackup) ){
		mkdir($this->dirDbBackup);
	    }
	    file_put_contents($this->dirDbBackup . date('Y-m-d_H-i-s') . '-IMPORT.log', implode("\n", $output));
	    return false;
	}
	return true;
    }

    private function loopRename($old, $new) {
	set_time_limit(15);
	while (true) {
	    if (rename($old, $new)) {
		return true;
	    }
	    usleep(rand(10, 1000));
	}
	return false;
    }

    private function delTree($dir) {
	if (!file_exists($dir)) {
	    return true;
	}
	$files = array_diff(scandir($dir), array('.', '..'));
	foreach ($files as $file) {
	    (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
    }

}

if (isset($_GET['echo'])) {
    $UpdateInstaller = new UpdateInstaller();
    $step = $UpdateInstaller->request('step');
    echo $UpdateInstaller->appUpdate($step);
}