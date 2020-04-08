<?php
class ModelCatalogSync extends Model{
    public $manuCache=[];
    public function getManufacturerId($name){
        if(isset($this->manuCache[$name])){
            return $this->manuCache[$name];
        }
        $query= $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "manufacturer WHERE name='$name'");
        if( $query && $query->rows && $query->rows[0] ){
            return $this->manuCache[$name]=$query->rows[0]['manufacturer_id'];
        }
        $this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer SET name='$name'");
        return $this->db->getLastId();
    }
    
    public function getProductDigests(){
        $sql="
            SELECT 
                product_id,
                model,
                prod.image,
                MD5(CONCAT(ean,sku,quantity,ROUND(price,2),ROUND(weight,4),prod_dsc.name,COALESCE(mnf.name,''))) field_hash
            FROM
                " . DB_PREFIX . "product prod
                    JOIN
                " . DB_PREFIX . "product_description prod_dsc USING(product_id)
                    LEFT JOIN
                " . DB_PREFIX . "manufacturer mnf USING(manufacturer_id)";
        $query = $this->db->query($sql);
        foreach( $query->rows as &$row ){
            $row['img_hash']= '';
            $row['img_time']= '';
            if( $row['image'] && file_exists(DIR_IMAGE.$row['image']) ){
                $row['img_hash']= md5_file(DIR_IMAGE.$row['image']);
                $row['img_time']= filemtime(DIR_IMAGE.$row['image']);
            }
        }
        return $query->rows;
    }
}






class SyncUtils extends Controller{
    public function __construct($registry) {
        parent::__construct($registry);
        $this->def_lang_id=(int)$this->config->get('config_language_id');
    }
    
    
    public function index() {
        echo 'welcome to our api';
    }
    
    protected function load_admin_model($route) {
        $class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $route);
        $file = realpath(__DIR__ . '/../../../admin/model/' . $route . '.php');
        if (is_file($file)) {
            include_once($file);
            $modelName = str_replace('/', '', ucwords("Model/" . $route, "/"));
            $proxy = new $modelName($this->registry);
            $this->registry->set('model_' . str_replace('/', '_', (string) $route), $proxy);
        } else {
            throw new \Exception('Error: Could not load model ' . $route . '!');
        }
    }
    
    protected function productMergeArray( $updated_product,$current_product=NULL ){
        $empty_product=[
            'model' => '',
            'sku' => '',
            'upc' => '',
            'ean' => '',
            'jan' => '',
            'isbn' => '',
            'mpn' => '',
            'location' => '',
            'quantity' => '',
            'minimum' => '',
            'subtract' => '',
            'stock_status_id' => '',
            'date_available' => '',
            'manufacturer_id' => '',
            'manufacturer_name'=>'',
            'shipping' => '1',
            'price' => '',
            'points' => '',
            'weight' => '',
            'weight_class_id' => '',
            'length' => '',
            'width' => '',
            'height' => '',
            'length_class_id' => '',
            'status' => '',
            'tax_class_id' => '',
            'product_store'=>[0],
            'sort_order' => '',
            'keyword' => '',
            'product_name'=>'',
            'product_description'=>[]
        ];
        if( $current_product ){
            $result_product=array_merge($current_product,$updated_product);
        } else {
            $result_product=array_merge($empty_product,$updated_product);
        }
        $result_product['product_description'][$this->def_lang_id]['name']=isset($result_product['name'])?$result_product['name']:'';
        $result_product['product_description'][$this->def_lang_id]['meta_title']=isset($result_product['name'])?$result_product['name']:'';
        $result_product['product_description'][$this->def_lang_id]['tag']=isset($result_product['tag'])?$result_product['tag']:'';
        $result_product['product_description'][$this->def_lang_id]['description']=isset($result_product['description'])?$result_product['description']:'';
        $result_product['product_description'][$this->def_lang_id]['meta_description']=isset($result_product['meta_description'])?$result_product['meta_description']:'';
        $result_product['product_description'][$this->def_lang_id]['meta_keyword']=isset($result_product['meta_keyword'])?$result_product['meta_keyword']:'';
        if( isset($result_product['manufacturer_name']) ){
            $result_product['manufacturer_id']=$this->model_catalog_sync->getManufacturerId($result_product['manufacturer_name']);
        }
        return $result_product;
    }
    protected function getDefaultLanguage(){
        return (int)$this->config->get('config_language_id');
    }
}
class ControllerApiSync extends SyncUtils {
   
    public function productsDigestGet(){
        $this->model_catalog_sync=new ModelCatalogSync($this->registry);
        $prod_list=$this->model_catalog_sync->getProductDigests();
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($prod_list));        
    } 
    
    private function getFullProduct($product_id){
        $current_product=$this->model_catalog_product->getProduct($product_id);
        $current_product['product_store']=$this->model_catalog_product->getProductStores($product_id);
        
        $current_product['product_description']=$this->model_catalog_product->getProductDescriptions($product_id);
        $current_product['product_category']=$this->model_catalog_product->getProductCategories($product_id);
        $current_product['product_filter']=$this->model_catalog_product->getProductFilters($product_id);
        $current_product['product_attribute']=$this->model_catalog_product->getProductAttributes($product_id);
        
        $current_product['product_option']=$this->model_catalog_product->getProductOptions($product_id);
        $current_product['product_image']=$this->model_catalog_product->getProductImages($product_id);
        $current_product['product_discount']=$this->model_catalog_product->getProductDiscounts($product_id);
        
        $current_product['product_special']=$this->model_catalog_product->getProductSpecials($product_id);
        $current_product['product_related']=$this->model_catalog_product->getProductRelated($product_id);
        $current_product['product_download']=$this->model_catalog_product->getProductDownloads($product_id);
        
        $current_product['product_reward']=$this->model_catalog_product->getProductRewards($product_id);
        $current_product['product_layout']=$this->model_catalog_product->getProductLayouts($product_id);
        
        if( VERSION >= '3.0.0.0' ){
            $current_product['product_seo_url']=$this->model_catalog_product->getProductSeoUrls($product_id);
        }
        
        return $current_product;
    }

    private function composeProductSpecial($price) {
        $product_special_object[] = [
            'customer_group_id' => 1,
            'priority'=> 1,
            'price' => $price,
            'date_start' => date("Y-m-d"),
            'date_end'=> date('Y-m-d', strtotime("+2 years"))
        ];
        return $product_special_object;
    }
    
    public function productsUpdate() {
        $synced_models=[];
        $this->load_admin_model('catalog/product');
        $this->model_catalog_sync=new ModelCatalogSync($this->registry);
        if( !isset($this->request->post['products']) ){
            die("No products recieved!");
        }
        if( !file_exists(DIR_IMAGE.'catalog/synced/') ){
            mkdir(DIR_IMAGE.'catalog/synced/',0777);
        }
        $products = json_decode(html_entity_decode($this->request->post['products']),JSON_OBJECT_AS_ARRAY);
        if( !$products ){
            echo '[]';
            return false;
        }
        try{
            foreach ($products as $product) {
                if( $product['price_raw']!=$product['price'] ){
                    $product['product_special']=$this->composeProductSpecial($product['price_raw']);
                } else {
                    $product['product_special']=[];
                }
                
                if( isset($product['local_img_data']) ){
                    $product['image']='catalog/synced/'.$product['remote_img_filename'];
                    $img_filename=DIR_IMAGE.$product['image'];
                    file_put_contents($img_filename, base64_decode($product['local_img_data']));
                }
                if( $product['action']=='edit' ){
                    $current_product=$this->getFullProduct($product['product_id']);
                    $product=$this->productMergeArray($product,$current_product);
                    $this->model_catalog_product->editProduct($product['product_id'],$product);
                    $synced_models[]=$product['model'];
                } else
                if( $product['action']=='add' ){
                    $product=$this->productMergeArray($product);
                    $this->model_catalog_product->addProduct($product);
                    $synced_models[]=$product['model'];
                } else
                if( $product['action']=='delete' ){
                    $this->model_catalog_product->deleteProduct($product['product_id']);
                    $synced_models[]=$product['model'];
                }
            }
        } catch( Exception $ex){
            die($ex);
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($synced_models));
    }
}