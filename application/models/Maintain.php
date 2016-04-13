<?php

class Maintain extends CI_Model {
    
    private $dirDbBackup="/iSell_DB_BACKUP/";
    private $dirWork;
    
    public function getCurrentVersionStamp(){
	$this->dirWork = realpath('.');
	if( file_exists($this->dirWork.'/.git') ){
	    return ['stamp'=>date ("Y-m-d\TH:i:s\Z", time()),'branch'=>$this->getGitBranch()];
	}
	return ['stamp'=>date ("Y-m-d\TH:i:s\Z", filemtime($this->dirWork)),'branch'=>$this->getGitBranch()];
    }
    
    private function getGitBranch(){
	$matches=[];
        preg_match("/\/(\w+).zip/", BAY_UPDATE_URL, $matches);
	return $matches[1];
    }
    
    public function updateInstall(){
	$this->updateConfigurator();
	$this->dirWork = realpath('.');
	$file = str_replace("\\", "/", $this->dirWork.'/install/db_update.sql');
	$this->backupImportExecute($file);
	return true;
    }
    
    private function updateConfigurator(){
	$this->dirWork = realpath('.');
	$this->xcopy($this->dirWork.'/install/configurator',realpath('../../') );
    }
    
    private function xcopy($src,$dst) {
	$dir = opendir($src);
	!file_exists($dst) && mkdir($dst);
	while( false !== ($file = readdir($dir)) ) {
	    if( $file=='.' || $file=='..' ){
		continue;
	    }
	    if ( is_dir($src.'/'.$file) ) {
		$this->xcopy( $src.'/'.$file, $dst.'/'.$file );
	    } else {
		copy( $src.'/'.$file, $dst.'/'.$file );
	    }
	}
	closedir($dir);
    }
    
    private function setupConf(){
	$this->dirWork = realpath('.');
	$conf_file=  $this->dirWork."/conf".rand(1,1000);
	$conf='[client]
	    user="'.BAY_DB_USER.'"
	    password="'.BAY_DB_PASS.'"';
	file_put_contents($conf_file, $conf);
	return $conf_file;
    }
    
    private function backupImportExecute( $file ){
	$output=[];
	$conf_file=$this->setupConf();
        $path_to_mysql=$this->db->query("SHOW VARIABLES LIKE 'basedir'")->row()->Value;
	exec("$path_to_mysql/bin/mysql --defaults-file=$conf_file ".BAY_DB_NAME." <".$file." 2>&1",$output);
	unlink($conf_file);
	if( count($output) ){
	    file_put_contents($this->dirDbBackup.date('Y-m-d_H-i-s').'-IMPORT.log', implode( "\n", $output ));
	    return false;
	}
	return true;
    }
    
    public function backupImport(){
	$this->Base->set_level(4);
        $file=$this->input->post('filename');
	if( file_exists($this->dirDbBackup.$file) ){
	    return $this->backupImportExecute($this->dirDbBackup.$file);
	}
        return false;
    }
    
    public function backupDump(){
        $this->Base->set_level(4);
        $path_to_mysql=$this->db->query("SHOW VARIABLES LIKE 'basedir'")->row()->Value;
	if( !file_exists ($this->dirDbBackup) ){
	    mkdir($this->dirDbBackup);
	}
        $output=[];
        $filename=$this->dirDbBackup.date('Y-m-d_H-i-s')."-".BAY_DB_NAME.'-ISELL-DB-BACKUP.sql';
        exec("$path_to_mysql/bin/mysqldump --user=".BAY_DB_USER." --password=".BAY_DB_PASS."  --default-character-set=utf8 --single-transaction=TRUE --routines --events  ".BAY_DB_NAME." >".$filename,$output);
        if( count($output) ){
            file_put_contents($filename.'.log', implode( "\n", $output ));
            return false;
        }
        return true;
    }
    
    public function backupList(){
	$this->Base->set_level(4);
	$files = array_diff(scandir($this->dirDbBackup), array('.', '..'));
	arsort($files);
	return array_values ($files);
    }
    
    public function backupListNamed(){
	$this->Base->set_level(4);
	$named=[];
	$list=$this->backupList();
	foreach($list as $file){
	    $named[]=['file'=>$file];
	}
	return $named;
    }
    
    public function backupDown( $file ){
	$this->Base->set_level(4);
	if( file_exists ($this->dirDbBackup.$file) ){
	    header('Content-type: application/force-download');
	    header('Content-Disposition: attachment; filename="'.$file.'"');
	    echo file_get_contents($this->dirDbBackup.$file);
	} else {
	    show_error('X-isell-error: File not found!'.$this->dirDbBackup.$file, 404);
	}
    }
    
    public function backupUp(){
	if( !file_exists ($this->dirDbBackup) ){
	    mkdir($this->dirDbBackup);
	}
	if( $_FILES['upload_file'] && !$_FILES['upload_file']['error'] ){
	    return 'uploaded'.move_uploaded_file( $_FILES['upload_file']["tmp_name"] , $this->dirDbBackup.$_FILES['upload_file']['name'] );
	}
        return 'error'.$_FILES['upload_file']['error'];
    }
    
    public function backupDelete(){
	$this->Base->set_level(4);
        $file=$this->input->post('filename');
	return unlink($this->dirDbBackup.$file);
    }
}
