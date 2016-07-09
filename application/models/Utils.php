<?php
require_once 'Catalog.php';
class Utils extends Catalog{

    //////////////////////////////////////
    //FOLOWING FUNCTIONS ARE DEPRECATED
    //////////////////////////////////////
    public function spellAmount($number, $unit = NULL, $return_cents = true) {
	if (!$unit) {
	    $unit[0] = array('копійка', 'копійки', 'копійок');
	    $unit[1] = array('гривня', 'гривні', 'гривень');
	    $unit[2] = array('тисяча', 'тисячи', 'тисяч');
	    $unit[3] = array('мільон', 'мільони', 'мільонів');
	}
	$millions = $this->getNumberPosition($number, 1000000, 100);
	$thousands = $this->getNumberPosition($number, 1000, 100);
	$ones = $this->getNumberPosition($number, 1, 100);
	$cents = $this->getNumberPosition($number * 100, 1, 10);

	$str = $this->spellNumber($millions, $unit[3]) . ' ' . $this->spellNumber($thousands, $unit[2]) . ' ' . $this->spellNumber($ones, $unit[1]) . ' ' . $this->spellNumber($cents, $unit[0], $return_cents);
	$str = trim($str);
	return mb_strtoupper(mb_substr($str, 0, 1, 'utf-8'), 'utf-8') . mb_substr($str, 1, mb_strlen($str) - 1, 'utf-8');
    }

    public function spellNumber($number, $units=null, $ret_number = false) {
	$hundreds_i = $this->getNumberPosition($number, 100, 1);
	$tens_i = $this->getNumberPosition($number, 10, 1);
	$ones_i = $this->getNumberPosition($number, 1, 1);
	if (!($hundreds_i || $tens_i || $ones_i) && !$ret_number){
	    return '';
	}
	if( $units ){
	    if ($ones_i === 1 && $tens_i != 1){
		$unit = $units[0];
	    } else 
	    if ($ones_i > 1 && $ones_i < 5){
		$unit = $units[1];
	    } else {
		$unit = $units[2];
	    }
	    if ($ret_number) {
		if ($number < 10)
		    return "0$number $unit";
		return "$number $unit";
	    }
	} else {
	    $unit='';
	}

	$ones = array("", "одна", "дві", "три", "чотири", "п'ять", "шість", "сім", "вісім", "дев'ять");
	$tens = array("", "десять", "двадцять", "тридцять", "сорок", "п'ятдесят", "шістдесят", "сімдесят", "вісімдесят", "дев'яносто");
	$teens = array("", "одинадцять", "дванадцять", "тринадцять", "чотирнадцять", "п'ятнадцять", "шістнадцять", "сімнадцять", "вісімнадцять", "дев'ятнадцять");
	$hundreds = array("", "сто", "двісті", "триста", "чотириста", "п'ятсот", "шістсот", "сімсот", "вісімсот", "дев'ятсот");

	if ($tens_i == 1){
	    return "$hundreds[$hundreds_i] $teens[$ones_i] $unit";
	}
	else{
	    return "$hundreds[$hundreds_i] $tens[$tens_i] $ones[$ones_i] $unit";
	}
    }

    private function getNumberPosition($number, $position, $range = 1) {//DEPRECATED
	$number-=$position * 10 * $range * floor($number / $position / 10 / $range);
	return floor($number / $position);
    }
    public function getLocalDate($tstamp) {
	$time = strtotime($tstamp);
	$months = array("січня", "лютого", "березня", "квітня", "травня", "червня", "липня", "серпня", "вересня", "жовтня", "листопада", "грудня");
	return date('d', $time) . ' ' . $months[date('m', $time) - 1] . ' ' . date('Y', $time);
    }
    ///////////////////////////////////////////
    //END OF DEPRECATED FUNCTIONS
    ///////////////////////////////////////////
    
    
    
    
    
    
    
    /////////////////////////////
    //SMS FUNCTIONS
    /////////////////////////////
    public function sendSms() {
	$number=$this->request('number');
	$body=$this->request('body');
	if (!$this->Base->pref('SMS_SENDER') || !$this->Base->pref('SMS_USER') || !$this->Base->pref('SMS_PASS')) {
	    $this->Base->msg("Настройки для отправки смс не установленны");
	    return false;
	}
	if (!in_array('https', stream_get_wrappers())) {
	    $this->Base->msg("Sms can not be sent. https is not available");
	    return false;
	}
	try {
	    if (time() - $this->Base->svar('smsSessionTime') * 1 > 25 * 60) {
		$this->Base->svar('smsSessionTime', time());
		$sid = json_decode(file_get_contents("https://integrationapi.net/rest/User/SessionId?login=" . $this->Base->pref('SMS_USER') . "&password=" . $this->Base->pref('SMS_PASS')));
		$this->Base->svar('smsSessionId', $sid);
	    }
	    $post_vars = array(
		'sessionId' => $this->Base->svar('smsSessionId'),
		'sourceAddress' => $this->Base->pref('SMS_SENDER'),
		'destinationAddress' => $number,
		'data' => $body
	    );
	    $opts = array(
		'http' => array(
		    'method' => "POST",
		    'content' => http_build_query($post_vars)
		)
	    );
	    $msg_ids = json_decode(
		    file_get_contents('https://integrationapi.net/rest/Sms/Send', false, stream_context_create($opts))
	    );
	    if (!$msg_ids[0])
		return false;
	} catch (Exception $e) {
	    $this->Base->svar('smsSessionTime', 0);
	    /*
	     * Make smsSid expire to try again
	     */
	    return false;
	}
	return true;
    }
    /////////////////////////////
    //EMAIL FUNCTIONS
    /////////////////////////////
    private function sendEmail($to,$subject,$body,$file=null){
        $this->Base->set_level(1);
        $this->load->library('email');
        $this->email->initialize([
            'useragent'=>'iSell',
            'protocol'=>'smtp',
            'charset'=>'utf8',
	    'smtp_timeout'=>10,
            'smtp_host'=>$this->Base->pref('SMTP_SERVER'),
            'smtp_user'=>$this->Base->pref('SMTP_USER'),
            'smtp_pass'=>$this->Base->pref('SMTP_PASS'),
	    'smtp_port'=>$this->Base->pref('SMTP_PORT'),
	    'smtp_crypto'=>$this->Base->pref('SMTP_CRYPTO')
        ]);
	$this->email->set_newline("\r\n");
        $this->email->from($this->Base->pref('SMTP_SENDER_MAIL'),$this->Base->pref('SMTP_SENDER_NAME'));
        $this->email->to($to);
	if( $this->Base->pref('SMTP_SEND_COPY') ){
	    $this->email->cc($this->Base->pref('SMTP_SENDER_MAIL'));
	}
        $this->email->subject($subject);
        $this->email->message($body);
        if( $file ){
	    $this->email->attach($file['data'], 'attachment', $file['name'], $file['mime']);
	}
        $ok=$this->email->send(false);
        if( !$ok ){
            $err=$this->email->print_debugger(['headers', 'subject', 'body']);
            $this->Base->msg($err);
        }
        return $ok;
    }
    public function postEmail(){
	$to=$this->input->get_post('to');
	$subject=$this->input->get_post('subject');
	$body=$this->input->get_post('body');
	$dump_id=$this->input->get_post('dump_id');
        $out_type=$this->input->get_post('out_type');
        $send_file=$this->input->get_post('send_file');
	$file=$send_file?$this->generateFile($dump_id,$out_type,$subject):null;
	return $this->sendEmail($to, $subject, $body, $file);
    }
    private $mimes=[
        '.html'=>'text/html',
        '.xls'=>'application/vnd.ms-excel',
        '.xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        '.pdf'=>'application/pdf',
        '.xml'=>'text/xml'
        ];
    private function generateFile($dump_id,$out_type='.xlsx',$subject='file'){
        $ViewManager=$this->Base->load_model('ViewManager');
	$file_data=$ViewManager->getFile($dump_id,$out_type);
	if( $file_data ){
	    return [
		'data'=>$file_data,
		'mime'=>$this->mimes[$out_type],
		'name'=>str_replace(' ', '_', $subject).$out_type
	    ];
	}
	$this->Base->response(0);
        return null;
    }
    /////////////////////////////
    //TREE MAINTAINANCE FUNCTIONS
    /////////////////////////////
    public function treeRecalculate(){
        foreach(['acc_tree','companies_tree','stock_tree'] as $table){
            $this->treePathRecalculate($table, 0);
            $this->treeTopRecalculate($table);
        }
    }
    private function treeTopRecalculate($table){
        $res = $this->db->query("SELECT branch_id,path FROM $table WHERE parent_id=0");
	foreach ($res->result() as $row) {
            $this->db->query("UPDATE $table SET top_id='{$row->branch_id}' WHERE path LIKE '{$row->path}%'");
	}
	$res->free_result();        
    }
    private function treePathRecalculate( $table, $parent_id = 0) {
	$where="";
	if( $parent_id!==null ){
	    $where="parent_id=$parent_id";
	}
	$res = $this->db->query("SELECT * FROM $table WHERE $where");
	foreach ($res->result() as $row) {
	    $this->treeUpdatePath($table, $row->branch_id);
            $this->treePathRecalculate($table, $row->branch_id);
	}
	$res->free_result();
    }
    /////////////////////////////
    //SELF PRICE FUNCTIONS
    /////////////////////////////
    public function stockQtyRecalculate(){
	$sql="
	    UPDATE 
		stock_entries se
	    SET 
		se.product_quantity = 
		(SELECT 
			SUM(IF(doc_type = 2,de.product_quantity,- de.product_quantity)) calc_product_quantity
		    FROM
			document_entries de
			    JOIN
			document_list dl USING (doc_id)     
		    WHERE
			de.product_code=se.product_code AND (doc_type = 1 OR doc_type = 2) AND dl.is_commited = 1 AND dl.notcount = 0
		GROUP BY product_code)";
	$this->db->query($sql);
	return $this->db->affected_rows();
    }
    private function selfPriceOldApiRecalculate($idate,$fdate,$active_filter){
	$Document2=$this->Base->bridgeLoad('Document');
	$res = $this->db->query("SELECT doc_id,passive_company_id FROM document_list WHERE is_commited=1 AND doc_type=1 AND '$idate'<=cstamp AND cstamp<='$fdate' $active_filter ORDER BY passive_company_id");
	echo "SELECT doc_id,passive_company_id FROM document_list WHERE is_commited=1 AND doc_type=1 AND '$idate'<=cstamp AND cstamp<='$fdate' $active_filter ORDER BY passive_company_id";
	if( $res ){
	    foreach ($res->result() as $row) {
		if ($Document2->Base->pcomp('company_id') != $row->passive_company_id){
		    $Document2->Base->selectPassiveCompany($row->passive_company_id);
		    echo " pcomp_id".$row->passive_company_id;
		}
		$doc_id = $row->doc_id;
		$Document2->selectDoc($doc_id);
		$Document2->updateTrans('profit_only');
	    }
	    $res->free_result();	    
	}
    }
    private function dmy2iso( $dmy ){
	$chunks=  explode('.', $dmy);
	return "$chunks[2]-$chunks[1]-$chunks[0]";
    }

    ////////////////////////////////////////////////////////////
    // SELF RECALCULATION FUNCTIONS
    ////////////////////////////////////////////////////////////
    private function selfPriceCorrectEntries(){
	$sql_update="UPDATE document_entries de JOIN tmp_self_calc tsc USING(doc_entry_id) SET de.self_price=tsc.sp WHERE doc_type=1;";
	$this->db->query($sql_update);
    }
    private function selfPriceCreateTable($active_filter){
	$sql_vars="SET @i:=0,@pointer:=0,@current_code:='',@qty_left1:=0,@qty_left:=0,@current_self_price=0.00;";
	$sql_tbl_drop="DROP TEMPORARY TABLE IF  EXISTS tmp_self_calc;";
	$sql_tbl_create="CREATE TEMPORARY TABLE tmp_self_calc AS (SELECT 
		doc_entry_id,
		doc_type,
		product_code,
		product_quantity,
		self_price,
		IF(product_code <> @current_code,(@current_code:=product_code) + (@qty_left:=0) + (@current_self_price:=0),1)*0 x,
		    IF(doc_type = 2 AND NOT is_reclamation,@current_self_price:=(self_price*product_quantity+COALESCE(@current_self_price,0)*@qty_left)/(product_quantity+@qty_left),0 ) xx,
		IF(doc_type = 2, (@qty_left:=@qty_left + product_quantity), (@qty_left:=@qty_left - product_quantity) ) qty_left,
		@current_self_price sp,
		i
	     FROM (
		SELECT 
		    *,
		    IF(product_code <> @current_code,(@current_code:=product_code) + (@qty_left1:=0),1)*0 x,
		    IF(doc_type = 2, (@qty_left1:=@qty_left1 + product_quantity), (@qty_left1:=@qty_left1 - product_quantity) ) qty_left,
		    IF(@pointer,IF(doc_type=1,@pointer,@pointer-5),@i:=@i+10) i,
		    IF(@qty_left1<0,@pointer:=@i,@pointer:=0) pointer
		FROM
		    (SELECT 
			doc_entry_id,
			doc_type,
			is_reclamation,
			product_code,
			product_quantity,
			self_price
		    FROM
			document_entries
			    JOIN 
			document_list USING (doc_id)
		    WHERE
			notcount = 0 AND is_commited = 1 $active_filter
		    ORDER BY product_code ,  cstamp) t ) tt
	    ORDER BY i);";
	$this->db->query($sql_vars);
	$this->db->query($sql_tbl_drop);
	$this->db->query($sql_tbl_create);
    }
    
    public function selfPriceInvoiceRecalculate($idatedmy,$fdatedmy,$active_mode=''){
	set_time_limit(300);
	$idate=$this->dmy2iso($idatedmy).' 00:00:00';
	$fdate=$this->dmy2iso($fdatedmy).' 23:59:59';
        if( $active_mode=='all_active' ){
            $active_filter='';
        } else {
            $active_filter=" AND active_company_id='".$this->Base->acomp('company_id')."'";
        }
	$this->selfPriceCreateTable($active_filter);
	$this->selfPriceCorrectEntries();
	$this->selfPriceStockAssign();
	$this->selfPriceOldApiRecalculate($idate, $fdate, $active_filter);
    }
    
    private function selfPriceStockAssign(){
	$sql_vars="SET @current_product_code:='';";
	$sql_tbl_drop="DROP TEMPORARY TABLE IF  EXISTS tmp_stock_self;";
	$sql_tbl_create="
	    CREATE TEMPORARY TABLE tmp_stock_self AS(
	    SELECT 
		*
	    FROM
		(SELECT product_code,sp,qty_left FROM tmp_self_calc ORDER BY i DESC) ttt
	    WHERE
		IF(@current_product_code <> product_code,@current_product_code:=product_code,0));";
	$sql_update="UPDATE stock_entries JOIN tmp_stock_self USING(product_code) SET self_price=sp;";
	$this->db->query($sql_vars);	
	$this->db->query($sql_tbl_drop);	
	$this->db->query($sql_tbl_create);	
	$this->db->query($sql_update);	
	return $this->db->affected_rows();
    }
    
    public function selfPriceStockRecalculate($active_mode=''){   
        if( $active_mode=='all_active' ){
            $active_filter='';
        } else {
            $active_filter=" AND active_company_id='".$this->Base->acomp('company_id')."'";
        }
	$this->selfPriceCreateTable($active_filter);
 	return $this->selfPriceStockAssign();
    }
    
    
    ////////////////////////////////////////////////////////////
    // STOCK UTILS FUNCTIONS
    ////////////////////////////////////////////////////////////
    public function stockCalcMin( $parent_id, $period, $ratio ){
	$this->check($parent_id,'int');
	$this->check($period,'int');
	$this->check($ratio,'double');
	$branch_ids=$this->treeGetSub('stock_tree',$parent_id);
	$where="WHERE se.parent_id IN (".implode(',',$branch_ids).")";
	$stock_table="
	    UPDATE
		stock_entries se
	    SET
		product_wrn_quantity=
		(SELECT
		    ROUND(SUM(IF(TO_DAYS(NOW()) - TO_DAYS(dl.cstamp) <= $period,de.product_quantity,0))*$ratio/10)*10
		FROM
		    document_entries de
			JOIN
		    document_list dl ON de.doc_id=dl.doc_id AND dl.is_commited=1 AND dl.notcount=0 AND dl.doc_type=1
		WHERE 
		    de.product_code=se.product_code
		GROUP BY se.product_code) 
	    $where";
	$this->query($stock_table);
	return $this->db->affected_rows();
    }
    public function stockCalcIncomeOrder( $parent_id=0 ){
	$this->check($doc_id,'int');
	$having=$this->decodeFilterRules();
	$branch_ids=$this->treeGetSub('stock_tree',$parent_id);
	$where="product_wrn_quantity>product_quantity AND parent_id IN (".implode(',',$branch_ids).")";
	$sql="
	    SELECT
		product_code,
		IF(product_bpack,CEIL((product_wrn_quantity-product_quantity)/product_bpack)*product_bpack,product_wrn_quantity-product_quantity) qty
	    FROM
		stock_entries
		    JOIN
		prod_list USING(product_code)
	    WHERE $where
	    HAVING $having";
	$buy_order=$this->get_list($sql);
	$DocumentItems=$this->Base->load_model("DocumentItems");
	$doc_id=$DocumentItems->createDocument(2);//create buy document
	foreach($buy_order as $row){
	   $DocumentItems->entryAdd($doc_id,$row->product_code,$row->qty);
	}
	return $doc_id;
    }}
