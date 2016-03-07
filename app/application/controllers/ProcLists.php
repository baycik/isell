<?php
require_once('iSellBase.php');
class ProcLists extends iSellBase{
    public function ProcLists(){
        $this->ProcessorBase(2);
    }
    public function onDefault(){
        $this->response_tpl('lists/lists_main.html');
    }
    public function onEventList(){
        $selected_label=$this->request('label',0,'');
        $selected_date=$this->request('date');
        $grid_query=$this->getGridQuery();

        $this->LoadClass('Lists');
        $table_data=$this->Lists->eventListData( $selected_label, $selected_date, $grid_query );
        $table_data['activeDates']=$this->Lists->getEventDates();
        $this->response($table_data);
    }
    public function onEventListViewOut(){
        $selected_label=$this->request('label',0,'');
        $selected_date=$this->request('date');
        $grid_query=$this->getGridQuery();
        $out_type=$this->request('out_type',0,'.print');

        $this->LoadClass('Lists');
        $view=$this->Lists->eventListData( $selected_label, $selected_date, $grid_query );
        $view['date']=$selected_date;
        $view['label']=$selected_label;

        $this->LoadClass('FileEngine');
        $this->FileEngine->assign( $view, 'xlsx/TPL_EventList.xlsx' );
        $this->FileEngine->show_controls=true;
        $this->FileEngine->send("EventList$out_type");
        exit;
    }
    public function onSaveEvent(){
        $event_id=$this->request('event_id',1);
        $eventObj=array();
        $eventObj['event_date']=$this->request('event_date');
        $eventObj['event_name']=$this->request('event_name');
        $eventObj['event_label']=$this->request('event_label');
        $eventObj['event_target']=$this->request('event_target');
        $eventObj['event_place']=$this->request('event_place');
        $eventObj['event_note']=$this->request('event_note');
        $eventObj['event_descr']=$this->request('event_descr');
        $eventObj['event_is_private']=$this->request('event_is_private',1);
        $this->LoadClass('Lists');
	header("X-isell-type:OK");
        $this->Lists->updateEvent( $event_id, $eventObj );
    }
    public function onDeleteEvent(){
	$delIds = $this->request('delIds', 3);
	$this->LoadClass('Lists');
	header("X-isell-type:OK");
        $this->Lists->deleteEvent( $delIds );        
    }
    public function onGetEventLabels(){
        $selected_day=$this->request('selectedDay');
        $this->LoadClass('Lists');
        $labels=$this->Lists->getEventLabels($selected_day);
        $this->response($labels);
    }
    public function onActiveEventDates(){
        $this->LoadClass('Lists');
        $dates=$this->Lists->getEventDates();
        $this->response($dates);
    }
}
?>