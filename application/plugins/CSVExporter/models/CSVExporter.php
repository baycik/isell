<?php

/* Group Name: Синхронизация
 * User Level: 2
 * Plugin Name: CSVExporter
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: Tool for csv export 
 * Author: baycik 2019
 * Author URI: http://isellsoft.com
 */

class CSVExporter extends Catalog {

    public $settings = [];

    public function export() {
        $this->Hub->load_model('Storage');
        $usd_ratio = $this->Hub->pref('usd_ratio');
        $settings = $this->getSettings();
        $branch_ids = $settings->categories;
        
        $img_url = '';
        if (strrpos($settings->publicUrl, '/') == 0) {
            $img_url = $settings->publicUrl . 'public/image.php?size=500x500&path=';
        } else {
            $img_url = $settings->publicUrl . '/public/image.php?size=500x500&path=';
        }
        $all_categories = [];
        foreach ($branch_ids as $category) {
            $all_categories = array_merge($all_categories, $this->getCategories($category->branch_id));
        }
        !is_dir("../public") && mkdir("../public", 0777);
        $file_path = str_replace('\\', '/', realpath("../public")) . '/isell_export.csv';
        @unlink($file_path);
        $this->putAttributesConfig($file_path);
        $sql = "
            SELECT
                SUBSTRING_INDEX(SUBSTRING_INDEX(path, '/', 2), '/', -1) col1,
                SUBSTRING_INDEX(SUBSTRING_INDEX(path, SUBSTRING_INDEX(path, '/', 2), -1),label,1) col2,
                label col3,
                REPLACE(product_code,';',',') col4,
                REPLACE(ru,';',',') col5,
                analyse_brand col6,
                analyse_origin col7,
                product_quantity col8,
                product_barcode col9,
                product_article col10,
                product_bpack col11,
                product_spack col12,
                product_weight col13,
                product_volume col14,
                product_unit col15,
                CONCAT ('$img_url',product_img) col16,
                GET_PRICE(product_code, " . $settings->pcomp_id . ", '$usd_ratio') col17,
                GET_SELL_PRICE(product_code, " . $settings->pcomp_id . ", '$usd_ratio') col18,
                '' col19,
                (SELECT 
                    GROUP_CONCAT(IFNULL(
                        (SELECT attribute_value FROM attribute_values av WHERE al.attribute_id = av.attribute_id AND av.product_id = pl.product_id), '') SEPARATOR '|')
                    FROM attribute_list al ORDER BY al.attribute_id
                ) col20
            FROM
                prod_list pl 
                    JOIN
                stock_entries se USING (product_code)
                    JOIN
                stock_tree st ON (se.parent_id = st.branch_id)    
            WHERE
                product_img AND
                se.parent_id IN (" . implode(',', $all_categories) . ")
            ORDER BY product_code
            INTO OUTFILE '$file_path'
            CHARACTER SET cp1251 
            FIELDS TERMINATED BY ';'
            ENCLOSED BY ''
            LINES TERMINATED BY '\r\n'";
        $this->query($sql);
        return $this->db->affected_rows();
    }
    
    
    
    private function putAttributesConfig($file_path) {
        $this->query("SET @index:=-1");
        $sql = "
            SELECT 'attribute_group' as `field`,
            attribute_name as `name`,
            'Свойства товара' as `group_description`,
            @index:=@index+1 as `index`
            FROM attribute_list
            ";
        $attributes = $this->get_list($sql);
        $filtes_from_config = $this->getSettings()->filters;
        $filters = [];
        foreach($attributes as $attribute){
            foreach($filtes_from_config as $filter){
                $filter_object = [];
                if($attribute->name == $filter->attribute_name){
                    $filter_object['field'] = $attribute->field;
                    $filter_object['name'] = $attribute->name;
                    $filter_object['index'] = $attribute->index;
                    $filter_object['delimeter'] = ',';
                    $filters[] = $filter_object;
                }
            }
        }
        $result_array = [
            'attributes' => $attributes,
            'filters'=> $filters
        ];
        file_put_contents(str_replace('isell_export.csv', 'attribute_config.json', $file_path), json_encode($result_array ));
        return;
    }

    public $updateSettings = ['settings' => 'json'];

    public function updateSettings($settings) {
        $this->settings = $settings;
        $encoded = json_encode($settings, JSON_UNESCAPED_UNICODE);
        $sql = "
            UPDATE
                plugin_list
            SET 
                plugin_settings = '$encoded'
            WHERE plugin_system_name = 'CSVExporter'    
            ";
        $this->query($sql);
        return $this->getSettings();
    }

    public function getSettings() {
        $sql = "
            SELECT
                plugin_settings
            FROM 
                plugin_list
            WHERE plugin_system_name = 'CSVExporter'    
            ";
        $row = $this->get_row($sql);
        return json_decode($row->plugin_settings);
    }


    private function getCategories($category_id) {
        $branches = $this->treeGetSub('stock_tree', $category_id);
        return $branches;
    }

}
