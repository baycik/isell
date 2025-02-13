<?php
/* Group Name: Склад
 * User Level: 2
 * Plugin Name: Распродажа
 * Plugin URI: 
 * Version: 0.1
 * Description: Устанавливает распродажную цену на редко продающтеся позиции
 * Author: baycik 2021
 * Author URI: 
 */
class StockSaleCalculator extends PluginManager {
    
    public function promoSet( int $branch_id, int $day_passed=90, float $deviation=5 ){
        $full_sale_day_passed=$day_passed*2;
        $full_sale_discount=0.7;  
        $usd_ratio=$this->Hub->pref('usd_ratio');
        $clear_old_promo_sql="
            DELETE FROM
                price_list
            WHERE
                label='PROMO'
                AND product_code IN 
                (SELECT 
                    product_code 
                FROM
                        stock_entries se
                    JOIN
                        stock_tree st ON st.branch_id=se.parent_id
                WHERE
                    path_id LIKE '/$branch_id/%');";
        $this->query($clear_old_promo_sql);
        $create_new_promo_sql="
            INSERT price_list (product_code,label,sell,buy,curr_code)
            SELECT 
                se.product_code,
                'PROMO',
                ROUND(IF(self_price>0,self_price,IF(prl.curr_code='USD',$usd_ratio,1)*buy) * (1 + RAND() / 100 * $deviation),2) * (IF(DATEDIFF(NOW(), fetch_stamp) > $full_sale_day_passed,$full_sale_discount,1)) new_promo_price,
                0,
                ''
            FROM
                stock_entries se
                    JOIN
                stock_tree st ON st.branch_id = se.parent_id
                    JOIN
                prod_list pl USING (product_code)
                    JOIN
                price_list prl ON prl.product_code=pl.product_code AND prl.label=''
            WHERE
                path_id LIKE '/$branch_id/%'
                AND fetch_stamp IS NOT NULL
                AND ( DATEDIFF(NOW(), fetch_stamp) > $day_passed OR analyse_class='D' )
                AND product_quantity>0
                AND (self_price>0 OR buy>0)
            HAVING
                new_promo_price>0;
            ";
        $this->query($create_new_promo_sql);
        return $this->db->affected_rows();
    }
}
