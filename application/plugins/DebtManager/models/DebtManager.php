<?php

/* Group Name: Работа с клиентами
 * User Level: 2
 * Plugin Name: Менеджер задолженностей
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: Менеджер задолженностей и платежей клиентов 
 * Author: baycik 2019
 * Author URI: http://isellsoft.com
 */


class DebtManager extends Catalog {
    public $settings = [];
    public function getDebts(){
        
        $acomp = $this->Hub->svar('acomp');
        $sql = "
            SELECT 
                acctr.trans_id, acctr.is_disabled, acctr.active_company_id,  acctr.amount, acctr.description, acctr.cstamp,
                acctr.passive_company_id, cl.company_name, cl.company_person, cl.company_email, cl.company_mobile, cl.company_address
            FROM
                isell_db.acc_trans acctr
                JOIN
                companies_list cl ON(acctr.passive_company_id = cl.company_id)

            WHERE
                trans_status IN (1, 2)
                AND active_company_id = '".$acomp->company_id."'
                AND acc_debit_code = 361 
                AND acc_credit_code = 702
                ";
        
        return $this->get_list($sql);
    }
    

}
