<?php
/* Group Name: Документ
 * User Level: 2
 * Plugin Name: Менеджер накладных
 * Plugin URI: http://isellsoft.ru
 * Version: 1.0
 * Description: Инструмент для манипуляций с документами
 * Author: baycik 2017
 * Author URI: http://isellsoft.ru
 */
class DocumentManager extends Catalog{
    public $result=['config'=>'json'];
    public function result($config){
	return [];
    }
}
