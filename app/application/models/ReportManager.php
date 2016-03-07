<?php
require_once 'Catalog.php';
class ReportManager extends Catalog {
    private $plugin_folder='application/views/plugins/reports/';
    private $current_info;
    public function listFetch(){
	$plugins=$this->scanFolder($this->plugin_folder);
	$reports=[];
	foreach($plugins as $plugin_folder){
	    $info=$this->infoGet($plugin_folder);
	    if( $info['user_level']<=$this->Base->svar('user_level') ){
		$reports[]=$info;
	    }
	}
	function sort_bygroup($a,$b){
	    if( $a['group_name']==$b['group_name'] ){
		return 0;
	    }
	    return ($a['group_name']>$b['group_name'])?-1:1;
	}
	usort($reports,'sort_bygroup');
	return $reports;
    }
    
    private function scanFolder( $path ){
	$this->Base->set_level(1);
	$files = array_diff(scandir($path), array('.', '..'));
	arsort($files);
	return array_values($files);	
    }
    
    private function infoGet( $report_id=null ){
	$info=include $this->plugin_folder.$report_id."/info.php";
	$info['report_id']=$report_id;
	return $info;
    }
    
    public function formGet( $report_id=null ){
	$this->check($report_id,'\w+');
	if( $report_id && file_exists($this->plugin_folder.$report_id.'/form.html') ){
	    return file_get_contents($this->plugin_folder.$report_id.'/form.html');
	}
	show_error('X-isell-error: Form not found!', 500);
    }
    
    public function formSubmit( $report_id=null ){
	$this->current_info=$this->infoGet($report_id);
	$tpl_files=isset($this->current_info['template'])?$this->current_info['template']:$this->current_info['report_id'].'.xlsx';
	$Plugin=$this->Base->load_plugin('reports',$report_id);
	$dump=[
	    'tpl_files_folder'=>"plugins/reports/{$this->current_info['report_id']}/",
	    'tpl_files'=>$tpl_files,
	    'title'=>$this->current_info['title'],
	    'user_data'=>[
		'email'=>$this->Base->svar('pcomp')?$this->Base->svar('pcomp')->company_email:'',
		'text'=>$this->current_info['title']
	    ],
	    'view'=>$Plugin->viewGet()
	];
	$ViewManager=$this->Base->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect('.print');
    }
}