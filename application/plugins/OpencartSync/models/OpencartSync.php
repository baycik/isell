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
    public function install(){
	$install_file=__DIR__."/install.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($install_file);
    }
    public function uninstall(){
	$uninstall_file=__DIR__."/uninstall.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($uninstall_file);
    }
    
    private function productSend(){
        $rowcount_limit=300; 
        $requestsize_limit=3*1024*1024;//2MB
        $request=[];
        $products_skipped=[];
        $requestsize_total=0;
        
        $pcomp_id=$this->settings->plugin_settings->pcomp_id;
        $dratio=$this->Hub->pref('usd_ratio');
        $this->Hub->load_model('Storage');
        $this->Hub->load_model('Stock');
        
        $sql = "SELECT
                    model,ean,quantity,price,weight,name,manufacturer_name,
                    volume,
                    posl.*,
                    product_img local_img_filename,
                    MD5(CONCAT(ean,quantity,ROUND(price,2),ROUND(weight,4),name,manufacturer_name)) local_field_hash
                FROM
                    plugin_opencart_sync_list posl
                LEFT JOIN
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
                        prod_list USING(product_code) ) t ON remote_model=model 
                    LIMIT $rowcount_limit";
        $products=$this->get_list($sql);
        
        foreach($products as $product){
            $item=['local_img_b64size'=>0,'local_img_hash'=>0];
            
            if( $this->settings->plugin_settings->img_up || $this->settings->plugin_settings->img_down ){
                $item=$this->productImageInfoGet( $product );
            }
            if( $this->settings->plugin_settings->fields_up ){
                $item['ean']=$product->ean;
                $item['quantity']=$product->quantity;
                $item['price']=$product->price;
                $item['weight']=$product->weight;
                $item['volume']=$product->volume;
                $item['name']=$product->name;
                $item['manufacturer_name']=$product->manufacturer_name;
            }
            $item['model']=$product->model;
            $item['product_id']=$product->remote_product_id;
            if( $item['local_img_hash'] == $product->remote_img_hash && $product->local_field_hash == $product->remote_field_hash ){
                $products_skipped[]=$product->model;
                continue;
            }
            if( !$product->local_field_hash ){
                $item['action']='delete';
            } else
            if( $product->remote_product_id ){
                $item['action']='edit';
            } else {
                $item['action']='add';
            }
            $request[]=$item;
            $item_size=$item['local_img_b64size']+500;
            $requestsize_total+=$item_size;
            if( $requestsize_total>$requestsize_limit ){
                break;
            }
        }
	
	//print_r($request);
	
        $postdata=[
            'products'=>json_encode($request)
        ];
        $getdata=[
            'route'=>'api/sync/productsUpdate',
            'api_token'=>$this->api_token
        ];
        $response=$this->sendToGateway($postdata,$getdata);
        $products_synced=json_decode($response);
	if( json_last_error()>0 ){
	    die($response);
	}
        if($products_synced){
            $products_to_remove= array_merge($products_skipped,$products_synced);
        } else {
            $products_to_remove=$products_skipped;
        }
        return $this->productRemoveFromSyncList($products_to_remove);
    }
    
    private function productImageInfoGet( $product ){
        $image_info=['local_img_b64size'=>0,'local_img_hash'=>0];
        $filesize=$this->Storage->file_size('dynImg/'.$product->local_img_filename);
        if( !$product->local_img_filename || $filesize>2*1024*1024 ){
            return $image_info;
        }
        $local_img_time=$this->Storage->file_time("dynImg/".$product->local_img_filename);
        $image_info['local_img_hash']=$this->Storage->file_checksum("dynImg/".$product->local_img_filename); 
//        if( $product->model==24111001 ){
//            echo $filesize." dynImg/".$product->local_img_filename;
//            print_r($image_info);
//            die();
//        }
        if( $image_info['local_img_hash']!==$product->remote_img_hash ){
            if( $this->settings->plugin_settings->img_up && $image_info['local_img_hash'] && $local_img_time>$product->remote_img_time ){
                //Upload local->remote
                $ext = pathinfo($product->local_img_filename, PATHINFO_EXTENSION);
                $file_data=$this->Storage->file_restore('dynImg/'.$product->local_img_filename);
                $image_info['local_img_data']=base64_encode($file_data);
                $image_info['local_img_b64size']= strlen($image_info['local_img_data']);
                $image_info['remote_img_filename']=$this->filename_prepare("$product->model $product->name").".$ext";
            } else 
            if( $this->settings->plugin_settings->img_down && $product->remote_img_hash && $product->remote_img_time > $local_img_time ){
                $ext = pathinfo($product->remote_img_url, PATHINFO_EXTENSION);
                $dynImgName= microtime().$ext;
                $dynImgPath=$this->Storage->storageFolder.'/dynImg/'.$dynImgName;
                $sourceUrl=$this->settings->plugin_settings->gateway_url.'/image/'.$product->remote_img_url;
                if( copy($sourceUrl,$dynImgPath) ){
                    $this->Stock->productUpdate($product->model, 'product_img', $dynImgName);
                }
            }
        }
        return $image_info;
    }
    
    private function productRemoveFromSyncList( $products_to_remove ){
        if( count($products_to_remove) ){
            $list= "'".implode("','", $products_to_remove)."'";
            $this->query("DELETE FROM plugin_opencart_sync_list WHERE remote_model IN ($list)");
            return $this->db->affected_rows();
        }
        return 0;
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
        $this->query("START TRANSACTION");
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
                        remote_img_time='{$product->img_time}',
                        remote_img_url='{$product->image}'
                        ";
                $this->query($sql);
            }
            $sql="INSERT INTO 
                    plugin_opencart_sync_list
                 SELECT 
                    NULL,product_code,'','',0,''
                FROM
                    stock_entries se
                        JOIN
                    prod_list USING(product_code)
                WHERE
                    product_code NOT IN (SELECT remote_model FROM plugin_opencart_sync_list)";
            $this->query($sql);
            $this->query("COMMIT");
            return count($product_digest);
        }
        return 0;
    }
    
    public $sync=['step'=>['string','fetch_digest']];
    public function sync($current_step){
        $message="";
        switch($current_step){
            case 'fetch_digest':
                $current_step='send_products';
                $product_count=$this->productDigestGet();
                if( $product_count ){
                    $message="Recieved $product_count digests from remote server";
                } else {
                    $message="Server has no any products";
                }
                break;
            case 'send_products':
                $product_count=$this->productSend();
                if( $product_count ){
                    $message="Synced $product_count products with remote server";
                } else {
                    $message="Syncronisation ends!";
                    die($message);
                }
                break;
        }
        header("Refresh: 1; url=./?step=$current_step");
        echo $message;
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
        if( $response && isset($response->api_token) ){
            $this->api_token=$response->api_token;
            $this->Hub->svar('opencart_api_token',$response->api_token);
            return true;
        } else {
            die('failed to login '.$text);
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
}