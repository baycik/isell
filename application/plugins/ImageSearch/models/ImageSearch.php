<?php

/* Group Name: Синхронизация
 * User Level: 2
 * Plugin Name: ImageSearch
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: Tool for product image search 
 * Author: adamhider 2019
 * Author URI: http://isellsoft.com
 */

class ImageSearch extends Catalog {
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
    
}
