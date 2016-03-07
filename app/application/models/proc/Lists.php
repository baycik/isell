<?php

require_once 'Data.php';

class Lists extends Data {

    public function updateEvent($event_id, $eventObj) {
	if ($event_id == 0)
	    $event_id = $this->addEvent();
	$set = "event_label='{$eventObj['event_label']}',event_name='{$eventObj['event_name']}',event_date='{$eventObj['event_date']}',event_target='{$eventObj['event_target']}',event_note='{$eventObj['event_note']}',event_place='{$eventObj['event_place']}',event_descr='{$eventObj['event_descr']}',event_is_private='{$eventObj['event_is_private']}'";
	$this->Base->query("UPDATE " . BAY_DB_MAIN . ".event_list SET $set WHERE event_id='$event_id'");
    }

    public function addEvent() {
	$user_id = $this->Base->svar('user_id');
	$this->Base->query("INSERT INTO " . BAY_DB_MAIN . ".event_list SET event_date=NOW(),event_user_id=$user_id");
	return mysql_insert_id();
    }
    public function deleteEvent( $delIds ){
	$this->deleteGridRows(BAY_DB_MAIN . ".event_list", $delIds);
    }

    public function getEventLabels($selected_day) {
	$user_id = $this->Base->svar('user_id');
	$user_level = $this->Base->svar('user_level');
	return $this->Base->get_list("SELECT event_label,COUNT(event_label) AS count FROM " . BAY_DB_MAIN . ".event_list WHERE event_status<1 AND DATE(event_date)='$selected_day' AND IF(event_is_private,IF(event_user_id='$user_id' OR $user_level=3,1,0),1) GROUP BY event_label");
    }

    public function getEventDates() {//must be optimized
	$user_id = $this->Base->svar('user_id');
	$user_level = $this->Base->svar('user_level');
	return $this->Base->get_list("SELECT DATE_FORMAT(event_date,'%Y-%m-%d') AS event_date FROM " . BAY_DB_MAIN . ".event_list WHERE event_status<1 AND IF(event_is_private,IF(event_user_id='$user_id' OR $user_level=3,1,0),1) GROUP BY event_date");
    }

    public function eventListData($selected_label, $selected_date, $table_query) {
	$user_id = $this->Base->svar('user_id');
	$user_level = $this->Base->svar('user_level');
	$select = array();
	$select[] = "event_id";
	$select[] = "event_is_private";
	$select[] = "IF(event_is_private,'lock Приватное событие','') AS is_private";
	$select[] = "event_label";
	$select[] = "DATE_FORMAT(event_date,'%d.%m.%Y') AS event_date";
	$select[] = "event_name";
	$select[] = "event_target";
	$select[] = "event_place";
	$select[] = "CONCAT(' ',event_note) AS event_note"; //for mob phones
	$select[] = "event_descr";
	$select[] = "IF(event_status=0,'time Не выполнено','ok Выполнено') AS event_status";
	$select = implode(',', $select);
	$where = "event_status<1 AND DATE(event_date)='$selected_date' AND event_label='$selected_label' AND IF(event_is_private,IF(event_user_id='$user_id' OR $user_level>3,1,0),1)";
	$order = 'ORDER BY event_date DESC';
	return $this->getGridData(BAY_DB_MAIN.'.event_list', $table_query, $select, $where, $order);
    }

}

?>
