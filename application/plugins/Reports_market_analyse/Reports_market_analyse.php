<?php
class Reports_market_analyse extends Catalog{
    public function __construct() {
	$this->fdate=$this->dmy2iso( $this->request('fdate','\d\d.\d\d.\d\d\d\d') ).' 23:59:59';
        $this->group_by_filter=$this->request('group_by_filter');
	$this->group_by=$this->request('group_by','\w+');
	parent::__construct();
    }
    private function dmy2iso( $dmy ){
	$chunks=  explode('.', $dmy);
	return "$chunks[2]-$chunks[1]-$chunks[0]";
    }

    public function viewGet(){
        $having=$this->group_by_filter?"HAVING group_by LIKE '%$this->group_by_filter%'":"";
        
        $pcomp_id=$this->Hub->pcomp('company_id');
        $sql_clear="DROP  TABLE IF EXISTS tmp_market_report";#TEMPORARY
        $sql_prepare="CREATE  TABLE tmp_market_report ( INDEX(product_code) ) ENGINE=MyISAM AS (
            SELECT
                B article,
                product_code,
                ru product_name,
                analyse_type,
                analyse_group,
                C store_code,
                D sold,
                E leftover
            FROM
                imported_data
                    LEFT JOIN
                prod_list pl ON analyse_section=B
            WHERE
                B<>'' 
                AND label='маркет'
            $having)";
        
        $sql_price_setup="SET @_product_code:='',@_acomp_id:=2,@_pcomp_id:=222,@_to_cstamp:='{$this->fdate}';";
        $sql_price_clear="DROP  TABLE IF EXISTS tmp_market_report_price";
        $sql_price_prepare="CREATE  TABLE tmp_market_report_price ( INDEX(product_code) ) ENGINE=MyISAM AS (
            SELECT product_code,ROUND(SUM(qty*invoice_price)/SUM(qty),2) avg_price FROM(
            SELECT 
                product_code,
                invoice_price,
                @_quantity:=IF(product_code<>@_product_code AND @_product_code:=product_code,total,@_quantity)-product_quantity q,
                product_quantity+LEAST(0,@_quantity) qty
            FROM
                (SELECT 
                    product_code,
                    product_quantity,
                    invoice_price,
                    total
                FROM
                    (SELECT product_code,SUM(sold)+SUM(leftover) total FROM tmp_market_report GROUP BY product_code) tmr
                        JOIN
                    document_entries de USING(product_code)
                        JOIN
                    document_list USING (doc_id)
                WHERE
                    cstamp < @_to_cstamp
                    AND active_company_id=@_acomp_id
                    AND passive_company_id=@_pcomp_id
                ORDER BY product_code,cstamp DESC) sub
            ) sub2
            WHERE qty>0
            GROUP BY product_code)";
        $this->query($sql_clear);
        $this->query($sql_prepare);
        $this->query($sql_price_setup);
        $this->query($sql_price_clear);
        $this->query($sql_price_prepare);
        
        $sql_fetch="
            SELECT
                *,
                avg_price,
                avg_price*sold sold_sum,
                avg_price*leftover leftover_sum
            FROM
                tmp_market_report
                    JOIN
                tmp_market_report_price USING(product_code)";
        $sql_summary_type_fetch="
            SELECT
                $this->group_by group_by,
                SUM(sold) sold,
                SUM(avg_price*sold) sold_sum,
                SUM(leftover) leftover,
                SUM(avg_price*leftover) leftover_sum
            FROM
                tmp_market_report  
                JOIN
                tmp_market_report_price USING(product_code)
            GROUP BY $this->group_by";
        $this->query($sql_clear);
        $this->query($sql_prepare);
	$rows=$this->get_list($sql_fetch);
	$type_rows=$this->get_list($sql_summary_type_fetch);

	return [
	    'rows'=>count($rows)?$rows:[[]],
	    'type_rows'=>count($type_rows)?$type_rows:[[]]
	];
    }
}