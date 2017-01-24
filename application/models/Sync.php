<?php
include "Catalog.php";
class Sync extends Catalog {
    private $gateway_url = "https://nilsonmag.com/open/?route=extension/module/baycikSync";
    //private $gateway_url = "http://localhost:888/nilsonmag.com/open/?route=extension/module/baycikSync";
    private $login = "Default";
    private $key = "9aFJXRMqyA1dQ1HHy5q618e5V9zKklmgswUu6vqsE3g3lP0OJpyb6Il6rnPGqCKM8QM38HpWJmvUWsrkCxpvITQ7CMZcrVkrkwMOQ4DC8b95nxX8kAi2ux0HxgLRaQwKsSS40AEQVE9M2ikusdZfl1ujpH1o19aX36SLgQMcmWOwzFulfdsrEh7YfEnDr4fr44zBhQQ9aQX2O9WFNQvXBLa8OZSE8a4gyLxZKYPVJukhyzzuOuiEq7pqRGKbCNx6";
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
