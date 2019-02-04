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
class CSVExporter extends Catalog{
   public $settings = [];
  
   public function export (){
        $this->Hub->load_model('Storage');
        $settings = $this->getSettings();
        $branch_ids = $settings->categories;
        $all_categories = [];
        $img_url = '';
        if(strpos($settings->publicUrl, '/')){
            $img_url = $settings->publicUrl. 'public/index.php?size=500x500&path=';
        } else {
            $img_url = $settings->publicUrl. '/public/index.php?size=500x500&path=';
        }
        $usd_ratio = $this->Hub->pref('usd_ratio');
        foreach ($branch_ids as $category){
            $all_categories= array_merge(array(),  $this->getCategories($category->branch_id));
        }
        $date = (new \DateTime())->format('Y_m_d_H_i_s');  
        $sql = "
            SELECT 
                product_code,
                ru,
                analyse_brand,
                product_id,
                product_quantity,
                path AS category_lvl1,
                CONCAT ('http://localhost:888/public/index.php?size=500x500&path=',product_img) as img, 
                GET_PRICE(product_code, ".$settings->pcomp->company_id.", '$usd_ratio') as price1, GET_SELL_PRICE(product_code, ".$settings->pcomp->company_id.", '$usd_ratio') as price2,
                    IFNULL( (SELECT attribute_value FROM  attribute_values av WHERE pl.product_id = av.product_id AND av.attribute_id = '".$settings->attributes->attribute_1."'), '') as attribute_1,
                    IFNULL( (SELECT attribute_value FROM  attribute_values av WHERE pl.product_id = av.product_id AND av.attribute_id = '".$settings->attributes->attribute_2."'), '') as attribute_2,
                    IFNULL( (SELECT attribute_value FROM  attribute_values av WHERE pl.product_id = av.product_id AND av.attribute_id = '".$settings->attributes->attribute_3."'), '') as attribute_3,
                    IFNULL( (SELECT attribute_value FROM  attribute_values av WHERE pl.product_id = av.product_id AND av.attribute_id = '".$settings->attributes->attribute_4."'), '') as attribute_4,
                    IFNULL( (SELECT attribute_value FROM  attribute_values av WHERE pl.product_id = av.product_id AND av.attribute_id = '".$settings->attributes->attribute_5."'), '') as attribute_5,
                    IFNULL( (SELECT attribute_value FROM  attribute_values av WHERE pl.product_id = av.product_id AND av.attribute_id = '".$settings->attributes->attribute_6."'), '') as attribute_6,
                    IFNULL( (SELECT attribute_value FROM  attribute_values av WHERE pl.product_id = av.product_id AND av.attribute_id = '".$settings->attributes->attribute_7."'), '') as attribute_7,
                    IFNULL( (SELECT attribute_value FROM  attribute_values av WHERE pl.product_id = av.product_id AND av.attribute_id = '".$settings->attributes->attribute_8."'), '') as attribute_8,
                    IFNULL( (SELECT attribute_value FROM  attribute_values av WHERE pl.product_id = av.product_id AND av.attribute_id = '".$settings->attributes->attribute_9."'), '') as attribute_9,
                    IFNULL( (SELECT attribute_value FROM  attribute_values av WHERE pl.product_id = av.product_id AND av.attribute_id = '".$settings->attributes->attribute_10."'), '') as attribute_10,
                    IFNULL( (SELECT attribute_value FROM  attribute_values av WHERE pl.product_id = av.product_id AND av.attribute_id = '".$settings->attributes->attribute_11."'), '') as attribute_11,
                    IFNULL( (SELECT attribute_value FROM  attribute_values av WHERE pl.product_id = av.product_id AND av.attribute_id = '".$settings->attributes->attribute_12."'), '') as attribute_12
            FROM
                prod_list pl 
                    JOIN
                stock_entries se USING (product_code)
                    JOIN
                stock_tree st ON (se.parent_id = st.branch_id)    
            WHERE
                product_img AND
                se.parent_id IN (". implode(',',$all_categories).")
            INTO OUTFILE '/isell_export_".$date.".csv'
            CHARACTER SET utf8 
            FIELDS TERMINATED BY ';'
            ENCLOSED BY ''
            LINES TERMINATED BY '\r\n'     
            ";
        
        $this->query($sql);        
        
   }
   
    public $updateSettings = ['settings'=>'json'];
    public function updateSettings ($settings){
        $this->settings = $settings;
        $encoded = json_encode($settings, JSON_UNESCAPED_UNICODE);
        //echo $encoded;
        //die;
        //$this->getProducts($user_input['categories']->branch_id);
        $sql ="
            UPDATE
                plugin_list
            SET 
                plugin_settings = '$encoded'
            WHERE plugin_system_name = 'CSVExporter'    
            ";
        $this->query($sql);
        
        return $this->getSettings();
   }
   
    public function getSettings (){
        $sql ="
            SELECT
                plugin_settings
            FROM 
                plugin_list
            WHERE plugin_system_name = 'CSVExporter'    
            ";
       $list = $this->get_list($sql);
       
       return json_decode($list[0]->plugin_settings);
   }
   
   public $addCategory = ['user_input'=>'json'];
   public function addCategory ($user_input){
      // $this->getProducts($user_input['categories']->branch_id);
       print_r($user_input);
   }
   
   
   
   private function getCategories($category_id){
       $branches = $this->treeGetSub('stock_tree', $category_id);
       return $branches;
   }

}