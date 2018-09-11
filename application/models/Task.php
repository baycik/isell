<?php

class Task extends Events {

    private $currentTask = [];
    public $doNext = [];

    public function doNext() {
	$user_id = $this->Hub->svar('user_id');
	echo $sql = "SELECT
		*
	    FROM
		event_list
	    WHERE
		event_label='-TASK-'
		AND event_status='undone'
		AND (event_liable_user_id='$user_id' OR event_liable_user_id IS NULL OR event_liable_user_id='')
		AND event_date<NOW()
	    ORDER BY event_date ASC
	    LIMIT 1";
	$this->currentTask = $this->get_row($sql);
	if ($this->currentTask) {
	    return $this->execute_task();
	}
    }

    
    private function execute_task(){
	$this->log("TASK {$this->currentTask->event_name} is executing");
	//$this->eventUpdate($this->currentTask->event_id, 'event_status', 'executing');
	if ( $this->execute_program() ) {
	    $this->currentTask->event_status = 'done';
	    if ($this->currentTask->event_repeat !='') {
		$this->postpone($this->currentTask->event_repeat . " DAY_MINUTE");
		$this->currentTask->event_status = 'undone';
	    }
	} else {
	    //$this->postpone("1 HOUR");
	    $this->currentTask->event_status = 'undone';
	}
	$this->currentTask->event_date_done=date("Y-m-d H:i");
	return $this->saveTask();	
    }
    
    private function execute_program() {
	if ( !isset($this->currentTask->event_program )) {
	    return true;
	}
	$program = json_decode($this->currentTask->event_program);
	$program_length = count($program->commands);
	for ($i=0; $i < $program_length; $i++) {
	//$this->log("TASK program");
	    $command = $program->commands[$i];
	    if ( $this->currentTask->event_target > $i) {
		continue;
	    }
	    print_r($command);
	    print_r($this->currentTask);
	    $this->currentTask->event_note = $this->execute_command($command, $this->currentTask->event_note);
	    $this->currentTask->event_target = $i+1;
	    if ($this->currentTask->event_target == $program_length) {
		$this->currentTask->event_target = 0;
		return true;
	    }
	    if ($command->async == true) {
		break;
	    }
	}
	return false;
    }

    private function execute_command($command, $previous_return) {
	$args = [];
	if (is_array($command->arguments)) {
	    foreach ($command->arguments as $arg) {
		if ($arg == '-PREVIOUS-RETURN-') {
		    $arg = $previous_return;
		}
		$args[] = $arg;
	    }
	}
	restore_error_handler ();
	restore_exception_handler ();
	try{
	    $Model = $this->Hub->load_model($command->model);
	    $return = call_user_func_array([$Model, $command->method], $args);
	} catch (Exception $ex) {
	    $return = $ex->getMessage();
	    $this->log("TASK {$this->currentTask->event_name}[$Model, $command->method],$command->arguments  EXECUTION FAILED ERROR: $return");
	    return false;
	}
	$this->log("TASK {$this->currentTask->event_name} [$Model, $command->method],$command->arguments RETURNED VALUE:$return");
	return $return;
    }

    private function saveTask() {
	$user_id = $this->Hub->svar('user_id');
	$this->currentTask->modified_by=$user_id;
	return $this->update('event_list', $this->currentTask, ['event_id' => $this->currentTask->event_id]);
    }

    private function postpone($interval) {
	$this->currentTask->event_date=$this->get_value("SELECT DATE_ADD('{$this->currentTask->event_date}',INTERVAL $interval)");
	$this->log("TASK {$this->currentTask->event_name} postponed $interval");
    }

    public $taskListFetch=[];
    public function taskListFetch(){
	$sql="
	    SELECT
		*,
		DATE_FORMAT(event_date,'%d.%m.%Y') event_date_dmy,
		(SELECT nick FROM user_list WHERE user_id=created_by) created_by,
		(SELECT nick FROM user_list WHERE user_id=modified_by) modified_by
	    FROM
		event_list
	    WHERE
		event_label='-Task-'
	    ORDER BY event_status='undone',event_priority";
	return $this->get_list($sql);
    }
}