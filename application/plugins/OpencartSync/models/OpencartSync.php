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
class OpencartSync extends PluginManager{
    public $settings;
    function __construct(){
	$this->settings=$this->settingsDataFetch('OpencartSync');
    }
    function init(){
        $this->api_token=$this->Hub->svar('opencart_api_token');
        if( !$this->api_token ){
            $this->login();
        }
        echo 'Тoken:'.$this->api_token;
    }
/*    private function getProducts($page = 0){
        $limit = 10;
        $offset = $limit * $page;
        $sql = "
            SELECT
                    product_code,
                    ru product_name,
                    GET_PRICE(product_code,'{$this->settings->plugin_settings->pcomp_id}','{$this->settings->plugin_settings->dollar_ratio}') product_price,
                    product_quantity,
                    product_volume,
                    product_weight,
                    product_barcode,
                    analyse_brand
                FROM
                    stock_entries se
                        JOIN
                    prod_list USING(product_code)
                        JOIN
                    price_list USING(product_code)
                        JOIN
                    stock_tree st ON se.parent_id=st.branch_id
                ORDER BY fetch_count DESC
                LIMIT $limit OFFSET $offset";
        return $this->get_list($sql);
    }

    public function recieve(){
        
    }
    
    public $send=['int'];
    public function send( $page=0 ) {
        $data=$this->getProducts($page);
        $postdata = array(
            'json_data' => json_encode($data),
            'page'=>$page,
            'login' => $this->settings->plugin_settings->login,
            'key' => $this->settings->plugin_settings->key
        );
        echo $this->sendToGateway($postdata,$this->settings->plugin_settings->gateway_url.'/accept');
    }*/

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
            'route'=>'api/bayproduct/getProducts',
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
