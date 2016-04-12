<?php
require_once 'iSellBase.php';
class SyncInit extends iSellBase{
	private $pass='tetraftalate';
	private $target_url="http://plustrade.com.ua/";
	public function SyncInit(){
		$this->Sync=new Sync();
		$this->Sync->Base=$this;
		$this->ProcessorBase(1);
	}
	public function onDefault(){
		$ok=0;
		$err=array();
		$action_log=$this->Sync->fetchTableSyncList();
		//if( count($action_log['RD'])==0 )continue;
		
		$postdata=array(
				'action_log' => json_encode($action_log),
				'mod'  => 'SyncAccept',
				'rq'   => 'SyncAccept',
				'pass' => $this->pass
		);
		$result=$this->Sync->sendToGateway( $this->target_url, $postdata );
		$commited_actions=json_decode($result,true);
		if( isset($commited_actions['ok']) || $commited_actions['err'] ){
			$ok+=count($commited_actions['ok']);
			$err=$commited_actions['err'];
			$this->Sync->clearReplicationLog( $commited_actions['ok'] );
		}
		else{
			header('Content-type: text/html; charset=utf-8;');
			die('Sync accept response: '.$result);
		}
		$this->responseSyncResult( $ok, $err );
	}
	private function responseSyncResult( $ok, $err ){
		$err_num=count($err);
		$left=$this->Sync->countReplicationLog();
		header('Content-type: text/html; charset=utf-8;');
		header("Refresh: ".($left&&!$err_num?1:60)."; url=".$_SERVER['REQUEST_URI']);
		print "<html><head><style>body{margin:0px;padding:0px;font-size:10px;font-family:arial;}</style><body>";
		if( $left && $ok>0 ){
			$msg="<b>$ok/".($left+$ok)."</b> строчек синхронизированно";
		}
		else if( $ok ){
			$msg="<b>$ok</b> строчек синхронизированно";
		}
		else if( $err_num ){
			$msg="action_id: {$err[0][0]} -> {$err[0][1]}";
			$this->svar( 'session_msg', $msg );
		}
		else{
			$msg="Строчек для синхронизации нет";
		}
		echo "<nobr>$msg</nobr>";
		print "</body></html>";
		exit;
	}
	public function onFillRepLog(){
		$this->set_level(3);
		$table_name=$this->request('table');
		$this->Sync->fillRepLog($table_name);
	}
}

class Sync{
	public function fetchTableSyncList(){
		$limit=100;
		$table_names=$this->Base->get_list("SELECT DISTINCT action_table FROM bay.replication_log");
		$action_log=array();
		$action_log['RD']=array();
		foreach( $table_names as $table ){
			$table_name=$table['action_table'];
			$fields=$this->Base->get_field_list( $table_name );
			$action_log['RD']+=$this->Base->get_list("SELECT * FROM bay.replication_log rl LEFT JOIN bay.$table_name tn ON tn.".$fields['keys'][0]."=rl.action_key WHERE action_table='$table_name' ORDER BY action_stamp,action_id LIMIT $limit");
			//echo "SELECT * FROM bay.replication_log rl LEFT JOIN bay.$table_name tn ON tn.".$fields['keys'][0]."=rl.action_key WHERE action_table='$table_name' ORDER BY action_stamp,action_id LIMIT $limit";
		}
		return $action_log;
	}
	public function clearReplicationLog( $commited_ids, $flag ){
		$where="action_id='".implode("' OR action_id='",$commited_ids)."'";
		$this->Base->query("DELETE FROM bay.replication_log WHERE $where");
	}
	public function countReplicationLog(){
		return $this->Base->get_row("SELECT COUNT(*) FROM bay.replication_log",0);
	}
	public function fillRepLog( $table_name ){
		$fields=$this->Base->get_field_list( $table_name );
		$this->Base->query("INSERT IGNORE INTO bay.replication_log (action_table,action_type,action_key) SELECT '$table_name','R',".$fields['keys'][0]." FROM $table_name");
		return true;
	}
	public function sendToGateway( $gateway_url, $postdata ){
		set_time_limit(7);
		$context=stream_context_create(
			array(
				'http' =>array(
					'method'  => 'POST',
					'header'  => 'Content-type: application/x-www-form-urlencoded',
					'content' => http_build_query($postdata)
				)
			) 
		); 
		return file_get_contents( $gateway_url, false, $context );
	}
}
?>