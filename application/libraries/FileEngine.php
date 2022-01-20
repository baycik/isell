<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

class FileEngine {
    private $conversion_table = [
        '.html' => ['.html' => 'Веб Страница', '.pdf' => 'PDF'],
        '.xlsx' => ['.xlsx' => 'Excel', '.html' => 'Веб Страница', '.pdf' => 'PDF'],
        '.xml'  => ['.xml' => 'XML Экспорт Данных']
    ];
    private $view;
    private $tpl_files;
    private $export_types = array();
    private $tpl_ext;
    private $compilator;
    private $compiled_html;
    private $post_processor = null;
    private $page_orientation = 'portrait';
    public $header_mode = 'send_headers';
    public $user_data;
    public $file_name_override;
    public $tplModifier;
    public $tpl_files_folder = 'application/views/rpt/';

    private function header($text) {
        if ($this->header_mode === 'send_headers') {
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
            $this->compilator = 'PhpSpreadsheet';
            try {
                $this->Spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_name);
            } catch (Exception $e) {
                die("Can't load the template of view! $file_name");
            }
            $this->Worksheet = $this->Spreadsheet->getActiveSheet();
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

    public function setPageOrientation($orientation) {
        $this->page_orientation = $orientation;
    }

    private function compile($tpl_file) {
        $this->loadFileTpl($tpl_file);
        if (file_exists($tpl_file . '.php')) {
            /* Script for custom processing of templates */
            include $tpl_file . '.php';
        }
        if ($this->compilator == 'PhpSpreadsheet') {
            if (isset($this->tplModifier)) {
                $this->Worksheet = call_user_func_array($this->tplModifier, [$this, $this->Worksheet]);
            }
            $this->renderWorkbook();
        } else if ($this->compilator == 'Rain') {
            $this->rain->assign('v', $this->view);
        }
    }

    private function setup_compilator($out_ext) {//Finding suitable template to output needed file type
        $tpl_file_byformat=null;
        $tpl_file_byexpiration=null;
        foreach ($this->tpl_files as $tpl_file) {
            $tpl_ext = substr($tpl_file, strrpos($tpl_file, '.'));
            if ($this->conversion_table[$tpl_ext]) {
                $this->export_types = array_merge($this->export_types, $this->conversion_table[$tpl_ext]);
            }
            if (!$this->tpl_ext && in_array($out_ext, array_keys($this->conversion_table[$tpl_ext]))) {
                $this->tpl_ext = $tpl_ext;
                $tpl_file_byformat=$tpl_file;
            }
        }
        $tpl_file_basename=str_replace($this->tpl_ext,'',$tpl_file_byformat);
        $tpl_file_search_pattern="{$this->tpl_files_folder}{$tpl_file_basename}*{$this->tpl_ext}";
        $tpl_file_list=glob($tpl_file_search_pattern);
        arsort($tpl_file_list);
        if( isset($this->view->doc_view->tstamp) ){
            $view_date=explode(' ',$this->view->doc_view->tstamp);
            foreach($tpl_file_list as $filename){
                preg_match('/(\d\d\d\d-\d\d-\d\d)/', $filename, $out);
                if( isset($out[0]) && $view_date[0]>=$out[0] ){
                    $tpl_file_byexpiration=$filename;
                    break;
                }
            }
        }
        if(!$tpl_file_byexpiration){
            $tpl_file_byexpiration= array_shift($tpl_file_list);
        }
        $this->compile($tpl_file_byexpiration);
    }

    public function send($file_name, $is_printpage = false) {
        $out_extension = substr($file_name, strrpos($file_name, '.'));
        if ($out_extension == '.print') {
            $out_extension = '.html';
            $is_printpage = true;
            $this->show_controls = true;
        } else {
            $this->header('Content-Disposition: attachment;filename="' . $file_name . '"');
            $this->header('Cache-Control: max-age=0');
            $this->show_controls = false;
        }
        
        if ($out_extension == '.pdf') {
            $this->header_mode = 'noheaders';
            $full_html = $this->fetch(".html");
            $this->header_mode = 'send_headers';
            $parent = realpath(BAY_STORAGE . 'wkhtml/');
            $rnd = rand(10, 1000000);
            $tmphtml = $parent . "/html-tmp$rnd.html";
            $tmppdf = $parent . "/pdf-tmp$rnd.pdf";
            $pdfengine = $parent . '/wkhtmltopdf.exe';
            file_put_contents($tmphtml, $full_html);
            exec("$pdfengine --zoom 1.2 -O $this->page_orientation $tmphtml $tmppdf  2>&1", $output);
            if (count($output)) {
                file_put_contents($parent . '/pdferror.log', implode("\n", $output));
            }
            $this->header("Content-type: application/pdf");
            $this->header('Content-Disposition: attachment;filename="' . $file_name . '"');
            header('Content-Length: ' . filesize($tmppdf));
            echo file_get_contents($tmppdf);
            unlink($tmphtml);
            unlink($tmppdf);
            return true;
        }
        
        $this->setup_compilator($out_extension);
        if ($this->compilator == 'PhpSpreadsheet') {
            if ($out_extension == '.html' || $is_printpage) {
                $this->header('Content-Type: text/html; charset="utf-8"');
                $this->Writer = new \PhpOffice\PhpSpreadsheet\Writer\Html($this->Spreadsheet);
                $style = $this->Writer->generateStyles(true);
                $page='' . $this->Writer->generateSheetData() . '';
                if ($this->post_processor) {
                    $page = call_user_func($this->post_processor, $page);
                }
                $html = $style . $page;
                $export_types = $this->export_types;
                $show_controls = $this->show_controls;

                $context = [
                    'user_data' => $this->user_data,
                    'export_types' => $this->export_types,
                    'show_controls' => $this->show_controls,
                    'page_orientation'=>$this->page_orientation,
                    'html' => $html,
                    'view' => $this->view
                ];
                $this->Hub->load->view('rpt/FileEngineWrapper', $context);
            } else if ($out_extension == '.xlsx') {
                $this->header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $this->Writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this->Spreadsheet, "Xlsx");
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
                $context = [
                    'export_types' => $this->export_types,
                    'show_controls' => $this->show_controls,
                    'html' => $html,
                    'view' => $this->view
                ];
                $this->Hub->load->view('rpt/FileEngineWrapper', $context);
            } else if ($out_extension == '.doc') {
                $this->header("Content-type: application/octet-stream");
                if ($this->compiled_html) {
                    $html = $this->compiled_html;
                } else {
                    $html = $this->rain->draw($this->tpl_file, true);
                }
                $word_header = true;
                include APPPATH . 'views/rpt/FileEngineWrapper.php';
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
////////////////////////////////////////////////////////
//SPREADSHEET TEMPALTE RENDERING ENGINE
////////////////////////////////////////////////////////
    private function renderWorkbook() {
        //return;
        error_reporting(E_ALL ^ E_NOTICE);
        $highestRow = $this->Worksheet->getHighestRow(); // e.g. 10
        $highestColumn = $this->Worksheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        for ($row = 1; $row <= $highestRow; ++$row) {
            $currentLoopRow = NULL;
            for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                $cellRawValue = $this->Worksheet->getCellByColumnAndRow($col, $row)->getValue();
                if (strpos($cellRawValue, '[]') !== false) {//containing loop
                    //echo "col$col row$row cellRawValue$cellRawValue currentLoopRow$currentLoopRow\n\n";
                    if ($currentLoopRow !== $row) {
                        $currentLoopRow = $row;
                        $insertedRowCount = $this->loopInsertRows($col, $row, $cellRawValue);
                        if (!$insertedRowCount) {//loop not found
                            break;
                        }
                        $highestRow += $insertedRowCount;
                    }
                }
                if ($currentLoopRow !== NULL) {
                    $this->loopColFill($col, $row, $cellRawValue, $insertedRowCount);
                    $cellRawValue = $this->Worksheet->getCellByColumnAndRow($col, $row)->getValue();
                }
                if (strpos($cellRawValue, '$v') !== false) {
                    $v = $this->view;
                    $cellValue = eval('return "' . addslashes($cellRawValue) . '";');
                    $this->Worksheet->getCellByColumnAndRow($col, $row)->setValue($cellValue);
                }
            }
        }
    }

    private function loopInsertRows($col, $row, $cellRawValue) {
        $v = $this->view;
        preg_match('/(\$[\-\>\w\[\d*\]]*)\[\]\.*/', $cellRawValue, $matches);
        $loopArrayName = $matches[1];
        $loopArray = eval("return $loopArrayName;");
        $loopCount = $loopArray?count($loopArray):0;
        if ($loopCount > 1) {
            $this->Worksheet->insertNewRowBefore($row + 1, $loopCount - 1);
        }
        return $loopCount;
    }

    private function loopColFill($col, $row, $cellRawValue, $insertedRowCount) {
        $mergingRange = $this->loopColMergeGet($col, $row);
        for ($i = 0; $i < $insertedRowCount; $i++) {
            if (strpos($cellRawValue, '[]->i')) {
                $cellLoopValue = $i + 1;
            } else {
                $cellLoopValue = str_replace('[]', "[$i]", $cellRawValue);
            }
            $this->Worksheet->getCellByColumnAndRow($col, $row + $i)->setValue($cellLoopValue);
            if ($mergingRange) {
                $currentRange = str_replace($row, $row + $i, $mergingRange);
                $this->Worksheet->mergeCells($currentRange);
            }
        }
    }

    private function loopColMergeGet($col, $row) {
        $cell = $this->Worksheet->getCellByColumnAndRow($col, $row);
        foreach ($this->Worksheet->getMergeCells() as $cells) {
            if ($cell->isInRange($cells)) {
                return $cells;
            }
        }
    }
    
    public function copyRows( $srcRange, $dstCell, array $search=null, array $replace=null ) {

        if( !isset($destSheet)) {
            $destSheet = $this->Worksheet;
        }

        if( !preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', $srcRange, $srcRangeMatch) ) {
            // Invalid src range
            return;
        }

        if( !preg_match('/^([A-Z]+)(\d+)$/', $dstCell, $destCellMatch) ) {
            // Invalid dest cell
            return;
        }

        $srcColumnStart = $srcRangeMatch[1];
        $srcRowStart = $srcRangeMatch[2];
        $srcColumnEnd = $srcRangeMatch[3];
        $srcRowEnd = $srcRangeMatch[4];

        $destColumnStart = $destCellMatch[1];
        $destRowStart = $destCellMatch[2];

        $srcColumnStart = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($srcColumnStart);
        $srcColumnEnd = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($srcColumnEnd);
        $destColumnStart = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($destColumnStart);

        if ($srcRowEnd-$srcRowStart > 0) {
            $this->Worksheet->insertNewRowBefore($destRowStart, $srcRowEnd-$srcRowStart+1);
        }
        $rowCount = 0;
        for ($row = $srcRowStart; $row <= $srcRowEnd; $row++) {
            $colCount = 0;
            for ($col = $srcColumnStart; $col <= $srcColumnEnd; $col++) {
                $cell = $this->Worksheet->getCellByColumnAndRow($col, $row);
                $cellText=$cell->getValue();
                if( $search ){
                    $cellText= str_replace($search, $replace, $cellText);
                }
                $style = $this->Worksheet->getStyleByColumnAndRow($col, $row);
                $dstCell = PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($destColumnStart + $colCount) . (string)($destRowStart + $rowCount);
                $destSheet->setCellValue($dstCell, $cellText);
                $destSheet->duplicateStyle($style, $dstCell);

                // Set width of column, but only once per column
                if ($rowCount === 0) {
                    $w = $this->Worksheet->getColumnDimensionByColumn($col)->getWidth();
                    $destSheet->getColumnDimensionByColumn ($destColumnStart + $colCount)->setAutoSize(false);
                    $destSheet->getColumnDimensionByColumn ($destColumnStart + $colCount)->setWidth($w);
                }

                $colCount++;
            }

            $h = $this->Worksheet->getRowDimension($row)->getRowHeight();
            $destSheet->getRowDimension($destRowStart + $rowCount)->setRowHeight($h);

            $rowCount++;
        }

        foreach ($this->Worksheet->getMergeCells() as $mergeCell) {
            $mc = explode(":", $mergeCell);
            $mergeColSrcStart = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(preg_replace("/[0-9]*/", "", $mc[0]));
            $mergeColSrcEnd = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(preg_replace("/[0-9]*/", "", $mc[1]));
            $mergeRowSrcStart = ((int)preg_replace("/[A-Z]*/", "", $mc[0]));
            $mergeRowSrcEnd = ((int)preg_replace("/[A-Z]*/", "", $mc[1]));

            $relativeColStart = $mergeColSrcStart - $srcColumnStart;
            $relativeColEnd = $mergeColSrcEnd - $srcColumnStart;
            $relativeRowStart = $mergeRowSrcStart - $srcRowStart;
            $relativeRowEnd = $mergeRowSrcEnd - $srcRowStart;

            if (0 <= $mergeRowSrcStart && $mergeRowSrcStart >= $srcRowStart && $mergeRowSrcEnd <= $srcRowEnd) {
                $targetColStart = PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($destColumnStart + $relativeColStart);
                $targetColEnd = PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($destColumnStart + $relativeColEnd);
                $targetRowStart = $destRowStart + $relativeRowStart;
                $targetRowEnd = $destRowStart + $relativeRowEnd;

                $merge = (string)$targetColStart . (string)($targetRowStart) . ":" . (string)$targetColEnd . (string)($targetRowEnd);
                //Merge target cells
                $destSheet->mergeCells($merge);
            }
        }
    }

    public static function copyStyleXFCollection(Spreadsheet $sourceSheet, Spreadsheet $destSheet) {
        $collection = $sourceSheet->getCellXfCollection();
        foreach ($collection as $key => $item) {
            $destSheet->addCellXf($item);
        }
    }
    
    public function splitToPages($table_template_source_range,$tables){
        preg_match_all('/\d+/', $table_template_source_range, $output_array);
        $table_template_starting_row=$output_array[0][0];
        $table_template_final_row=$output_array[0][1];
        $table_template_row_count = $table_template_final_row-$table_template_starting_row+1;
        for( $i=1;$i<$this->view->tables_count;$i++){
            $table_template_destination_cell="A".($table_template_starting_row+$table_template_row_count*$i);
            $this->copyRows($table_template_source_range, $table_template_destination_cell,['tables[0]','{$current_page}'],["tables[$i]",$i+1]);
            $page_break_current="A".($table_template_starting_row+$table_template_row_count*$i);
            $this->Worksheet->setBreak($page_break_current, \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::BREAK_ROW);
        }
    }
    
//    
//    
//    
//    
//    public function prepareChart(){
//        
//        
//        PhpOffice\PhpSpreadsheet\Settings::setChartRenderer(\PhpOffice\PhpSpreadsheet\Chart\Renderer\JpGraph::class);
//        
//        
//        
//        foreach ($this->Worksheet->getChartCollection() as &$chart) {
//            if ($chart instanceof \PhpOffice\PhpSpreadsheet\Chart\Chart) {
//                $plotArea = $chart->getPlotArea(); //get the plot area of the chart (one thing)
//                $dataSeries = $plotArea->getPlotGroup(); //array of all the data series
//                $dataManipulated = false;
//                foreach ($dataSeries as &$dataSer) { //by reference to change the values deep down!!
//                    $val = $dataSer->getPlotValues();
//                    foreach ( $val as &$dataSeriesValues) {
//                        
//                        
//                        
//                        $dataSource = $dataSeriesValues->getDataSource();
//                        $dataSeriesValues->setDataSource('Report!$J$20');
//                        
//                        
//                        //$dataValues = $dataSeriesValues->getDataValues();
//                        
//                        
//                        $dataValues = [
//                            523,
//                            562,
//                            42
//                        ];
//                        
//                        
//                        
//                        $dataSeriesValues->setDataValues( $dataValues );
//                        
//                        
//                        
//                        
////                        $dataValuesLength = count($dataValues);
////                        for ($y=$pointCount; $y < $dataValuesLength; $y++) {
////                            unset($dataValues[$y]);
////                        }                        
////                        if ( strpos($dataSource, 'DRange_Dates_' . $tf ) !== false ) {
////                            $dataSeriesValues->setDataSource( $sheetName . '!D2:' . $xC . '2' );                            
////                        } elseif ( strpos($dataSource, 'DRange_AvgVarInt_modified_' . $tf ) !== false ) {
////                            $dataSeriesValues->setDataSource( 'midlayer!B10:' . $xCML . '10' ); 
////                        } elseif ( strpos($dataSource, 'DRange_AvgVarRate_' . $tf ) !== false ) {
////                            $dataSeriesValues->setDataSource( 'midlayer!B7:' . $xCML . '7' ); 
////                        }
//                        
//                    }
//                    
//                    
//                    
//                    
//                    
//                    //$cat = $dataSer->getPlotCategories();
////                    foreach ( $cat as &$categoryValues) {
////                        $dataSource = $categoryValues->getDataSource();
////                        $dataValues = $categoryValues->getDataValues();
////                        
////                        
////                        
////                        
////                        
////                        print_r($dataValues);die;
////                        
////                        
////                        
////                        
////                        
////                        
////                        $dataValuesLength = count($dataValues);
////                        for ($y=$pointCount; $y < $dataValuesLength; $y++) {
////                            unset($dataValues[$y]);
////                        }                        
////                        if ( strpos($dataSource, 'DRange_Dates_' . $tf ) !== false ) {
////                            $categoryValues->setDataSource( $sheetName . '!D2:' . $xC . '2' );                            
////                        } elseif ( strpos($dataSource, 'DRange_AvgVarInt_modified_' . $tf ) !== false ) {
////                            $categoryValues->setDataSource( 'midlayer!B10:' . $xCML . '10' ); 
////                        } elseif ( strpos($dataSource, 'DRange_AvgVarRate_' . $tf ) !== false ) {
////                            $categoryValues->setDataSource( 'midlayer!B7:' . $xCML . '7' ); 
////                        }
////                        $categoryValues->setDataValues( $dataValues );
////                    }
//                }
//                $plotArea->setPlotSeries($dataSeries); 
//                unset($dataSeriesValues); // break the reference with the last element
//                unset($categoryValues); // break the reference with the last element
//                unset($dataSer); // break the reference with the last element  
//            }
//        }
//    }
//    
//    
    
    
    
    
}