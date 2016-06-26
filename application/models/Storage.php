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

    private $storageFolder = '../storage';

    public function file_store($path, $data) {
	$parts = explode('/', $path);
	$filename = array_pop($parts);
	$dir_path = $this->storageFolder . "/" . implode('/', $parts);
	if (!file_exists($dir_path)) {
	    mkdir($dir_path, 0777, true);
	}
	return file_put_contents("$dir_path/$filename", $data);
    }

    public function file_restore($path) {
	if (!file_exists($this->storageFolder . "/" . $path)) {
	    return null;
	}
	return file_get_contents($this->storageFolder . "/" . $path);
    }

    public function file_remove() {
	$path = implode('/', func_get_args());
	if (!file_exists($this->storageFolder . "/" . $path)) {
	    return null;
	}
	return unlink($this->storageFolder . "/" . $path);
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

    public function upload($dir = '', $filename = null) {
	if (!file_exists($this->storageFolder . "/" . $dir)) {
	    mkdir($this->storageFolder . "/" . $dir);
	}
	if ($_FILES['upload_file'] && !$_FILES['upload_file']['error']) {
	    return 'uploaded' . move_uploaded_file($_FILES['upload_file']["tmp_name"], $this->storageFolder . "/" . $dir . "/" . ($filename ? $filename : $_FILES['upload_file']['name']));
	}
	return 'error' . $_FILES['upload_file']['error'];
    }

    public function image_get() {
	$args = func_get_args();
	ob_start();
	    call_user_func_array(array($this, 'image_flush'),$args);
	return ob_get_contents();
    }
    public function image_flush(){
	$args = func_get_args();
	$size_x = array_shift($args);
	$path = implode('/', $args);
	if (!file_exists($this->storageFolder . "/" . $path)) {
	    return null;
	}
	$size = explode('x', $size_x);
	$thumb=$this->image_resize($this->storageFolder . "/" .$path, $size[0], $size[1]);
	
	
	$black = imagecolorallocate($thumb, 0, 0, 0);
	$grey = imagecolorallocate($thumb, 255, 255, 255);
	
        $mark=$this->input->get_post('mark');
        if( $mark ){
            $font = './system/fonts/texb.ttf';
            $font_size=floor($size[0]/10);
            imagettftext($thumb, $font_size, 0, $font_size/2+$font_size/17, $font_size+$font_size/17, $grey, $font, $mark);
            imagettftext($thumb, $font_size, 0, $font_size/2, $font_size, $black, $font, $mark);
        }
	header("Content-type: image/jpeg");
	imagejpeg($thumb,NULL,90);
        exit();
    }

    private function image_resize($path, $width, $height) {
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
	    default :
		die("unknown image type");
		break;
	}
	$srcw = imagesx($src);
	$srch = imagesy($src);
	$ratio = ( $width / $srcw < $height / $srch ) ? $width / $srcw : $height / $srch;
	$thumb = imagecreatetruecolor($srcw * $ratio, $srch * $ratio);
	imagecopyresampled($thumb, $src, 0, 0, 0, 0, $srcw * $ratio, $srch * $ratio, $srcw, $srch);
	return $thumb;
    }

}
