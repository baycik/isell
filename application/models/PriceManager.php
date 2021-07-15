<?php
class PriceManager extends Catalog{
    public $min_level=3;
    public function breakevenListFetch( int $offset, int $limit, string $sortby='', string $sortdir='', array $filter=[] ){
        if (empty($sortby)) {
            $sortby = "st.label, ct.label";
            $sortdir = "ASC";
        }
        $having = $this->makeFilter($filter);
        $sql="
            SELECT
                pb.*,
                st.branch_id,
                st.label category_label,
                cl.company_id,
                ct.label pcomp_label
            FROM
                stock_tree st
                    LEFT JOIN
                price_breakeven pb USING(branch_id)
                    LEFT JOIN
                companies_list cl USING(company_id)
                    LEFT JOIN
                companies_tree ct ON cl.branch_id=ct.branch_id
            WHERE
                st.parent_id=0
            HAVING
                $having
            ORDER BY 
                $sortby $sortdir
            LIMIT $limit OFFSET $offset";
        return $this->get_list($sql);
    }
    
    public function breakevenCreate( int $branch_id, int $company_id=0 ){
        $this->create('price_breakeven', ['branch_id'=>$branch_id,'company_id'=>0]);
        return $this->create('price_breakeven', ['branch_id'=>$branch_id,'company_id'=>$company_id]);
    }
    public function breakevenUpdate( int $breakeven_rule_id, string $field, string $value ){
        return $this->update('price_breakeven',[$field=>$value],['breakeven_rule_id'=>$breakeven_rule_id]);
    }
    public function breakevenDelete( int $breakeven_rule_id ){
        return $this->delete('price_breakeven',['breakeven_rule_id'=>$breakeven_rule_id]);
    }
    
    
    public function breakevenResultListFetch( int $branch_id=0, int $company_id=0, int $offset, int $limit, string $sortby='', string $sortdi='', array $filter=[] ){
        if (empty($sortby)) {
            $sortby = "pl.product_code";
            $sortdir = "ASC";
        }
        $usd_ratio=$this->Hub->pref('usd_ratio');
        $having = $this->makeFilter($filter);
        
        $price_label=$this->get_value("SELECT price_label FROM companies_list WHERE company_id='$company_id'");
        
        $sql="
            SELECT
                product_code,
                ru product_name,
                ROUND(LEFTOVER_CALC(product_code,NOW(),product_quantity,'selfprice'),2) self_price,
                ROUND(buy,2) buy,
                ROUND(GET_BREAKEVEN_PRICE(product_code,$company_id,$usd_ratio,LEFTOVER_CALC(product_code,NOW(),product_quantity,'selfprice')),2) breakeven_price
            FROM
                stock_entries se
                    JOIN
                stock_tree st ON se.parent_id=st.branch_id
                    JOIN
                prod_list pl USING(product_code)
                    JOIN
                price_list prl USING(product_code)
            WHERE
                st.top_id=$branch_id
                AND prl.label='$price_label'
            HAVING
                $having
            ORDER BY 
                $sortby $sortdir
            LIMIT $limit OFFSET $offset";
        return $this->get_list($sql);
    }

}