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
    
    public function activate(){
        $Events=$this->Hub->load_model("Events");
        $Events->Topic('beforeMatchesGet')->subscribe('StockAnalog','matchesAddAnalogs');
        $Events->Topic('beforeMatchesTmpCreated')->subscribe('StockAnalog','matchesTmpAddAnalogs');
    }
    
    public function deactivate(){
        $Events=$this->Hub->load_model("Events");
        $Events->Topic('beforeMatchesGet')->unsubscribe('StockAnalog','matchesAddAnalogs');
        $Events->Topic('beforeMatchesTmpCreated')->unsubscribe('StockAnalog','matchesTmpAddAnalogs');
    }
    
    public function listFetch( int $offset, int $limit, string $sortby=null, string $sortdir=null, array $filter = null){
        $this->Hub->set_level(3);
        $having = '';
        if( $filter ){
           $having = "HAVING ".$this->makeFilter($filter); 
        }
        $this->query("SET @minprice:=0, @currentid:=0, @difflimit:=1.15");
	$sql ="
            SELECT
                *,
                IF(@currentid<>analog_group_id,(@currentid:=analog_group_id)*0+(@minprice:=product_price)*0,product_price/@minprice>@difflimit) diff
            FROM
                (SELECT 
                    st.label,
                    analog_group_id,
                    SUBSTRING(MD5(analog_group_id),1,6) analog_group_tag,
                    pl.product_id,
                    pl.product_code,
                    ru product_name,
                    sell,
                    ROUND(sell) product_price,
                    se.product_quantity
                FROM 
                    stock_entries se
                        JOIN
                    stock_tree st ON se.parent_id=st.branch_id
                        JOIN
                    prod_list pl USING(product_code)
                        LEFT JOIN
                    price_list pp ON pp.product_code=se.product_code AND LENGTH(pp.label)<1
                        LEFT JOIN
                    plugin_analog_list USING(product_id)
                $having
                ORDER BY analog_group_id DESC,sell
                LIMIT $limit OFFSET $offset
                ) t
                
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
        return $this->link_by_id( $product_id, $product_id2 );
    }
    
    public function link_bulk_by_id( int $product_id, array $product_ids2 ){
        $analog_group_id=0;
        foreach($product_ids2 as $product_id2){
            $analog_group_id=$this->link_by_id( $product_id, $product_id2 );
        }
        return $analog_group_id;
    }
    
    private function link_by_id( int $product_id,int $product_id2 ){
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
                pl.analyse_class,
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
                    product_id,doc_ratio,passive_company_id,product_quantity,doc_type
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
                AND IF(doc_type=1,se.product_quantity>=de.product_quantity,1)
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
    
    public function import( string $label, string $product_code1, string $product_code2 ){
        $sql_list="SELECT 
                    row_id,
                    pd1.product_id pid1,
                    pd2.product_id pid2
                FROM 
                    imported_data 
                        JOIN
                    prod_list pd1 ON `$product_code1`=pd1.product_code
                        JOIN
                    prod_list pd2 ON `$product_code2`=pd2.product_code
                WHERE 
                    label LIKE '%$label%'";
        $imported_count=0;
        $import_list=$this->get_list($sql_list);
        foreach($import_list as $linked){
            $group_id=$this->link_by_id( $linked->pid1, $linked->pid2 );
            if( $group_id ){
                $this->query("DELETE FROM imported_data WHERE row_id='$linked->row_id'");
                $imported_count++;
            }
        }
        return $imported_count;
    }
    
    public function export(){
        $sql_list="
            INSERT INTO
                imported_data (A,B,label)
            SELECT
                pl1.product_code,
                pl2.product_code,
                'analog'
            FROM 
                (SELECT
                    analog_group_id,
                    product_id
                FROM
                    plugin_analog_list
                GROUP BY 
                    analog_group_id) 
                AS tmp_groups
                    JOIN
                plugin_analog_list pal ON tmp_groups.analog_group_id=pal.analog_group_id AND tmp_groups.product_id<>pal.product_id
                    JOIN
                prod_list pl1 ON tmp_groups.product_id=pl1.product_id
                    JOIN
                prod_list pl2 ON pal.product_id=pl2.product_id";
        $this->query($sql_list);
        return $this->db->affected_rows();
    }
    
    
    
    
    public function matchesAddAnalogs($query, $registerer_param=null, $previuos_return=null ){
//        if( $previuos_return ){
//            $query=$previuos_return;
//        }
//        $this->Hub->set_level(1);
//        $query['table'].="
//            LEFT JOIN
//                plugin_analog_list pal USING(product_id)";
//        $query['select'].=",IF(pal.analog_group_id,(SELECT GREATEST(COUNT(*)-1,0) FROM plugin_analog_list WHERE analog_group_id=pal.analog_group_id),0) analog_count";
        return $query;
    }
    
    public function matchesTmpAddAnalogs($query, $registerer_param=null, $previuos_return=null ){
        if( $previuos_return ){
            $query=$previuos_return;
        }
        $this->Hub->set_level(1);
        if( !preg_match('/%[\S]{3,}%/',$query['inner']['where']) ){
            return $query;
        }
        //expanding query only if clue is longer than 3 symbols
        $expand_sql="
            SELECT
                GROUP_CONCAT(DISTINCT IF(pal2.product_id,pal2.product_id,pl.product_id)) product_id_list
            FROM
                prod_list pl
            LEFT JOIN
                plugin_analog_list pal1 ON pl.product_id=pal1.product_id
            LEFT JOIN
                plugin_analog_list pal2 ON pal1.analog_group_id=pal2.analog_group_id
            {$query['inner']['where']}
            ";
        $product_id_list=$this->get_value($expand_sql);
        $query['inner']['where']="WHERE pl.product_id IN ($product_id_list)";
        return $query;
    }
}