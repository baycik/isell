<?php

class AttributeFilter extends Catalog{
//    public function matchesListFetch(string $q, int $limit=12, int $offset=0, string $sortby, string $sortdir, int $category_id=0, int $pcomp_id=0) {
//        $where=     $this->matchesListGetWhere( $q, $category_id );
//        $order_by=  $this->matchesListGetOrderBy($sortby,$sortdir);
//        $this->matchesListCreateTemporary($where);
//        $groupped_filter=$this->constructFilter();
//        $sql="
//            SELECT
//                *,
//                COALESCE(price_promo,price_label,price_basic) price_final
//            FROM
//                tmp_matches_list
//            ORDER BY $order_by
//            LIMIT $limit OFFSET $offset";
//        $matches=$this->get_list($sql);
//        return [
//            'groupped_filter'=>$groupped_filter,
//            'matches'=>$matches
//        ];
//    }
    
    
    public function filterOut(){
        $groupped_filter=$this->constructFilter();
        
    }
    
    private function constructFilter(){
        $sql="
        SELECT 
            al.*,
            attribute_value,
            attribute_value_hash,
            COUNT(*) product_count
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
        $current_attribute_id=0;
        foreach($filter_list as $entry){
            if( $current_attribute_id != $entry->attribute_id ){
                $group_name="group_".$entry->attribute_id;
                $groupped_filter[$group_name]=[
                    'attribute_name'=>$entry->attribute_name,
                    'entries'=>[]
                ];
                $current_attribute_id = $entry->attribute_id;
            }
            $groupped_filter[$group_name]['entries'][]=$entry;
        }
        return $groupped_filter;
    }
    
    private function attributeListFetch($attribute_value_ids, $where){
        $this->query("CREATE TEMPORARY TABLE attributes_temp SELECT av.*, pl.parent_id FROM attribute_values av JOIN product_list_temp pl ON av.product_id = pl.product_id AND $where");
        $attributes_where = '1';
        if( $attribute_value_ids ){
             foreach($attribute_value_ids as $index=>$attribute_value){
                 $attributes_where .= " AND pl.attribute_value_hash LIKE '%$attribute_value%' ";
             }
        }
        $sql="
            SELECT 
                *,
                GROUP_CONCAT(DISTINCT CONCAT(t.attribute_value, '::', t.attribute_value_hash, '::', t.product_total) 
                    ORDER BY t.attribute_id ASC, t.attribute_value*1 ASC
                    SEPARATOR '|') attribute_values
            FROM
                (SELECT 
                        al.attribute_id,
                        al.attribute_name,
                        al.attribute_unit,
                        al.attribute_prefix,
                        av.attribute_value,
                        av.product_id,
                        av.attribute_value_hash,
                        IF(ptotal.product_total IS NOT NULL, ptotal.product_total, 0) product_total
                    FROM
                        attributes_temp av
                        JOIN 
                        attribute_list al USING (attribute_id)
                    LEFT JOIN 
                        (SELECT 
                           av.attribute_value_hash,  parent_id, COUNT(product_id) as product_total
                        FROM
                                product_list_temp pl
                                        JOIN 
                        attribute_values av USING (product_id)
                                        JOIN 
                        attribute_list al USING (attribute_id)
                        WHERE  $attributes_where  
                        GROUP BY av.attribute_value_hash) ptotal ON  ptotal.attribute_value_hash = av.attribute_value_hash
                    GROUP BY av.attribute_value_hash) t
            GROUP BY t.attribute_id
            ";
        $attribute_list = $this->get_list($sql);
        
        return $this->attributeListCompose($attribute_list);
    }
    
    private function attributeListCompose($attribute_list){
        foreach($attribute_list as &$attribute){
            $attribute->attribute_values = explode('|',$attribute->attribute_values);
            foreach($attribute->attribute_values as &$attribute_value){
                $attribute_value_exploded = explode('::', $attribute_value);
                $attribute_value = [
                    'attribute_value' => $attribute_value_exploded[0],
                    'attribute_value_id' => isset($attribute_value_exploded[1])?$attribute_value_exploded[1]:'',
                    'product_total' => isset($attribute_value_exploded[2])?$attribute_value_exploded[2]*1:0,
                    'attribute_id' => $attribute->attribute_id,
                    'attribute_unit' => $attribute->attribute_unit,
                    'attribute_name' => $attribute->attribute_name,
                    'attribute_prefix' => $attribute->attribute_prefix
                ];
            }
        }
        return $attribute_list;
    }
}

