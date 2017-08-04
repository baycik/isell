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
                1 product_price,
                E leftover
            FROM
                imported_data
                    LEFT JOIN
                prod_list pl ON analyse_section=B
            WHERE
                B<>'' 
                AND label='маркет'
            $having)";
        
            $sql_price_clear="DROP TEMPORARY TABLE tmp_market_report_price";
            $sql_price_prepare="CREATE TEMPORARY TABLE tmp_market_report_price ( INDEX(product_code) ) ENGINE=MyISAM AS (
                SELECT 
                    invoice_price*(dl.vat_rate/100+1)
                FROM 
                    document_entries de
                        JOIN 
                    document_list dl USING(doc_id)
                        JOIN
                    tmp_market_report USING(product_code)
                WHERE 
                    passive_company_id='$pcomp_id' 
                    AND doc_type=1 
                    AND is_commited=1
                    AND cstamp<'$this->fdate'
                    AND de.product_code=pl.product_code
                ORDER BY cstamp DESC


)";
        
        
        
        
        
        
        
        
        
        
        $sql_fetch="
            SELECT
                *,
                product_price*sold sold_sum,
                product_price*leftover leftover_sum
            FROM
                tmp_market_report";
        $sql_summary_type_fetch="
            SELECT
                $this->group_by group_by,
                SUM(sold) sold,
                SUM(product_price*sold) sold_sum,
                SUM(leftover) leftover,
                SUM(product_price*leftover) leftover_sum
            FROM
                tmp_market_report
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