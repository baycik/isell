<?php
require_once 'Catalog.php';
class Chat extends Catalog{
    public $min_level=1;
    public function getUserList(){
        $sql="SELECT 
                user_id,
                user_login,
		CONCAT(first_name,' ',last_name) name,
		MAX( IF(event_status=1,1,0) ) has_new
            FROM
                user_list
                    LEFT JOIN
                event_list ON event_user_id=user_id
		GROUP BY user_id";
        return $this->get_list($sql);
    }
    public function sendRecieve( $he='all' ){
	$msg=$this->request('message');
	$this->check($he);
        if( $he && $msg ){
            $this->addMessage($he, $msg);
        }
        return $this->getMessages($he);
    }
    private function addMessage( $he, $msg ){
        $user_id = $this->Base->svar('user_id');
        $sql="INSERT INTO
                event_list
              SET 
                event_label='Chat',
                event_date=NOW(),
                event_user_id='$user_id',
                event_target='$he',
                event_descr='$msg',
                event_is_private=1,
		event_status=1";
        $this->query($sql);
    }
    private function getMessages( $he ){
        $me = $this->Base->svar('user_login');
	$this->query("SET @unread_id=0;");
        $sql="SELECT
            event_list.*,
            DATE_FORMAT(event_date,'%H:%i:%s') time,
            IF(event_target='$me' OR event_target='all',1,NULL) for_me,
            event_target reciever,
            user_login sender,
	    event_status=1 unread,
	    IF( (event_target='$me' OR event_target='all') AND @unread_id=0 AND event_status=1,@unread_id:=event_id,0) unread_id
                FROM
                    event_list
                        JOIN
                    user_list ON event_user_id=user_id
                WHERE 
                    event_label='Chat' 
                HAVING
                    IF('$he'='all',
                        reciever='all' OR reciever='$me',
                        sender='$me' AND reciever='$he' OR sender='$he' AND reciever='$me')
                ORDER BY event_date";
	$messages=$this->get_list($sql);
	$this->setAsRead();
        return ['msgs'=>$messages,'has_new'=>$this->checkNew()];
    }
    private function setAsRead(){
	$this->query("UPDATE event_list SET event_status=2 WHERE event_id=@unread_id;");
    }
    public function checkNew(){
	$me = $this->Base->svar('user_login');
	$sql="SELECT COUNT(*) FROM event_list WHERE event_status=1 AND (event_target='all' OR event_target='$me')";
	return $this->get_value($sql);
    }
}
