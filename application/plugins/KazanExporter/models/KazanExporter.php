<?php

/* Group Name: Синхронизация
 * User Level: 2
 * Plugin Name: KazanExporter
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: Tool for kazan export 
 * Author: baycik 2019
 * Author URI: http://isellsoft.com
 */

class KazanExporter extends Catalog {

    
    public function index(){
        if(isset($_FILES['file'])){
            $product_codes = $this->getCsv($_FILES['file']["tmp_name"]);
        }
    }
    
    public $start = ['filename'=>'string'];
    public function start($filename){
        $product_codes = $this->getSettings();
        $this->viewGet($product_codes, 'NilsonCrimeaLeftovers');
        return true;
    }

    private function viewGet($product_codes, $filename){
	$table=$this->getProductList($product_codes);
	$dump=[
	    'tpl_files_folder'=>__DIR__.'/../xlsx_template/',
	    'tpl_files'=>'KazanExporter.xlsx',
	    'title'=>"",
	    'user_data'=>[],
	    'view'=>[
		'date'=>date('d.m.Y'),
		'filter'=>'',
		'rows'=>$table
	    ]
	];
        !is_dir("../public") && mkdir("../public", 0777);
        $file_path = str_replace('\\', '/', realpath("../public")) . '/';
	file_put_contents($file_path.$filename.'.xlsx', $this->exportXLSX($dump));
    }
    
    private function exportXLSX($dump){
        $dump_view = new stdClass();
        $dump_view->date = $dump['view']['date'];
        $dump_view->filter = $dump['view']['filter'];
        $dump_view->rows = $dump['view']['rows'];
        $this->load->library('FileEngine');
        $FileEngine=new FileEngine();
        $FileEngine->tpl_files_folder=$dump['tpl_files_folder'];
        $FileEngine->assign($dump_view, $dump['tpl_files']);
        
        $file_name = str_replace(' ','_',$dump['title']).'.xlsx';
        $FileEngine->header_mode='';
        return $FileEngine->fetch($file_name);
    }
    
    private function getProductList($product_codes){
        $pcomp_id='932';
        $usd_ratio=$this->Hub->pref('usd_ratio');;
        $product_codes = addslashes($product_codes);
        $sql = "
            SELECT 
                se.product_code, se.product_quantity, pl.ru as product_name ,GET_SELL_PRICE(se.product_code,$pcomp_id,$usd_ratio) product_price
            FROM 
                stock_entries se
                JOIN
                prod_list pl USING(product_code)
            WHERE 
                product_code IN ($product_codes)
            ORDER BY se.product_code        
            ";
        return $this->get_list($sql);
    }
    
    private function getCsv($filename){
        $list = [];
        $csv = file($filename);
        foreach($csv as &$row){
            $list[] = explode(';', $row)[0];
        }
        $result = implode(',',$list);
        $this->updateSettings($result);
        return $result;
    }
 
    private function updateSettings($settings) {
        $sql = "
            UPDATE
                plugin_list
            SET 
                plugin_settings = '$settings'
            WHERE plugin_system_name = 'KazanExporter'    
            ";
        $this->query($sql);
        return;
    }

    private function getSettings() {
        $sql = "
            SELECT
                plugin_settings
            FROM 
                plugin_list
            WHERE plugin_system_name = 'KazanExporter'    
            ";
        $row = $this->get_row($sql);
        return $row->plugin_settings;
    }
}
