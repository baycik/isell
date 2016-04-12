<?php
function delTree($dir) {
    if( !file_exists ($dir) ){
	return true;
    }
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
	(is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}
function loopRename( $old, $new ){
    while( true ){
	if( rename($old, $new) ){
	    return true;
	}
	usleep(rand(10,1000));
    }
    return false;
}
if( $_POST['swap'] ){
    set_time_limit(15);
    error_reporting(E_ERROR | E_PARSE);
    if( file_exists("isell3") && file_exists("isell3_new") ){
	delTree("isell3_backup");
	if( loopRename("isell3", "isell3_backup") ){
            header("X-isell-type:OK");
	    echo loopRename("isell3_new", "isell3");
	}
    }
}