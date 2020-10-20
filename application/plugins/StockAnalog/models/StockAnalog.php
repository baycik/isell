<?php
/* Group Name: Склад
 * User Level: 2
 * Plugin Name: Аналоги
 * Plugin URI: 
 * Version: 1.0
 * Description: Linking products as analogs
 * Author: baycik 2020
 * Author URI: 
 */

class StockAnalog extends Catalog{
    
    public function install(){
	$install_file=__DIR__."/../install/install.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($install_file);
    }
    
    public function uninstall(){
	$uninstall_file=__DIR__."/../install/uninstall.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($uninstall_file);
    }
    
    
    public function listFetch( int $offset, int $limit, string $sortby=null, string $sortdir=null, array $filter = null){
        $this->Hub->set_level(3);
        if ( empty($sortby) ) {
	    $sortby = "product_code";
	    $sortdir = "ASC";
	}
        $having = '';
        if( $filter ){
           $having = "HAVING ".$this->makeFilter($filter); 
        };
	$sql ="
            SELECT 
                SUBSTRING(MD5(analog_group_id),1,6) analog_group_tag,
                pl.product_id,
                pl.product_code,
                ru product_name
            FROM 
                stock_entries se
                        JOIN
                prod_list pl USING(product_code)
                        LEFT JOIN
                plugin_analog_list USING(product_id)
            $having
            ORDER BY analog_group_id DESC,$sortby $sortdir
            LIMIT $limit OFFSET $offset
            ";
	return $this->get_list($sql);
    }
    
    public function link( int $product_id, string $product_code ){
        $product_to_link_sql="
            SELECT 
                product_id
            FROM 
                prod_list 
            WHERE 
                product_code='$product_code'";
        $product_id2=$this->get_value($product_to_link_sql);
        if( !$product_id2 ){
            return 'product_not_found';
        }
        if( $product_id==$product_id2 ){
            return 'product_duplicate';
        }
        $sql_get_group_id="
            SELECT 
                analog_group_id
            FROM 
                plugin_analog_list
            WHERE
                product_id=$product_id
                ";
        $analog_group_id=$this->get_value($sql_get_group_id);
        if( !$analog_group_id ){
            $analog_group_id=$this->get_value("SELECT COALESCE(MAX(analog_group_id),0)+1 FROM plugin_analog_list");
            $this->query("INSERT INTO plugin_analog_list SET product_id=$product_id,analog_group_id=$analog_group_id");
        }
        $this->unlink( $product_id2 );
        $this->query("INSERT INTO plugin_analog_list SET product_id=$product_id2,analog_group_id={$analog_group_id}");
        return $analog_group_id;
    }
    
    public function unlink( int $product_id ){
        $group_sql="
            SELECT 
                analog_group_id,
                COUNT(*) cnt
            FROM 
                plugin_analog_list pal1
                    JOIN
                plugin_analog_list pal2 USING(analog_group_id)
            WHERE 
                pal1.product_id='$product_id'
            ";
        $group=$this->get_row($group_sql);
        if( $group->cnt<3 ){
            return $this->delete('plugin_analog_list',['analog_group_id'=>$group->analog_group_id]);
        }
        return $this->delete('plugin_analog_list',['product_id'=>$product_id]);
    }
    
    public function analogListGet( int $doc_entry_id ){
        $sql="
            SELECT
                pl.product_id,
                se.product_img,
                pl.product_code,
                ru product_name,
                se.product_quantity,
                pl.product_unit,
                GET_SELL_PRICE(product_code,passive_company_id,doc_ratio) product_price
            FROM
                plugin_analog_list pal1
                    JOIN
                plugin_analog_list pal2 USING(analog_group_id)
                    JOIN
                prod_list pl ON pal2.product_id=pl.product_id
                    JOIN
                stock_entries se USING(product_code)
                    JOIN
                (SELECT 
                    product_id,doc_ratio,passive_company_id,product_quantity
                FROM
                    document_entries
                        JOIN
                    document_list USING(doc_id)
                        JOIN
                    prod_list USING(product_code)
                WHERE
                    doc_entry_id='$doc_entry_id') de ON de.product_id=pal1.product_id
            WHERE
                pal1.product_id<>pl.product_id
                AND se.product_quantity>=de.product_quantity
            ";
        return $this->get_list($sql);
    }
    
    public function analogEntrySwap( int $doc_entry_id, int $product_id ){
        $product_code=$this->get_value("SELECT product_code FROM prod_list WHERE product_id='$product_id'");
        $CurrentEntry=$this->get_row("SELECT * FROM document_entries JOIN document_list USING(doc_id) WHERE doc_entry_id='$doc_entry_id'");
        
        $DocumentItems=$this->Hub->load_model("DocumentItems");
        $DocumentItems->selectDoc($CurrentEntry->doc_id);
        $DocumentItems->entryAdd($CurrentEntry->doc_id, $product_code, $CurrentEntry->product_quantity);
        $DocumentItems->entryDelete( $CurrentEntry->doc_id, $CurrentEntry->doc_entry_id );
        return true;
    }
}