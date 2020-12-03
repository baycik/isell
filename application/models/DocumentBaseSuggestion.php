<?php
trait DocumentBaseSuggestion{
    public $suggestFetch = ['q' => 'string'];

    public function suggestFetch($q) {
        if ($q == '') {
            return [];
        }
        $company_lang = $this->Hub->pcomp('language');
        if (!$company_lang) {
            $company_lang = 'ru';
        }
        $where = ['is_service=0'];
        $clues = explode(' ', $q);
        foreach ($clues as $clue) {
            if ($clue == '') {
                continue;
            }
            $where[] = "(product_code LIKE '%$clue%' OR $company_lang LIKE '%$clue%')";
        }
        $sql = "
	    SELECT
		product_code,
		$company_lang label,
		product_spack,
		product_quantity
	    FROM
		prod_list
		    JOIN
		stock_entries USING(product_code)
	    WHERE
		" . ( implode(' AND ', $where) ) . "
		    ORDER BY fetch_count-DATEDIFF(NOW(),fetch_stamp) DESC, product_code
	    LIMIT 15
	    ";
        return $this->get_list($sql);
    }

    public $pickerListFetch = ['parent_id' => 'int', 'offset' => ['int', 0], 'limit' => ['int', 10], 'sortby' => 'string', 'sortdir' => '(ASC|DESC)', 'filter' => 'json'];

    public function pickerListFetch($parent_id, $offset, $limit, $sortby, $sortdir, $filter) {
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