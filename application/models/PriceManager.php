<?php
class PriceManager extends Catalog{
    public function breakevenGetList(){
        $sql="
            SELECT
                pb.*,
                st.label category_name,
                ct.label company_label
            FROM
                price_breakeven pb
                    LEFT JOIN
                stock_tree st USING(branch_id)
                    LEFT JOIN
                companies_list cl USING(company_id)
                    LEFT JOIN
                companies_tree ct ON cl.branch_id=ct.branch_id
            ORDER BY 
                branch_id, company_id";
        
        return $this->get_list($sql);
    }
}