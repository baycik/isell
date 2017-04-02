<?php
class Importer extends Catalog{
    public $getRows=['label'=>'string','page'=>['int',1],'rows'=>['int',50]];
    public function getRows( $label,$page,$rows ){
	if( $label ){
	    $where="WHERE label LIKE '%$label%'";
	} else {
	    $where='';
	}
	$total=$this->get_value("SELECT COUNT(*) FROM imported_data WHERE label LIKE '%$label%'");
	$entries=$this->get_list("SELECT * FROM imported_data $where LIMIT $rows OFFSET ".(($page-1)*$rows));
	return ['rows'=>$entries,'total'=>$total];
    }
    public $Up=['label'=>'string'];
    public function Up( $label ){
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
			$value = $cell->getCalculatedValue();
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
    public $deleteRows=['row_ids'=>'[\d,]+'];
    public function deleteRows($row_ids){
	$this->query("DELETE FROM imported_data WHERE row_id IN ($row_ids)");
	return $this->db->affected_rows();
    }
    public $deleteAll=['label'=>'[\w_\d]+'];
    public function deleteAll( $label ){
	if( $label ){
	    $where="WHERE label='$label'";
	} else {
	    $where='';
	}
	$this->query("DELETE FROM imported_data $where");
	return $this->db->affected_rows();
    }
    
}
