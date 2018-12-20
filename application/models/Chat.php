<?php
require_once 'Catalog.php';
class Chat extends Catalog{
    public $min_level=1;
    
    public $getUserList=[];
    public function getUserList(){
	$my_id = $this->Hub->svar('user_id');
        $sql="SELECT 
                user_id,
                user_login,
		CONCAT(first_name,' ',last_name) name,
                user_is_staff,
                TIMESTAMPDIFF(MINUTE,last_activity,NOW())<3 is_online, 
		(SELECT 1 FROM event_list WHERE created_by=user_id AND event_status='undone' AND event_date<NOW() AND event_liable_user_id='$my_id' LIMIT 1) has_new
            FROM
                user_list
            ORDER BY user_is_staff DESC,first_name
                ";
        return $this->get_list($sql);
    }
    
    public $sendRecieve=['int'];
    public function sendRecieve( $his_id='all' ){
	$msg=$this->request('message');
	if( $this->request('is_phone_sms','bool') ){
	    if( $this->sendPhoneSms($his_id, $msg) ){
		$msg="[sms] ".$msg;
	    }
	}
        if( $his_id && $msg ){
            $this->addMessage($his_id, $msg);
        }
	return $this->getDialog($his_id);
    }
    private function sendPhoneSms($his_id,$msg){
        if( $his_id>0 ){
            $user_phone=$this->get_value("SELECT user_phone FROM user_list WHERE user_id='$his_id'");
            $Utils=$this->Hub->load_model('Utils');
            $sender=$this->Hub->svar('user_sign');
           return $user_phone && $Utils->sendSms($user_phone,"$sender написал вам в чате: \n$msg");
        }
	return false;
    }
    private function addMessage( $his_id, $msg ){
        $my_id = $this->Hub->svar('user_id');
        $sql="INSERT INTO
                event_list
              SET 
                event_label='Chat',
                event_date=NOW(),
                created_by='$my_id',
                modified_by='$my_id',
                event_liable_user_id='$his_id',
                event_descr='$msg',
                event_is_private=1,
		event_status='undone'";
        $this->query($sql);
    }

    private function setAsRead(){
	$this->query("UPDATE event_list SET event_status='done' WHERE event_id=@undone_id;");
    }
    
    public $getDialog=['int','int'];
    public function getDialog( $his_id, $limit=15 ){
	$my_id = $this->Hub->svar('user_id');
	$this->query("SET @undone_id=0;");
	$sql="
	    SELECT * FROM (SELECT
		event_id,
		event_descr,
		event_priority,
		event_name,
		DATE_FORMAT(event_date,'%d.%m.%Y %H:%i') time,
		event_date,
		event_target,
		event_place,
		event_status,
		IF(event_label='Chat',1,0) is_chat,
		IF(event_liable_user_id='$my_id',1,NULL) for_me,
		IF(@undone_id=0 AND event_liable_user_id='$my_id' AND event_status='undone' AND event_label='Chat',@undone_id:=event_id,0) undone_id
	    FROM 
		event_list
	    WHERE
		(event_liable_user_id='$my_id' AND created_by='$his_id') OR (event_liable_user_id='$his_id' AND created_by='$my_id')
	    ORDER BY event_date DESC
	    LIMIT $limit) t
		ORDER BY event_date
	    ";
	$dialog=$this->get_list($sql);
	$this->setAsRead();
        return ['dialog'=>$dialog,'has_new'=>$this->checkNew('skip_tasks')];
    }
    
    public $checkNew=[];
    public function checkNew($mode){
	$my_id = $this->Hub->svar('user_id');
	$sql="SELECT 
		COUNT(*) 
	    FROM 
		event_list 
	    WHERE 
		event_status='undone' AND event_date<NOW() AND event_liable_user_id='$my_id'";
	$new_message_count=$this->get_value($sql);
        $this->query("UPDATE user_list SET last_activity=NOW() WHERE user_id='$my_id'");
	if( $new_message_count>0 ){
	    return $new_message_count;
	}
        if( $mode!=='skip_tasks' ){
            $this->Hub->load_model("Task")->doNext();
        }
    }
}
