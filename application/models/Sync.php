<?php
include "Catalog.php";
class Sync extends Catalog {
    private $gateway_url = "http://nilsonmag.com/open/?route=module/baycikSync";
    private $login = "Default";
    private $key = "PeeyPTkk7ptcaWPEocWjJKub199ov8zsGblWGS1RDjZmoDWHJbNkilItjvk7ofHnVeeJz48l9FIUEAuxJ3z3SIqd9lGpvEakqZzV2AizA19epJ1nz1DWXT090ic5BOn4PefoabFCOz19zKhJmUYr8uZ2ol3HDcoyJIFj9tPg2Kyt8DQWVtwBYJZKN2QXhOzdejbMwED6eqt2XcwDvbOx7WDqxpMMMjykmapuzGxY9shietVfoIndLsBKwEvAfgx4";
    private $defaultUserId='319';
    private $dollarRatio=66;

    private function getProducts($page = 0){
        $limit = 10000;
        $offset = $limit * $page;
        $sql = "
            SELECT
                    product_code,
                    ru product_name,
                    ROUND(sell
                    *IF(discount IS NOT NULL,discount,1)
                    *IF(curr_code='USD',$this->dollarRatio,1),2)
                        product_price,
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
                        LEFT JOIN
                    companies_discounts cd ON st.top_id = cd.branch_id
                WHERE company_id='$this->defaultUserId'
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
