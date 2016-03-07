<?php

class ViewManager extends CI_Model{
    private $viewStorageFolder='application/views/dumps/';
    private $dump;
    function __construct() {
	if(!file_exists($this->viewStorageFolder)){
	    mkdir($this->viewStorageFolder);
	}
	$this->dump=new stdClass();
	$this->clear();
	parent::__construct();
    }
    private function clear(){
	$files = array_diff(scandir($this->viewStorageFolder), array('.', '..'));
	foreach ($files as $file) {
	    if( time()-filectime("$this->viewStorageFolder/$file")>24*60*60 ){
		unlink("$this->viewStorageFolder/$file");
	    }
	}
    }
    public function store( $dump ){
	$this->dump->dump_id=$dump['dump_id']= str_replace('.','',microtime(true));
	$json=json_encode($dump);
	file_put_contents($this->viewStorageFolder.$this->dump->dump_id, $json);
	return $this->dump->dump_id;
    }
    public function restore( $dump_id ){
	if( file_exists($this->viewStorageFolder.$dump_id) ){
	    $json=  file_get_contents($this->viewStorageFolder.$dump_id);
	    $this->dump=  json_decode($json);
	    return true;
	}
	return false;
    }
    public function out( $out_type='.print', $header_mode='send_headers' ){
	if( $this->dump ){
	    $FileEngine=$this->Base->load_model('FileEngine');
	    if( isset($this->dump->tpl_files_folder) ){
		$FileEngine->tpl_files_folder=$this->dump->tpl_files_folder;
	    }
	    $FileEngine->assign($this->dump->view, $this->dump->tpl_files);
	    if ( $out_type=='.print' ) {
		$file_name = '.print';
		$FileEngine->show_controls = true;
		$FileEngine->user_data = [
		    'title' => $this->dump->title,
		    'msg' => $this->dump->user_data->text,
		    'email' => $this->dump->user_data->email,
		    'fgenerator'=>'ViewManager',
		    'out_type'=>$out_type,
		    'dump_id' => $this->dump->dump_id
		    ];
	    } else {
		$file_name = str_replace(' ','_',$this->dump->title).$out_type;
	    }
	    $FileEngine->header_mode=$header_mode;
	    return $FileEngine->fetch($file_name);
	}
	return null;
    }
    public function getFile($dump_id,$out_type){
	$this->restore($dump_id);
	return $this->out($out_type);
    }
    public function flush( $out_type, $header_mode='send_headers' ){
	echo $this->out( $out_type, $header_mode );
	exit;
    }
    public function outRedirect($out_type){
	header("Location: ../../ViewManager/export/?dump_id={$this->dump->dump_id}&out_type={$out_type}");
    }
    public function export(){
	$dump_id=$this->input->get_post('dump_id');
	$out_type=$this->input->get_post('out_type');
	if( $this->restore($dump_id) ){
	    $this->flush($out_type);
	}
	exit('FILE DELETED');
    }
}
