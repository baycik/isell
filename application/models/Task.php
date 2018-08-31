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
	    $this->execute();
	}
    }
    
    public function addProgram( $event_id, $next_task=0, $commands=[] ){
	$program=[
	    'next_task'=>$next_task,
	    'commands'=>$commands
	];
	$program_json=  json_encode($program);
	$this->eventUpdate($event_id,'event_program',$program_json);
    }

    private function execute(){
	if( isset($this->currentTask->event_program) ){
	    $returned=[];
	    $program=json_decode($this->currentTask->event_program);
	    foreach($program->commands as $command){
		$Model=$this->Hub->load_model($command->model);
		$return=call_user_func_array([$Model, $command->method],$command->arguments);
		if( $return===false ){
		    break;
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