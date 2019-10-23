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
		AND event_label<>'-Task-'
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
                AND event_label<>'chat'
		AND event_label<>'-Task-' $label_filter 
	    ORDER BY event_status='undone' AND DATE(event_date)<DATE(NOW()),event_label,event_priority IS NULL,event_priority,event_target";
	return $this->get_list($sql);
    }
    
    public $eventGet=['int'];
    public function eventGet( $event_id ){
	$sql="SELECT
		*,
		DATE_FORMAT(event_date,'%d.%m.%Y') event_date_dmy,
		(SELECT nick FROM user_list WHERE user_id=created_by) created_by,
		(SELECT nick FROM user_list WHERE user_id=modified_by) modified_by,
		event_status
	    FROM
		event_list
	    WHERE
		event_id='$event_id'";
	return $this->get_row($sql);
    }
    
    public function eventDeleteDocumentTasks( int $doc_id ){
        return $this->delete("event_list",['doc_id'=>$doc_id,'event_label'=>'-TASK-']);
    }
    
    public $eventDelete=['int'];
    public function eventDelete( $event_id ){
	return $this->delete("event_list",['event_id'=>$event_id]);
    }
    
    public function eventChange($event_id, $event){
        $event['modified_by']=$this->Hub->svar('user_id');
	return $this->update('event_list', $event, ['event_id'=>$event_id]);
    }
    
    public $eventUpdate=['int','\w+','raw'];
    public function eventUpdate( $event_id, $field, $value ){
	$this->Hub->set_level(2);
	return $this->update('event_list', [$field=>$value], ['event_id'=>$event_id]);
    }
    
    public function eventCreate($event){
        $event['created_by']=$this->Hub->svar('user_id');
        $event['event_creator_user_id']=$this->Hub->svar('user_id');
        return $this->create('event_list', $event);
    }
    
    public $eventSave=[
        'event_id'=> 'int',
        'doc_id'=>'int',
        'event_date'=>'string',
        'event_priority'=>'string',
        'event_name'=>['raw',''],
        'event_label'=>['raw',''],
        'event_target'=>['raw',''],
        'event_place'=>['raw',''],
        'event_note'=>['raw',''],
        'event_descr'=>['raw',''],
        'event_program'=>['raw',''],
        'event_repeat'=>'string',
        'event_status'=>'string',
        'event_liable_user_id'=>'string',
        'event_is_private'=>'string',
        ];
    public function eventSave( 
            $event_id,
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
            $event_is_private ){
	$this->Hub->set_level(2);
	$event=[
	    'doc_id'=>$doc_id,
	    'event_date'=>$event_date,
	    'event_priority'=>$event_priority,
	    'event_name'=>$event_name,
	    'event_label'=>$event_label,
	    'event_target'=>$event_target,
	    'event_place'=>$event_place,
	    'event_note'=>$event_note,
	    'event_descr'=>$event_descr,
	    'event_program'=>$event_program,
	    'event_repeat'=>$event_repeat,
	    'event_status'=>$event_status,
	    'event_liable_user_id'=>$event_liable_user_id,
	    'event_is_private'=>$event_is_private
	];
	if( !$event_id ){
            return $this->eventCreate($event);
	}
	return $this->eventChange($event_id, $event);
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
    
    public function Topic( string $topic ){
        $this->topic=$topic;
        return $this;
    }
    public function subscribe( string $model, string $method, string $param='' ){
        $event_liable_user_id=$this->Hub->svar('user_id');
        $event=[
            'event_place'=>$model,
            'event_target'=>$method,
            'event_note'=>$param,
            'event_liable_user_id'=>$event_liable_user_id,
            'event_label'=>'-TOPIC-',
            'event_name'=>$this->topic
        ];
        $this->eventCreate($event);
    }
    public function unsubscribe( string $model, string $method, int $event_liable_user_id=NULL ){
        $user_case='';
        if( $event_liable_user_id ){
            $user_case=" AND event_liable_user_id='$event_liable_user_id'";
        }
        $this->query("DELETE FROM event_list WHERE event_label='-TOPIC-' AND event_name='$this->topic' AND event_place='$model' AND event_target='$method' $user_case");
    }
    public function publish(){
        $arguments=func_get_args();
        $listener_list=$this->get_list("SELECT event_place,event_target,event_liable_user_id,event_note FROM event_list WHERE event_label='-TOPIC-' AND event_name='$this->topic'");
        $previuos_return=null;
        foreach($listener_list as $listener){
            $Model=$this->Hub->load_model($listener->event_place);
            $method=$listener->event_target;
            $arguments[]=$listener->event_note;//custom registerer parameter
            $arguments[]=&$previuos_return;//previous events results
            try{
                $previuos_return=call_user_func_array([$Model, $method],$arguments);
            } catch (Exception $ex) {
                $this->unsubscribe( $listener->event_place, $listener->event_target, $listener->event_liable_user_id );
                $this->log("Topic subscriber '{$listener->event_place}->{$listener->event_target}' has been removed due to error: ".$ex);
            }
        }
        return $previuos_return;
    }
}
