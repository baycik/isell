<?php
/* Group Name: Синхронизация
 * User Level: 2
 * Plugin Name: Opencart Синхронизатор
 * Plugin URI: http://isellsoft.com
 * Version: 1
 * Description: Синхронизация с интернет-магазином opencart.com
 * Author: baycik 2017
 * Author URI: http://isellsoft.com
 */
class OpencartSyncUtils extends PluginManager{
    protected function filename_prepare($str){
        $translit=array(
            "А"=>"a","Б"=>"b","В"=>"v","Г"=>"g","Д"=>"d","Е"=>"e","Ё"=>"e","Ж"=>"zh","З"=>"z","И"=>"i","Й"=>"y","К"=>"k","Л"=>"l","М"=>"m","Н"=>"n","О"=>"o","П"=>"p","Р"=>"r","С"=>"s","Т"=>"t","У"=>"u","Ф"=>"f","Х"=>"h","Ц"=>"ts","Ч"=>"ch","Ш"=>"sh","Щ"=>"shch","Ъ"=>"","Ы"=>"y","Ь"=>"","Э"=>"e","Ю"=>"yu","Я"=>"ya",
            "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"e","ж"=>"zh","з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h","ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"shch","ъ"=>"","ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
            "A"=>"a","B"=>"b","C"=>"c","D"=>"d","E"=>"e","F"=>"f","G"=>"g","H"=>"h","I"=>"i","J"=>"j","K"=>"k","L"=>"l","M"=>"m","N"=>"n","O"=>"o","P"=>"p","Q"=>"q","R"=>"r","S"=>"s","T"=>"t","U"=>"u","V"=>"v","W"=>"w","X"=>"x","Y"=>"y","Z"=>"z"
        );
        $result=strtr($str,$translit);
        $result=preg_replace("/[^a-zA-Z0-9_]/i","-",$result);
        $result=preg_replace("/\-+/i","-",$result);
        $result=preg_replace("/(^\-)|(\-$)/i","",$result);
        return $result;
    }    
}

class OpencartSync extends OpencartSyncUtils{
    public $min_level=2;
    public $settings;
    function __construct(){
        set_time_limit(120);
	$this->settings=$this->settingsDataFetch('OpencartSync');
    }
    
    function init(){
        $this->api_token=$this->Hub->svar('opencart_api_token');
        if( !$this->api_token ){
            $this->login();
        }
    }
    
    private function productSend($current_offset,$limit=5){
        $requestsize_limit=2*1024*1024;//2MB
        $pcomp_id=$this->settings->plugin_settings->pcomp_id;
        $dratio=$this->settings->plugin_settings->dollar_ratio;
        $this->Hub->load_model('Storage');
        $sql = "SELECT
                    model,ean,quantity,price,weight,name,manufacturer_name,
                    volume,
                    posl.*,
                    product_img local_img_filename,
                    MD5(CONCAT(ean,quantity,ROUND(price,2),ROUND(weight,4),name,manufacturer_name)) local_field_hash
                FROM
                    (SELECT
                        product_code model,
                        product_barcode ean,
                        product_quantity quantity,
                        GET_SELL_PRICE(product_code,'$pcomp_id','$dratio') price,
                        product_weight weight,
                        ru name,
                        product_volume volume,
                        analyse_brand manufacturer_name,
                        product_img
                    FROM
                        stock_entries se
                            JOIN
                        prod_list USING(product_code)
                    LIMIT $limit OFFSET $current_offset) t
                LEFT JOIN
                    plugin_opencart_sync_list posl ON remote_model=model";
        $products=$this->get_list($sql);
        
        $request=[];
        $requestsize_total=0;
        foreach($products as $product){
            $item=$this->productImageInfoGet( $product->local_img_filename,$product->remote_img_hash,$product->remote_img_time,$product->model,$product->name );
            $item_size=$item['local_img_b64size'];
            if( $product->local_field_hash !== $product->remote_field_hash ){
                $item['product_id']=$product->remote_product_id;
                $item['model']=$product->model;
                $item['ean']=$product->ean;
                $item['quantity']=$product->quantity;
                $item['price']=$product->price;
                $item['weight']=$product->weight;
                $item['volume']=$product->volume;
                $item['name']=$product->name;
                $item['manufacturer_name']=$product->manufacturer_name;
                $item_size+=500;
            }
            if( $item_size==0 || $item_size+$requestsize_total>$requestsize_limit ){
                continue;
            }
            $requestsize_total+=$item_size;
            $request[]=$item;
        }
        $postdata=[
            'products'=>json_encode($request)
        ];
        $getdata=[
            'route'=>'api/sync/productsUpdate',
            'api_token'=>$this->api_token
        ];
        $json=$this->sendToGateway($postdata,$getdata);
        $this->productUpdateResponseProcess($json);
    }
    
    private function productImageInfoGet( $local_img_filename, $remote_img_hash, $remote_img_time, $model, $name ){
        $image_info=['local_img_b64size'=>0];
        $local_img_time=$this->Storage->file_time("dynImg/".$local_img_filename);
        $local_img_hash=$this->Storage->file_checksum("dynImg/".$local_img_filename); 
        if( $local_img_hash!==$remote_img_hash ){
            if( $local_img_hash>0 && $local_img_time>$remote_img_time ){
                //Upload local->remote
                $file_data=$this->Storage->file_restore('dynImg/'.$local_img_filename);
                $ext = pathinfo($local_img_filename, PATHINFO_EXTENSION);
                $image_info['remote_img_filename']=$this->filename_prepare("$model $name").".$ext";
                $image_info['local_img_data']=base64_encode($file_data);
                $image_info['local_img_b64size']= strlen($image_info['local_img_data']);
            } else 
            if( $remote_img_hash>0 && $remote_img_time>$local_img_time ){

            }
        }
        return $image_info;
    }
    
    private function productUpdateResponseProcess($json){
        $updated_models= json_decode($json);
        if( count($updated_models) ){
            $list= implode(',', $updated_models);
            $this->query("DELETE FROM plugin_opencart_sync_list WHERE remote_model='".$this->db->escape_str($list)."'");
        }
    }
    
    private function productDigestGet(){
        $postdata=[
        ];
        $getdata=[
            'route'=>'api/sync/productsDigestGet',
            'api_token'=>$this->api_token
        ];
        $json=$this->sendToGateway($postdata,$getdata);
        $product_digest= $json?json_decode($json):null;
        //print_r($product_digest);
        $this->query("DELETE FROM plugin_opencart_sync_list");
        if( $product_digest ){
            foreach( $product_digest as $product ){
                $sql="INSERT 
                        plugin_opencart_sync_list
                    SET 
                        remote_product_id='{$product->product_id}',
                        remote_model='{$product->model}',
                        remote_field_hash='{$product->field_hash}',
                        remote_img_hash='{$product->img_hash}',
                        remote_img_time='{$product->img_time}'";
                $this->query($sql);
            }
            return true;
        }
        return false;
    }
    
    public $sync=['step'=>['string','fetch_digest'],'offset'=>['int',0]];
    public function sync($current_step,$current_offset){
        switch($current_step){
            case 'fetch_digest':
                $this->productDigestGet();
                $current_step='send_products';
                //$current_offset=0;
                break;
            case 'send_products':
                $this->productSend($current_offset);
                //$current_offset+=10;
                die('end of sync');
                break;
        }
        header("Location: ./?step=$current_step&offset=$current_offset");
    }

    public $login=[];
    public function login(){
        $postdata = array(
            'username' => $this->settings->plugin_settings->login,
            'key' => $this->settings->plugin_settings->key
        );
        $getdata=[
            'api_token'=>'',
            'route'=>'api/login'
        ];
        $text=$this->sendToGateway($postdata,$getdata);
        try{
            $response= json_decode($text);
        } catch (Exception $ex) {
            die($ex.">>> ".$text);
        }
        if( $response && $response->api_token ){
            $this->api_token=$response->api_token;
            $this->Hub->svar('opencart_api_token',$response->api_token);
            return true;
        } else {
            print('failed to login');
            return false;
        }
    }

    private function sendToGateway($postdata=[],$getdata=[]) {
        $url=$this->settings->plugin_settings->gateway_url."?".http_build_query($getdata);
        $context = stream_context_create(
            [
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'content' => http_build_query($postdata)
                ]
            ]
        );
        return file_get_contents($url, false, $context);
    }
    
    /*
    public $cartAdd=[];
    public function cartAdd(){
        $postdata=[
            'product_id'=>28,
            'product_quantity'=>33
        ];
        $getdata=[
            'route'=>'api/cart/add',
            'api_token'=>$this->api_token
        ];
        header("Content-type:text/plain");
        echo $this->sendToGateway($postdata, $getdata);
    }
    
    public $productListGet=[];
    public function productListGet(){
        $postdata=[
            'filter'=>json_encode([
                'start'=>1,
                'limit'=>2
            ])
        ];
        $getdata=[
            'route'=>'api/sync/getProducts',
            'api_token'=>$this->api_token
        ];
        header("Content-type:text/plain");
        $text=$this->sendToGateway($postdata, $getdata);
        print_r(json_decode($text));
    }
    
    public $categoryListGet=[];
    public function categoryListGet(){
        $postdata=[
        ];
        $getdata=[
            'route'=>'api/bayproduct/getCategories',
            'api_token'=>$this->api_token
        ];
        header("Content-type:text/plain");
        $text=$this->sendToGateway($postdata, $getdata);
        
        
        print_r(json_decode($text));
    }*/
}