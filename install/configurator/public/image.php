<?php
    include '../config.php';
    
  function image_flush( $size_x, $path ){
	$path = BAY_STORAGE . "/dynImg/" . $path;
	$cache=$path . "_{$size_x}.png";
	if (is_dir($path) || !file_exists($path)) {
	    $path='img/notfound.jpg';
	}
	if( !file_exists($cache) ){
	    $size = explode('x', $size_x);
	    $thumb=image_resize($path, $size[0], $size[1]);
	    imagepng($thumb,$cache);
	}
	header("Content-type: image/png");
        passthru($cache);
    }

     function image_resize($path, $width, $height) {
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
	switch ($info[2]) {
	    case IMAGETYPE_PNG:
		imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
		break;
	}
	imagecopyresampled($thumb, $src, 0, 0, 0, 0, $srcw * $ratio, $srch * $ratio, $srcw, $srch);
	return $thumb;
    }
    
    if (!empty ($_GET['size']) && !empty($_GET['path'])){
        $size_x = $_GET['size'];
        $path = $_GET['path'];
        image_flush( $size_x, $path );
    };
    