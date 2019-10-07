<?php
/* Group Name: Р РЋР С”Р В»Р В°Р Т‘
 * User Level: 2
 * Plugin Name: Р СљР ВµР Р…Р ВµР Т‘Р В¶Р ВµРЎР‚ Р В°РЎвЂљРЎвЂљРЎР‚Р С‘Р В±РЎС“РЎвЂљР С•Р Р†
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
	    'title'=>"Р В­Р С”РЎРѓР С—Р С•РЎР‚РЎвЂљ РЎвЂљР В°Р В±Р В»Р С‘РЎвЂ РЎвЂ№",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Р вЂќР С•Р В±РЎР‚Р С•Р С–Р С• Р Т‘Р Р…РЎРЏ'
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
                'Comment'=>'Р С™Р С•Р Т‘'
            ],
            [
                'Field'=>'attribute_name',
                'Comment'=>'Р С’РЎвЂљРЎР‚Р С‘Р В±РЎС“РЎвЂљ'
            ],
            [
                'Field'=>'attribute_value',
                'Comment'=>'Р вЂ”Р Р…Р В°РЎвЂЎР ВµР Р…Р С‘Р Вµ'
            ],
            [
                'Field'=>'attribute_unit',
                'Comment'=>'Р вЂўР Т‘Р С‘Р Р…Р С‘РЎвЂ Р В°'
            ]
        ];
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    /////////////////////////////////////////////////////
    //STOCK MATCHES FILTERING
    /////////////////////////////////////////////////////
    public function filterOut(){
        $filter_selected_grouped=$this->request('filter_selected_grouped','json');
        $this->filterConstruct( $filter_selected_grouped );
        $this->filterApply( $filter_selected_grouped );
    }
    
    public function filterGet(){
        //return $this->Hub->svar('filter_list');
        return $this->filterConstructTree();
    }
    
    private function filterApply( $filter_selected_grouped ){
        $and_case='';
        $and_delimeter='';
        if( count($filter_selected_grouped) ){
            foreach( $filter_selected_grouped as $filter_group_id=>$options ){
                $id_chunks=explode('-',$filter_group_id);
                if( $id_chunks[0]=='attribute_id' ){
                    $or_case="'".implode("','",$options)."'";
                    $and_case.=$and_delimeter." MAX(attribute_value_hash IN ($or_case)) ";
                    $and_delimeter="*";
                }
            }
        }
        
        $join="";
        $where="0 ";
        if( $and_case ){
            $join="LEFT JOIN
                (SELECT 
                    product_id
                FROM
                    attribute_values
                GROUP BY product_id
                HAVING $and_case) filter_join USING (product_id)";
            $where.="OR filter_join.product_id IS NULL";
        }
        $remove_sql="
            DELETE
                tmp_matches_list
            FROM
                tmp_matches_list
                $join
            WHERE
                $where";
        die($remove_sql);
        $this->query($remove_sql);
    }
    
    private function filterConstruct( $filter_selected_grouped ){
        $this->Hub->svar('filter_list',[]);
        $this->filterConstructPriceRanges();
        $this->filterConstructAttributes( $filter_selected_grouped );
    }
    
//    private function filterConstructPriceRanges111(){
//        $minmax=$this->get_row("SELECT MIN(price_final) price_min,MAX(price_final) price_max FROM tmp_matches_list");
//        
//        $fraction_count=4;
//        $fraction=$minmax->price_max/($fraction_count-1);
//        $roundto=pow(10,strlen(round($fraction))-1);
//        $rounded_fraction=round($fraction/$roundto)*$roundto;
//        
//        $calc_fraction_count="
//        SELECT
//            $rounded_fraction*1 range1,
//            $rounded_fraction*2 range2,
//            $rounded_fraction*3 range3,
//            $rounded_fraction*4 range4,
//            SUM(FLOOR(price_final/$rounded_fraction)=0) range_count1,
//            SUM(FLOOR(price_final/$rounded_fraction)=1) range_count2,
//            SUM(FLOOR(price_final/$rounded_fraction)=2) range_count3,
//            SUM(FLOOR(price_final/$rounded_fraction)>=3) range_count4,
//            COUNT(*) total_count
//        FROM
//            tmp_matches_list";
//        $ranges=$this->get_row($calc_fraction_count);
//        $ranges->min=$minmax->price_min;
//        $ranges->max=$minmax->price_max;
//        return [$ranges];
//    }
    
    private function filterConstructPriceRanges(){
        $minmax=$this->get_row("SELECT MIN(price_final) price_min,MAX(price_final) price_max FROM tmp_matches_list");
        
        $fraction_count=4;
        $fraction=$minmax->price_max/($fraction_count-1);
        $roundto=pow(10,strlen(round($fraction))-1);
        $rounded_fraction=round($fraction/$roundto)*$roundto;
        
        $calc_fraction_count="
        SELECT
            $rounded_fraction*1 range1,
            $rounded_fraction*2 range2,
            $rounded_fraction*3 range3,
            $rounded_fraction*4 range4,
            SUM(FLOOR(price_final/$rounded_fraction)=0) range_count1,
            SUM(FLOOR(price_final/$rounded_fraction)=1) range_count2,
            SUM(FLOOR(price_final/$rounded_fraction)=2) range_count3,
            SUM(FLOOR(price_final/$rounded_fraction)>=3) range_count4,
            COUNT(*) total_count
        FROM
            tmp_matches_list";
        $ranges=$this->get_row($calc_fraction_count);
        $ranges->min=$minmax->price_min;
        $ranges->max=$minmax->price_max;
        $filter_options=[
            (object) [
                'filter_group_id'=>'price_final',
                'filter_group_name'=>'Price',
                'filter_option_field'=>'price_final',
                'filter_option_id'=>"0_{$ranges->range1}",
                'filter_option_label'=>"Less than $ranges->range1",
                'match_count'=>$ranges->range_count1
            ],
            (object) [
                'filter_group_id'=>'price_final',
                'filter_group_name'=>'Price',
                'filter_option_field'=>'price_final',
                'filter_option_id'=>"{$ranges->range1}_{$ranges->range2}",
                'filter_option_label'=>"$ranges->range1 - $ranges->range2",
                'match_count'=>$ranges->range_count2
            ],
            (object) [
                'filter_group_id'=>'price_final',
                'filter_group_name'=>'Price',
                'filter_option_field'=>'price_final',
                'filter_option_id'=>"{$ranges->range2}_{$ranges->range3}",
                'filter_option_label'=>"$ranges->range2 - $ranges->range3",
                'match_count'=>$ranges->range_count3
            ],
            (object) [
                'filter_group_id'=>'price_final',
                'filter_group_name'=>'Price',
                'filter_option_field'=>'price_final',
                'filter_option_id'=>"{$ranges->range4}_{$minmax->price_max}",
                'filter_option_label'=>"More than $ranges->range4",
                'match_count'=>$ranges->range_count4
            ],
        ];
        $this->filterStoreFilterOptions( $filter_options );
    }
    
    private function filterConstructAttributes( $filter_selected_grouped ){
        $selected_checker="0";
        $selected_list='';
        $selected_delimeter='';
        $and_case='';
        $and_delimeter='';
        $counter="COUNT(*)";
        if( count($filter_selected_grouped) ){
            foreach( $filter_selected_grouped as $filter_group_id=>$options ){
                $id_chunks=explode('-',$filter_group_id);
                if( $id_chunks[0]=='attribute_id' ){
                    $or_case="'".implode("','",$options)."'";
                    $selected_list.=$selected_delimeter.$or_case;
                    $selected_delimeter=',';
                    
                    $and_case=$and_delimeter."(attribute_value_hash IN ($or_case))";
                    $and_delimeter="*";
                }
            }
            $selected_checker=" IF( attribute_value_hash IN ($selected_list),1,0) ";
            $counter="333";
        }
        $sql="
            SELECT 
                CONCAT('attribute_id-',attribute_id) 
                    filter_group_id,
                attribute_name 
                    filter_group_name,
                'attribute_value_hash'
                    filter_option_field,
                attribute_value_hash 
                    filter_option_id,
                CONCAT(attribute_prefix,' ',attribute_value,' ',attribute_unit) 
                    filter_option_label,
                $counter 
                    match_count,
                $selected_checker 
                    is_selected
            FROM
                tmp_matches_list
                    JOIN
                attribute_values av USING(product_id)
                    JOIN
                attribute_list al USING(attribute_id)
            GROUP BY attribute_value_hash
            ORDER BY attribute_name,attribute_id,attribute_value";
        $filter_options=$this->get_list($sql);
        $this->filterStoreFilterOptions( $filter_options );
    }
    
    
    private function filterStoreFilterOptions( $options ){
        $filter_list=$this->Hub->svar('filter_list') OR $filter_list=[];
        $this->Hub->svar('filter_list',array_merge($filter_list,$options));
    }
    
    private function filterConstructTree(){
        $filter_list=$this->Hub->svar('filter_list');
        $tree=[];
        $group_index=-1;
        $current_attribute_id=0;
        foreach($filter_list as $entry){
            if( $current_attribute_id !== $entry->filter_group_id ){
                $group_index++;
                $group=[
                    'filter_group_id'=>$entry->filter_group_id,
                    'filter_group_name'=>$entry->filter_group_name,
                    'filter_group_options'=>[]
                ];
                if( !empty($entry->filter_group_range) ){
                    $group['filter_group_range']=$entry->filter_group_range;
                }
                $tree[$group_index]=$group;
                $current_attribute_id = $entry->filter_group_id;
            }
            $tree[$group_index]['filter_group_options'][]=$entry;
        }
        return $tree;
    }
    
}