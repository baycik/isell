<?php
class Task extends Events{
    private $currentTask=[];
    
    public $doNext=[];
    public function doNext(){
	$user_id = $this->Hub->svar('user_id');
	$sql="SELECT
		*
	    FROM
		event_list
	    WHERE
		event_label='-TASK-'
		AND event_status='undone'
		AND (event_liable_user_id='$user_id' OR event_liable_user_id IS NULL)
	    ORDER BY event_date ASC
	    LIMIT 1";
	$this->currentTask=$this->get_row($sql);
	if( $this->currentTask ){
	    $this->eventUpdate($this->currentTask->event_id,'event_status','inprogress');
	    $ok=$this->execute();
	    if( $ok ){
		$new_status='done';
		if( $this->currentTask->event_repeat>0 ){
		    $this->postpone($this->currentTask->event_repeat." DAY");
		    $new_status='undone';
		}
	    } else {
		$this->postpone("1 HOUR");
		$new_status='undone';
	    }
	    $this->eventUpdate($this->currentTask->event_id,'event_status',$new_status);
	}
    }
    
    private function postpone( $interval ){
	return $this->query("UPDATE event_list SET event_date= DATE_ADD(event_date,INTERVAL $interval) WHERE event_id='{$this->currentTask->event_id}'");
    }
    
    
    public $addProgram=['event_id'=>'int'];
    public function addProgram( $event_id, $next_task=0, $commands=[] ){
	$commands=[
	    [
		'model'=>'DocumentItems',
		'method'=>'createDocument',
		'arguments'=>[1]
	    ]
	];
	$event_id=81;
	
	
	
	$program=[
	    'next_task'=>$next_task,
	    'commands'=>$commands
	];
	$program_json=  json_encode($program);
	return $this->eventUpdate($event_id,'event_program',$program_json);
    }

    private function execute(){
	if( isset($this->currentTask->event_program) ){
	    $returned=[];
	    $program=json_decode($this->currentTask->event_program);
	    foreach($program->commands as $command){
		$Model=$this->Hub->load_model($command->model);
		$return=call_user_func_array([$Model, $command->method],$command->arguments);
		if( $return===false ){
		    echo ("[$Model, $command->method],$command->arguments  EXECUTION FAILED");
		    return false;
		}
		$returned[]=$return;
	    }
	    return $returned;
	}
    }
}
/*

[
    'next_task'=>'event_id',
    'commands'=>[
	[
	    'model'=>'',
	    'method'=>'',
	    'arguments'=>[]
	]
    ]
];
 

 */