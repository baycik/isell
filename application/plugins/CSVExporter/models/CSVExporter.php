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
        $all_categories = [];
        $img_url = '';
        if (strrpos($settings->publicUrl, '/') == 0) {
            $img_url = $settings->publicUrl . 'public/index.php?size=500x500&path=';
        } else {
            $img_url = $settings->publicUrl . '/public/index.php?size=500x500&path=';
        }
        foreach ($branch_ids as $category) {
            $all_categories = array_merge(array(), $this->getCategories($category->branch_id));
        }
        !is_dir("../public") && mkdir("../public", 0777);
        $file_path = str_replace('\\', '/', realpath("../public")) . '/isell_export.csv';
        @unlink($file_path);


        $attribute_select = $this->getAttributesSelect($settings);
        $sql = "
            SELECT 
                REPLACE(product_code,';',',') product_code,
                REPLACE(ru,';',',') ru,
                analyse_brand,
                product_id,
                product_quantity,
                path AS category_lvl1,
                $attribute_select
                CONCAT ('http://localhost:888/public/index.php?size=500x500&path=',product_img) as img, 
                GET_PRICE(product_code, " . $settings->pcomp_id . ", '$usd_ratio') as price1, GET_SELL_PRICE(product_code, " . $settings->pcomp_id . ", '$usd_ratio') as price2
            FROM
                prod_list pl 
                    JOIN
                stock_entries se USING (product_code)
                    JOIN
                stock_tree st ON (se.parent_id = st.branch_id)    
            WHERE
                product_img AND
                se.parent_id IN (" . implode(',', $all_categories) . ")
            INTO OUTFILE '$file_path'
            CHARACTER SET cp1251 
            FIELDS TERMINATED BY ';'
            ENCLOSED BY ''
            LINES TERMINATED BY '\r\n'";
        return $this->query($sql);
    }

    private function getAttributesSelect($settings) {
        $attribute_select = "";
        foreach ($settings->attributes as $key => $attribute_id) {
            $attribute_select .= "IFNULL( (SELECT attribute_value FROM  attribute_values av WHERE pl.product_id = av.product_id AND av.attribute_id = '$attribute_id'), '') as $key,";
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
