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
    private function getProducts($page = 0){
        $limit = 10000;
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
        $this->sendToGateway($postdata,$this->settings->plugin_settings->gateway_url.'/accept');
    }

    private function sendToGateway($postdata,$url) {
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
        echo file_get_contents($url, false, $context);
    }

}
