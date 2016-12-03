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
    private function marketGetAll(){
	$offset=0;
	$marketItems=[];
	do{
	    $response=$this->vk->market->get(['owner_id'=>$this->market_id,'count'=>200,'offset'=>$offset,'extended'=>1]);
	    if( !is_array($response) || !count($response['items']) ){
		break;
	    }
	    $marketItems=array_merge($marketItems,$response['items']);
	    $offset+=200;
	} while( 1 );
	return $marketItems;
    }
//    public function marketGetAndStore(){
//	$items=$this->marketGetAll();
//	$Storage=$this->Base->load_model('Storage');
//	return $Storage->json_store('vk_api_sync/downloaded_market_items.json',$items);
//    }
    /*
     * marketMakeDictionary
     * finds product_code in description and links it with id in market items
     */
    public function marketGetAndCombine(){
	$items=$this->marketGetAll();
	if( !is_array($items) ){
	    throw new Exception('No downloaded market items are found');
	}
	foreach($items as &$item){
	    $product_code='';
	    preg_match('|Модель:(.*)$|mi',$item['description'],$product_code);
	    $item['product_code']=trim($product_code[1]);
	}
	$Storage=$this->Base->load_model('Storage');
	return $Storage->json_store('vk_api_sync/market_items_combine.json',$items);
    }
    private function uploadProducts($chunk){
	$item=$chunk[0];
	
	
	//print_r( $item);
	
	$product=[
	    'owner_id'=>$this->market_id,
	    'item_id'=>$item->id,
	    'name'=>$item->title,
	    'description'=>$item->description,
	    'category_id'=>$item->category->id,
	    'price'=>666,//$item->price->amount/1000,
	    'main_photo_id'=>$item->photos[0]->id
	];
	
	$this->vk->market->edit($product);
	print_r($product);
	$product=[''];
    }
    public function uploadChunk(){
	$limit=10;
	$offset=$this->Base->svar('vk_api_offset');
	
	$Storage=$this->Base->load_model('Storage');
	$items=$Storage->json_restore('vk_api_sync/market_items_combine.json');
	$chunk=array_slice($items,$offset,$limit);
	$this->uploadProducts($chunk);
	
	$this->Base->svar('vk_api_offset',$offset+$limit);
	return 0;
    }
    public function syncProducts(){
	$next_step='';
	$this_step=$this->Base->svar('vk_api_next_step');
	switch($this_step){
	    case 'upload_chunk':
		$left_count=$this->uploadChunk();
		$next_step=$left_count?'upload_chunk':'stop';
		echo '-- Синхронизация товаров. Осталось: '.$left_count;
		break;
	    case 'stop':
		echo 'Синхронизация окончена';
		$this->Base->svar('vk_api_next_step','');
		break;
	    default:
		//$this->marketMakeDictionary();
		$next_step='upload_chunk';
		echo '-- Получен список товаров с ВК';
		$this->Base->svar('vk_api_offset',0);
 	}
	$this->Base->svar('vk_api_next_step',$next_step);
	return ' ok';
    }
}
