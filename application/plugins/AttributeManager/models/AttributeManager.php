<?php
/* Group Name: Склад
 * User Level: 2
 * Plugin Name: Менеджер аттрибутов
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: Tool for managing product attributes
 * Author: baycik 2019
 * Author URI: http://isellsoft.com
 */
class AttributeManager extends Catalog{
    
    public $min_level=3;
    public function install(){
	$install_file=__DIR__."/install.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($install_file);
    }
    public function uninstall(){
	$uninstall_file=__DIR__."/uninstall.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($uninstall_file);
    }
    
    public function view( string $path ){
	$this->load->view($path);
    }
    
    public $listFetch = ['offset' => ['int', 0], 'limit' => ['int', 5], 'sortby' => 'string', 'sortdir' => '(ASC|DESC)', 'filter' => 'json'];
    public function listFetch( $offset, $limit, $sortby, $sortdir, $filter = null){
        if (empty($sortby)) {
	    $sortby = "attribute_name";
	    $sortdir = "ASC";
	}
        $null = null;
        $having = '';
        if($filter){
           $having = "HAVING ".$this->makeFilter($filter); 
        };
        $where = '';
	$sql ="
            SELECT *
            FROM attribute_list
            $having
            ORDER BY $sortby $sortdir
            LIMIT $limit OFFSET $offset
            ";
	return $this->get_list($sql);
    }
    
    public $attributeUpdate = ['attribute_id' => 'int', 'attribute_name' => 'string', 'attribute_unit' => 'string', 'attribute_prefix' => 'string'];
    public function attributeUpdate( $attribute_id, $attribute_name, $attribute_unit, $attribute_prefix ){
        if($attribute_id == 0){
            $sql = "
                INSERT INTO
                    attribute_list
                SET 
                    attribute_name = '$attribute_name',
                    attribute_unit = '$attribute_unit',
                    attribute_prefix = '$attribute_prefix'
                ";
            return  $this->query($sql);          
        };
        $sql = "
            UPDATE 
                attribute_list
            SET 
                attribute_name = '$attribute_name',
                attribute_unit = '$attribute_unit',
                attribute_prefix = '$attribute_prefix'
            WHERE attribute_id = $attribute_id       
            ";
        return  $this->query($sql);
    }
    
    public $attributeValueUpdate = ['attribute_id' => 'int', 'product_id' => 'int', 'attribute_value' => 'string'];
    public function attributeValueUpdate( $attribute_id, $product_id, $attribute_value ){
        if($attribute_id == 0){
            return;          
        };
        $sql = "
            UPDATE 
                attribute_values
            SET 
                attribute_value = '$attribute_value'
            WHERE attribute_id = $attribute_id 
                AND product_id = '$product_id'
            ";
        return  $this->query($sql);
    }
    
    
    public $attributeDelete = ['rows' => 'json'];
    public function attributeDelete( $rows ){
        foreach($rows as $row){
            $sql = "
            DELETE FROM 
                attribute_list
            WHERE attribute_id = {$row['attribute_id']}       
            ";
            $this->query($sql); 
        }
        return  $this->query($sql);
    }
    
    public $getAttributes = [];
    public function getAttributes(){
        $sql = "
            SELECT 
                *
            FROM 
                attribute_list
            ORDER BY attribute_name";
        return $this->get_list($sql);
    }
    
     public $getAttributesByCode = ['product_code' => 'string'];
    public function getAttributesByCode($product_code){
        $sql = "
            SELECT * 
            FROM 
                attribute_values av
            JOIN 
		attribute_list USING (attribute_id)
            WHERE 
                av.product_id = (SELECT product_id FROM prod_list WHERE product_code = '$product_code')
            ORDER BY attribute_name
        ";
        return $this->get_list($sql);
    }
    
    
    public $addProduct = [ 'attribute_id' => 'string', 'product_code' => 'string', 'attribute_value' => 'string' ];
    public function addProduct ($attribute_id, $product_code, $attribute_value){
        $sql = "
            INSERT INTO
                    attribute_values (attribute_id, product_id, attribute_value)
            VALUE({$attribute_id},(SELECT product_id FROM prod_list WHERE product_code = '$product_code'), '$attribute_value' )       
            ";
        
        return $this->query($sql);        
    }
       
    
    public $deleteProduct = [ 'rows' => 'json' ];
    public function deleteProduct ($rows){
        $cases=[];
        foreach($rows as $row){
            $cases[]="(product_id={$row['product_id']} AND attribute_id={$row['attribute_id']})";
        }
        $where= implode(' OR ', $cases);
        $sql = "
            DELETE FROM
                    attribute_values 
            WHERE $where";
        return $this->query($sql);        
    }
        
    
    public $getProducts = [ 'attribute_id' => 'int','offset' => ['int', 0], 'limit' => ['int', 5], 'sortby' => 'string', 'sortdir' => '(ASC|DESC)', 'filter' => 'json'];
    public function getProducts( $attribute_id,$offset, $limit, $sortby, $sortdir, $filter = null ){
         if (empty($sortby)) {
	    $sortby = "attribute_name";
	    $sortdir = "ASC";
	}
        $having = '';
        $where = '';
        if($filter){
           $having = "HAVING ".$this->makeFilter($filter);
        }
        if($attribute_id){
            $where="WHERE attribute_id = '$attribute_id'";
        }
        $sql = "
            SELECT 
                attribute_list.*, attribute_value,
                ru, product_code, product_id
            FROM 
                attribute_values
                    JOIN
                prod_list USING(product_id)
                    JOIN
                attribute_list USING(attribute_id)    
            $where
            $having
            ORDER BY product_code, $sortby $sortdir
            LIMIT $limit OFFSET $offset
            ";
        return $this->get_list($sql);
    }
    
    public $import = ['label' => 'string', 'source' => 'raw', 'target' => 'raw'];
    public function import($label, $source, $target ) {
	$source = array_map('addslashes', $source);
	$target = array_map('addslashes', $target);
	return $this->importInTable( $source, $target, $label);
    }

    private function importInTable($src, $trg, $label) {
        $product_code_source_column='';
        $attributes=[];
        for ($i = 0; $i < count($src); $i++) {
            $source_column=$src[$i];
            if($trg[$i]=='product_code'){
                $product_code_source_column=$source_column;
            } else if($source_column){
                $target=explode('_',$trg[$i]);
                $target_attribute_id=$target[1];
                $attributes[$target_attribute_id]=$source_column;
            }
        }
        foreach ($attributes as $attribute_id=>$attribute_source_column){
            $attribute_units=$this->get_row("SELECT attribute_unit,attribute_prefix FROM attribute_list WHERE attribute_id=$attribute_id");
            $attribute_value="$attribute_source_column";
            if($attribute_units){
                if($attribute_units->attribute_unit){
                    $attribute_value="REPLACE($attribute_value,'{$attribute_units->attribute_unit}','')";
                    $unit_translit=iconv('UTF-8', 'ASCII//TRANSLIT', $attribute_units->attribute_unit);
                    $attribute_value="REPLACE($attribute_value,'{$unit_translit}','')";
                }
                if($attribute_units->attribute_prefix){
                    $attribute_value="REPLACE($attribute_value,'{$attribute_units->attribute_prefix}','')";
                    $prefix_translit=iconv('UTF-8', 'ASCII//TRANSLIT', $attribute_units->attribute_prefix);
                    $attribute_value="REPLACE($attribute_value,'{$prefix_translit}','')";
                }
            }
            
            $sqli="INSERT IGNORE INTO
                    attribute_values 
                (`attribute_id`,`product_id`,`attribute_value`)
                SELECT 
                    $attribute_id,product_id,$attribute_value
                FROM 
                    imported_data
                        JOIN
                    prod_list ON product_code = $product_code_source_column
                WHERE 
                    label LIKE '%$label%'
                ON DUPLICATE KEY UPDATE attribute_value={$attribute_source_column};";
            $this->query($sqli);
            $total_rows=$this->db->affected_rows();
        }
        $this->query("DELETE FROM attribute_values WHERE attribute_value=''");
        $this->query("DELETE FROM imported_data WHERE label LIKE '%$label%' AND {$product_code_source_column} IN (SELECT product_code FROM prod_list pl JOIN attribute_values av USING (product_id))");
	return $total_rows;
    }
    
    public $tableViewGet=['out_type'=>['string','.print'],'attribute_id' => ['int',0], 'filter' => 'json'];
    public function tableViewGet($out_type,$attribute_id,$filter){
	$rows=$this->getProducts( $attribute_id,0, 10000, null, null, $filter = null );
	$dump=[
            'tpl_files_folder'=>"application/plugins/AttributeManager/views/",
	    'tpl_files'=>'GridTpl.xlsx',
	    'title'=>"Экспорт таблицы",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'struct'=>$this->tableStructure(),
	    'view'=>[
		'rows'=>$rows
	    ]
	];

	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
    
    public function tableStructure(){
        return [
            [
                'Field'=>'product_code',
                'Comment'=>'Код'
            ],
            [
                'Field'=>'attribute_name',
                'Comment'=>'Атрибут'
            ],
            [
                'Field'=>'attribute_value',
                'Comment'=>'Значение'
            ],
            [
                'Field'=>'attribute_unit',
                'Comment'=>'Единица'
            ]
        ];
    }
    
    
    /////////////////////////////////////////////////////
    //STOCK MATCHES FILTERING
    /////////////////////////////////////////////////////
    public function filterOut(){
        $selected_hashes=$this->request('selected_hashes','[0-9a-f\&\|]+');
        $selected_list="0";
        if( $selected_hashes ){
            $selected_list= "'".str_replace(["&","|"], "','", $selected_hashes)."'";
        }
        $groupped_filter=$this->filterConstruct( $selected_list );
        $this->Hub->svar('groupped_filter',$groupped_filter);
        
        return $this->filterApply($selected_hashes);
    }
    
    public function filterGet(){
        return $this->Hub->svar('groupped_filter');
    }
    
    private function filterApply( $selected_hashes ){
        if( !$selected_hashes ){
            return '';
        }
        $or_case="'".str_replace('|', "','", $selected_hashes)."'";
        $and_case=" MAX(attribute_value_hash IN (".str_replace('&',"')) * MAX(attribute_value_hash IN ('",$or_case).")) ";
        $sql="
            JOIN 
        (SELECT 
            product_id
        FROM
            attribute_values
        GROUP BY product_id
        HAVING($and_case)) t USING(product_id)";
        return $sql;
    }
    
    private function filterConstruct( $selected_list ){
        $select_checker="0";
        if( $selected_list ){
            $select_checker=" IF( attribute_value_hash IN ($selected_list),1,0) ";
        }
        $sql="
        SELECT 
            al.*,
            attribute_value,
            attribute_value_hash,
            COUNT(*) product_count,
            $select_checker is_selected
        FROM
            tmp_matches_list
                JOIN
            attribute_values av USING(product_id)
                JOIN
            attribute_list al USING(attribute_id)
        GROUP BY attribute_value_hash
        ORDER BY attribute_name,attribute_value";
        $filter_list=$this->get_list($sql);
        $groupped_filter=[];
        $group_index=-1;
        $current_attribute_id=0;
        foreach($filter_list as $entry){
            if( $current_attribute_id != $entry->attribute_id ){
                $group_index++;
                $groupped_filter[$group_index]=[
                    'attribute_id'=>$entry->attribute_id,
                    'attribute_name'=>$entry->attribute_name,
                    'attribute_values'=>[]
                ];
                $current_attribute_id = $entry->attribute_id;
            }
            $groupped_filter[$group_index]['attribute_values'][]=$entry;
        }
        return $groupped_filter;
    }
    
}