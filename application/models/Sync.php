<?php
include "Catalog.php";
class Sync extends Catalog {
    private $gateway_url = "http://nilsonmag.com/open/?route=module/baycikSync";
    private $login = "Default";
    private $key = "NqSnphoicYAYfO5ewh9N6u5qrExwJhui8ZeXRb4E3YJHp28hG4heyx6Q6tz79tqPtojEQ6drB2wZqxIR4zsJOlknjJBoKDXCaIiAwdgWte4k9QUrs3zuRAArGl35Mr5zeDDuzzG19tb6GrgypY8Z8LQVoenz4VbTfuvYoAnn7gkLpwqn4NHGT0OpyUpVAkdGLXshCDD3S3DH7BTwhEuz1wIdqPFCIHBjmxgsRbTVYqCeh0KSVIWhR6awTXUEvpnZCv3a2xIh0MhiQSQjB6bpsAXNBgdqLz5f63LYYfdFudinHfSRbEn8jYV3UkHoSha43nNVqBy8KHqa2HRkcvwwEMVhIWOC8LRIySuWH8KlbDPsZsRvl2eWf1AZv50Y9J4m4lcKCddUgtVkGwSgRmMIxmUIFJs0WD0ptB5zXimbyDgMDdtkwevbVOO21eGs26PCDCJzmo9VQMZEcbMNCZPjpZrX8jEbaji2kdBSDy2S33Q6sjSWkw7MZSNH0a4RkpsDxZVBgfvuo9qwicrsaxP522DbwzemTtc2yflhyxk8Kp5AvDChz8ltUH19kOCDo2HuG1QORlJlN9lcbiPox1LtTUnMCveE8u3DcHs3jhPxV0LWOU7utyvSpaeBAjvMGHCxiNxj09Mv4ojYoYZAP3fjKHBK4XZPfa6zN5CUQBbkemj7WkDihsNx7UuvNpWpNqv4uWqM1bT3Qp6nGsEUpCLtu0aa7IacaG9FDKpIl4YlcBZHmF5S0HlIzuCg5d2A9swop4whqSPY1vQjOVIys6p6x5U0BZryQIKqfyYomTNTAqHH2DRXBBQsvgYbUGy3RWCxBpqD3lBmk4t4guNKZeXsljF9kLmESRnchzu0EDV1mv8zfowCKF69CoXrTuaFO19xDgx5UhgDpRGNLO0mUxBXIiq3ktTnhwM4TublsRFQQ7oNfZhd3hrgEUxQn8VOxGeNFqTbRMjwJSJuE4mnRjv8IS5gtileIIpmTsZZGPE0wJdhyw2ifefAjIntPF7e829q";
    private $defaultUserId='319';
    private $dollarRatio=66;

    private function getProducts($page = 0){
        $limit = 10000;
        $offset = $limit * $page;
        $sql = "
            SELECT
                    product_code,
                    ru product_name,
                    GET_PRICE(product_code,'$this->defaultUserId',$this->dollarRatio) product_price,
                    product_quantity,
                    product_volume,
                    product_weight,
                    barcode,
                    analyse_group
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

    public function send( $page=0 ) {
        $data=$this->getProducts($page);
        $postdata = array(
            'json_data' => json_encode($data),
            'page'=>$page,
            'login' => $this->login,
            'key' => $this->key
        );
        $this->sendToGateway($postdata);
    }

    private function sendToGateway($postdata) {
        set_time_limit(120);
        $context = stream_context_create(
                array(
                    'http' => array(
                        'method' => 'POST',
                        'header' => 'Content-type: application/x-www-form-urlencoded',
                        'content' => http_build_query($postdata)
                    )
                )
        );
        echo file_get_contents($this->gateway_url, false, $context);
    }

}
