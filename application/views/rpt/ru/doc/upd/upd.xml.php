<?php



//header("Content-type:text/plain");print_r($this->view);die();



$creation_time=strtotime($this->view->doc_view->tstamp);
$this->view->doc_view->creation_date=date("d.m.Y",$creation_time);
$this->view->doc_view->creation_time=date("H:i:s",$creation_time);