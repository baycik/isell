<?php

require_once 'Catalog.php';
class Importer extends Catalog{
    public function getRows( $label='' ){
	$page=$this->request('page','int',1);
	$rows=$this->request('rows','int',50);
	$this->check($label);
	if( $label ){
	    $where="WHERE label='$label'";
	} else {
	    $where='';
	}
	$total=$this->get_value("SELECT COUNT(*) FROM imported_data WHERE label='$label'");
	$entries=$this->get_list("SELECT * FROM imported_data $where LIMIT $rows OFFSET ".(($page-1)*$rows));
	return ['rows'=>$entries,'total'=>$total];
    }
    public function up( $label='' ){
	$f=['A','B','C','D','E','F','G','H','I','K','L','M','N','O','P','Q'];
	if( $_FILES['upload_file'] && !$_FILES['upload_file']['error'] ){
	    require_once "application/libraries/report/PHPExcel.php";
	    $this->PHPexcel = PHPExcel_IOFactory::load($_FILES['upload_file']["tmp_name"]);
	    if ($this->PHPexcel) {
		$this->Worksheet = $this->PHPexcel->getActiveSheet();
		foreach ($this->Worksheet->getRowIterator() as $row) {
		    $i = 0;
		    $set=['label'=>$label];
		    foreach ($row->getCellIterator() as $cell) {
			$value = $cell->getValue();
			$set[$f[$i++]]=$value==null?"":$value;
			if( $i>15 ){
			    break;
			}
		    }
		    $this->create('imported_data',$set);
		}
		return 'imported';
	    }
	    return 'phpexcel not loaded';
	}
        return 'error'.$_FILES['upload_file']['error'];
    }
    public function deleteRows(){
	$row_ids=$this->request('row_ids','[\d,]+');
	$this->query("DELETE FROM imported_data WHERE row_id IN ($row_ids)");
	return $this->db->affected_rows();
    }
    public function deleteAll( $label='' ){
	$this->check($label,'[\w_\d]+');
	if( $label ){
	    $where="WHERE label='$label'";
	} else {
	    $where='';
	}
	$this->query("DELETE FROM imported_data $where");
	return $this->db->affected_rows();
    }
    
}
