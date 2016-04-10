<?php
require_once 'Catalog.php';
class Stock extends Catalog {
    public function branchFetch() {
	$parent_id=$this->request('id','int',0);
	return $this->treeFetch("stock_tree", $parent_id, 'top');
    }
    public function stockTreeCreate($parent_id,$label){
	$this->Base->set_level(2);
	$this->check($parent_id,'int');
	$this->check($label);
	return $this->treeCreate('stock_tree', 'folder', $parent_id, $label, 'calc_top_id');
    }
    public function stockTreeUpdate($branch_id,$field,$value) {
	$this->Base->set_level(2);
	$this->check($branch_id,'int');
	$this->check($field);
	$this->check($value);
	return $this->treeUpdate('stock_tree', $branch_id, $field, $value, 'calc_top_id');
    }
    public function stockTreeDelete( $branch_id ){
	$this->Base->set_level(4);
	$this->check($branch_id,'int');
	$sub_ids=$this->treeGetSub('stock_tree', $branch_id);
	$in=implode(',', $sub_ids);
	$this->query("DELETE FROM stock_tree WHERE branch_id IN ($in)");
	$deleted=$this->db->affected_rows();
	return $deleted;
    }
    public function listFetch( $page=1, $rows=30, $parent_id=0, $having=null ){
	$this->check($page,'int');
	$this->check($rows,'int');
	$this->check($parent_id,'int');
	$offset=($page-1)*$rows;
	if( $offset<0 ){
	    $offset=0;
	}
	if( !$having ){
	    $having=$this->decodeFilterRules();
	}
	$where='';
	if( $parent_id ){
	    $branch_ids=$this->treeGetSub('stock_tree',$parent_id);
	    $where="WHERE se.parent_id IN (".implode(',',$branch_ids).")";
	}
	$sql="SELECT
		st.label parent_label,
		ROUND( SUM(IF(TO_DAYS(NOW()) - TO_DAYS(dl.cstamp) <= 90,de.product_quantity,0))/3 ) m3,
		SUM(IF(TO_DAYS(NOW()) - TO_DAYS(dl.cstamp) <= 30,de.product_quantity,0)) m1,
		pl.*,
		pp.sell,
		pp.buy,
		pp.curr_code,
		se.stock_entry_id,
		se.parent_id,
		se.party_label,
		se.product_quantity,
		se.product_wrn_quantity,
		se.self_price
	    FROM
		stock_entries se
		    JOIN
		prod_list pl USING(product_code)
		    LEFT JOIN
		price_list pp USING(product_code)
		    LEFT JOIN
		stock_tree st ON se.parent_id=branch_id
		    LEFT JOIN
		document_entries de USING(product_code)
		    LEFT JOIN
		document_list dl ON de.doc_id=dl.doc_id AND dl.is_commited=1 AND dl.doc_type=1 AND notcount=0
	    $where
	    GROUP BY se.product_code
	    HAVING $having
            ORDER BY se.parent_id,se.product_code
	    LIMIT $rows OFFSET $offset";
	$result_rows=$this->get_list($sql);
	$total_estimate=$offset+(count($result_rows)==$rows?$rows+1:count($result_rows));
	return array('rows'=>$result_rows,'total'=>$total_estimate);
    }
    public function labelFetch(){
	$q=$this->request('q','string',0);
	return $this->get_list("SELECT branch_id,label FROM stock_tree WHERE label LIKE '%$q%'");
    }
    
    public function productGet(){
	$product_code=$this->request('product_code');
	$sql="SELECT
		    *
		FROM
		    stock_entries se
			JOIN
		    prod_list USING(product_code)
			LEFT JOIN
		    price_list USING(product_code)
		WHERE 
		    product_code='{$product_code}'";
	return $this->get_row($sql);
    }

    public function productSave(){
	$this->Base->set_level(2);
	$affected_rows=0;
	$product_code=$this->request('product_code');
        $product_code_new=$this->request('product_code_new','^[\p{L}\d\. ,-_]+$');
        if( !$product_code_new ){// do not match the pattern
            return false;
        }
	if( $product_code && $product_code<>$product_code_new ){
	    $this->update('prod_list', ['product_code'=>$product_code_new], ['product_code'=>$product_code]);
	    $affected_rows+=$this->db->affected_rows()*1;
	}
	$product_code=$product_code_new;
	$prod_list=[
	    'product_code'=>$product_code,
	    'ru'=>$this->request('ru'),
	    'ua'=>$this->request('ua'),
	    'en'=>$this->request('en'),
	    'product_unit'=>$this->request('product_unit'),
	    'product_spack'=>$this->request('product_spack','int'),
	    'product_bpack'=>$this->request('product_bpack','int'),
	    'product_weight'=>$this->request('product_weight','double'),
	    'product_volume'=>$this->request('product_volume','double'),
	    'product_uktzet'=>$this->request('product_uktzet'),
	    'analyse_type'=>$this->request('analyse_type'),
	    'analyse_group'=>$this->request('analyse_group'),
	    'analyse_class'=>$this->request('analyse_class'),
	    'analyse_section'=>$this->request('analyse_section'),            
	    'barcode'=>$this->request('barcode')
	];
	$prod_list_set=$this->makeSet($prod_list);
	$this->query("INSERT INTO prod_list SET $prod_list_set ON DUPLICATE KEY UPDATE $prod_list_set");
	$affected_rows+=$this->db->affected_rows()*1;
	
	$stock_entries=[
	    'product_code'=>$product_code,
	    'parent_id'=>$this->request('parent_id','int'),
	    'product_wrn_quantity'=>$this->request('product_wrn_quantity','int'),
	    'party_label'=>$this->request('party_label')
	];
	$stock_entries_set=$this->makeSet($stock_entries);
	$this->query("INSERT INTO stock_entries SET $stock_entries_set ON DUPLICATE KEY UPDATE $stock_entries_set");
	$affected_rows+=$this->db->affected_rows()*1;
	
	$price_list=[
	    'product_code'=>$product_code,
	    'buy'=>$this->request('buy','double'),
	    'sell'=>$this->request('sell','double'),
	    'curr_code'=>$this->request('curr_code'),
	];
	$price_list_set=$this->makeSet($price_list);
	$this->query("INSERT INTO price_list SET $price_list_set ON DUPLICATE KEY UPDATE $price_list_set");
	$affected_rows+=$this->db->affected_rows()*1;
	
	return $affected_rows;
    }
    private function makeSet( $array ){
	$set=[];
	foreach( $array as $key=>$val ){
	    $set[]="$key='$val'";
	}
	return implode(',',$set);
    }
    public function import(){
	$label=$this->request('label');
	$source = array_map('addslashes',$this->request('source','raw'));
	$target = array_map('addslashes',$this->request('target','raw'));
        $parent_id=$this->request('parent_id','int');
        if( $parent_id ){
            $source[]=$parent_id;
            $target[]='parent_id';
        }
	$this->importInTable('prod_list', $source, $target, '/product_code/ru/ua/en/product_spack/product_bpack/product_weight/product_volume/product_unit/product_uktzet/barcode/analyse_type/analyse_group/analyse_class/analyse_section/', $label);
	$this->importInTable('price_list', $source, $target, '/product_code/sell/buy/curr_code/', $label);
	$this->importInTable('stock_entries', $source, $target, '/product_code/party_label/parent_id/', $label);
	$this->query("DELETE FROM imported_data WHERE label LIKE '%$label%' AND {$source[0]} IN (SELECT product_code FROM stock_entries)");
        return  $this->db->affected_rows();
    }
    private function importInTable( $table, $src, $trg, $filter, $label ){
	$set=[];
	$target=[];
	$source=[];
	for( $i=0;$i<count($trg);$i++ ){
            if( strpos($filter,"/{$trg[$i]}/")!==false && !empty($src[$i]) ){
		$target[]=$trg[$i];
		$source[]=$src[$i];
                if( $trg[$i]!='parent_id' ){/*set parent_id only for new added rows*/
                    $set[]="{$trg[$i]}=$src[$i]";
                }
	    }
	}
	$target_list=  implode(',', $target);
	$source_list=  implode(',', $source);
	$set_list=  implode(',', $set);
	$this->query("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label LIKE '%$label%' ON DUPLICATE KEY UPDATE $set_list");
	//print("INSERT INTO $table ($target_list) SELECT $source_list FROM imported_data WHERE label='$label' ON DUPLICATE KEY UPDATE $set_list");
	return $this->db->affected_rows();
    }
    public function productDelete(){
	$product_codes=$this->request('product_code','raw');
        $product_codes_in= "'".implode("','", array_map('addslashes',$product_codes))."'";
        $this->query("DELETE FROM stock_entries WHERE product_quantity=0 AND product_code IN ($product_codes_in)");
        return $this->db->affected_rows();
    }
    public function productMove(){
        $parent_id=$this->request('parent_id','int');
	$product_codes=$this->request('product_code','raw');
        $product_codes_in= "'".implode("','", array_map('addslashes',$product_codes))."'";
        $this->query("UPDATE stock_entries SET parent_id='$parent_id' WHERE product_code IN ($product_codes_in)");
        return $this->db->affected_rows();
    }
    public function movementsFetch( $page=1, $rows=30, $having=null ){
	$this->check($page,'int');
	$this->check($rows,'int');
	$offset=($page-1)*$rows;
	if( $offset<0 ){
	    $offset=0;
	}
	if( !$having ){
	    $having=$this->decodeFilterRules();
	}
        $sql="SELECT
                DATE_FORMAT(dl.cstamp,'%d.%m.%Y') oper_date,
                CONCAT(dt.doc_type_name,IF(dl.is_reclamation,' (Возврат)',''),' #',dl.doc_num) doc,
		(SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=passive_company_id) plabel,
		(SELECT label FROM companies_tree JOIN companies_list USING(branch_id) WHERE company_id=active_company_id) alabel,
                product_code,
                ru,
                IF(doc_type=1,product_quantity,'') sell,
                IF(doc_type=2,product_quantity,'') buy
            FROM
                document_entries de
                    JOIN
                document_list dl USING(doc_id)
                    JOIN
                document_types dt USING(doc_type)
                    JOIN
                prod_list USING(product_code)
            WHERE
                is_commited AND NOT notcount
            HAVING $having
            ORDER BY dl.cstamp DESC
            LIMIT $rows OFFSET $offset";
	$result_rows=$this->get_list($sql);
	$this->distinctMovementsRows($result_rows);
	$total_estimate=$offset+(count($result_rows)==$rows?$rows+1:count($result_rows));
	return array('rows'=>$result_rows,'total'=>$total_estimate);
    }
    private function distinctMovementsRows( &$result_rows ){
	$prev_concat='';
	foreach( $result_rows as $row ){
	    $concat=$row->alabel.$row->oper_date.$row->doc.$row->plabel;
	    if( $prev_concat==$concat ){
		$row->oper_date='';
		$row->doc='';
		$row->alabel='';
		$row->plabel='';
	    }
	    $prev_concat=$concat;
	}
    }
}
