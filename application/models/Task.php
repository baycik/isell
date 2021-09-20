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
		AND ( event_status='undone' OR event_status='pending' OR  (event_status='executing' AND DATE_ADD(event_date_done,INTERVAL 1 HOUR)<NOW()) )
		AND (event_liable_user_id='$user_id' OR event_liable_user_id IS NULL OR event_liable_user_id='')
		AND event_date<NOW()
	    ORDER BY event_date ASC
	    LIMIT 1";
	$this->currentTask = $this->get_row($sql);
	if ($this->currentTask) {
            $this->Hub->silence_msg=true;
                $result=$this->execute_task();
            $this->Hub->silence_msg=false;
	    return $result;
	}
    }

    
    private function execute_task(){
	$this->logErrors();
        session_write_close();
        $this->currentTask->event_status = 'executing';
	$this->currentTask->event_date_done=date("Y-m-d H:i");
        $this->saveTask();
	$this->log("TASK '{$this->currentTask->event_name}' is started");
	$this->currentTask->event_status = 'undone';
	if ( $this->execute_program() ) {
	    $this->currentTask->event_status = 'done';
	    if ($this->currentTask->event_repeat !='') {
		$this->postpone("'{$this->currentTask->event_repeat}' DAY_MINUTE");
		$this->currentTask->event_status = 'pending';
	    }
	}
	return $this->saveTask();	
    }
    
    private function execute_program() {
	if ( !isset($this->currentTask->event_program ) ){
	    return true;
	}
	$program = json_decode($this->currentTask->event_program);
	$program_length = count($program->commands);
        $this->Hub->log_output_messages=true;
	for ($i=0; $i < $program_length; $i++) {
	    $command = $program->commands[$i];
	    if ( $this->currentTask->event_target > $i ) {
		continue;
	    }
	    $this->currentTask->event_note = $this->execute_command($command, $this->currentTask->event_note);
	    $this->currentTask->event_target = $i+1;
	    if ($this->currentTask->event_target == $program_length) {
		$this->currentTask->event_target = 0;
		return $this->currentTask->event_note;
	    }
	    if ($command->async == true) {
		break;
	    }
	}
	return false;
    }

    private function execute_command($command, $previous_return) {
        if( !is_array($command->arguments) ){
            $command->arguments=explode(',', $command->arguments??'');
        }
        foreach ($command->arguments as $arg) {
            if ($arg == '-PREVIOUS-RETURN-') {
                $arg = $previous_return;
            }
            $args[] = $arg;
        }
        try{
            $Model = $this->Hub->load_model($command->model);
            $return = call_user_func_array([$Model, $command->method], $args);
            $this->log("TASK {$this->currentTask->event_name} {$command->model}->{$command->method}(".implode(',',$args)."): RETURNED VALUE:'$return'");
        } catch (Exception $ex) {
            $return=false;
            $this->log("TASK ERROR {$this->currentTask->event_name} {$command->model}->{$command->method}(".implode(',',$args)."): $ex");
        }
	return $return;
    }
    
    private function logErrors(){
	global $_this;
	$_this=$this;
	function log_exception( $e ){
	    global $_this;
	    $message = "Type: " . get_class( $e ) . "; Message: {$e->getMessage()}; File: {$e->getFile()}; Line: {$e->getLine()};";
	    $_this->log($message);
            if( $e instanceof ErrorException ){
                $critical_exceptions =
                    1 * E_ERROR |
                    1 * E_PARSE |
                    1 * E_CORE_ERROR |
                    1 * E_COMPILE_ERROR |
                    1 * E_USER_ERROR |
                    1 * E_STRICT |
                    1 * E_RECOVERABLE_ERROR;
                if( $e->getSeverity() & $critical_exceptions == 0 ){
                    //only warning or notice no need to postpone
                    return;
                }
            }
            $_this->postpone("'0 1:0' DAY_MINUTE");
	}
	function log_error( $severity, $str, $file, $line ){
	    log_exception( new ErrorException( $str, 0, $severity, $file, $line ) );
	}
	function check_for_fatal(){
	    global $_this;
	    $_this->saveTask();
	    $error = error_get_last();
	    if ( $error["type"] == E_ERROR ){
		log_error( $error["type"], $error["message"], $error["file"], $error["line"] );
	    }
	}
	register_shutdown_function( "check_for_fatal" );
	set_error_handler( "log_error" );
	set_exception_handler( "log_exception" );
	//ini_set( "display_errors", "off" );
	error_reporting( E_ALL );
    }

    public function saveTask() {
	$user_id = $this->Hub->svar('user_id');
	$this->currentTask->modified_by=$user_id;
	return $this->update('event_list', $this->currentTask, ['event_id' => $this->currentTask->event_id]);
    }

    public function postpone($interval) {
	$this->currentTask->event_date=$this->get_value("SELECT DATE_ADD(NOW(),INTERVAL $interval)");//'{$this->currentTask->event_date}'
	$this->currentTask->event_status='pending';
	$this->log("TASK {$this->currentTask->event_name} postponed $interval");
    }
    
    public $taskListFetch=['offset'=>'int','limit'=>'int','sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json'];
    public function taskListFetch($offset,$limit,$sortby,$sortdir,$filter=null){
	if( empty($sortby) ){
	    $sortby="event_status<>'undone',event_status<>'pending',event_priority";
	    //$sortdir="DESC";
	}
	$having=$this->makeFilter($filter);
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
	    HAVING $having
	    ORDER BY $sortby $sortdir
	    LIMIT $limit OFFSET $offset";
	return $this->get_list($sql);
    }
}