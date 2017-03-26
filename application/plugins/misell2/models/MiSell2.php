<?php
/* User Level: 1
 * Group Name: Мобильное
 * Plugin Name: MiSell2
 * Version: 2017-03-26
 * Description: Мобильное приложение
 * Author: baycik 2017
 * Author URI: isellsoft.com
 * Trigger before: MiSell2
 * 
 * Description of DocumentSell
 * This class handles all of sell documents
 * @author Baycik
 */
class MiSell2 extends Catalog{
    
    public $doclistGet=['date'=>'string','compfilter'=>'string'];
    public function doclistGet($date,$compfilter){
	$sql="SELECT
		*
	    FROM
		document_list
	    WHERE
		SUBSTRING(cstamp,0,10)='$date'
		AND (doc_type=1 OR doc_type=2)
	    LIMIT 10";
	return $this->get_list($sql);
    }
}
