<?php

class Task extends Events {

    private $currentTask = [];
    public $doNext = [];

    public function doNext() {
	$user_id = $this->Hub->svar('user_id');
	$sql = "SELECT
		*
	    FROM
		event_list
	    WHERE
		event_label='-TASK-'
		AND event_status='undone'
		AND (event_liable_user_id='$user_id' OR event_liable_user_id IS NULL)
	    ORDER BY event_date ASC
	    LIMIT 1";
	$this->currentTask = $this->get_row($sql);
	die(333);
	if ($this->currentTask) {
	    return $this->execute_task();
	}
    }

    
    private function execute_task(){
	die(333);
	$this->eventUpdate($this->currentTask->event_id, 'event_status', 'executing');
	if ( $this->execute_program() ) {
	    $this->currentTask->event_status = 'done';
	    if ($this->currentTask->event_repeat > 0) {
		$this->postpone($this->currentTask->event_repeat . " DAY");
		$this->currentTask->event_status = 'undone';
	    }
	} else {
	    //$this->postpone("1 HOUR");
	    $this->currentTask->event_status = 'undone';
	}
	$this->saveTask();	
    }
    
    private function execute_program() {
	
	
	
	if ( !isset($this->currentTask->event_program )) {
	    return true;
	}
	$program = json_decode($this->currentTask->event_program);
	$program_length = count($program);
	for ($i = 0; $i < $program_length; $i++) {
	    $command = $program->$i;
	    
	    print_r($command);
	    
	    
	    if ( $command->disabled || $this->currentTask->event_target > $i ) {
		continue;
	    }
	    $this->currentTask->event_note = $this->execute_command($command, $this->currentTask->event_note);
	    if ($command->async == true) {
		$this->currentTask->event_target = $i;
		return false;
	    }
	    if ($i == $program_length) {
		$this->currentTask->event_target = 0;
		return true;
	    }
	}
	return false;
    }

    private function execute_command($command, $previous_return) {
	$Model = $this->Hub->load_model($command->model);
	$args = [];
	if (is_array($command->arguments)) {
	    foreach ($command->arguments as $arg) {
		if ($arg == '-PREVIOUS-RETURN-') {
		    $arg = $previous_return;
		}
		$args[] = $arg;
	    }
	}
	$return = call_user_func_array([$Model, $command->method], $args);
	if ($return === false) {
	    echo ("[$Model, $command->method],$command->arguments  EXECUTION FAILED");
	    return false;
	}
	return $return;
    }

    private function saveTask() {
	return $this->update('event_list', $this->currentTask, ['event_id' => $this->currentTask->event_id]);
    }

    private function postpone($interval) {
	return $this->query("UPDATE event_list SET event_date= DATE_ADD(event_date,INTERVAL $interval) WHERE event_id='{$this->currentTask->event_id}'");
    }

    public $addProgram = ['event_id' => 'int'];

    public function addProgram($event_id, $commands = []) {
	$commands = [
	    [
		'model' => 'Maintain',
		'method' => 'backupDump',
		'arguments' => null,
		'async' => true,
		'disabled' => false
	    ],
	    [
		'model' => 'Maintain',
		'method' => 'backupDumpZip',
		'arguments' => ['-PREVIOUS-RETURN-'],
		'async' => true,
		'disabled' => false
	    ]
	];
	$event_id = 81;



	$program = [
	    'commands' => $commands
	];
	$program_json = json_encode($program);
	return $this->eventUpdate($event_id, 'event_program', $program_json);
    }

}

/*
 * event_note=returned value from previous command
 * event_target=pointer to current command


$program=   [
		'commands'=>[
		    [
			'model'=>'',
			'method'=>'',
			'arguments'=>[]
		    ]
		]
	    ];
 

 */