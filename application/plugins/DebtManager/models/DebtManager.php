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
    
    public $getBlock = ['filter' => 'json'];
    private $system_user = false;
    private $current_group_by_date = '';
    public function getBlock($filter){
        session_write_close();
        $this->Hub->set_level(2);
        $user_id = $this->Hub->svar('user_id');
        $this->createTmp($filter);
        
        $list = $this->getEntries();
        if(isset($list[0])){
            $total = $this->getTotal();
        } else {
            $total = '';
        }
        if($filter['block_number'] > -1){
            $view_date = $this->composeDate($this->current_group_by_date, $filter['block_number']);
        } else {
            $view_date = false;
        }
        $buy_total = '';
        $sell_total = '';
        if(isset($total[0])){
            $buy_total = $total[0]->amount_buy; 
            $sell_total = $total[0]->amount_sell;
        } 
        return ['date' => $view_date, 'list' => $list, 'total' => ['buy'=> $buy_total, 'sell'=> $sell_total] ];
    }
    
    private function createTmp($filter) {
        $acomp = $this->Hub->svar('acomp');
        
        $block_number = $filter['block_number'];
        $settings = $this->getUserSettings();
        $passive_company_id = '';
        if($settings->sell_trans == true){
            $sell_trans = " (acc_debit_code = 361 AND acc_credit_code = 702) ";
        } else {
            $sell_trans = ' 0 ';
        }
        if($settings->buy_trans == true){
            $buy_trans = " (acc_credit_code = 631) ";
        } else {
            $buy_trans = ' 0 ';
        }
        if(!isset($settings->group_by_date)){
            $settings->group_by_date = 'DAY';
        } 
        if($settings->group_by_date != 'DAY' && $settings->group_by_date != 'WEEK' ){
            $ofyear = '';
        } else {
            $ofyear = 'OFYEAR';
        }
        $this->current_group_by_date = $settings->group_by_date;
        if($settings->pcomp_id != 0){
            $passive_company_id = " AND passive_company_id = '$settings->pcomp_id'";
        } else {
            $passive_company_id = "";
        }
        if($block_number == -1){
            $where = "AND DATE_ADD(cstamp, INTERVAL deferment DAY) < DATE_ADD(CURDATE(), INTERVAL 0 {$settings->group_by_date})";
            
        } else {
            $where = "AND {$settings->group_by_date}$ofyear(DATE_ADD(cstamp, INTERVAL deferment DAY)) = {$settings->group_by_date}$ofyear(DATE_ADD(CURDATE(), INTERVAL $block_number {$settings->group_by_date}))";
            $where .= " AND DATE_ADD(cstamp, INTERVAL deferment DAY) > DATE_ADD(CURDATE(), INTERVAL 0 {$settings->group_by_date})";
            $where .= "  AND DATE_ADD(cstamp, INTERVAL deferment DAY) > DATE_ADD(CURDATE(), INTERVAL '".($block_number-1)."' {$settings->group_by_date})";
            $where .= " AND DATE_ADD(cstamp, INTERVAL deferment DAY) < DATE_ADD(CURDATE(), INTERVAL '".($block_number+1)."' {$settings->group_by_date})";
        }
        if(isset( $settings->user_assigned_path)){
            $path = "AND (ct.path LIKE '%".str_replace(",", "%' OR ct.path LIKE '%", $settings->user_assigned_path)."%')";
        } else {
            $path = '';
        }
        $where .= $path;
        if($settings->group_by_pcomp == true){
            $group_by = "passive_company_id ";
        } else {
            $group_by = 'trans_id ';
        }
        $this->query("DROP TABLE IF EXISTS tmp");
        $sql = "
                CREATE TEMPORARY TABLE tmp  
                SELECT 
                    DATE_FORMAT(DATE(TIMESTAMPADD(DAY, cl.deferment, acctr.cstamp)),'%Y%-%m%-%d') AS pay_date,
                    IF(acctr.acc_credit_code = '631', CONCAT('-', ROUND(SUM(acctr.amount),2)),'') as amount_buy,
                    IF(acctr.acc_credit_code = '631', '', ROUND(SUM(acctr.amount),2)) as amount_sell,
                    GROUP_CONCAT(acctr.description SEPARATOR ', ')  as description,
                    acctr.passive_company_id,
                    ct.label as company_label,
                    cl.company_name,
                    cl.company_person,
                    cl.company_email,
                    cl.company_mobile,
                    cl.company_address,
                    IF(acctr.acc_credit_code = '631', 'buy', 'sell') as trans_type,
                    acc_credit_code,
                    trans_id
                FROM
                    isell_db.acc_trans acctr
                JOIN companies_list cl ON (acctr.passive_company_id = cl.company_id)
                JOIN companies_tree ct ON (cl.branch_id = ct.branch_id)
                WHERE
                    trans_status IN (1,2,6,7) 
                    $passive_company_id
                    AND active_company_id = '$acomp->company_id'
                    AND ($sell_trans OR $buy_trans)
                    $where
                GROUP BY acctr.$group_by
                ORDER BY pay_date ASC
        ";
        $this->query($sql);
    }
    
    private function getEntries(){
        return $this->get_list("SELECT * FROM tmp");
    }
    private function getTotal(){
        return $this->get_list("SELECT ROUND(SUM(amount_buy), 2) as amount_buy, ROUND(SUM(amount_sell), 2) as amount_sell FROM tmp");
    }
    
    private function composeDate($group_by, $block_number ){
        if($group_by === 'WEEK' ){ 
            $curr_date = "CONCAT(STR_TO_DATE(CONCAT(YEARWEEK(DATE_ADD(curdate(), INTERVAL $block_number WEEK)),' Monday'), '%X%V %W'),'|',DATE_ADD(DATE(CURDATE() + INTERVAL (8 - DAYOFWEEK(CURDATE())) DAY), INTERVAL $block_number WEEK))";
        } else if ($group_by === 'QUARTER'){
            $curr_date = "CONCAT ($group_by(DATE_ADD(CURDATE(), INTERVAL $block_number $group_by)),'|',DATE_ADD(CURDATE(), INTERVAL $block_number $group_by))";
        } else {
            $curr_date = "DATE_ADD(CURDATE(), INTERVAL $block_number $group_by)";
        } 
        $date = $this->get_row("SELECT $curr_date as curr_date FROM isell_db.acc_trans acctr LIMIT 1")->curr_date;
        $quarter = 'Квартал';
        $year = 'Год';
        $date_arr = [
            'day' => '',
            'month' => 0,
            'quarter' => 0,
            'year' => '0'
        ];
        switch($group_by){
            case 'DAY':
                $date_arr['day'] = (int)explode('-', $date)[2];
                $date_arr['month'] = (int)explode('-', $date)[1];
                $date_arr['year'] = explode('-', $date)[0];
                return $date_arr;
             case 'WEEK':
                $dates = explode('|', $date);
                 if(explode('-',$dates[0])[1] != explode('-',$dates[1])[1]){
                    $date_arr['day'] = (int)explode('-',$dates[0])[2].' - '.(int)explode('-',$dates[1])[2];
                    $date_arr['month'] = (int)explode('-',$dates[0])[1];
                 } else {
                    $date_arr['day'] = (int)explode('-',$dates[0])[2].' - '.(int)explode('-',$dates[1])[2];
                    $date_arr['month'] = (int)explode('-',$dates[0])[1];
                 }
                
                $date_arr['year'] = explode('-', $date)[0];
                return $date_arr;    
            case 'MONTH':
                $date_arr['day'] = '';
                $date_arr['month'] = (int)explode('-',$date)[1];
                $date_arr['year'] = explode('-', $date)[0];
                return $date_arr;
            case 'QUARTER':
                $date_arr['quarter'] = (int)explode('|',$date)[0];
                $date_arr['year'] = explode('-',explode('|', $date)[1])[0];
                return $date_arr;
            case 'YEAR':
                $date_arr['year'] = explode('-', $date)[0];
                return $date_arr;   
        }
    }
    
    public function notificate($notificate, $user_id){
        $Events=$this->Hub->load_model('Events');
        if(!$notificate){
            $Events->eventDelete( $this->settings[$user_id]['event_id'] );
            return 0;
        }
        $program = [
            'commands' => [[
                "model" => "DebtManager",
                "method" => "sendNotification",
                "arguments" => $user_id,
                "async" => 0,
                "disabled" => 0
            ]]
        ];
            $event_id= null;
            $doc_id='';
            $event_date=date("Y-m-d H:i:s");
            $event_priority='3medium';
            $event_name='Уведомление';
            $event_label='-TASK-';
            $event_target='0';
            $event_place='';
            $event_note='';
            $event_descr='Уведомление о задолженностях';
            $event_program = json_encode($program);
            $event_repeat='0 7:0';
            $event_status='pending';
            $event_liable_user_id='';
            $event_is_private = '1';
        $event_id = $Events->eventSave($event_id, 
                $doc_id, 
                $event_date,
                $event_priority, 
                $event_name, 
                $event_label, 
                $event_target,
                $event_place,
                $event_note,
                $event_descr,
                $event_program,
                $event_repeat,
                $event_status,
                $event_liable_user_id,
                $event_is_private
                );
        return $event_id;
    }
    
    public $sendNotifications = ['user_id' => 'int'];
    public function sendNotification($user_id){
        $msg = $this->composeMessage();
        $Chat=$this->Hub->load_model('Chat');
        $Chat->addMessage($user_id, $msg, true);
    }
    
    public $composeMessage = [];
    public function composeMessage(){
        $this->system_user = true;
        $msg = $this->composeMessageTemplate();
        $this->system_user = false;
        return $msg;
    }
    
    private function composeMessageTemplate() {
        $filter = [];
        $msg = 'Уважаемый '.$this->Hub->svar('user')->first_name.',</br>';
            $filter['block_number'] = -1;
            $this->createTmp($filter);
            $total = $this->getTotal()[0];
            if(empty($total->amount_buy) && empty($total->amount_sell)){
                return;
            }
            if($filter['block_number'] == -1){
                if((int)$total->amount_sell != 0){
                    $msg .= 'Есть просроченные платежи от клиентов на сумму: <b>'.$total->amount_sell.'</b>.</br> ';
                } 
                if((int)$total->amount_buy != 0){
                    $msg .= 'Есть задолженность перед поставщиками в размере: <b>'.$total->amount_buy.'</b>.</br> ';
                }
            }
            
        $msg .= 'Будьте любезны, получите больше информации в <a href="#Home#home_main_tabs" onclick="location="#Home#home_main_tabs">Менеджере задолженностей</a>.';
        return $msg;
    }
    
    public $updateSettings = ['user_settings' => 'json', 'user_id'=>'int'];
    public function updateSettings($user_settings, $user_id = false) {
        $user_settings['user_assigned_path'] = addslashes($user_settings['user_assigned_path']);
        $this->settings = $this->getAllSettings();
        if(!$user_id){
            $user_id = $this->Hub->svar('user_id');
        }
        if(empty($this->settings)){
            $user_settings['group_by_date'] = 'DAY';
            $this->settings[0] = $user_settings;
        } 
        $this->settings[$user_id] = $user_settings;
        if((bool)$this->settings[$user_id]['notificate']){
            if(!(bool)$this->settings[$user_id]['event_id']){
                $this->settings[$user_id]['event_id'] = $this->notificate(1,$user_id);
            }
        } else {
            $this->settings[$user_id]['event_id'] = $this->notificate(0,$user_id);
        }
        $encoded = json_encode($this->settings, JSON_UNESCAPED_UNICODE);
        $sql = "
            UPDATE
                plugin_list
            SET 
                plugin_settings = '$encoded'
            WHERE plugin_system_name = 'DebtManager'    
            ";
        $this->query($sql);
        return $this->settings[$user_id];
    }
    
    public function getAllSettings() {
        $sql = "
            SELECT
                plugin_settings
            FROM 
                plugin_list
            WHERE plugin_system_name = 'DebtManager'    
            ";
        $row = $this->get_row($sql);
        return json_decode($row->plugin_settings, true);
    }
    
    public $getUserSettings = ['user_id' => 'int'];
    public function getUserSettings($user_id = false) {
        if(!$user_id){
            if($this->system_user){
                $user_id = 0;
            }else {
                $user_id = $this->Hub->svar('user_id');
            }
        }
        $sql = "
            SELECT
                plugin_settings
            FROM 
                plugin_list
            WHERE plugin_system_name = 'DebtManager'    
            ";
        $row = $this->get_row($sql);
        $user_settings = json_decode($row->plugin_settings);
        if(isset($user_settings->{$user_id})){
            if($user_id != 0){
                $Events=$this->Hub->load_model('Events');
                $event_id = $Events->eventGet($user_settings->{$user_id}->event_id);  
                if(!$event_id){
                    $user_settings->{$user_id}->event_id = 0;
                    $user_settings->{$user_id}->notificate = 0;
                }
            }
            return $user_settings->{$user_id};
        } else {
            return $this->updateSettings($settings = [
                'group_by_date' => 'WEEK',
                'group_by_pcomp'=> '0',
                'pcomp_name'=> '',
                'pcomp_id'=> '',
                'buy_trans'=> 'true',
                'sell_trans'=> 'true',
                'notificate'=> '0',
                'event_id'=> '0',
                'user_assigned_path'=> ''
                ], $user_id);
        }
    }
    
    
    public function dashboard(){
        $this->Hub->set_level(2);
        $this->load->view("../dashboard.html");
    }

}
