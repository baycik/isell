<?php
/* Group Name: Склад
 * User Level: 3
 * Plugin Name: Эффективность запасов
 * Plugin URI: 
 * Version: 1.0
 * Description: Выводит информацию о соотношении продаж к складским остаткам
 * Author: baycik 2018
 * Author URI: 
 * Trigger before: Reports_summary_sell_profit
 */
class Reports_summary_sell_stock extends Catalog
{
    private $idate;
    private $fdate;
    private $all_active;
    private $count_reclamations;
    private $include_vat;
    private $in_alt_currency;
    private $show_entries;
    private $group_by_filter;
    private $group_by;
    private $group_by2;
    private $group_by2_comma;
    private $group_by2_slash;
    
    public function __construct()
    {
        $this->idate = $this->request('idate') . ' 00:00:00';
        $this->fdate = $this->request('fdate') . ' 23:59:59';
        $this->all_active = $this->request('all_active', 'bool');
        $this->count_reclamations = $this->request('count_reclamations', 'bool', 0);
        $this->include_vat = $this->request('include_vat', 'bool', 0);
        $this->in_alt_currency = $this->request('in_alt_currency', 'bool', 0);
        $this->show_entries = $this->request('show_entries', 'bool', 0);
        $this->group_by_filter = $this->request('group_by_filter');
        $this->group_by = $this->request('group_by', '\w+');
        if (!in_array($this->group_by, ['parent_id', 'product_code', 'analyse_type', 'analyse_brand', 'analyse_class', 'product_article'])) {
            $this->group_by = 'parent_id';
        }

        $this->group_by2 = $this->request('group_by2', '\w+');
        $this->group_by2_comma = $this->group_by2 ? ',' . $this->group_by2 : '';
        $this->group_by2_slash = $this->group_by2 ? ",'/',$this->group_by2" : '';
        parent::__construct();
    }
    public function check(&$var, $type = null)
    {
        $type = str_replace('?', '', $type);
        switch ($type) {
            case 'raw':
                break;
            case 'int':
                $var = (int) $var;
                break;
            case 'float':
            case 'double':
                $var = (float) $var;
                break;
            case 'bool':
                $var = $var ? 1 : 0;
                break;
            case 'escape':
            case 'string':
                $var =  addslashes($var);
                break;
            case 'json':
            case 'array':
            case '?array':
                if (is_array($var)) {
                    break; //native post array
                }
                $var = trim($var, "\"");
                $result = json_decode($var, true);
                if (json_last_error() != JSON_ERROR_NONE) {
                    $var = stripslashes($var);
                    $result = json_decode($var, true);
                }
                if (json_last_error() != JSON_ERROR_NONE) {
                    throw new Exception('JSON error: ' . json_last_error_msg(), 500);
                }
                $var = $result;
                break;
            case 'object':
            case '?object':
                $var = trim($var, "\"");
                $result = json_decode($var, false);
                if (json_last_error() != JSON_ERROR_NONE) {
                    $var = stripslashes($var);
                    $result = json_decode($var, false);
                }
                if (json_last_error() != JSON_ERROR_NONE) {
                    throw new Exception('JSON error: ' . json_last_error_msg(), 500);
                }
                $var = $result;
                break;
            default:
                if ($type) {
                    $matches = [];
                    preg_match('/' . $type . '/u', $var, $matches);
                    $var =  isset($matches[0]) ? $matches[0] : null;
                } else {
                    $var =  addslashes($var);
                }
        }
        return $var;
    }
    public function request($name, $type = null, $default = null)
    {
        $value = $this->input->get_post($name);
        if (!is_array($value) && strlen($value) == 0) {
            return $default;
        }
        return $this->check($value, $type);
    }
    private function dmy2iso($dmy)
    {
        $chunks =  explode('.', $dmy);
        return "$chunks[2]-$chunks[1]-$chunks[0]";
    }
    public function viewGet()
    {
        $active_filter = $this->all_active ? '' : ' AND active_company_id=' . $this->Hub->acomp('company_id');
        $reclamation_filter = $this->count_reclamations ? '' : ' AND is_reclamation=0';
        $having = $this->group_by_filter ? "HAVING group_by LIKE '%" . str_replace(",", "%' OR group_by LIKE '%", $this->group_by_filter) . "%'" : "";

        $sql_tmp_drop1 = "DROP TEMPORARY TABLE IF EXISTS tmp_summary_sell_buy;";
        $sql_tmp_create1 = "CREATE TEMPORARY TABLE tmp_summary_sell_buy (PRIMARY KEY (product_code)) AS(
            SELECT
                product_code,
                SUM( IF(doc_type=2,product_quantity,-product_quantity) ) stock_qty,

                SUM( IF(doc_type=2 AND cstamp<'$this->fdate', self_price *IF($this->include_vat,dl.vat_rate/100+1,1) *IF($this->in_alt_currency AND doc_ratio,1/doc_ratio,1) *product_quantity,0) ) buy_prod_sum,
                SUM( IF(doc_type=2 AND cstamp<'$this->fdate', product_quantity,0) ) buy_qty,

                SUM( IF(doc_type=1 AND cstamp>'$this->idate',invoice_price*IF($this->include_vat,dl.vat_rate/100+1,1)/IF($this->in_alt_currency,doc_ratio,1)*product_quantity,0) ) sell_prod_sum,
                SUM( IF(doc_type=1 AND cstamp>'$this->idate',product_quantity,0) ) sell_qty
            FROM
                document_entries de
                    JOIN
                document_list dl USING(doc_id)
            WHERE
                (doc_type=1 OR doc_type=2) AND cstamp<'$this->fdate' AND is_commited=1 AND notcount=0 $active_filter $reclamation_filter
            GROUP BY product_code)";
        $this->query($sql_tmp_drop1);
        $this->query($sql_tmp_create1);

        $sql_tmp_drop2 = "DROP TEMPORARY TABLE IF EXISTS tmp_summary_sell_stock;";
        $sql_tmp_create2 = "CREATE TEMPORARY TABLE tmp_summary_sell_stock AS(
            SELECT 
                product_code _product_code,
                ru,
                sell_prod_sum,
                IF(buy_qty>0,(buy_prod_sum/buy_qty)*stock_qty,0) stock_entry_sum,
                stock_qty,
                sell_qty,
                CONCAT(IF('$this->group_by'='parent_id',(SELECT `path` FROM stock_tree WHERE branch_id=se.parent_id),$this->group_by) $this->group_by2_slash) group_by,
                $this->group_by $this->group_by2_comma
            FROM
                stock_entries se
                    JOIN
                prod_list pl USING(product_code)
                    LEFT JOIN
                tmp_summary_sell_buy USING(product_code)
            $having
            )";
        $sql_summary = "SELECT 
                group_by,
                SUM(sell_prod_sum) sell_sum,
                SUM(stock_entry_sum) stock_sum,
                SUM(stock_qty) stock_sum_qty,
                SUM(sell_qty) sell_sum_qty
            FROM
                tmp_summary_sell_stock
            GROUP BY
		        $this->group_by $this->group_by2_comma
            HAVING
                sell_sum OR stock_sum
            ORDER BY
                sell_sum DESC,stock_sum DESC
            ";
        //die($sql);

        $this->query($sql_tmp_drop2);
        $this->query($sql_tmp_create2);

		$earlier = new DateTime($this->idate);
		$later = new DateTime($this->fdate);
		$day_span = $later->diff($earlier)->format("%a"); //3

        $summary_rows = $this->get_list($sql_summary);
        $total_sell = 0;
        $total_sell_qty = 0;
        $total_stock = 0;
        $total_stock_qty = 0;
        foreach ($summary_rows as $row) {
            $total_sell += $row->sell_sum;
            $total_sell_qty += $row->sell_sum_qty;
            $total_stock += $row->stock_sum;
            $total_stock_qty += $row->stock_sum_qty;
        }
		
		$total_stock_sellout = $total_sell_qty>0 ? round( $day_span * $total_stock_qty / $total_sell_qty, 0) : '';
        foreach ($summary_rows as $row) {
            $row->sell_proc =    $total_sell ? round($row->sell_sum / $total_sell, 4) : '';
            $row->stock_proc =   $total_stock ? round($row->stock_sum / $total_stock, 4) : '';
            $row->sellout_days =   $row->sell_sum_qty>0 ? round( $day_span * $row->stock_sum_qty / $row->sell_sum_qty, 0) : '';
            $this->clear_zero($row);
        }

        if ($this->show_entries) {
            $rows = $this->get_list("SELECT * FROM tmp_summary_sell_stock WHERE stock_entry_sum OR sell_prod_sum ORDER BY group_by,_product_code");
            foreach ($rows as $row) {
                $this->clear_zero($row);
            }
        }
        $view = [
            'total_sell' => round($total_sell, 2),
            'total_stock' => round($total_stock, 2),
            'total_sell_qty' => round($total_sell_qty, 2),
            'total_stock_qty' => round($total_stock_qty, 2),
            'total_stock_sellout' => $total_stock_sellout,
            'summary_rows' => count($summary_rows) ? $summary_rows : [[]],
            'rows' => isset($rows) ? $rows : [[]],
            'input' => [
                'idate' => $this->iso2dmy($this->idate),
                'fdate' => $this->iso2dmy($this->fdate),
                'all_active' => $this->all_active,
                'count_reclamations' => $this->count_reclamations,
                'count_sells' => $this->count_sells,
                'in_alt_currency' => $this->in_alt_currency,
                'include_vat' => $this->include_vat,
                'group_by_client' => $this->group_by_client,
                'language' => $this->language,
                'group_by_filter' => $this->group_by_filter,
                'group_by' => $this->group_by
            ]
        ];
        return $view;
    }
    private function clear_zero(&$row)
    {
        foreach ($row as &$field) {
            if (is_numeric($field) && $field == 0) {
                $field = '';
            }
        }
    }
    private function iso2dmy($iso)
    {
        $chunks =  explode(' ', $iso);
        $chunks =  explode('-', $chunks[0]);
        return "$chunks[2].$chunks[1].$chunks[0]";
    }
}