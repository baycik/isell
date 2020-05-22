<?php

class ControllerextensionmoduleiSellSync extends Controller {

    private $created = 0;
    private $updated = 0;
    private $def_lang_id;

	public function index(){
		echo 'index';
	}
	public function accept(){
        $login=$_POST['login'];
        $key=$_POST['key'];
        //die("HELLO");
        
        $this->checkApiKey($login,$key);

        


        $this->def_lang_id=$this->getDefaultLanguage();
        require('admin/model/catalog/product.php');
        $this->productModel = new ModelCatalogProduct($this->registry);

        if (isset($_POST['json_data'])) {
            $incomingData = json_decode( $_POST['json_data'] );
            $this->importProducts($incomingData);
            exit(json_encode([
                'page' => $this->request->post['page'],
                'updated' => $this->updated,
                'created' => $this->created
            ]));
        }
        exit('no incoming data');
	}

    private function importProducts($products) {
        foreach ($products as $product) {
            $this->translateAndUpdate($product);
        }
    }

    private function translateAndUpdate($p) {
        $productData = [
            'model' => $p->product_code,
            'sku' =>  $p->sku,
            'upc' => '',
            'ean' => $p->product_barcode,
            'jan' => '',
            'isbn' => '',
            'mpn' => '',
            'location' => '',
            'quantity' => $p->product_quantity,
            'minimum' => '',
            'subtract' => '',
            'stock_status_id' => '',
            'date_available' => '',
            'manufacturer_id' => '',
            '_manufacturer_name'=>$p->analyse_brand,
            'shipping' => '',
            'price' => $p->product_price,
            'points' => '',
            'weight' => $p->product_weight,
            'weight_class_id' => '',
            'length' => round(pow($p->product_volume, 1 / 3) * 1000),
            'width' => round(pow($p->product_volume, 1 / 3) * 1000),
            'height' => round(pow($p->product_volume, 1 / 3) * 1000),
            'length_class_id' => '',
            'status' => '',
            'tax_class_id' => '',
            'product_store'=>[0],
            'sort_order' => '',
            'keyword' => '',
            'product_name'=>$p->product_name,
            'product_description' => [
                $this->def_lang_id => [
                    'name' => $p->product_name,
                    'meta_title' => $p->product_name,
                    'tag' => '',
                    'meta_description' => '',
                    'meta_keyword' => '',
                    'description' => ''
                ]
            ]
        ];
        $this->productUpdate($productData);
    }
	
    private function productGet( $model ){
        $sql="
            SELECT 
                product_id
            FROM
                ".DB_PREFIX."product
            WHERE model='{$model}'
            LIMIT 1";
        $query = $this->db->query($sql);
        return ($query && $query->rows)?$query->rows[0]:null;
    }
    
    private function productDoUpdate( $product_id, $productData  ){
        $sql="UPDATE
                " . DB_PREFIX . "product
            SET
                ean='{$productData['ean']}',
                sku='{$productData['sku']}',
                quantity='{$productData['quantity']}',
                price='{$productData['price']}',
                weight='{$productData['weight']}',
                length='{$productData['length']}',
                width='{$productData['width']}',
                height='{$productData['height']}',
		manufacturer_id=IF(" . DB_PREFIX . "product.manufacturer_id," . DB_PREFIX . "product.manufacturer_id,(SELECT manufacturer_id FROM " . DB_PREFIX . "manufacturer m WHERE m.name='{$productData['_manufacturer_name']}'))
            WHERE
                product_id='$product_id'";
        $this->db->query($sql);
        
        $sql="UPDATE
                " . DB_PREFIX . "product_description
            SET
                name='".addslashes($productData['product_name'])."',
                meta_title='".addslashes($productData['product_name'])."'
            WHERE
                product_id='$product_id'";
        $this->db->query($sql);
    }

    private function productUpdate($productData) {
        $product=$this->productGet( $productData['model'] );
        if ( $product ) {
            $product_id = $product['product_id'];
            $this->productDoUpdate($product_id, $productData);
            $this->updated++;
        } else {
            $this->productModel->addProduct($productData);
            $this->created++;
        }
    }

    private function getDefaultLanguage() {
        $query = $this->db->query("SELECT language_id FROM " . DB_PREFIX . "language WHERE status=1 LIMIT 1");
        foreach ($query->rows as $result) {
            return $result['language_id'];
        }
    }
	

    private function checkApiKey($name, $key) {
        $query = $this->db->query("SELECT api_id FROM " . DB_PREFIX . "api WHERE `username`='$name' AND `key`='$key' AND status=1");
        if (isset($query->rows[0]) && $query->rows[0]['api_id']) {
            return true;
        }
		return true;
        exit('unauthorized');
    }
}?>