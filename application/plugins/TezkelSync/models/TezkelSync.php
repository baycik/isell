<?php

/* Group Name: Синхронизация
 * User Level: 2
 * Plugin Name: TezkelSync
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: Tool for export 
 * Author: baycik 2023
 * Author URI: http://isellsoft.com
 */


class TezkelSync extends Catalog {
    public $settings;
    private function apiExecute( string $function, array $data ){
        $url = $this->settings->gateway_url."/$function";
        $token=$this->settings->gateway_token;

        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json","Authorization: Bearer {$token}"]);

        $result = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if( curl_error($curl) ){
            print_r("{$this->settings->gateway_url} API Execution error: ".curl_error($curl));
        }
        curl_close($curl);
        if( $httpcode>299 ){
            return $httpcode.$result;
        }
        return $result;
    }
    
    public function export_json(){
        $this->Hub->load_model('Storage');
        $usd_ratio = $this->Hub->pref('usd_ratio');
        $this->settings = $this->getSettings()->json;
        
        
        $categoryWhere='1';
        $branch_ids = $this->settings->categories;
        if($branch_ids){
            $all_categories = [];
            foreach ($branch_ids as $category) {
                $all_categories = array_merge($all_categories, $this->getCategories($category->branch_id));
            }
            $categoryWhere="se.parent_id IN (" . implode(',', $all_categories) . ")";
        }

        
        $export_json=[];
        $sql="
            SELECT
                product_id,
                product_code,
                ru,
                en,
                product_quantity,
                GET_SELL_PRICE(product_code, " . $this->settings->pcomp_id . ", '$usd_ratio'),
                product_barcode,
                product_weight,
                product_unit,
                analyse_type
            FROM
                prod_list pl 
                    JOIN
                stock_entries se USING (product_code)
                    JOIN
                stock_tree st ON (se.parent_id = st.branch_id)    
            WHERE
                $categoryWhere
        ";
        $result = $this->query($sql);
        foreach ($result->result_array() as $row) {
            $export_json['rows'][]=array_values($row);
        }
        $result->free_result();
        $export_json['cols']=[
            'product_external_id',
            'product_code',
            'product_name',
            'product_description',
            'product_quantity',
            'product_price',
            'product_barcode',
            'product_weight',
            'product_unit',
            'product_category_name',
        ];
        return $this->apiExecute("Product/listSave",$export_json);
    }
    
    
    
    
    public function export_csv() {
        $this->Hub->load_model('Storage');
        $usd_ratio = $this->Hub->pref('usd_ratio');
        $settings = $this->getSettings()->csv;
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
        $attribute_config = $this->putAttributesConfig($file_path);
        $sql = "
            SELECT * FROM (
            SELECT
                '<!$attribute_config!>' as col1,'' as col2,'' as col3,'' as col4,'' as col5,'' as col6,'' as col7,'' as col8,'' as col9,'' as col10,
                '' as col11,'' as col12,'' as col13,'' as col14,'' as col15,'' as col16,'' as col17,'' as col18,'' as col19,'' as col20,'' as col21,'' as col22
            UNION ALL
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
                ROUND(POWER(product_volume,0.33),2) col14,
                ROUND(POWER(product_volume,0.33),2) col15,
                ROUND(POWER(product_volume,0.33),2) col16,
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
            )t
            INTO OUTFILE '$file_path'
            CHARACTER SET utf8 
            FIELDS TERMINATED BY ';'
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'";
        $this->query($sql);
        return $this->db->affected_rows();
    }
    
    public function export_yml() {
        $this->Hub->load_model('Storage');
        $usd_ratio = $this->Hub->pref('usd_ratio');
        $settings = $this->getSettings()->yml;
        $branch_ids = $settings->categories;
        $all_categories = [];
        foreach ($branch_ids as $category) {
            $all_categories = array_merge($all_categories, $this->getCategories($category->branch_id));
        }
        !is_dir("../public") && mkdir("../public", 0777);
        $file_path = str_replace('\\', '/', realpath("../public")) . '/yml_export.yml';
        @unlink($file_path);
        $sql = "
            SELECT
                pl.product_id,
                GET_SELL_PRICE(product_code, " . $settings->pcomp_id . ", '$usd_ratio') price,
                'RUR' currencyId,
                se.parent_id categoryId,
                '' picture,
                'false' store,
                'pickup' delivery,
                REPLACE(ru, ';', ',') name,
                REPLACE(product_code, ';', ',') vendorCode,
                'true' manufacturer_warranty,
                pl.product_barcode barcode
            FROM
                prod_list pl 
                    JOIN
                stock_entries se USING (product_code)
                    JOIN
                stock_tree st ON (se.parent_id = st.branch_id)    
            WHERE
                se.parent_id IN (" . implode(',', $all_categories) . ")
            ORDER BY product_code";
        $data = $this->get_list($sql);
        $category_list = $this->get_list("SELECT branch_id, parent_id, label FROM stock_tree WHERE branch_id IN (" . implode(',', $all_categories) . ")");
        $this->generateXML($data, $category_list,$file_path);
        return count($data);
    }

    private function generateXML($data, $category_list,$file_path) {
        $company_name = $this->Hub->acomp('company_name');
        
        //create the xml document
        $xmlDoc = new DOMDocument('1.0', 'UTF-8');
        
        $doc = $xmlDoc->createElement('yml_catalog');
        $mother_node = $xmlDoc->appendChild($doc);
        $mother_node->setAttribute("date", date("Y-m-d H:i"));
        
        $root = $doc->appendChild($xmlDoc->createElement("shop"));
        $root->appendChild($xmlDoc->createElement("name",$company_name));
        $root->appendChild($xmlDoc->createElement("company",$company_name));
        $root->appendChild($xmlDoc->createElement("url",''));
        
        $currencies = $root->appendChild($xmlDoc->createElement("currencies"));
        $currency = $xmlDoc->createElement("currency");
        $currencies_node = $currencies->appendChild($currency);
        $currencies_node->setAttribute("id", 'RUR');
        $currencies_node->setAttribute("rate", '1');
        
        $categories = $root->appendChild($xmlDoc->createElement("categories",''));
        foreach($category_list as $category){
            if(!empty($category)){
                $xml_category = $xmlDoc->createElement('category',$category->label);
                $category_node = $categories->appendChild($xml_category);
                $category_node->setAttribute("id", $category->branch_id);
            }
        }
        
        $products = $root->appendChild($xmlDoc->createElement('offers'));
        foreach($data as $item){
            if(!empty($item)){
                $product = $xmlDoc->createElement('offer');
                $item_node = $products->appendChild($product);
                $item_node->setAttribute("id", $item->product_id);
                $item_node->setAttribute("available", 'true');
                foreach($item as $key=>$val){
                    if($key != 'product_id'){
                        $product->appendChild($xmlDoc->createElement($key, $val));
                    }
                }
            }
        }
        header("Content-Type: text/plain; charset=utf-8");
        $xmlDoc->formatOutput = true;
        $xmlDoc->save($file_path);
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
        $filtes_from_config = $this->getSettings()->csv->filters;
        $filters = [];
        $attribute_result = '';
        $filter_result = '';
        foreach($attributes as $attribute){
            $attribute_result .= $attribute->name.'|'.$attribute->group_description.',';
            foreach($filtes_from_config as $filter){
                $filter_object = [];
                if($attribute->name == $filter->attribute_name){
                    $filter_result .= $attribute->field.'|'.$attribute->name.',';
                    $filter_object['field'] = $attribute->field;
                    $filter_object['name'] = $attribute->name;
                    $filter_object['index'] = $attribute->index;
                    $filter_object['delimeter'] = ',';
                    $filters[] = $filter_object;
                }
            }
        }
        return $attribute_result;
        $result_array = [
            'attributes' => $attributes,
            'filters'=> $filters
        ];
        return json_encode($result_array, JSON_UNESCAPED_UNICODE );
        
        file_put_contents(str_replace('isell_export.csv', 'attribute_config.json', $file_path), json_encode($result_array, JSON_UNESCAPED_UNICODE ));
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
            WHERE plugin_system_name = 'TezkelSync'    
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
            WHERE plugin_system_name = 'TezkelSync'    
            ";
        $row = $this->get_row($sql);
        return json_decode($row->plugin_settings);
    }


    private function getCategories($category_id) {
        $branches = $this->treeGetSub('stock_tree', $category_id);
        return $branches;
    }

}
