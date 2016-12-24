<?php
require_once 'Catalog.php';
class Chat extends Catalog{
    public $min_level=1;
    public function getUserList(){
	$my_id = $this->Hub->svar('user_id');
        $sql="SELECT 
                user_id,
                user_login,
		CONCAT(first_name,' ',last_name) name,
		(SELECT 1 FROM event_list WHERE created_by=user_id AND event_status='undone' AND event_date<NOW() AND event_user_liable='$my_id' LIMIT 1) has_new
            FROM
                user_list";
        return $this->get_list($sql);
    }
    public function sendRecieve( $his_id='all' ){
	$msg=$this->request('message');
	$this->check($his_id,'int');
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
                event_user_liable='$his_id',
                event_descr='$msg',
                event_is_private=1,
		event_status='undone'";
        $this->query($sql);
    }

    private function setAsRead(){
	$this->query("UPDATE event_list SET event_status='done' WHERE event_id=@undone_id;");
    }
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
		IF(event_user_liable='$my_id',1,NULL) for_me,
		IF(@undone_id=0 AND event_user_liable='$my_id' AND event_status='undone' AND event_label='Chat',@undone_id:=event_id,0) undone_id
	    FROM 
		event_list
	    WHERE
		(event_user_liable='$my_id' AND created_by='$his_id') OR (event_user_liable='$his_id' AND created_by='$my_id')
	    ORDER BY event_date DESC
	    LIMIT $limit) t
		ORDER BY event_date
	    ";
	$dialog=$this->get_list($sql);
	$this->setAsRead();
        return ['dialog'=>$dialog,'has_new'=>$this->checkNew()];
    }
    public function checkNew(){
	$my_id = $this->Hub->svar('user_id');
	$sql="SELECT 
		COUNT(*) 
	    FROM 
		event_list 
	    WHERE 
		event_status='undone' AND event_date<NOW() AND event_user_liable='$my_id'";
	return $this->get_value($sql);
    }
}
