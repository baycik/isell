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
    public function activeDatesGet() {//must be optimized
	$user_id = $this->Base->svar('user_id');
	$user_level = $this->Base->svar('user_level');
	$sql="SELECT 
		DISTINCT(DATE(event_date)) event_date
	    FROM 
		event_list 
	    WHERE 
		event_label<>'chat' 
		AND ( NOT event_is_private OR event_is_private AND (event_user_id='$user_id' OR $user_level>=3) )
	    ORDER BY event_date DESC";
	return $this->get_list($sql);
    }
    
    public function listFetch( $date, $label=null ){
	$this->check($date,'\d\d\d\d-\d\d-\d\d');
	$this->check($label);
	$label_filter=$label?" AND event_label='$label'":'';
	$sql="
	    SELECT
		*,
		DATE_FORMAT(event_date,'%d.%m.%Y') date_dmy,
		(SELECT nick FROM user_list WHERE user_id=created_by) created_by,
		(SELECT nick FROM user_list WHERE user_id=modified_by) modified_by
	    FROM
		event_list
	    WHERE
		DATE(event_date)='$date' AND event_label<>'chat' $label_filter
	    ORDER BY event_label,event_priority IS NULL,event_priority,event_target";
	return $this->get_list($sql);
    }
    
    public function eventDelete( $event_id ){
	$this->check($event_id,'int');
	return $this->delete("event_list",['event_id'=>$event_id]);
    }
    public function eventSave( $event_id ){
	$this->Base->set_level(2);
	$this->check($event_id,'int');
	$event=[
	    'event_date'=>$this->request('event_date'),
	    'event_priority'=>$this->request('event_priority'),
	    'event_name'=>$this->request('event_name'),
	    'event_label'=>$this->request('event_label'),
	    'event_target'=>$this->request('event_target'),
	    'event_place'=>$this->request('event_place'),
	    'event_note'=>$this->request('event_note'),
	    'event_descr'=>$this->request('event_descr'),
	    'event_is_private'=>$this->request('event_is_private'),
	    'modified_by'=>$this->Base->svar('user_id')
	];
	if( !$event_id ){
	    $event['created_by']=$this->Base->svar('user_id');
	    return $this->create('event_list', $event);
	}
	return $this->update('event_list', $event, ['event_id'=>$event_id]);
    }
    
    public function eventMove( $olddate, $newdate, $event_id, $label, $mode ){
	$this->check($event_id,'int');
	$this->check($olddate,'\d\d\d\d-\d\d-\d\d');
	$this->check($newdate,'\d\d\d\d-\d\d-\d\d');
	$this->check($label);
	if( $mode=='all' ){
	    $this->query("UPDATE event_list SET event_date='$newdate' WHERE DATE(event_date)='$olddate' AND event_label='$label'");
	    return $this->db->affected_rows();
	}
	return $this->update('event_list',['event_date'=>$newdate],['event_id'=>$event_id]);
    }
    public function eventViewGet(){
	$label=$this->request('label');
	$event_date=$this->request('event_date','\d\d\d\d-\d\d-\d\d');
	$out_type=$this->request('out_type');
	
	$rows=$this->listFetch($event_date,$label);
	$dump=[
	    'tpl_files'=>$this->Base->acomp('language').'/EventList.xlsx',
	    'title'=>"Список Заданий",
	    'user_data'=>[
		'email'=>$this->Base->svar('pcomp')?$this->Base->svar('pcomp')->company_email:'',
		'text'=>'Доброго дня'
	    ],
	    'view'=>[
		'label'=>$label,
		'date'=>date('d.m.Y',  strtotime($event_date)),
		'rows'=>$rows
	    ]
	];
	$ViewManager=$this->Base->load_model('ViewManager');
	$ViewManager->store($dump);
	$ViewManager->outRedirect($out_type);
    }
}
