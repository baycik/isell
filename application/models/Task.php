<?php
class Task extends Catalog{
    
    public $doNext=[];
    public function doNext(){
	$user_id = $this->Hub->svar('user_id');
	$sql="SELECT
		event_id
	    FROM
		event_list
	    WHERE
		event_label='-TASK-'
		AND event_status='undone'
		AND (event_liable_user_id='$user_id' OR event_liable_user_id IS NULL)
	    ORDER BY event_date ASC
	    LIMIT 1";
	$event_id=$this->get_value($sql);
	$this->execute($event_id);
    }
    
    
    public function listFetch(){
	
    }

    public function add( $task ){
	
    }
    public function change( $task_id, $task ){
	
    }
    public function markDone( $task_id ){
	
    }

    public function remove( $task_id ){
	
    }
    private function execute( $event_id ){
	$task=$this->get('event_list',['event_id'=>$event_id]);
	
	
	echo $event_id;
	print_r( $task);
    }
}