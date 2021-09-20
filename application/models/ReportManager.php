<?php
require_once 'Catalog.php';
class ReportManager extends PluginManager {
    private $plugin_folder='application/plugins/';
    private $current_info;
    
    public $listFetch=[];
    public function listFetch($mode=null){
	return parent::listFetch('ReportsOnly');
    }
    
    private function scanFolder( $path ){
	$this->Hub->set_level(1);
	$files = array_diff(scandir($path), array('.', '..'));
	arsort($files);
	return array_values($files);	
    }
    
    public $formGet=['\w+'];
    public function formGet( $report_id=null ){
	if( $report_id && file_exists($this->plugin_folder.$report_id.'/form.html') ){
	    return file_get_contents($this->plugin_folder.$report_id.'/form.html');
	}
	if( $report_id && file_exists($this->plugin_folder.$report_id.'/views/form.html') ){
	    return file_get_contents($this->plugin_folder.$report_id.'/views/form.html');
	}
	show_error('X-isell-error: Form not found!', 500);
    }
    
    
    private function load_report( $plugin_name ){
        if( file_exists("application/plugins/$plugin_name/models/$plugin_name.php") ){
            require_once "application/plugins/$plugin_name/models/$plugin_name.php";
        } else {
            require_once "application/plugins/$plugin_name/$plugin_name.php";
        }
	$Plugin=new $plugin_name();
	$Plugin->Hub=$this->Hub;
	return $Plugin;
    }
    
    public $formSubmit=['\w+'];
    public function formSubmit( $system_name=null ){
	$this->current_info=$this->get_plugin_headers($system_name);
	$tpl_files=isset($this->current_info['plugin_template'])?$this->current_info['plugin_template']:$this->current_info['system_name'].'.xlsx';
	$Plugin=$this->load_report($system_name);
        if( file_exists("application/plugins/{$this->current_info['system_name']}/views/") ){
            $template_folder="application/plugins/{$this->current_info['system_name']}/views/";
        } else {
            $template_folder="application/plugins/{$this->current_info['system_name']}/";
        }
	$dump=[
	    'tpl_files_folder'=>$template_folder,
	    'tpl_files'=>$tpl_files,
	    'title'=>$this->current_info['plugin_name'],
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>$this->current_info['plugin_name']
	    ],
	    'view'=>$Plugin->viewGet()
	];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect('.print');
    }
}