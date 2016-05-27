<?php

/*
 * This class is interacting with app storage of files
 */

/**
 * Description of Storage
 *
 * @author Baycik
 */
class Storage  extends CI_Model {
    private $storageFolder='../storage';
    
    public function file_store( $path, $data ){
	$parts = explode('/', $path);
        $filename = array_pop($parts);
	$dir_path=$this->storageFolder."/".implode('/',$parts);
	if( !file_exists($dir_path) ){
	    mkdir($dir_path,0777,true);
	}
	return file_put_contents("$dir_path/$filename", $data);
    }
    
    public function file_restore($path){
	if( !file_exists($this->storageFolder."/".$path) ){
	    return null;
	}
	return file_get_contents($this->storageFolder."/".$path);
    }
    
    public function file_remove($path){
	if( !file_exists($this->storageFolder."/".$path) ){
	    return null;
	}
	return unlink($this->storageFolder."/".$path);
    }
    
    public function file_list($dir){
	$this->load->helper('directory');
	return directory_map($this->storageFolder."/".$dir);
    }
    
    public function json_store( $path, $data ){
	return $this->file_store( $path, json_encode($data) );
    }
    
    public function json_restore($path){
	return json_decode($this->file_restore($path));
    }
    
    public function upload( $dir, $filename ){
	if( !file_exists ($this->storageFolder."/".$dir) ){
	    mkdir($this->storageFolder."/".$dir);
	}
	if( $_FILES['upload_file'] && !$_FILES['upload_file']['error'] ){
	    return 'uploaded'.move_uploaded_file( $_FILES['upload_file']["tmp_name"] , $this->storageFolder."/".$dir."/".($filename?$filename:$_FILES['upload_file']['name']) );
	}
        return 'error'.$_FILES['upload_file']['error'];
    }
    
}
