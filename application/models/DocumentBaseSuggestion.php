<?php
trait DocumentBaseSuggestion{
    
    public function suggestFetch( string $q='', int $offset=0,int $limit=10, int $doc_id=0, int $category_id=0 ){
        session_write_close();
        $matches=$this->suggestResultFetch($q, $offset, $limit, $doc_id, $category_id);
        if( !$matches ){
            $matches=$this->suggestResultFetch($this->transliterate($q,'fromlatin'), $offset, $limit, $doc_id, $category_id);
        }
        if( !$matches ){
            $matches=$this->suggestResultFetch($this->transliterate($q,'fromcyrilic'), $offset, $limit, $doc_id, $category_id);
        }
        return $matches;
    }
    
    private function suggestResultFetch( string $q, int $offset=0,int $limit=10, int $doc_id=0, int $category_id=0 ){
        $pcomp_id=$this->Hub->pcomp('company_id');
        $usd_ratio=$this->Hub->pref('usd_ratio');
	if( $doc_id ){
	    $this->documentSelect($doc_id);
	    $pcomp_id=$this->doc('passive_company_id');
	    $usd_ratio=$this->doc('doc_ratio');
	}
	$where="1";
	if( strlen($q)==13 && is_numeric($q) ){
	    $where="product_barcode=$q";
	} else if( $q ){
	    $cases=[];
	    $clues=  explode(' ', $q);
	    foreach ($clues as $clue) {
		if ($clue == ''){
		    continue;
		}
		$cases[]="(pl.product_code LIKE '%$clue%' OR ru LIKE '%$clue%')";
	    }
	    if( count($cases)>0 ){
		$where=implode(' AND ',$cases);
	    }
	}
        if( $category_id ){
            $branch_ids = $this->treeGetSub('stock_tree', $category_id);
            $where .= " AND parent_id IN (" . implode(',', $branch_ids) . ")";
        }
//        if( $this->doc('doc_type')==3 || $this->doc('doc_type')==4 ){
//            $where .= " AND is_service=1";
//        }
        $this->query("SET @promo_limit:=3;");
	$sql="
            SELECT
                *,
		ROUND(GET_SELL_PRICE(product_code,'$pcomp_id','$usd_ratio'),2) product_price_total,
                ROUND(GET_PRICE(product_code,'$pcomp_id','$usd_ratio'),2) product_price_total_raw
            FROM (
                SELECT
                    product_id,
                    pl.product_code,
                    pl.analyse_class,
                    ru product_name,
                    product_spack,
                    product_quantity leftover,
                    product_img,
                    product_unit,
                    CONCAT( 
                        product_quantity<>0,
                        IF( prl.product_code IS NOT NULL AND (@promo_limit:=@promo_limit-1)>=0,1,0),
                        LPAD(fetch_count-DATEDIFF(NOW(),COALESCE(se.fetch_stamp,se.modified_at)),6,'0')
                    ) popularity
                FROM
                    stock_entries se
                        JOIN
                    prod_list pl USING(product_code)
                        LEFT JOIN
                    price_list prl ON se.product_code=prl.product_code AND label='PROMO'
                WHERE $where
                ORDER BY 
                    popularity DESC,
                    pl.product_code
                LIMIT $limit OFFSET $offset) inner_table";
        $suggested=$this->get_list($sql);//for plugin modifications
        return $suggested;
    }

    public function pickerListFetch( int $parent_id=0, int $offset=0, int $limit=10, string $sortby=null, string $sortdir=null, array $filter=[]) {
        $pcomp_id = $this->Hub->pcomp('company_id');
        $doc_ratio = $this->Hub->pref('usd_ratio');

        $having = $this->makeFilter($filter);
        $order = '';
        $where = '';
        if ($parent_id) {
            $branch_ids = $this->treeGetSub('stock_tree', $parent_id);
            $where = "WHERE se.parent_id IN (" . implode(',', $branch_ids) . ")";
        }
        if ($sortby) {
            $order = "ORDER BY $sortby $sortdir";
        }
        $sql = "SELECT 
		pl.product_code,
		ru,
		product_quantity,
		ROUND(GET_PRICE(product_code,'$pcomp_id','$doc_ratio'),2) price,
		product_spack
	    FROM 
		stock_entries se
		    JOIN
		prod_list pl USING(product_code)
	    $where 
	    HAVING $having 
	    $order
	    LIMIT $limit OFFSET $offset";
        return $this->get_list($sql);
    }
}