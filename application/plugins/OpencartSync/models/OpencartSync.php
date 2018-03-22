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
	$this->settings=$this->settingsDataFetch('OpencartSync');
    }
    function init(){
        $this->api_token=$this->Hub->svar('opencart_api_token');
        if( !$this->api_token ){
            $this->login();
        }
        //echo 'Тoken:'.$this->api_token;
    }
    private function productSend($current_offset,$limit){
        $pcomp_id=$this->settings->plugin_settings->pcomp_id;
        $dratio=$this->settings->plugin_settings->dollar_ratio;
        $sql = "SELECT
                    product_code model,
                    ru name,
                    GET_SELL_PRICE(product_code,'$pcomp_id','$dratio') price,
                    product_quantity quantity,
                    product_volume volume,
                    product_weight weight,
                    product_barcode ean,
                    analyse_brand manufacturer_name,
                    product_img
                FROM
                    stock_entries se
                        JOIN
                    prod_list USING(product_code)
                LIMIT $limit OFFSET $current_offset";
        $products=$this->get_list($sql);
        
        
        $Storage=$this->Hub->load_model('Storage');
        foreach($products as $product){
            if( $product->product_img ){
                $product->img_time=$Storage->file_time("dynImg/".$product->product_img);
                $product->img_checksum=$Storage->file_checksum("dynImg/".$product->product_img);
                $product->img_filename=$this->filename_prepare("$product->model $product->name");
            }
        }
        
        print_r($products);
        die();
        
        
        $postdata=[
            'products'=>json_encode($products)
        ];
        $getdata=[
            'route'=>'api/sync/updateProducts',
            'api_token'=>$this->api_token
        ];
        //header("Content-type:text/plain");
        //echo $this->Hub->svar('ocsync_current_step');
        //echo $this->Hub->svar('ocsync_current_offset');
        echo $this->sendToGateway($postdata,$getdata);
    }
    
    private function productDigestGet(){
        $postdata=[
        ];
        $getdata=[
            'route'=>'api/sync/getProductDigests',
            'api_token'=>$this->api_token
        ];
        $json_digest=$this->sendToGateway($postdata,$getdata);
        $this->query("UPDATE plugin_list SET plugin_json_data='$json_digest' WHERE plugin_system_name='OpencartSync'");
        
    }

    private function sendToGateway($postdata=[],$getdata=[]) {
        $url=$this->settings->plugin_settings->gateway_url."?".http_build_query($getdata);
        set_time_limit(120);
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
    
    public $sync=[];
    public function sync(){
        $limit=10;
        $current_step='';//$this->Hub->svar('ocsync_current_step');
        $current_offset=$this->Hub->svar('ocsync_current_offset');
        if( !$current_step ){
            $current_step='fetch_digest';
        }
        switch($current_step){
            case 'fetch_digest':
                $this->productDigestGet();
                break;
            case 'send_products':
                $this->productSend($current_offset,$limit);
                break;
        }
        
        //$current_offset+=$limit;
        $this->Hub->svar('ocsync_current_step',$current_step);        
        $this->Hub->svar('ocsync_current_offset',$current_offset);
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
    }
}