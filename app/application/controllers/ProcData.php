<?php

require_once('iSellBase.php');

class ProcData extends iSellBase {

    private $permited_tables;

    function ProcData() {
	$this->permited_tables = json_decode(file_get_contents('config/permited_tables.json', true));
	$this->ProcessorBase(1);
    }

    public function index() {
	$this->response_tpl('data/data_main.html');
    }

    private function checkTable() {
	$table_name = $this->request('table_name');
	foreach ($this->permited_tables as $table) {
	    if ($this->svar('user_level') < $table->level){
		continue;
            }
	    if ($table_name == $table->table_name){
		return $table_name;
            }
	}
	$this->response_wrn("The '$table_name' table is not permitted for browsing!");
    }

    public function onPermitedTableList() {
	$table_list = array("items" => array());
	foreach ($this->permited_tables as $table) {
	    if (isset($table->level) && $this->svar('user_level') < $table->level || isset($table->hidden) && $table->hidden){
		continue;
            }
	    $table_list['items'][] = array('table_name' => $table->table_name,'table_title'=>$table->table_title);
	}
	$this->response($table_list);
    }

    public function onTableStructure() {
	$table_name = $this->checkTable();
	$this->LoadClass('Data');
	$table_structure = $this->Data->getTableStructure($table_name);
	$this->response($table_structure, 1);
    }

    public function onTableData() {
	$table_name = $this->checkTable();
	$table_query = $this->get_table_query();
	$this->LoadClass('Data');
	$table_data = $this->Data->getTableData($table_name, $table_query);
	$this->response($table_data);
    }

    public function onXlsDownload() {
	$table_name = $this->checkTable();
	$table_query = $this->get_table_query();
	$table_query['limit'] = 99999;

	set_time_limit(360);
	$this->LoadClass('Data');
	$request_select = $this->request('select'); //select cols to add to xls
	if ($request_select) {
	    //Lets test if request_select contains real columns
	    $table_structure = $this->Data->getTableStructure($table_name);
	    $request_select = explode(',', $request_select); //array of wanted columns
	    foreach ($table_structure['columns'] as $col) {
		if (in_array($col['field'], $request_select)) {
		    $table_select.=',' . $col['field'];
		    $table_select_names.=',' . $col['name'];
		}
	    }
	    $table_select = substr($table_select, 1);
	    $table_select_names = substr($table_select_names, 1);
	} else {
	    $table_structure = $this->Data->getTableStructure($table_name, 'name');
	    $table_select = '*';
	    $table_select_names = implode(',', $table_structure['columns']);
	}

	$table_data = $this->Data->getTableData($table_name, $table_query, $table_select);
	include 'Lib/report/Report.php';
	$rpt = new Report();
	$rpt->autofit = true;
	//$rpt->addImage('img/header.bmp','A1');
	$rpt->setHeader($table_select_names, 'A2');
	$row_count = count($table_data['rows']);
	for ($i = 0; $i < $row_count; $i++) {
	    $rpt->writeln($table_data['rows'][$i]);
	}
	$rpt->xlsHeaders(date("d.m.Y") . "_DataFrom_$table_name.xls");
	echo $rpt->getXlsData();
    }

    public function onTablePrintOut() {
	$table_name = $this->checkTable();
	$table_query = $this->get_table_query();
	$out_type = $this->request('out_type');

	$this->LoadClass('Data');
	$table_data = $this->Data->getTableData($table_name, $table_query);
	$table_structure = $this->Data->getTableStructure($table_name);


	require_once 'lib/rain/rain.tpl.class.php';
	$tpl = new RainTPL();
	$tpl->configure('tpl_dir', 'tpl/data/');
	$tpl->configure('cache_dir', 'tpl/companies/doc_tpls/tmp/');
	$tpl->assign('table_name');
	$tpl->assign('table_structure', $table_structure);
	$tpl->assign('table_data', $table_data);
	$tpl->assign('date', date('d.m.y'));
	$html = $tpl->draw('table_print_out', true);

	header('Content-type: text/html; charset=utf-8;');
	if ($out_type == 'html_file') {
	    header("Content-Disposition: attachment; filename=\"$doc[view_tpl]_№$doc[view_num].html\"");
	    header("Expires: 0");
	    header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
	    header("Pragma: public");
	    require 'tpl/companies/doc_tpls/document_wrapper.php';
	} else {
	    $show_controls = true;
	    require 'tpl/companies/doc_tpls/document_wrapper.php';
	}
	exit;
    }

//	public function onXlsUpload(){
//		set_time_limit(240);
//		$table_name=$this->checkTable();
//		$this->rmethod='alert';
//		
//		function is_valid( $path ){
//			$fhandle=fopen($path,'r');
//				$header=fread($fhandle,8);
//			fclose($fhandle);
//			return $header == pack("CCCCCCCC",0xd0,0xcf,0x11,0xe0,0xa1,0xb1,0x1a,0xe1);
//		}
//		if( !is_valid($_FILES['Filedata']['tmp_name']) ){
//			$this->response('Неверный формат файла. Формат должен быть .xls (Excel 97-2003).');
//		}
//		
//		require_once 'lib/_______Excel/ExcelParser.php';
//		$file = new ExcelParser();
//		$file->setOutputEncoding('utf-8');
//		$file->read( $_FILES['Filedata']['tmp_name'] );
//		
//		$table_data=$file->sheets[0]['cells'];
//		$this->LoadClass('Data');
//		$this->Data->setTableData($table_name,$table_data);
//		$this->response('Файл был успешно загружен!');
//	}
    public function onDeleteRows() {
	$table_name = $this->checkTable();
	$delete_ids = $this->request('delete_ids', 3);
	$this->LoadClass('Data');
	$this->Data->deleteRows($table_name, $delete_ids);
    }

    public function onUpdateRow() {
	$table_name = $this->checkTable();
	$update_id = $this->request('update_id', 3);
	$update_col = $this->request('update_col');
	$update_value = $this->request('update_val');
	$this->LoadClass('Data');
	$this->Data->updateRow($table_name, $update_id, $update_col, $update_value);
    }

    public function onInsertRow() {
	$table_name = $this->checkTable();
	$insert_id = $this->request('insert_id', 3);
	$this->LoadClass('Data');
	$this->Data->insertRow($table_name, $insert_id);
    }

    public function onDocumentImport() {
	$file_name = 'documentData.xlsx';
	$doc_id = 6570;
	$fields = array(
	    'product_code',
	    'product_quantity',
	    'invoice_price'
	);
	$is_commited = $this->get_row("SELECT is_commited FROM document_list WHERE doc_id=$doc_id", 0);
	if ($is_commited != 0)
	    $this->response_error("Doc is already commited!!!");
	$this->query("DELETE FROM document_entries WHERE doc_id=$doc_id");

	require_once "libraries/report/PHPExcel.php";
	$this->PHPexcel = PHPExcel_IOFactory::load($file_name);
	if ($this->PHPexcel) {
	    $this->Worksheet = $this->PHPexcel->getActiveSheet();
	    foreach ($this->Worksheet->getRowIterator() as $row) {
		$i = 0;
		$rowfields = array();
		foreach ($row->getCellIterator() as $cell) {
		    $value = $cell->getValue();
		    if ($i == 0 && $value == '')
			break;
		    $field = $fields[$i++];
		    if ($field == 'product_quantity')
			$rowfields[] = "product_quantity=product_quantity+'" . addslashes($value) . "'";
		    else
			$rowfields[] = "$field='" . addslashes($value) . "'";
		}
		if (!empty($rowfields)) {
		    $set = implode(',', $rowfields) . ",doc_id=$doc_id";
		    $this->query("INSERT INTO document_entries SET $set ON DUPLICATE KEY UPDATE $set");
		}
	    }
	    $this->response('OK');
	} else
	    $this->response('phpexcel not loaded');
    }

    ///////////////////////////////
    //GRID FUCNCTIONS 
    ///////////////////////////////
    public function onGridStructure() {
	$grid_name = $this->checkTable();
	$this->LoadClass('Data');
	$grid_structure = $this->Data->getGridStructure($grid_name);
	$this->response($grid_structure, 1);
    }

    public function onGridData() {
	$table_name = $this->checkTable();
	$grid_query = $this->getGridQuery();
	$this->LoadClass('Data');
	$table_data = $this->Data->getGridData($table_name, $grid_query); //, "CONCAT('ok ',product_code) AS product_code"
	$this->response($table_data);
    }

    public function onDeleteGridRow() {
	$table_name = $this->checkTable();
	$delIds = $this->request('delIds', 3);
	$this->LoadClass('Data');
	$this->Data->deleteGridRows($table_name, $delIds);
    }

    public function onInsertGridRow() {
	$table_name = $this->checkTable();
	$newrow = $this->request('newrow', 3);
	$this->LoadClass('Data');
	$this->Data->insertGridRow($table_name, $newrow);
    }

    public function onUpdateGridRow() {
	$table_name = $this->checkTable();
	$key = $this->request('key', 3);
	$value = $this->request('value', 3);
	$this->LoadClass('Data');
	$this->Data->updateGridRow($table_name, $key, $value);
    }

    public function onGridUpload() {
	set_time_limit(240);
	$table_name = $this->checkTable();
	$this->rmethod = 'alert';
	$this->LoadClass('Data');
	if ($_FILES['Filedata']['error'] == 0){
	    $result = $this->Data->loadFromFile($table_name, $_FILES['Filedata']['tmp_name']);
	}
	else {
	    $this->response('Ошибка загруки: ' . $_FILES['Filedata']['error']);
	}
	$this->response($result ? 'Файл был успешно загружен!' : 'Возникла ошибка обработки!' . $_FILES['Filedata']['error'] );
    }

    public function onGridOut() {
	set_time_limit(240);
	$table_name = $this->checkTable();
	$grid_query = $this->getGridQuery();
	$out_type = $this->request('out_type', 0, '.print');
	$this->LoadClass('Data');
	$grid_data = $this->Data->getGridData($table_name, $grid_query); //, "CONCAT('ok ',product_code) AS product_code"
	$grid_structure = $this->Data->getGridStructure($table_name);
	$this->Data->getGridOut($grid_structure, $grid_data, $out_type);
	exit;
    }

}

?>