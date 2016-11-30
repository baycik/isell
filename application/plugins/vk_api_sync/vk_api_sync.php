<?php
/* Group Name: ВКонтакте
 * User Level: 2
 * Plugin Name: VK Синхронизатор
 * Plugin URI: http://isellsoft.com
 * Version: 0.1
 * Description: Синхронизация с маркетом на сайте Вконтакте
 * Author: baycik 2016
 * Author URI: http://isellsoft.com
 */

require_once 'models/PluginManager.php';
require_once 'vk.php';
class vk_api_sync extends PluginManager{
    public $settings;
    function __construct(){
	$this->settings=$this->settingsDataFetch('vk_api_sync');
	$this->market_id=-abs($this->settings->market_id);
	$this->vk=new Vk($this->settings);
	header("Content-type: text/plain");
    }
    public function marketGetAll(){
	$offset=0;
	$marketItems=[];
	do{
	    $response=$this->vk->market->get(['owner_id'=>$this->market_id,'count'=>200,'offset'=>$offset]);
	    if( !is_array($response) || !count($response['items']) ){
		break;
	    }
	    $marketItems=array_merge($marketItems,$response['items']);
	    $offset+=200;
	} while( 1 );
	return $marketItems;
    }
    public function marketGetAndStore(){
	$items=$this->marketGetAll();
	$Storage=$this->Base->load_model('Storage');
	return $Storage->json_store('vk_api_sync/downloaded_market_items.json',$items);
    }
    /*
     * marketMakeDictionary
     * finds product_code in description and links it with id in market items
     */
    public function marketMakeDictionary(){
	$items=$this->marketGetAll();
	if( !is_array($items) ){
	    throw new Exception('No downloaded market items are found');
	}
	$dictionary=[];
	foreach($items as $item){
	    $product_code='';
	    preg_match('|Модель:(.*)$|mi',$item['description'],$product_code);
	    $dictionary[]=['id'=>$item['id'],'product_code'=>trim($product_code[1])];
	}
	$Storage=$this->Base->load_model('Storage');
	return $Storage->json_store('vk_api_sync/market_dictionary.json',$dictionary);
    }
}
