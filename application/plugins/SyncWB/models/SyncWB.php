<?php
/* Group Name: Синхронизация
 * User Level: 2
 * Plugin Name: SyncWB
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: Tool for export 
 * Author: baycik 2025
 * Author URI: http://isellsoft.com
 */

class SyncWB extends PluginBase
{
    /*
     * Min user level to use this plugin
     */
    public $min_level = 1;
    /**
     * plugin settings accessible from plugin admin panel. Stores as text JSON
     * @var object 
     */
    protected $plugin_settings;
    /**
     * plugin data for plugins need. Uses MySql JSON col type
     * @var object 
     */
    protected $plugin_data;


    function __construct()
    {
        parent::__construct();
        //Loads $this->plugin_settings and $this->plugin_data
        //To save changes use $this->pluginSettingsFlush();
        $this->pluginSettingsLoad();
    }

    /**
     * execute db installation script
     * @return bool
     */
    public function install()
    {
        $this->Hub->set_level(4);
        $install_file = __DIR__ . "/../install/install.sql";
        $this->load->model('Maintain');
        return $this->Maintain->backupImportExecute($install_file);
    }

    /**
     * execute db uninstallation script
     * @return bool
     */
    public function uninstall()
    {
        $this->Hub->set_level(4);
        $uninstall_file = __DIR__ . "/../install/uninstall.sql";
        $this->load->model('Maintain');
        return $this->Maintain->backupImportExecute($uninstall_file);
    }

    /**
     * Do action when plugin activated
     */
    public function activate() {}

    /**
     * Do action when plugin deactivated
     */
    public function deactivate() {}


    private function apiExecute( string $url, ?array $request=null, string $method='POST' ){
        $headers[]="Authorization: {$this->plugin_settings->token}";
        $curl = curl_init(); 
        switch( $method ){
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                if( $request ){
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($request));
                    $headers[]="Content-Type: application/json";
                }
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if( $request ){
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($request));
                    $headers[]="Content-Type: application/json";
                }
                break;
            case 'GET':
                if( $request ){
                    $query=http_build_query($request);
                    $url .= "?$query";
                }
                break;
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($curl);

        pl(curl_getinfo($curl));
        if( curl_getinfo($curl, CURLINFO_HTTP_CODE)>299 ){
            log_message("error","$url API Execute error: ".$result);
            die($result);
        }
        curl_close($curl);
        return json_decode($result);
    }

    public function listSyncCard(){
        $request=[
            "settings"=>[
                "filter"=>[
                    "withPhoto"=>-1
                ],
                "cursor"=>[
                    "limit"=>3
                ]
            ]
        ];
        $cards=[];
        for( $i=0; $i<1; $i++ ){
            $response=$this->apiExecute('https://content-api.wildberries.ru/content/v2/get/cards/list',$request,'POST');
            $cards=array_merge($cards,$response->cards);
            if( $response->cursor->total<$request["settings"]["cursor"]["limit"] ){
                break;
            }
            $request["settings"]["cursor"]["updatedAt"]=$response->cursor->updatedAt;
            $request["settings"]["cursor"]["nmID"]=$response->cursor->nmID;
        }
        $this->listSyncCardUpsert($cards);
        $this->listSyncPrice();
        
        $this->listSyncFill();
    }
    private function listSyncCardUpsert( $cards ){
        if( !is_array($cards) ){
            return false;
        }
        foreach($cards as $card){
            $images=[];
            if( !empty($card->photos) ){
                foreach($card->photos as $photo){
                    $images[]=$photo->big;
                }
            }
            $set=[
                'wb_nmID'=>$card->nmID,
                'wb_vendorCode'=>$card->vendorCode,
                'wb_subjectID'=>$card->subjectID,
                'wb_subjectName'=>$card->subjectName,
                'wb_brand'=>$card->brand,
                'wb_title'=>$card->title,
                'wb_description'=>$card->description,
                'wb_needKiz'=>$card->needKiz,
                'wb_price'=>0,
                'wb_price_promo'=>0,
                'wb_createdAt'=>$card->createdAt,
                'wb_updatedAt'=>$card->updatedAt,
                'wb_characteristics'=>json_encode($card->characteristics),
                'wb_dimensions'=>json_encode($card->dimensions),
                'wb_images'=>implode(",",$images),
                'sync_status'=>'down_card'
            ];
            $set_fields=[];
            foreach($set as $key=>$val){
                $set_fields[]=" `$key`='".addslashes($val)."'";
            }
            $set_sql=implode(",",$set_fields);

            $upsert_sql="
                INSERT INTO
                    plugin_sync_wb
                SET
                    $set_sql
                ON DUPLICATE KEY UPDATE
                    $set_sql";
            $this->query($upsert_sql);
        }
    }

    public function listSyncPrice(){
        $limit=3;
        $request=[
            "limit"=>$limit,
            "offset"=>0,
            //"filterNmID"=>0
        ];
        $cards=[];
        for( $i=0; $i<1; $i++ ){
            $response=$this->apiExecute('https://discounts-prices-api.wildberries.ru/api/v2/list/goods/filter',$request,'GET');
            $cards=array_merge($cards,$response->data->listGoods);

            $count=count($response->data->listGoods);
            if( $count<$request["limit"] ){
                break;
            }
            $request["offset"]=$count+$request["limit"];
        }
        $this->listSyncPriceUpsert($cards);
    }
    private function listSyncPriceUpsert( $cards ){
        if( !is_array($cards) ){
            return false;
        }
        foreach($cards as $card){
            $set=[
                'wb_nmID'=>$card->nmID,
                'sync_status'=>'down_price'
            ];
            if( is_array($card->sizes) ){
                $set['wb_price']=$card->sizes[0]->price;
                $set['wb_price_promo']=$card->sizes[0]->discountedPrice;
            }
            $set_fields=[];
            foreach($set as $key=>$val){
                $set_fields[]=" `$key`='".addslashes($val)."'";
            }
            $set_sql=implode(",",$set_fields);

            $upsert_sql="
                INSERT INTO
                    plugin_sync_wb
                SET
                    $set_sql
                ON DUPLICATE KEY UPDATE
                    $set_sql";
            $this->query($upsert_sql);
        }
    }







    public function itemSyncCharsGet( int $subjectId ){
        $response=$this->apiExecute("https://content-api.wildberries.ru/content/v2/object/charcs/{$subjectId}",null,'GET');
        if( isset($response->data) ){
            return $response->data;
        }
        return [];
    }

    public function listSyncCardUpdate( object $card ){
        pl($card);die;
        $response=$this->apiExecute("https://content-api.wildberries.ru/content/v2/cards/update",(array)$card);
        return $response;
    }


    private function listSyncFill( ?array $filter=null ){
        $where="1";
        if( isset($filter['card_id']) ){
            $where.=" AND card_id={$filter['card_id']}";
        }
        $sql="
            UPDATE
                plugin_sync_wb wb
                    LEFT JOIN
                prod_list pl ON wb.wb_vendorCode=pl.product_code OR wb.product_code=pl.product_code
                    LEFT JOIN
                stock_entries se ON pl.product_code=se.product_code
                    LEFT JOIN
                stock_tree st ON st.branch_id=se.parent_id
            SET
                wb.product_id=pl.product_id,
                wb.product_code=pl.product_code,
                wb.product_name=ru,
                wb.product_category=path,
                wb.product_quantity=se.product_quantity
            WHERE
                $where
        ";
        $this->query($sql);
    }

    public function listFetch( int $offset, int $limit, string $sortby=null, string $sortdir=null, array $filter = null){
        $having = '';
        if( $filter ){
           $having = "HAVING ".$this->makeFilter($filter); 
        }
        $sql="
            SELECT
                *
            FROM
                plugin_sync_wb
            $having
            LIMIT $limit OFFSET $offset
        ";
        return $this->get_list($sql);
    }

    public function itemUpdate(int $card_id, string $field, ?string $value=null ){
        $ok=$this->update('plugin_sync_wb',[$field=>$value],['card_id'=>$card_id]);
        $filter=['card_id'=>$card_id];
        $this->listSyncFill($filter);

        return $ok;
    }

    private function itemLink($card_id){
        $existing_record=$this->db->from('plugin_sync_wb')->where('card_id', $card_id)->get();
        if( !$existing_record ){
            return false;
        }
        $sql="
        ";
    }


    public function pcompanySet( int $pcomp_id, string $pcomp_name ){
        echo $update_sql="UPDATE
                plugin_list
            SET
                plugin_json_data = JSON_SET(IFNULL(`plugin_json_data`,'{}'),'$.pcomp_id','$pcomp_id','$.pcomp_name','$pcomp_name')
            WHERE
	            plugin_system_name='SyncWB'
            ";
        $this->query($update_sql);
        return $this->db->affected_rows();
    }
}
