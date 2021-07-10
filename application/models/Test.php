<?php

class Test extends Catalog{
    private $reader;
    private $spreadsheet;
    private $template_file;
    
    
    
    function index(){
        $filename=APPPATH.'/views/rpt/ru/doc/schet-faktura2021.xlsx';
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        
        
        
        $spreadsheet = $reader->load($filename);
        
    }
    
    
    public function viewDataSet( $view ){
        $this->view=$view;
    }
    
    public function tempalteFileSet($template_file_path){
        $this->template_file=$template_file_path;
        
        if( strpos($template_file_path,'.xlsx')!==false ){
            $this->reader=new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        }
        $this->spreadsheet = $this->reader->load($this->template_file);
    }
    
    public function renderHtml( $output_file_path ='php://output' ){
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
        $writer->save($output_file_path);
    }
    
    
    
    
}