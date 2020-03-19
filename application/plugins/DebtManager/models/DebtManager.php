<?php

/* Group Name: Работа с клиентами
 * User Level: 2
 * Plugin Name: Календарь платежей
 * Plugin URI: http://isellsoft.com
 * Version: 1.0
 * Description: Календарь платежей 
 * Author: baycik 2019
 * Author URI: http://isellsoft.com
 */


class DebtManager extends Catalog {
    public $settings = [];
    public function index(){
        $this->Hub->set_level(3);
        $this->load->view('debt_manager.html');
    }
    
    
     public function install(){
        $this->Hub->set_level(4);
	$install_file=__DIR__."/../install/install.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($install_file);
    }
    
    public function uninstall(){
        $this->Hub->set_level(4);
	$uninstall_file=__DIR__."/../install/uninstall.sql";
	$this->load->model('Maintain');
	return $this->Maintain->backupImportExecute($uninstall_file);
    }
    public function activate(){
        $this->Hub->set_level(4);
	$this->tasksMakeActive();
    }
    public function deactivate(){
        $this->Hub->set_level(4);
	$this->tasksDelete();
    }
    
    public $getBlock = ['filter' => 'json'];
    private $system_user = false;
    private $current_group_by_date = '';
    public function getBlock($filter){
        session_write_close();
        $this->Hub->set_level(1);
        $user_id = $this->Hub->svar('user_id');
        $this->createTmp($filter,$user_id);
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
        if(!empty($total)){
            $buy_total = $total->amount_buy; 
            $sell_total = $total->amount_sell;
        } 
        return ['date' => $view_date, 'list' => $list, 'total' => ['buy'=> $buy_total, 'sell'=> $sell_total] ];
    }
    
    private function createTmp($filter,$user_id) {
        $user_level = $this->Hub->svar('user_level');
        $block_number = $filter['block_number'];
        $settings = $this->getUserSettings($user_id);
        $sell_code = "361";
        $buy_code = "631";
        $acomp = "";
        if(!isset($settings->acomp)){
            $settings->acomp = 0;
        }
         if($settings->acomp){
            $acomp = "AND active_company_id = {$this->Hub->svar('acomp')->company_id} ";
        }
           
        $trans_type_filter="";
        if($settings->sell_trans == true){
            $trans_type_filter.="acc_debit_code=$sell_code";
        }
        if($settings->buy_trans == true){
            if($trans_type_filter!=""){
                $trans_type_filter.=" OR ";
            }
            $trans_type_filter.="acc_credit_code=$buy_code";
        }
        if( !$trans_type_filter ){
            return false;
        }
        $amount_sell="ROUND(
                    SUM(
                        IF(acc_debit_code = $sell_code,
                            IF(trans_status = 2, GET_PARTLY_PAYED(active_company_id , passive_company_id ,$sell_code),amount),
                        NULL)
                    )
                ,2) AS amount_sell,";
        $amount_buy="ROUND(
                    SUM(
                        IF(acc_credit_code = $buy_code,
                            -1*IF(trans_status = 2, GET_PARTLY_PAYED(active_company_id , passive_company_id ,$buy_code),amount),
                        NULL)
                    )
                ,2) AS amount_buy,";
        if(!isset($settings->group_by_date)){
            $settings->group_by_date = 'DAY';
        } 
        $ofyear = 'OFYEAR';
        if($settings->group_by_date != 'DAY' && $settings->group_by_date != 'WEEK' ){
            $ofyear = '';
        }
        $this->current_group_by_date = $settings->group_by_date;
        $pcomp_filter="";
        if(!empty($filter['pcomp_id'])){
            $settings->pcomp_id = $filter['pcomp_id'];
        }
        if($settings->pcomp_id != 0){
            $pcomp_filter = " AND passive_company_id = '$settings->pcomp_id'";
        }
        if($block_number == -1){
            $where = "AND DATE_ADD(cstamp, INTERVAL deferment DAY) < DATE_ADD(CURDATE(), INTERVAL 0 {$settings->group_by_date})";
            
        } else {
            $where  = " AND {$settings->group_by_date}$ofyear(DATE_ADD(cstamp, INTERVAL deferment DAY)) = {$settings->group_by_date}$ofyear(DATE_ADD(CURDATE(), INTERVAL $block_number {$settings->group_by_date}))";
            $where .= " AND DATE_ADD(cstamp, INTERVAL deferment DAY) > DATE_ADD(CURDATE(), INTERVAL 0 {$settings->group_by_date})";
            $where .= " AND DATE_ADD(cstamp, INTERVAL deferment DAY) > DATE_ADD(CURDATE(), INTERVAL '".($block_number-1)."' {$settings->group_by_date})";
            $where .= " AND DATE_ADD(cstamp, INTERVAL deferment DAY) < DATE_ADD(CURDATE(), INTERVAL '".($block_number+1)."' {$settings->group_by_date})";
        }
        
        $user_assigned_path=$this->Hub->svar('user_assigned_path');
        $path = '';
        if( $user_assigned_path ){
            $path = "AND ct.path LIKE '$user_assigned_path%'";
        }
        
        $where .= $path;
        
        $group_by = "trans_id ";
        if($settings->group_by_pcomp == true){
            $group_by = "passive_company_id ";
        } 
        $this->query("DROP TABLE IF EXISTS tmp");
        $sql = "
                CREATE TEMPORARY TABLE tmp  
                SELECT 
                    DATE_FORMAT(DATE(TIMESTAMPADD(DAY, cl.deferment, acctr.cstamp)),'%Y%-%m%-%d') AS pay_date,
                    $amount_sell $amount_buy
                    ROUND(SUM(amount), 2) as total_amount,
                    GROUP_CONCAT(acctr.description SEPARATOR ', ')  as description,
                    acctr.passive_company_id,
                    ct.label as company_label,
                    cl.company_name,
                    cl.company_person,
                    cl.company_email,
                    cl.company_mobile,
                    cl.company_address,
                    IF(acctr.acc_credit_code = '631', 'buy', 'sell') as trans_type,
                    DATE_FORMAT(acctr.cstamp,'%Y%-%m%-%d') doc_date,
                    acc_credit_code,
                    trans_id
                FROM
                    isell_db.acc_trans acctr
                        JOIN 
                    companies_list cl ON (acctr.passive_company_id = cl.company_id)
                        JOIN 
                    companies_tree ct ON (cl.branch_id = ct.branch_id)
                WHERE
                    trans_status IN (1,2,6,7) 
                    $acomp
                    AND ($trans_type_filter)
                    AND ct.level <= $user_level
                    $pcomp_filter
                    $where
                GROUP BY acctr.$group_by
                HAVING amount_sell > 100 OR amount_buy < 100
                ORDER BY pay_date ASC
        ";
        $this->query($sql);
    }
    
    private function getEntries(){
        return $this->get_list("SELECT * FROM tmp");
    }
    private function getTotal(){
        return $this->get_row("SELECT ROUND(SUM(amount_buy), 2) as amount_buy, ROUND(SUM(amount_sell), 2) as amount_sell FROM tmp");
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
        $doc_id='';
        $event_date= date( "Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s")."+7 day" ));
        $event_priority='3medium';
        $event_name='Уведомление';
        $event_label='-TASK-';
        $event_target='0';
        $event_place='';
        $event_note='';
        $event_descr='Уведомление о задолженностях';
        $event_program = json_encode($program);
        $event_repeat='7 0:0';
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
    
    
    private function tasksMakeActive(){
        $settings = $this->getAllSettings();
        foreach($settings as $user_id=>$user_settings){
            if(isset($settings[$user_id]['event_disable']) && $settings[$user_id]['event_disable'] == '1' ){
                $user_settings['event_disable'] = '0';
                $this->updateSettings($user_settings, $user_id);
            }
        }
    }
    
    private function tasksDelete(){
        $settings = $this->getAllSettings();
        foreach($settings as $user_id=>$user_settings){
            if(isset($settings[$user_id]['event_id'])){
                $event_id = $user_settings['event_id'];
                $Events=$this->Hub->load_model('Events');
                $Events->eventDelete($event_id);
                $user_settings['event_id'] = '0';
                $user_settings['event_disable'] = '1';
                $this->updateSettings($user_settings, $user_id);
            }
        }
    }

    public $sendNotifications = ['user_id' => 'int'];
    public function sendNotification($user_id){
        $msg = $this->composeMessage($user_id);
        $Chat=$this->Hub->load_model('Chat');
        if($msg){
           $Chat->addMessage($user_id, $msg, true);
        }
        
    }
    
    private function composeMessage($user_id){
        $this->system_user = true;
        $msg = $this->composeMessageTemplate($user_id);
        $this->system_user = false;
        return $msg;
    }
    
    private function composeMessageTemplate($user_id) {
        $filter = [];
        $msg = 'Уважаемый '.$this->Hub->svar('user')->first_name.',</br>';
        $filter['block_number'] = -1;
        $this->createTmp($filter,$user_id);
        $total = $this->getTotal();
        if(empty($total)){
            return false;
        }
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
        if(!isset($user_settings['event_disable'])){
            $user_settings['event_disable'] = '0';
        }
        $this->settings = $this->getAllSettings();
        if(!$user_id){
            $user_id = $this->Hub->svar('user_id');
        }
        if(empty($this->settings)){
            $user_settings['group_by_date'] = 'DAY';
            $this->settings[0] = $user_settings;
        } 
        $this->settings[$user_id] = $user_settings;
        $user_level = $this->Hub->svar('user_level');
        if( $user_level>1 ){
            if((bool)$this->settings[$user_id]['notificate'] && $this->settings[$user_id]['event_disable'] == '0'){
                if(!(bool)$this->settings[$user_id]['event_id'] ){
                    $this->settings[$user_id]['event_id'] = $this->notificate(1,$user_id);
                }
            } else {
                $this->settings[$user_id]['event_id'] = $this->notificate(0,$user_id);
            }
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
        if( isset($user_settings->{$user_id}) ){
            $user_level = $this->Hub->svar('user_level');
            if( $user_id && $user_level>1 ){
                $Events=$this->Hub->load_model('Events');
                $event_id = $Events->eventGet($user_settings->{$user_id}->event_id);  
                if(!$event_id){
                    $user_settings->{$user_id}->event_id = 0;
                    $user_settings->{$user_id}->notificate = 0;
                }
            }
            return $user_settings->{$user_id};
        } else {
            return $this->updateSettings($settings[] = [
                'group_by_date' => 'WEEK',
                'group_by_pcomp'=> '0',
                'pcomp_name'=> '',
                'pcomp_id'=> '',
                'buy_trans'=> 'true',
                'sell_trans'=> 'true',
                'acomp'=> '0',
                'notificate'=> '0',
                'event_id'=> '0',
                'event_disable'=> '0',
                'user_assigned_path'=> $this->Hub->svar('user_assigned_path')
                ], $user_id);
        }
    }
    
    
    public function dashboard(){
        $this->Hub->set_level(1);
        $this->load->view("dashboard.html");
    }
    
    public function views( string $path ){
	header("X-isell-type:OK");
	$this->load->view($path);
    }

    
    /*=================================*/
    
    public $messageRender = ['type' => 'string', 'pcomp_id' => 'int'];
    public function messageRender($type, $pcomp_id){
        $settings = $this->getUserSettings($this->Hub->svar('user_id'));
        $filter= [];
        $filter['pcomp_id'] = $pcomp_id;
        $filter['group_by_date'] = $settings->group_by_date;
        $pcomp = $this->passiveCompanyGet($filter['pcomp_id']);
        $table = $this->blockListRender($filter);
        $data = [
            'passive_company' => $pcomp->company_name,
            'table' => $table
        ];
        return $this->load->view('debt_mail_template', $data, true);
    }
    
    public $blockListRender = ['filter' => 'json'];
    public function blockListRender($filter){
        $list = $this->blockListGet($filter);
        $data['list'] = $list;
        $table = $this->load->view('debt_table_template', $data, true);
        return $table;
    }
    
    private $blockListGet = ['filter' => 'json'];
    private function blockListGet($filter){
        $block_list = [];
        $last_transaction = $this->lastTransactionGet($filter);
        $last_block = $this->datediffInWeeks(date('Y-m-d'), $last_transaction, $filter['group_by_date']);
        for($i = 0; $i <= $last_block; $i++){
            $filter['block_number'] = $i-1;
            $block = $this->getBlock($filter);
            if(!empty($block['list'])){
                $block_list[] = $block;
            }
            
        }
        return $block_list;
    }
    
    
    private $lastTransactionGet = ['filter' => 'json'];
    private function lastTransactionGet($filter){
        $pcomp_id = $filter['pcomp_id'];
        $sql = "
            SELECT 
                MAX(DATE_FORMAT(DATE(TIMESTAMPADD(DAY, cl.deferment, acctr.cstamp)),'%Y%-%m%-%d')) pay_date
            FROM 
                isell_db.acc_trans acctr
                    JOIN 
                companies_list cl ON (acctr.passive_company_id = cl.company_id)
            WHERE 
                passive_company_id = '$pcomp_id'
            AND acc_debit_code = 361
            AND active_company_id = 1
            ";
        return $this->get_row($sql)->pay_date;
    }
    
    private $datediffInWeeks = ['date_from' => 'string', 'date_to' => 'string'];
    private function datediffInWeeks($date_from, $date_to, $date_group_by){
        if($date_from > $date_to) return false;
        $measure = 1;
        if($date_group_by == 'WEEK'){
            $measure = 7;
        } else if($date_group_by == 'MONTH'){
            $measure = 30;
        } else if($date_group_by == 'QUARTER'){
            $measure = 90;
        } else {
            $measure = 365;
        }
        $first = DateTime::createFromFormat('Y-m-d', $date_from);
        $second = DateTime::createFromFormat('Y-m-d', $date_to);
        return floor($first->diff($second)->days/$measure);
    }
    
    private $passiveCompanyGet = ['pcomp_id' => 'int'];
    private function passiveCompanyGet($pcomp_id){
        $sql = "
            SELECT 
                *
            FROM 
                companies_list 
            WHERE 
                company_id = '$pcomp_id'
            ";
        return $this->get_row($sql);
    }
    

}
