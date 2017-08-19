<?php
class Importer extends Catalog{

    public $listFetch=['label'=>'string', 'offset'=>'int','limit'=>'int','sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json'];
    public function listFetch($label, $offset,$limit,$sortby,$sortdir,$filter=null){
	if( empty($sortby) ){
	    $sortby='A';
	}
	$having=$this->makeFilter($filter);
	$sql="
	    SELECT 
		*
	    FROM 
		imported_data
            WHERE label LIKE '%$label%'
	    HAVING $having
	    ORDER BY $sortby $sortdir
	    LIMIT $limit OFFSET $offset";
	return $this->get_list($sql);
    }
    
    public $entryUpdate=['row_id'=>'int','field'=>'string','value'=>'string'];
    public function entryUpdate($row_id,$field,$value){
	$data=[$field=>$value];
	return $this->update('imported_data',$data,['row_id'=>$row_id]);
    }

    public $entryDelete=['import_ids'=>'raw'];
    public function entryDelete($row_ids){
	return $this->delete('imported_data','row_id',$row_ids);
    }
    
    public $entryCreate=['label'=>'string'];
    public function entryCreate($label){
	$insert_id=$this->create('imported_data',['label'=>$label]);
	$this->update('imported_data',['A'=>$insert_id],['row_id'=>$insert_id]);
	return $insert_id;
    }
    
    public $Up=['label'=>'string'];
    public function Up( $label ){
	$f=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q'];
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
    public $deleteRows=['row_ids'=>'[\d,]+'];
    public function deleteRows($row_ids){
	$this->query("DELETE FROM imported_data WHERE row_id IN ($row_ids)");
	return $this->db->affected_rows();
    }
    public $deleteAll=['label'=>'string'];
    public function deleteAll( $label ){
	if( $label ){
	    $where="WHERE label='$label'";
	} else {
	    $where='';
	}
	$this->query("DELETE FROM imported_data $where");
	return $this->db->affected_rows();
    }
    
    public $viewGet=['label'=>'string','out_type'=>'string'];
    public function viewGet($label,$out_type){
	$table=$this->get_list("SELECT * FROM imported_data WHERE IF('$label',label='$label',1)");
	$struct=$this->get_list("SHOW FULL COLUMNS FROM imported_data");
	array_shift($struct);
	array_shift($struct);
	$dump=[
	    'tpl_files'=>'/GridTpl.xlsx',
	    'title'=>"Экспорт таблицы",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'struct'=>$struct,
	    'view'=>[
		'rows'=>$table
	    ]
	];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}
