<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
class FileEngine{
    private $conversion_table = [
	'.html' => ['.doc' => 'Word Документ','.html' => 'Веб Страница','.pdf' => 'PDF'],
	'.xlsx' => ['.xlsx' => 'Excel', '.xls' => 'Excel 2003', '.html' => 'Веб Страница','.pdf' => 'PDF'],
	'.xml'  => ['.xml' => 'XML Экспорт Данных']
    ];
    private $view;
    private $tpl_files;
    private $export_types = array();
    private $tpl_ext;
    private $compilator;
    private $compiled_html;
    private $post_processor=null;
    public $header_mode='send_headers';
    public $user_data;
    public $file_name_override;
    public $tplModifier;
    public $tpl_files_folder='application/views/rpt/';
    
    private function header($text){
        if( $this->header_mode==='send_headers' ){
            header($text);
        }
    }

    public function loadHTML($html) {
        $this->compilator = 'Rain';
        $this->compiled_html = $html;
        $this->export_types = $this->conversion_table['.html'];
    }

    public function assign($view, $tpl_files) {
        $this->view = $view;
        $this->tpl_files = explode(',', $tpl_files);
    }

    private function loadFileTpl($file_name) {
        if ($this->tpl_ext == '.xlsx') {
            $this->compilator = 'PHPExcel';
            include "application/libraries/report/PHPExcel.php";
            try {
                $this->PHPexcel = PHPExcel_IOFactory::load($file_name);
            } catch (Exception $e) {
                die("Can't load the template of view! $file_name");
            }
            $this->Worksheet = $this->PHPexcel->getActiveSheet();
        } else if ($this->tpl_ext == '.html' || $this->tpl_ext == '.xml') {
            $this->compilator = 'Rain';
            include 'application/libraries/report/RainTPL.php';
            $this->tpl_file = substr($file_name, strrpos($file_name, '/') + 1, strrpos($file_name, '.') - strrpos($file_name, '/') - 1);
            $this->tpl_dir = substr($file_name, 0, strrpos($file_name, '/') + 1);
            $this->rain = new RainTPL();
            $this->rain->configure('tpl_dir', $this->tpl_dir);
            $this->rain->configure('tpl_ext', substr($this->tpl_ext, 1));
            $this->rain->configure('cache_dir', sys_get_temp_dir());
        }
    }
    
    private function compile($tpl_file) {
        $this->loadFileTpl($this->tpl_files_folder. $tpl_file);
	if( file_exists($this->tpl_files_folder. $tpl_file.'.php') ){
	    /*Script for custom processing of templates*/
	    include $this->tpl_files_folder. $tpl_file.'.php';
	}
        if ($this->compilator == 'PHPExcel') {
            if (isset($this->tplModifier)){
                $this->Worksheet = call_user_func_array($this->tplModifier, [$this, $this->Worksheet]);
	    }
            $this->renderWorkbook();
        }
        else if ($this->compilator == 'Rain') {
            $this->rain->assign('v', $this->view);
        }
    }

    private function setup_compilator($out_ext) {
	if ( $this->compiled_html ){
	    
	}
	/*
	 * Finding suitable template to output needed file type
	 */
        foreach ($this->tpl_files as $tpl_file) {
            $tpl_ext = substr($tpl_file, strrpos($tpl_file, '.'));
            if ($this->conversion_table[$tpl_ext]){
                $this->export_types = array_merge($this->export_types, $this->conversion_table[$tpl_ext]);
	    }
            if (!$this->tpl_ext && in_array($out_ext, array_keys($this->conversion_table[$tpl_ext]))) {
                $this->tpl_ext = $tpl_ext;
                $this->compile($tpl_file);
            }
        }
    }

    public function send($file_name, $is_printpage = false) {
        $out_extension = substr($file_name, strrpos($file_name, '.'));
        if ($out_extension == '.print') {
            $out_extension = '.html';
            $is_printpage = true;
        } else if ($out_extension == '.pdf') {
	    $this->header_mode='noheaders';
	    $full_html=$this->fetch(".html");
	    $this->header_mode='send_headers';
	    $parent=realpath('../storage/wkhtml/');
	    $rnd=rand(10,1000000);
	    $tmphtml = $parent."/html-tmp$rnd.html";
	    $tmppdf = $parent."/pdf-tmp$rnd.pdf";
	    $pdfengine = $parent.'/wkhtmltopdf.exe';
	    file_put_contents($tmphtml,$full_html);
	    exec("$pdfengine --zoom 1.2 $tmphtml $tmppdf  2>&1",$output);
	    if( count($output) ){
		file_put_contents($parent.'/pdferror.log', implode( "\n", $output ));
	    }
	    $this->header("Content-type: application/pdf");
            $this->header('Content-Disposition: attachment;filename="' .$file_name . '"');
	    echo file_get_contents($tmppdf);
	    unlink($tmphtml);
	    unlink($tmppdf);
	} else {
            $this->header('Content-Disposition: attachment;filename="' .$file_name . '"');
            $this->header('Cache-Control: max-age=0');
            $this->show_controls = false;
        }
        
	if (!$this->compiled_html) {
	    /*
	     * Find suitable  template to output needed file type
	     */

	    $this->setup_compilator($out_extension);
	}
	
	
	
	
	
        if ($this->compilator == 'PHPExcel') {
            if ($out_extension == '.html' || $is_printpage) {
                $this->header('Content-Type: text/html; charset="utf-8"');
                $this->Writer = new PHPExcel_Writer_HTML($this->PHPexcel);
                $style = $this->Writer->generateStyles(true);
                $page='<div class="page">' . $this->Writer->generateSheetData() . '</div>';
		if( $this->post_processor ){
		    $page=call_user_func($this->post_processor, $page);
		}
		$html=$style.$page;
                $export_types = $this->export_types;
                $show_controls = $this->show_controls;
                $user_data = $this->user_data;
                include 'FileEngineWrapper.php';
            } else if ($out_extension == '.xls') {
                $this->header('Content-Type: application/vnd.ms-excel');
                $this->Writer = PHPExcel_IOFactory::createWriter($this->PHPexcel, 'Excel5');
                $this->Writer->save('php://output');
            } else if ($out_extension == '.xlsx') {
                $this->header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $this->Writer = PHPExcel_IOFactory::createWriter($this->PHPexcel, 'Excel2007');
                $this->Writer->save('php://output');
            }
        } else
        if ($this->compilator == 'Rain') {
            if ($out_extension == '.html') {
                $this->header('Content-type: text/html; charset=utf-8;');
                if ($this->compiled_html) {
                    $html = $this->compiled_html;
                } else {
                    $html = $this->rain->draw($this->tpl_file, true);
                }
                $export_types = $this->export_types;
                $show_controls = $this->show_controls;
                $user_data = $this->user_data;
                include 'FileEngineWrapper.php';
            } else if ($out_extension == '.doc') {
                $this->header("Content-type: application/octet-stream");
                if ($this->compiled_html) {
                    $html = $this->compiled_html;
                } else {
                    $html = $this->rain->draw($this->tpl_file, true);
                }
                $word_header = true;
                include 'FileEngineWrapper.php';
            } else if ($out_extension == '.xml') {
                $this->header('Content-type: text/xml; charset=windows-1251;');
                $xml = $this->rain->draw($this->tpl_file, true);
                echo iconv('utf-8', 'windows-1251', $xml);
            }
        }
    }

    public function fetch($file_name) {
        ob_start();
        $this->send($file_name);
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    private function renderWorkbook() {
	error_reporting(E_ALL ^ E_NOTICE);
	$highestRow = $this->Worksheet->getHighestRow(); // e.g. 10
	$highestColumn = $this->Worksheet->getHighestColumn(); // e.g 'F'
	$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5
	$currentLoopRow=NULL;
	for($row=1; $row<=$highestRow; ++$row){
	    for ($col = 0; $col <= $highestColumnIndex; ++$col) {
		$cellRawValue=$this->Worksheet->getCellByColumnAndRow($col, $row)->getValue();
		if( strpos($cellRawValue,'[]')!==false ){//containing loop
		    if( $currentLoopRow!==$row ){
			$currentLoopRow=$row;
			$insertedRowCount=$this->loopInsertRows($col, $row, $cellRawValue);
                        if( !$insertedRowCount ){//loop not found
                            break;
                        }
			$highestRow+=$insertedRowCount;
		    }
		    $this->loopColFill($col, $row, $cellRawValue, $insertedRowCount);
		    $cellRawValue=$this->Worksheet->getCellByColumnAndRow($col, $row)->getValue();
		}
		if( strpos($cellRawValue,'$v')!==false ){
		    $v=$this->view;
		    $cellValue=eval('return "' . addslashes($cellRawValue) . '";');
		    $this->Worksheet->getCellByColumnAndRow($col, $row+$i)->setValue($cellValue);
		}
	    }
	}
    }

    private function loopInsertRows($col, $row, $cellRawValue){
	$v=$this->view;
	preg_match('/(\$[\-\>\w]*)\[\]\.*/',$cellRawValue, $matches);
	$loopArrayName=$matches[1];
	$loopArray=eval("return $loopArrayName;");
	$loopCount=count($loopArray);
	if( $loopCount>1 ){
	    $this->Worksheet->insertNewRowBefore($row + 1, $loopCount - 1);
	}
	return $loopCount;
    }
    
    private function loopColFill($col, $row, $cellRawValue, $insertedRowCount){
	$mergingRange=$this->loopColMergeGet($col, $row);
	for($i=0;$i<$insertedRowCount;$i++){
	    if( strpos($cellRawValue,'[]->i') ){
		$cellLoopValue=$i+1;
	    } else {
		$cellLoopValue=  str_replace('[]', "[$i]", $cellRawValue);
	    }
	    $this->Worksheet->getCellByColumnAndRow($col, $row+$i)->setValue($cellLoopValue);
	    if($mergingRange){
		$currentRange=  str_replace($row, $row+$i, $mergingRange);
		$this->Worksheet->mergeCells($currentRange);
	    }
	}
    }
    
    private function loopColMergeGet($col, $row){
	$cell=$this->Worksheet->getCellByColumnAndRow($col, $row);
	foreach($this->Worksheet->getMergeCells() as $cells) {
	    if ($cell->isInRange($cells)) {
		return $cells;
	    }
	}
    }
}
