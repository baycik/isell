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
    
    //public $getBlock = ['filter' => 'json'];
    private $system_user = false;
    private $current_group_by_date = '';
    public function getBlock( array $filter ){
        session_write_close();
        $this->Hub->set_level(1);
        $user_id = $this->Hub->svar('user_id');
        
        $params = $this->getUserSettings($user_id);
        if( $filter ){
            foreach( $filter as $key=>$value ){
                $params->{$key}=$value;
            }
        }
        $this->blockTableCreate($params);
        $list = $this->getEntries();
        if( count($list) ){
            $total = $this->getTotal();
        } else {
            $total = '';
        }
        if($filter['block_number'] > -1){
            $view_date = $this->composeDate($params);
        } else {
            $view_date = false;
        }
        $buy_total = 0;
        $sell_total = 0;

        if( !empty($total) ){
            $buy_total = $total->amount_buy; 
            $sell_total = $total->amount_sell;
        } 
        return ['date' => $view_date, 'list' => $list, 'total' => ['buy'=> $buy_total, 'sell'=> $sell_total] ];
    }
    
    private $blockTransTableCreated=false;
    private function blocksTransTableCreate( $params ){
        if( $this->blockTransTableCreated==true ){
            return true;
        }
        $filter_acomp = "";
        if( !empty($params->acomp_only) ){
            $filter_acomp = "AND active_company_id = {$this->Hub->svar('acomp')->company_id} ";
        }
        $filter_pcomp="";
        if( $params->pcomp_id ){
            $filter_pcomp = "AND passive_company_id = '".( (int) $params->pcomp_id )."'";
        }
        
        $sell_code = "361";
        $buy_code = "631";
        $filter_trans="";
        if( $params->sell_trans == true ){
            $filter_trans.="acc_debit_code=$sell_code"; 
        }
        if( $params->buy_trans == true ){
            if( $filter_trans!="" ){
                $filter_trans.=" OR ";
            }
            $filter_trans.="acc_credit_code=$buy_code";
        }
        if( !$filter_trans ){
            return false;
        }
        
        $user_level = $this->Hub->svar('user_level');
        $filter_level="AND ct.level <= $user_level";
        
        $filter_path = "";
        $user_assigned_path=$this->Hub->svar('user_assigned_path');
        if( $user_assigned_path ){
            $filter_path = "AND ct.path LIKE '$user_assigned_path%'";
        }
        $this->query("DROP TEMPORARY TABLE IF EXISTS tmp_trans_table");
        $trans_table="
            CREATE TEMPORARY TABLE tmp_trans_table AS
            SELECT
                *,
                DATE_ADD(cstamp, INTERVAL deferment DAY) due_date
            FROM
                isell_db.acc_trans acctr
                    JOIN 
                companies_list cl ON (acctr.passive_company_id = cl.company_id)
                    JOIN 
                companies_tree ct USING(branch_id)
            WHERE
                trans_status IN (1,2,6,7) 
                AND ($filter_trans)
                $filter_acomp
                $filter_pcomp
                $filter_level
                $filter_path
            ";
        $this->query($trans_table);
        $this->blockTransTableCreated=true;
        return true;
    }
    
    private function blockTableCreate( $params ) {
        $table_created=$this->blocksTransTableCreate( $params );
        if( !$table_created ){
            return false;
        }
        $block_number = $params->block_number;
        $filter_interval = "due_date < NOW()";
        if($block_number > -1){
            switch($params->group_by_date){
                case "WEEK":
                    $start=(new DateTime())->modify("Monday this week");
                    $period="week";
                    $interval=1;
                    break;
                case "MONTH":
                    $start=(new DateTime())->modify("first day of this month");
                    $period="month";
                    $interval=1;
                    break;
                case "QUARTER":
                    $offset = (date('n')-1)%3; // modulo ftw
                    $start = new DateTime("first day of -$offset month midnight");
                    $period="month";
                    $interval=3;
                    break;
                case "YEAR":
                    $start = new DateTime('first day of january this year');
                    $period="year";
                    $interval=1;
                    break;
                default :
                    $start = new DateTime();
                    $period="day";
                    $interval=1;
            }
            $now=(new DateTime())->format('Y-m-d 0:00:00');
            $idate=$start->modify("+".($interval*$block_number)." {$period}s")->format('Y-m-d 0:00:00');
            $idate=max([$now,$idate]);
            $fdate=$start->modify("+".($interval)." {$period}s")->format('Y-m-d 0:00:00');
            $filter_interval = "due_date > '$idate' AND due_date < '$fdate'";
        }
        
        $group_by = "trans_id";
        if($params->group_by_pcomp == true){
            $group_by = "passive_company_id";
        }
        
        $this->query("DROP TEMPORARY TABLE IF EXISTS debt_block_tmp");
        $sql = "
                CREATE TEMPORARY TABLE debt_block_tmp  
                SELECT 
                    ROUND(
                        SUM(
                            IF(acc_debit_code = 361,
                                IF(trans_status = 2, GET_PARTLY_PAYED(active_company_id , passive_company_id ,361),amount),
                            NULL)
                        )
                    ,2) AS amount_sell,
                    ROUND(
                        SUM(
                            IF(acc_credit_code = 631,
                                -1*IF(trans_status = 2, GET_PARTLY_PAYED(active_company_id , passive_company_id ,631),amount),
                            NULL)
                        )
                    ,2) AS amount_buy,
                    ROUND(SUM(amount), 2) as total_amount,
                    GROUP_CONCAT(description SEPARATOR ', ')  as description,
                    passive_company_id,
                    label as company_label,
                    company_name,
                    company_person,
                    company_email,
                    company_mobile,
                    company_address,
                    IF(acc_credit_code = '631', 'buy', 'sell') as trans_type,
                    DATE_FORMAT(cstamp,'%d.%m.%Y') doc_date_dmy,
                    DATE_FORMAT(due_date,'%d.%m.%Y') due_date_dmy,
                    acc_credit_code,
                    trans_id
                FROM
                    tmp_trans_table
                    WHERE
                        $filter_interval
                GROUP BY $group_by
                HAVING amount_sell > 10 OR amount_buy < 10
                ORDER BY due_date ASC";
        $this->query($sql);
    }
    
    private function getEntries(){
        return $this->get_list("SELECT * FROM debt_block_tmp");
    }
    private function getTotal(){
        return $this->get_row("SELECT ROUND(SUM(amount_buy), 2) as amount_buy, ROUND(SUM(amount_sell), 2) as amount_sell FROM debt_block_tmp");
    }
    
    private function composeDate( $params ){
        if($params->group_by_date === 'WEEK' ){ 
            $curr_date = "CONCAT(STR_TO_DATE(CONCAT(YEARWEEK(DATE_ADD(curdate(), INTERVAL {$params->block_number} WEEK)),' Monday'), '%X%V %W'),'|',DATE_ADD(DATE(CURDATE() + INTERVAL (8 - DAYOFWEEK(CURDATE())) DAY), INTERVAL {$params->block_number} WEEK))";
        } else if ($params->group_by_date === 'QUARTER'){
            $curr_date = "CONCAT ({$params->group_by_date}(DATE_ADD(CURDATE(), INTERVAL {$params->block_number} {$params->group_by_date})),'|',DATE_ADD(CURDATE(), INTERVAL {$params->block_number} {$params->group_by_date}))";
        } else {
            $curr_date = "DATE_ADD(CURDATE(), INTERVAL {$params->block_number} {$params->group_by_date})";
        } 
        $date = $this->get_row("SELECT $curr_date as curr_date FROM isell_db.acc_trans acctr LIMIT 1")->curr_date;
        //$quarter = 'Квартал';
        //$year = 'Год';
        $date_arr = [
            'day' => '',
            'month' => 0,
            'quarter' => 0,
            'year' => '0'
        ];
        switch($params->group_by_date){
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
        $this->blockTableCreate($filter,$user_id);
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
                $Events = $this->Hub->load_model('Events');
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
                'acomp_only'=> '0',
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
    
    
    
    
//    
//    public function companyMailingNotificationCreate( int $pcomp_id ){
//        //header("Content-type:text/plain");
//        $settings = $this->getUserSettings($this->Hub->svar('user_id'));
//        $passive_company = $this->passiveCompanyGet($pcomp_id);
//        $filter= [
//            'pcomp_id' => $pcomp_id,
//            'group_by_date' => $settings->group_by_date
//        ];
//        $data = [
//            'passive_company' => $passive_company->company_name,
//            'table' => $this->blockListRender($filter)
//        ];
//                    print_r($data);
//
//        $message = [
//            'message_reason'=>'Задолженность',
//            'message_note'=>$passive_company->company_label,
//            'message_subject'=>'Задолженность',
//        ];
//        $MailingManager = $this->Hub->load_model('MailingManager');
//        if(!empty($passive_company->company_email)){
//            $message['message_handler']='email';
//            $message['message_recievers'] = $passive_company->company_email;
//            $message['message_body'] = $this->load->view('debt_mail_template', $data, true);
//            die($message['message_body']);
//            $MailingManager->messageCreate($message);
//        }
//        if(!empty($passive_company->company_mobile)){
//            $message['message_handler']='sms';
//            $message['message_recievers'] = $passive_company->company_mobile;
//            $message['message_body'] = $this->load->view('debt_sms_template', $data, true);
//            $MailingManager->messageCreate($message);
//        }
//        return true;
//    }
    
    private function blockListCountGet( $filter ){
        $params=(object)[
            'pcomp_id'=>$filter['pcomp_id'],
            'sell_trans'=>true,
            'buy_trans'=>false
        ];
        $this->blocksTransTableCreate( $params );
        $sql = "SELECT {$filter['deferment']} - DATEDIFF(NOW(),MAX(cstamp)) FROM tmp_trans_table";
        $ahead_days=$this->get_value($sql);
        switch($filter['group_by_date']){
            case 'YEAR':
                return ceil($ahead_days/365);
            case 'QUARTER':
                return ceil($ahead_days/90);
            case 'MONTH':
                return ceil($ahead_days/30);
            case 'WEEK':
                return ceil($ahead_days/7);
            default :
                return $ahead_days;
        }
    }
    
    private function blockListGet($filter){
        $block_list = [
            'list'=>[],
            'grand_total_sell'=>0,
            'grand_total_buy'=>0
        ];
        $block_count = $this->blockListCountGet($filter);
        if($block_count==0){
            return null;
        }
        for($i = -1; $i < $block_count; $i++){
            $filter['block_number'] = $i;
            $block = $this->getBlock($filter);
            if($filter['block_number']==-1){
                $block['date']="expired";
            }
            $block_list['list'][] = $block;
            $block_list['grand_total_sell']+=$block['total']['sell'];
            $block_list['grand_total_buy']+=$block['total']['buy'];
        }
        return $block_list;
    }

    
    
    private $lastWidgetPcompId=0;
    public function PaymentCalendar( object $context ){
        if( $context->company_id??false ){
            if($this->lastWidgetPcompId!=$context->company_id){
                $this->blockTransTableCreated=false;
                $this->lastWidgetPcompId=$context->company_id;
            }
            $filter=[
                'pcomp_id'=>$context->company_id,
                'deferment'=>$context->deferment,
                'group_by_date'=>'WEEK'
            ];
            $block_list=$this->blockListGet($filter);
            if( $block_list===null ){
                return false;
            }
            $table_html=$this->load->view('debt_table_template', ['block_list' => $block_list], true);
            return $table_html;
        }
        return false;
    }
    
    public function DebtTotal( object $context ) {
        if( $context->company_id??false ){
            if($this->lastWidgetPcompId!=$context->company_id){
                $this->blockTransTableCreated=false;
                $this->lastWidgetPcompId=$context->company_id;
            }
            $params=(object)[
                'pcomp_id'=>$context->company_id,
                'sell_trans'=>true,
                'buy_trans'=>false
            ];
            $this->blocksTransTableCreate( $params );
            $sql = "
                SELECT 
                    ROUND(
                            SUM(
                                IF(acc_debit_code = 361,
                                    IF(trans_status = 2, GET_PARTLY_PAYED(active_company_id , passive_company_id ,361),amount),
                                NULL)
                            )
                        ,2) AS amount_sell
                FROM 
                    tmp_trans_table";
            $debt=$this->get_value($sql);
            if( !$debt ){
                return false;
            }
            return $debt;
        }
        return false;        
    }
    
    public function DebtExpired( object $context ) {
        if( $context->company_id??false ){
            if($this->lastWidgetPcompId!=$context->company_id){
                $this->blockTransTableCreated=false;
                $this->lastWidgetPcompId=$context->company_id;
            }
            $params=(object)[
                'pcomp_id'=>$context->company_id,
                'sell_trans'=>true,
                'buy_trans'=>false
            ];
            $this->blocksTransTableCreate( $params );
            $sql = "
                SELECT 
                    ROUND(
                            SUM(
                                IF(acc_debit_code = 361,
                                    IF(trans_status = 2, GET_PARTLY_PAYED(active_company_id , passive_company_id ,361),amount),
                                NULL)
                            )
                        ,2) AS amount_sell
                FROM 
                    tmp_trans_table
                WHERE
                    due_date>NOW()";
            $debt=$this->get_value($sql);
            if( !$debt ){
                return false;
            }
            return $debt;
        }
        return false;        
    }
}
