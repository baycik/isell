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
	if( $dump_id && file_exists($this->viewStorageFolder.$dump_id) ){
	    $json=  file_get_contents($this->viewStorageFolder.$dump_id);
	    $this->dump=  json_decode($json);
	    return true;
	}
	return false;
    }
    
    public $out=['string','send_headers'];
    public function out( $out_type='.print', $header_mode='send_headers' ){
	if( $this->dump ){
            session_write_close();
	    $this->load->library('FileEngine');
	    $FileEngine=new FileEngine();
            $FileEngine->Hub=$this->Hub;
	    if( isset($this->dump->tpl_files_folder) ){
		$FileEngine->tpl_files_folder=$this->dump->tpl_files_folder;
	    }
	    if( isset($this->dump->html) ){
		$FileEngine->loadHtml($this->dump->html);
	    } else 
	    if( isset($this->dump->tpl_files) ){
		$FileEngine->assign($this->dump->view, $this->dump->tpl_files);
	    }
	    if( isset($this->dump->struct) ){
		$FileEngine->tplModifier=$this->setupCols();
	    }
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
    private function setupCols(){
	return function($FileEngine, $Worksheet){
            $headerX = 1;
            $headerY = 1;
            $contentY = $headerY + 1;
            $headerTpl = $Worksheet->getCellByColumnAndRow($headerX, $headerY)->getValue();
            $cellTpl = $Worksheet->getCellByColumnAndRow($headerX, $headerY + 1)->getValue();
            foreach ($this->dump->struct as $i => $column) {
                if( $column->Width ){
                    
                } else if( preg_match("/(int|decimal|double)/", $column->Type) ){
		    $column->Width=8;
		} else if( preg_match("/varchar\((\d+)\)/", $column->Type, $matches) ){
		    $column->Width=$matches[1]<50?15:50;
		} else {
		    $column->Width=25;
		}
                $Worksheet->getColumnDimension(chr(65 + $headerX + $i))->setWidth($column->Width);
                $Worksheet->getCellByColumnAndRow($headerX + $i, $headerY)->setValue(str_replace('_title_', $column->Comment?$column->Comment:$column->Field, $headerTpl));
                $Worksheet->getCellByColumnAndRow($headerX + $i, $contentY)->setValue(str_replace('_field_', $column->Field, $cellTpl));
            }
            $alfaHeaderStart = chr(65 + $headerX-1);
            $alfaHeaderStop = chr(65 + $headerX + $i);
            $Worksheet->duplicateStyle($Worksheet->getStyle("$alfaHeaderStart$headerY"), "$alfaHeaderStart$headerY:$alfaHeaderStop$headerY");
            $Worksheet->duplicateStyle($Worksheet->getStyle("$alfaHeaderStart$contentY"), "$alfaHeaderStart$contentY:$alfaHeaderStop$contentY");
            return $Worksheet;	    
	};
    }
    public function getFile($dump_id,$out_type){
	$this->restore($dump_id);
	return $this->out($out_type);
    }
    public function flush( $out_type, $header_mode='send_headers' ){
	echo $this->out( $out_type, $header_mode );
	exit;
    }
    
    public function outRedirect( string $out_type){
	$app_folder=array_pop( explode('\\',FCPATH) );
	$full_uri=$_SERVER['REQUEST_URI'];
	$pos=strpos($full_uri,$app_folder);
	$url_base_folder=substr($full_uri,0,$pos).$app_folder;
	header("Location: {$url_base_folder}ViewManager/export/?dump_id={$this->dump->dump_id}&out_type={$out_type}");
    }
    
    public $export=['dump_id'=>'string','out_type'=>'string'];
    public function export($dump_id,$out_type){
	set_time_limit(600);
	if( $this->restore($dump_id) ){
	    $this->flush($out_type);
	}
	exit('FILE DELETED');
    }
}
