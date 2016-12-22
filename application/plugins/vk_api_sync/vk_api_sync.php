<?php
/* Group Name: ВКонтакте
 * User Level: 2
 * Plugin Name: VK Синхронизатор
 * Plugin URI: http://isellsoft.com
 * Version: 0.2
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
	    try{
		$response=$this->vk->market->get(['owner_id'=>$this->market_id,'count'=>200,'offset'=>$offset,'extended'=>1]);
	    } catch( Exception $e ){
		$this->error($e);
		return false;
	    }
	    if( !is_array($response) || !count($response['items']) ){
		break;
	    }
	    $marketItems=array_merge($marketItems,$response['items']);
	    $offset+=200;
	} while( 1 );
	return $marketItems;
    }
    private function marketGetAndCombine(){
	$items=$this->marketGetAll();
	if( !is_array($items) ){
	    echo '<br> No downloaded market items are found';
	    return false;
	}
	foreach($items as &$item){
	    $product_code='';
	    preg_match('|Модель:(.*)$|mi',$item['description'],$product_code);
	    $item['product_code']=isset($product_code[1])?trim($product_code[1]):null;
	}
	$Storage=$this->Base->load_model('Storage');
	return $Storage->json_store('vk_api_sync/market_items_combine.json',$items);
    }
    private function stockRating($product_code,$description){
	$stock_count=$this->get_value("SELECT product_quantity FROM stock_entries WHERE product_code='{$product_code}'");
	if( $stock_count>300 ){
	    $level="★★★★★";
	} else
	if( $stock_count>100 ){
	    $level="★★★★☆";
	} else
	if( $stock_count>50 ){
	    $level="★★★☆☆";
	} else 
	if( $stock_count>10 ){
	    $level="★★☆☆☆";
	}  else 
	if( $stock_count>0 ){
	    $level="★☆☆☆☆";
	} else {
	    $level="☆☆☆☆☆ (Под заказ)";
	}
	return preg_replace('|Наличие:(.*)$|mi', "Наличие: $level", $description);
    }
    private function uploadProduct($item){
	$company_id=$this->settings->pcomp_id;//company_id for wich we will retrieve prices
	$usd_ratio=$this->Base->pref('usd_ratio');
	$price=$this->get_value("SELECT GET_PRICE('{$item->product_code}',$company_id,$usd_ratio)");
	$description=$this->stockRating($item->product_code, $item->description);
	
	if( !is_numeric($price) ){
	    echo "<br> Не найден код: ".$item->product_code;
	    $this->Base->msg("Не найден код: ".$item->product_code);
	    return true;
	}
	if( $price && $price!=$item->price->amount/100 || $description!=$item->description ){
	    $product=[
		'owner_id'=>$this->market_id,
		'item_id'=>$item->id,
		'name'=>$item->title,
		'description'=>$description,
		'category_id'=>$item->category->id,
		'price'=>$price,
		'main_photo_id'=>$item->photos[0]->id
	    ];
	    try{
		echo "<br> Обновляем: ".$item->product_code;
		return $this->vk->market->edit($product);
	    } catch(Exception $e){
		$this->error($e);
		return false;
	    }
	}
	echo "<br> Не изменился ".$item->product_code;
	return true;
    }
    private function error( $e ){
	if( $e->getCode()==5 ){
	    die("<br> Ошибка ВК: ".$e->getMessage());
	}
	echo "<br> Ошибка ВК: ".$e->getMessage();
    }
    
    
    public function uploadChunk(){
	$limit=2;
	$offset=$this->Base->svar('vk_api_offset');
	
	$Storage=$this->Base->load_model('Storage');
	$items=$Storage->json_restore('vk_api_sync/market_items_combine.json');
	$chunk=array_slice($items,$offset,$limit);
	foreach($chunk as $item){
            if( !isset($item->product_code) ){
                echo "<br> Код не найден для: ".$item->title;;
            }
	    if( $this->uploadProduct($item) ){
		continue;
	    } else {
		sleep(1);
		$this->uploadProduct($item);
	    }
	}
	$this->Base->svar('vk_api_offset',$offset+$limit);
	return count($items)-$offset;
    }
    public function syncProducts(){
	$this_step=$this->Base->svar('vk_api_next_step');
	switch($this_step){
	    case 'upload_chunk':
		$left_count=$this->uploadChunk();
		if($left_count>0){
		    $next_step='upload_chunk';
		    echo ' -- Осталось: '.$left_count;
		} else{
		    $next_step='stop';
		    echo ' -- Все товары синхронизированы';		    
		}
		break;
	    case 'stop':
		echo ' Синхронизация окончена';
		$next_step='';
		break;
	    default:
		if( $this->marketGetAndCombine() ){
		    $next_step='upload_chunk';
		    echo '-- Получен список товаров с ВК';
		    $this->Base->svar('vk_api_offset',0);
		} else {
		    $next_step='stop';
		    echo 'Не удалось подключиться к ВК';
		}
		
		break;
 	}
	$this->Base->svar('vk_api_next_step',$next_step);
	return ' ok';
    }
}
