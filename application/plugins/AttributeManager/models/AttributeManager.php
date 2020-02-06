<?php
/* Group Name: Склад
 * User Level: 2
 * Plugin Name: Attribute manager
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: Tool for managing product attributes
 * Author: baycik 2019
 * Author URI: http://isellsoft.com
 */
class AttributeManager extends Catalog{
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
    public function activate(){
        $Events=$this->Hub->load_model("Events");
        $Events->Topic('beforeMatchesTmpCreated')->subscribe('AttributeManager','filterSetupMatchesTable');
        $Events->Topic('beforeMatchesFilterBuild')->subscribe('AttributeManager','filterBuildGroups');
    }
    public function deactivate(){
        $Events=$this->Hub->load_model("Events");
        $Events->Topic('beforeMatchesTmpCreated')->unsubscribe('AttributeManager','filterSetupMatchesTable');
        $Events->Topic('beforeMatchesFilterBuild')->unsubscribe('AttributeManager','filterBuildGroups');
    }
    
    
    public function view( string $path ){
	$this->load->view($path);
    }
    
    public $listFetch = ['offset' => ['int', 0], 'limit' => ['int', 5], 'sortby' => 'string', 'sortdir' => '(ASC|DESC)', 'filter' => 'json'];
    public function listFetch( $offset, $limit, $sortby, $sortdir, $filter = null){
        $this->Hub->set_level(3);
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
        $this->Hub->set_level(3);
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
        $this->Hub->set_level(3);
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
        $this->Hub->set_level(3);
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
        $this->Hub->set_level(3);
        $sql = "
            SELECT 
                *
            FROM 
                attribute_list
            ORDER BY attribute_name";
        return $this->get_list($sql);
    }
    

    public function getAttributesByCode(string $product_code){
        $this->Hub->set_level(3);
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
    
    public function addProduct (int $attribute_id, string $product_code, string $attribute_value){
        $this->Hub->set_level(3);
        $sql = "
            INSERT INTO
                    attribute_values (attribute_id, product_id, attribute_value)
            VALUE({$attribute_id},(SELECT product_id FROM prod_list WHERE product_code = '$product_code'), '$attribute_value' )       
            ";
        
        return $this->query($sql);        
    }
    
    public function deleteProduct ( array $rows){
        $this->Hub->set_level(3);
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
    
    public function getProducts( int $attribute_id, int $offset=0, int $limit=0, string $sortby=null, string $sortdir='', array $filter = null ){
        $this->Hub->set_level(3);
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
        $this->Hub->set_level(3);
	$source = array_map('addslashes', $source);
	$target = array_map('addslashes', $target);
	return $this->importInTable( $source, $target, $label);
    }

    private function importInTable($src, $trg, $label) {
        $this->Hub->set_level(3);
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
    
    public function tableViewGet( string $out_type='.print', int $attribute_id=0, array $filter ){
        $this->Hub->set_level(3);
	$rows=$this->getProducts( $attribute_id,0, 10000, null, null, $filter = null );
	$dump=[
            'tpl_files_folder'=>"application/plugins/AttributeManager/views/",
	    'tpl_files'=>'GridTpl.xlsx',
	    'title'=>"Атрибуты",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Добрый день'
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
        $this->Hub->set_level(3);
        return [
            [
                'Field'=>'product_code',
                'Comment'=>'Product code'
            ],
            [
                'Field'=>'attribute_name',
                'Comment'=>'Attribute name'
            ],
            [
                'Field'=>'attribute_value',
                'Comment'=>'Attribute value'
            ],
            [
                'Field'=>'attribute_unit',
                'Comment'=>'Attribute unit'
            ]
        ];
    }
    
    public function filterSetupMatchesTable( $query, $previuos_return ){
        if( $previuos_return ){
            $query=$previuos_return;
        }
        $query['outer']['select'].=",GROUP_CONCAT(attribute_value_hash) product_attribute_hashes";
        $query['outer']['table'].="LEFT JOIN
                attribute_values USING(product_id)";
        return $query;
    }
    
    public function filterBuildGroups( $Host ){
        $attribute_list_sql="
            SELECT 
                CONCAT('attribute_id','-', attribute_id) group_id,
                attribute_name group_name,
                attribute_value_hash,
                CONCAT(attribute_prefix,attribute_value,attribute_unit) option_label
            FROM
                attribute_list
                    JOIN
                attribute_values USING (attribute_id)
                    JOIN
                tmp_matches_list USING (product_id)
            GROUP BY attribute_id,attribute_value
            ORDER BY attribute_id,attribute_value*1,attribute_value";
        $attribute_list=$this->get_list($attribute_list_sql);
        
        $group_id=null;
        $other_condition=[];
        foreach($attribute_list as $attribute){
            if( $attribute->group_id!==$group_id ){
                if( $other_condition ){
                    $Host->matchesFilterBuildOption($group_id,  $this->lang("Other"), 'product_attribute_hashes IS NULL OR '.implode(' AND ',$other_condition));
                    $other_condition=[];
                }
                $group_id=$attribute->group_id;
                $Host->matchesFilterBuildGroup($group_id, $attribute->group_name);
                
            }
            $option_condition="LOCATE('$attribute->attribute_value_hash',product_attribute_hashes)>0";
            $other_condition[]="NOT $option_condition";
            $Host->matchesFilterBuildOption($group_id,  $attribute->option_label, $option_condition);
        }
        if( $other_condition ){
            $Host->matchesFilterBuildOption($group_id,  $this->lang("Other"), 'product_attribute_hashes IS NULL OR '.implode(' AND ',$other_condition));
            $other_condition=[];
        }
    }
}