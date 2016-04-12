<?php

class FileEngine {
    private $conversion_table = array();
    private $view;
    private $tpl_files;
    private $export_types = array();
    private $tpl_ext;
    private $compilator;
    private $compiled_html;
    public $file_name_override;
    public $tplModifier;

    public function FileEngine() {
        $this->conversion_table['.html'] = array('.html' => 'Веб Страница', '.doc' => 'Word Документ');
        $this->conversion_table['.xml'] = array('.xml' => 'XML Экспорт Данных');
        $this->conversion_table['.xlsx'] = array('.xlsx' => 'Excel 2007', '.xls' => 'Excel 2003', '.html' => 'Веб Страница', '.pdf' => 'PDF');
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
            include "libraries/report/PHPExcel.php";
            try {
                $this->PHPexcel = PHPExcel_IOFactory::load('application/'.$file_name);
            } catch (Exception $e) {
                die("Can't load the template of view! $file_name");
            }
            $this->Worksheet = $this->PHPexcel->getActiveSheet();
        } else if ($this->tpl_ext == '.html' || $this->tpl_ext == '.xml') {
            $this->compilator = 'Rain';
            include 'libraries/report/RainTPL.php';
            $this->tpl_file = substr($file_name, strrpos($file_name, '/') + 1, strrpos($file_name, '.') - strrpos($file_name, '/') - 1);
            $this->tpl_dir = 'application/'.substr($file_name, 0, strrpos($file_name, '/') + 1);
            $this->rain = new RainTPL();
            $this->rain->configure('tpl_dir', $this->tpl_dir);
            $this->rain->configure('tpl_ext', substr($this->tpl_ext, 1));
            $this->rain->configure('cache_dir', sys_get_temp_dir());
        }
    }
    
    private function compile($tpl_file) {
        $this->loadFileTpl('views/rpt/' . $tpl_file);
	if( file_exists('application/views/rpt/' . $tpl_file.'.php') ){
	    /*Script for custom processing of templates*/
	    include 'application/views/rpt/' . $tpl_file.'.php';
	}
        if ($this->compilator == 'PHPExcel') {
            if (isset($this->tplModifier))
                $this->Worksheet = call_user_func_array($this->tplModifier, array($this, $this->Worksheet));
            $this->renderWorkbook();
        }
        else if ($this->compilator == 'Rain') {
            $this->rain->assign('v', $this->view);
        }
    }

    private function setup_compilator($out_ext) {
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
        $ext = substr($file_name, strrpos($file_name, '.'));
        if ($ext == '.print') {
            $ext = '.html';
            $is_printpage = true;
        } else {
            header('Content-Disposition: attachment;filename="' .$file_name . '"');
            header('Cache-Control: max-age=0');
            $this->show_controls = false;
        }
        $this->setup_compilator($ext);
        if ($this->compilator == 'PHPExcel') {
            if ($ext == '.html' || $is_printpage) {
                header('Content-Type: text/html; charset="utf-8"');
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
                include 'views/rpt/Wrapper.php';
            } else if ($ext == '.xls') {
                header('Content-Type: application/vnd.ms-excel');
                $this->Writer = PHPExcel_IOFactory::createWriter($this->PHPexcel, 'Excel5');
                $this->Writer->save('php://output');
            } else if ($ext == '.xlsx') {
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $this->Writer = PHPExcel_IOFactory::createWriter($this->PHPexcel, 'Excel2007');
                $this->Writer->save('php://output');
            } else if ($ext == '.pdf') {
                header('Content-type: application/pdf');
                $this->Writer = PHPExcel_IOFactory::createWriter($this->PHPexcel, 'PDF');
                $this->Writer->save('php://output');
            }
        } else
        if ($this->compilator == 'Rain') {
            if ($ext == '.html') {
                header('Content-type: text/html; charset=utf-8;');
                if ($this->compiled_html) {
                    $html = $this->compiled_html;
                } else {
                    $html = $this->rain->draw($this->tpl_file, true);
                }
                $export_types = $this->export_types;
                $show_controls = $this->show_controls;
                $user_data = $this->user_data;
                include 'views/rpt/Wrapper.php';
            } else if ($ext == '.doc') {
                header("Content-type: application/octet-stream");
                if ($this->compiled_html) {
                    $html = $this->compiled_html;
                } else {
                    $html = $this->rain->draw($this->tpl_file, true);
                }
                $word_header = true;
                include 'views/rpt/Wrapper.php';
            } else if ($ext == '.xml') {
                header('Content-type: text/xml; charset=windows-1251;');
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

    private function evalVar($tpl, $v) {
        return eval('return ' . $tpl . ';');
    }

    private function evalStr($tpl, $v) {
        return eval('return "' . addslashes($tpl) . '";');
    }

    private function renderWorkbook() {
        $loop_row = NULL;
        foreach ($this->Worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            foreach ($cellIterator as $cell) {
                $cellTpl = $cell->getValue();
                if (strpos($cellTpl, '[loop]') !== false) {
                    preg_match_all('/(\$\w+)([\w\[\]]*)(\[loop\])([\w\[\]]*)?/', $cellTpl, $matches);
                    $loop_row = $row;
                    $loop_row->loop_var = $this->evalVar('$v' . $matches[2][0], $this->view);
                    $loop_row->index = $cell->getRow();
                    break;
                } else {
                    $cell->setValue($this->evalStr($cellTpl, $this->view));
                }
            }
        }
        if (isset($loop_row)) {
            $this->renderLoopRow($loop_row, $this->view);
        }
    }

    private function renderLoopRow($row, $view) {
        $items = $row->loop_var;
        $item_count = count($items);
        if ($item_count > 1){//Insert rows in count of item count of view
            $this->Worksheet->insertNewRowBefore($row->index + 1, $item_count - 1);
        }
        $cellIterator = $row->getCellIterator();
        foreach ($cellIterator as $cell) {
            $cellTpl = $cell->getValue();
            $column_letter = $cell->getColumn();
            //Look for tpl tags
            preg_match('/<(merge)(\d+)>/', $cellTpl, $tpl_tags);
            if( $tpl_tags[1]=='merge' ){
                $merge_width=$tpl_tags[2];
                $merge_final_letter=PHPExcel_Cell::stringFromColumnIndex(PHPExcel_Cell::columnIndexFromString($column_letter)+$merge_width-2);
                $cellTpl=str_replace("<merge$merge_width>", '', $cellTpl );
            } else {
                $merge_width=0;
            }
            //Parse cell tpl. Find path to data in view.
            preg_match_all('/(\$\w+)([\w\[\]]*)(\[loop\])([\w\[\]]*)?/', $cellTpl, $tpl_path);
            $path = $tpl_path[4][0];
            for ($i = 0; $i < $item_count; $i++) {
                $curr_row_index=$row->index + $i;
                $item = $items[$i];
                $item['i'] = $i + 1;
                if( $merge_width>0 ){//There is <mergeNUM> tag must merge NUM cells. Need to merge cell in new row as in tpl row
                    $this->Worksheet->mergeCells("{$column_letter}{$curr_row_index}:{$merge_final_letter}{$curr_row_index}");
                }
                if( $path ){//Evaluate cell if this is loop path
                    $new_cell_value=$this->evalStr("{\$v$path}", $item);
                } else 
                if( substr($cellTpl, 0, 1)==='=' ){//Replace row number of tpl to current row number in formulas
                    $new_cell_value=  str_replace($row->index, $curr_row_index, $cellTpl);
                } else {//copy tpl otherwise
                    $new_cell_value=$cellTpl;
                }
                $this->Worksheet->getCell($column_letter.$curr_row_index)->setValue($new_cell_value);
            }        
        }
    }
}

?>