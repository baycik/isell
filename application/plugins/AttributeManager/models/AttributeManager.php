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
    
    public $listFetch = ['offset' => ['int', 0], 'limit' => ['int', 5], 'sortby' => 'string', 'sortdir' => '(ASC|DESC)', 'filter' => 'json'];
    public function listFetch( $offset, $limit, $sortby, $sortdir, $filter = null){
        if (empty($sortby)) {
	    $sortby = "attribute_id";
	    $sortdir = "DESC";
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
    
    public $attributeUpdate = ['attribute_id' => 'int', 'attribute_name' => 'string', 'attribute_unit' => 'string'];
    public function attributeUpdate( $attribute_id, $attribute_name, $attribute_unit ){
        if($attribute_id == 0){
            $sql = "
                INSERT INTO
                    attribute_list
                SET 
                    attribute_name = '$attribute_name',
                    attribute_unit = '$attribute_unit'
                ";
            return  $this->query($sql);          
        };
        $sql = "
            UPDATE 
                attribute_list
            SET 
                attribute_name = '$attribute_name',
                attribute_unit = '$attribute_unit'
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
            ";
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
                AND av.attribute_id IN (SELECT distinct av.attribute_id FROM attribute_values)
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
       
    
    public $deleteProduct = [ 'attribute_id' => 'string', 'product_code' => 'string' ];
    public function deleteProduct ($attribute_id, $product_code){
        $sql = "
            DELETE FROM
                    attribute_values 
            WHERE attribute_id = {$attribute_id}
                AND product_id = (SELECT product_id FROM prod_list WHERE product_code = '$product_code')       
            ";
        return $this->query($sql);        
    }
        
    
    public $getProducts = [ 'attribute_id' => 'int', 'attribute_name' => 'string', 'attribute_unit' => 'string','offset' => ['int', 0], 'limit' => ['int', 5], 'sortby' => 'string', 'sortdir' => '(ASC|DESC)', 'filter' => 'json'];
    public function getProducts( $attribute_id, $attribute_name, $attribute_unit,$offset, $limit, $sortby, $sortdir, $filter = null ){
         if (empty($sortby)) {
	    $sortby = "attribute_id";
	    $sortdir = "DESC";
	}
        $null = null;
        $having = '';
        $where = '';
        if($filter){
           $having = "HAVING ".$this->makeFilter($filter);
        }else{
            $where="WHERE attribute_id = '$attribute_id'";
        };
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
            ORDER BY $sortby $sortdir
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
            } else {
                $target=explode('_',$trg[$i]);
                $target_attribute_id=$target[1];
                $attributes[$target_attribute_id]=$source_column;
                 
            }
        }
        
        $total_rows=0;
        foreach ($attributes as $attribute_id=>$attribute_source_column){
         
            $sql="INSERT INTO
                attribute_values 
                (`attribute_id`,`product_id`,`attribute_value`)
                SELECT 
                    $attribute_id,(SELECT product_id FROM prod_list WHERE product_code=$product_code_source_column),$attribute_source_column
                FROM 
                    imported_data 
                WHERE 
                    label LIKE '%$label%'
                ON DUPLICATE KEY UPDATE attribute_value={$attribute_source_column};
                    ";
            $this->query($sql);
            $total_rows+=$this->db->affected_rows();
            
        }
        $this->query("DELETE FROM imported_data WHERE label LIKE '%$label%' AND {$product_code_source_column} IN (SELECT product_code FROM prod_list pl JOIN attribute_values av USING (product_id))");
	return $total_rows;
    }
    
    

}