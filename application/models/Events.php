<?php
require_once 'Catalog.php';
class Events extends Catalog{
    public $min_level=2;
    /*
     * event_statuses
     * undone
     * done
     * 
     */
    public $activeDatesGet=[];
    public function activeDatesGet() {//must be optimized
	$user_id = $this->Hub->svar('user_id');
	$user_level = $this->Hub->svar('user_level');
	$sql="SELECT 
		DISTINCT(DATE(event_date)) event_date
	    FROM 
		event_list 
	    WHERE 
		event_label<>'chat' 
		AND ( NOT event_is_private OR event_is_private AND (event_creator_user_id='$user_id' OR $user_level>=3) )
	    ORDER BY event_date DESC";
	return $this->get_list($sql);
    }
    
    public $listFetch=['\d\d\d\d-\d\d-\d\d','string'];
    public function listFetch( $date, $label=null ){
	$label_filter=$label?" AND event_label='$label'":'';
	$sql="
	    SELECT
		*,
		DATE_FORMAT(event_date,'%d.%m.%Y') date_dmy,
		(SELECT nick FROM user_list WHERE user_id=created_by) created_by,
		(SELECT nick FROM user_list WHERE user_id=modified_by) modified_by,
		IF(event_status='undone' AND DATE(event_date)='$date','pending',event_status) event_status
	    FROM
		event_list
	    WHERE
		(
		    DATE(event_date)='$date' 
		    OR DATEDIFF(event_date,'$date')%event_repeat=0
		    OR event_status='undone' AND DATE(event_date)<DATE('$date')
		) 
                AND event_label<>'chat' $label_filter 
	    ORDER BY event_status='undone' AND DATE(event_date)<DATE(NOW()),event_label,event_priority IS NULL,event_priority,event_target";
	return $this->get_list($sql);
    }
    
    public $eventGet=['int'];
    public function eventGet( $event_id ){
	$this->check($event_id,'int');
	$sql="SELECT
		*,
		DATE_FORMAT(event_date,'%d.%m.%Y') event_date,
		(SELECT nick FROM user_list WHERE user_id=created_by) created_by,
		(SELECT nick FROM user_list WHERE user_id=modified_by) modified_by,
		event_status
	    FROM
		event_list
	    WHERE
		event_id='$event_id'";
	return $this->get_row($sql);
    }
    
    public $eventDelete=['int'];
    public function eventDelete( $event_id ){
	return $this->delete("event_list",['event_id'=>$event_id]);
    }
    
    public $eventUpdate=['int','\w+','raw'];
    public function eventUpdate( $event_id, $field, $value ){
	$this->Hub->set_level(2);
	return $this->update('event_list', [$field=>$value], ['event_id'=>$event_id]);
    }
    
    public $eventSave=['int'];
    public function eventSave( $event_id ){
	$this->Hub->set_level(2);
	$event=[
	    'event_date'=>$this->request('event_date'),
	    'event_priority'=>$this->request('event_priority'),
	    'event_name'=>$this->request('event_name','raw'),
	    'event_label'=>$this->request('event_label','raw'),
	    'event_target'=>$this->request('event_target','raw'),
	    'event_place'=>$this->request('event_place','raw'),
	    'event_note'=>$this->request('event_note','raw'),
	    'event_descr'=>$this->request('event_descr','raw'),
	    'event_repeat'=>$this->request('event_repeat'),
	    'event_status'=>$this->request('event_status'),
	    'event_liable_user_id'=>$this->request('event_liable_user_id'),
	    'event_is_private'=>$this->request('event_is_private'),
	    'modified_by'=>$this->Hub->svar('user_id')
	];
	if( !$event_id ){
	    $event['created_by']=$this->Hub->svar('user_id');
	    return $this->create('event_list', $event);
	}
	return $this->update('event_list', $event, ['event_id'=>$event_id]);
    }
    
    public $eventMove=['int','\d\d\d\d-\d\d-\d\d','string','\d\d\d\d-\d\d-\d\d','string'];
    public function eventMove( $event_id, $newdate, $mode=null, $olddate=null, $label=null ){
	if( $mode=='all' ){
	    $this->query("UPDATE event_list SET event_date='$newdate' WHERE DATE(event_date)='$olddate' AND event_label='$label'");
	    return $this->db->affected_rows();
	}
	return $this->update('event_list',['event_date'=>$newdate],['event_id'=>$event_id]);
    }
    
    public $eventViewGet=['label'=>'string','event_date'=>'\d\d\d\d-\d\d-\d\d','out_type'=>'string'];
    public function eventViewGet($label,$event_date,$out_type){	
	$rows=$this->listFetch($event_date,$label);
	$dump=[
	    'tpl_files'=>$this->Hub->acomp('language').'/EventList.xlsx',
	    'title'=>"Список Заданий",
	    'user_data'=>[
		'email'=>$this->Hub->svar('pcomp')?$this->Hub->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'view'=>[
		'label'=>$label,
		'date'=>date('d.m.Y',  strtotime($event_date)),
		'rows'=>$rows
	    ]
	];
	$ViewManager=$this->Hub->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}
