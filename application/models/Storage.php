<?php

/*
 * This class is interacting with app storage of files
 */

/**
 * Description of Storage
 *
 * @author Baycik
 */
class Storage extends CI_Model {
    public $storageFolder = BAY_STORAGE;
    
    public function file_store($path, $data, $flag=NULL) {
	$filename = basename($path);
	$dir_path = $this->storageFolder . "/" . dirname ( $path );
	if (!file_exists($dir_path)) {
	    mkdir($dir_path, 0777, true);
	}
	return file_put_contents("$dir_path/$filename", $data, $flag);
    }

    public function file_restore($path) {
	if (!file_exists($this->storageFolder . "/" . $path)) {
	    return null;
	}
	return file_get_contents($this->storageFolder . "/" . $path);
    }

    public $file_remove=['path'=>'string'];
    public function file_remove($path) {
	if (!file_exists($this->storageFolder . "/" . $path)) {
	    return null;
	}
	return unlink($this->storageFolder . "/" . $path);
    }
    
    public function dir_remove ($path,$remove_root){
        return $this->delTree($this->storageFolder . "/".$path,$remove_root);
    }
    
    private function delTree($path,$remove_root=true) {
	$path;
	if (!file_exists($path)) {
	    return true;
	}
	$files = array_diff(scandir($path), array('.', '..'));
	foreach ($files as $file) {
	    is_dir("$path/$file") ? $this->delTree("$path/$file",true) : unlink("$path/$file");
	}
	return $remove_root?rmdir($path):true;
    }
    
    public function file_list($dir) {
	$this->load->helper('directory');
	return directory_map($this->storageFolder . "/" . $dir);
    }

    public function json_store($path, $data) {
	return $this->file_store($path, json_encode($data));
    }

    public function json_restore($path) {
	return json_decode($this->file_restore($path));
    }

    public $upload=['dir'=>'raw','filename'=>'raw'];
    public function upload($dir = '', $filename = null) {
	if (!file_exists($this->storageFolder . "/" . $dir)) {
	    mkdir($this->storageFolder . "/" . $dir,0777,true);
	}
	if ($_FILES['upload_file'] && !$_FILES['upload_file']['error']) {
	    return 'uploaded' . move_uploaded_file($_FILES['upload_file']["tmp_name"], $this->storageFolder . "/" . $dir . "/" . ($filename ? $filename : $_FILES['upload_file']['name']));
	}
	return 'error' . $_FILES['upload_file']['error'];
    }
    
    public function download($dir = '', $source, $filename){
	if (!file_exists($this->storageFolder . "/" . $dir)) {
	    mkdir($this->storageFolder . "/" . $dir,0777,true);
	}
        try{
            set_time_limit(120);
            return copy($source,$this->storageFolder . "/" . $dir . "/" . ($filename));
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
        return false;
    }
    
    public function file_checksum($filename){
        $path=$this->storageFolder . "/" .$filename;
        return file_exists($path)&&!is_dir($path)?md5_file($path):0;
    }

    public function file_time($filename){
        $path=$this->storageFolder . "/" .$filename;
        return file_exists($path)?filemtime($path):0;
    }
    public function file_size($filename){
        $path=$this->storageFolder . "/" .$filename;
        return file_exists($path)?filesize($path):0;
    }

    public function image_get() {
	$args = func_get_args();
	ob_start();
	    call_user_func_array(array($this, 'image_flush'),$args);
	return ob_get_contents();
    }
    
    private function cache_control(){
	if( rand(0,100000)<2 ){//chance 0.001%
	    $allfiles=scandir ( $this->storageFolder.'/dynImg' );
	    $treshold=time()-30*24*60*60;
	    foreach($allfiles as $file){
		if(strpos($file,'x')===false || $file=='..' || $file=='.'){
		    continue;
		}
		if( filemtime($this->storageFolder .'/dynImg/'.$file)<$treshold ){
		    unlink($this->storageFolder .'/dynImg/'.$file);
		}
	    }
	    return true;
	}
	return false;
    }
    
    public $image_flush=['size'=>'string','path'=>'string'];
    public function image_flush( $size_x, $path ){
	$this->cache_control();
	$path = $this->storageFolder . "/" . $path;
	$cache=$path . "_{$size_x}.png";
	if (is_dir($path) || !file_exists($path)) {
	    $path='img/notfound.jpg';
	}
	if( !file_exists($cache) ){
	    $size = explode('x', $size_x);
	    $thumb=$this->image_resize($path, $size[0], $size[1]);
	    imagepng($thumb,$cache);
	}
	header("Content-type: image/png");
        exit(file_get_contents($cache));
    }

    public function image_resize($path, $width, $height) {
	$info = getimagesize($path);
	switch ($info[2]) {
	    case IMAGETYPE_PNG:
		$src = imagecreatefrompng($path);
		break;
	    case IMAGETYPE_JPEG:
		$src = imagecreatefromjpeg($path);
		break;
	    case IMAGETYPE_GIF:
		$src = imagecreatefromgif($path);
		break;
	    case IMAGETYPE_WEBP:
		$src = imagecreatefromwebp($path);
		break;
	    default :
		die("unknown image type");
		break;
	}
	$srcw = imagesx($src);
	$srch = imagesy($src);
	$ratio = ( $width / $srcw < $height / $srch ) ? $width / $srcw : $height / $srch;
	$thumb = imagecreatetruecolor($srcw * $ratio, $srch * $ratio);
	switch ($info[2]) {
	    case IMAGETYPE_PNG:
		imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
		break;
	}
	imagecopyresampled($thumb, $src, 0, 0, 0, 0, $srcw * $ratio, $srch * $ratio, $srcw, $srch);
	return $thumb;
    }

}
