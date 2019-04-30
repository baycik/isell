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
        $product_codes = $this->getCsv($_FILES['file']["tmp_name"]);
        $this->viewGet($product_codes);
    }
    
    
    public function viewGet($product_codes){
	$table=$this->getProductList($product_codes);
	$dump=[
	    'tpl_files_folder'=>__DIR__.'/../xlsx_template/',
	    'tpl_files'=>'KazanExporter.xlsx',
	    'title'=>"Остаток Nilson Крым",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'view'=>[
		'date'=>date('d.m.Y'),
		'filter'=>'',
		'rows'=>$table
	    ]
	];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
        $filename = '84650823nils_ost.xlsx';
        
        !is_dir("../public") && mkdir("../public", 0777);
        $file_path = str_replace('\\', '/', realpath("../public")) . '/';
        @unlink($file_path);
        
	file_put_contents($file_path.$filename, $this->exportXLSX($dump));
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
        $FileEngine->header_mode='send_headers';
        return $FileEngine->fetch($file_name);
    }
    
    private function getProductList($product_codes){
        $sql = "
            SELECT 
                se.product_code, se.product_quantity, pl.ru as product_name 
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
        return implode(',',$list);
    }
}
