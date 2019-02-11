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
        $attribute_select = $this->getAttributesSelect($settings);
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
                '' col20
                $attribute_select
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

    private function getAttributesSelect($settings) {
        $attribute_select = "";
        foreach ($settings->attributes as $key => $attribute_id) {
            $attribute_select .= ",IFNULL( (SELECT CONCAT(attribute_value,attribute_unit) FROM  attribute_values av JOIN attribute_list USING(attribute_id) WHERE attribute_value<>'' AND pl.product_id = av.product_id AND av.attribute_id = '$attribute_id'), '')";
        }
        return $attribute_select;
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
