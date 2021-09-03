<?php

require_once 'Catalog.php';

class Utils extends Catalog {

    //////////////////////////////////////
    //FOLOWING FUNCTIONS ARE DEPRECATED
    //////////////////////////////////////
    public $spellAmount = ['string', 'int', 'int'];

    public function spellAmount($number, $return_cents = true, $return_currency = true) {
        $blank_set = $this->Hub->pref('blank_set');
        if ($blank_set == 'ua') {
            $unit = [
                ['копійка', 'копійки', 'копійок'],
                ['гривня', 'гривні', 'гривень'],
                ['тисяча', 'тисячи', 'тисяч'],
                ['мільон', 'мільони', 'мільонів']
            ];
        }
        if ($blank_set == 'ru') {
            $unit = [
                ['копейка', 'копейки', 'копеек'],
                ['рубль', 'рубля', 'рублей'],
                ['тысяча', 'тысячи', 'тысяч'],
                ['миллион', 'миллиона', 'миллионов'],
                ['миллиард', 'милиарда', 'миллиардов']
            ];
        }
        $millions = $this->getNumberPosition($number, 1000000, 100);
        $thousands = $this->getNumberPosition($number, 1000, 100);
        $ones = $this->getNumberPosition($number, 1, 100);
        $cents = $this->getNumberPosition($number * 100, 1, 10);

        $str = $this->spellNumber($millions, $unit[3]) . ' '
                . $this->spellNumber($thousands, $unit[2]) . ' '
                . $this->spellNumber($ones, $return_currency ? $unit[1] : '') . ' '
                . $this->spellNumber($cents, $unit[0], $return_cents);
        $str = trim($str);
        return mb_strtoupper(mb_substr($str, 0, 1, 'utf-8'), 'utf-8') . mb_substr($str, 1, mb_strlen($str) - 1, 'utf-8');
    }

    public $spellNumber = ['string', 'string', 'int'];

    public function spellNumber($number, $units = null, $ret_number = false) {
        $hundreds_i = $this->getNumberPosition($number, 100, 1);
        $tens_i = $this->getNumberPosition($number, 10, 1);
        $ones_i = $this->getNumberPosition($number, 1, 1);
        if (!($hundreds_i || $tens_i || $ones_i) && !$ret_number) {
            return '';
        }
        if ($units) {
            if ($ones_i === 1 && $tens_i != 1) {
                $unit = $units[0];
            } else
            if ($ones_i > 1 && $ones_i < 5) {
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
            $unit = '';
        }

        $blank_set = $this->Hub->pref('blank_set');
        if ($blank_set == 'ua') {
            $ones = ["", "одна", "дві", "три", "чотири", "п'ять", "шість", "сім", "вісім", "дев'ять"];
            $tens = ["", "десять", "двадцять", "тридцять", "сорок", "п'ятдесят", "шістдесят", "сімдесят", "вісімдесят", "дев'яносто"];
            $teens = ["", "одинадцять", "дванадцять", "тринадцять", "чотирнадцять", "п'ятнадцять", "шістнадцять", "сімнадцять", "вісімнадцять", "дев'ятнадцять"];
            $hundreds = ["", "сто", "двісті", "триста", "чотириста", "п'ятсот", "шістсот", "сімсот", "вісімсот", "дев'ятсот"];
        }
        if ($blank_set == 'ru') {
            $ones = ["", "один", "два", "три", "четыре", "пять", "шесть", "семь", "восемь", "девять"];
            $tens = ["", "десять", 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто'];
            $teens = ["десять", 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать'];
            $hundreds = ["", 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот'];
        }
        if ($tens_i == 1) {
            return "$hundreds[$hundreds_i] $teens[$ones_i] $unit";
        } else {
            return "$hundreds[$hundreds_i] $tens[$tens_i] $ones[$ones_i] $unit";
        }
    }

    private function getNumberPosition($number, $position, $range = 1) {//DEPRECATED
        $number -= $position * 10 * $range * floor($number / $position / 10 / $range);
        return floor($number / $position);
    }

    public $getLocalDate = ['string'];

    public function getLocalDate($tstamp) {
        $time = strtotime($tstamp);
        $months = array("січня", "лютого", "березня", "квітня", "травня", "червня", "липня", "серпня", "вересня", "жовтня", "листопада", "грудня");
        return date('d', $time) . ' ' . $months[date('m', $time) - 1] . ' ' . date('Y', $time);
    }

    ///////////////////////////////////////////
    //END OF DEPRECATED FUNCTIONS
    ///////////////////////////////////////////

    private function russian_date($date_dot) {
        $date = explode(".", $date_dot);
        switch ($date[1]) {
            case 1: $m = 'января';
                break;
            case 2: $m = 'февраля';
                break;
            case 3: $m = 'марта';
                break;
            case 4: $m = 'апреля';
                break;
            case 5: $m = 'мая';
                break;
            case 6: $m = 'июня';
                break;
            case 7: $m = 'июля';
                break;
            case 8: $m = 'августа';
                break;
            case 9: $m = 'сентября';
                break;
            case 10: $m = 'октября';
                break;
            case 11: $m = 'ноября';
                break;
            case 12: $m = 'декабря';
                break;
        }
        return $date[0] . ' ' . $m . ' ' . $date[2];
    }

    /**
     * Возвращает сумму прописью
     * @author runcore
     * @uses morph(...)
     */
    private function num2str($num) {
        $nul = 'ноль';
        $ten = array(
            array('', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
            array('', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
        );
        $a20 = array('десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать');
        $tens = array(2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто');
        $hundred = array('', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот');
        $unit = array(// Units
            array('копейка', 'копейки', 'копеек', 1),
            array('рубль', 'рубля', 'рублей', 0),
            array('тысяча', 'тысячи', 'тысяч', 1),
            array('миллион', 'миллиона', 'миллионов', 0),
            array('миллиард', 'милиарда', 'миллиардов', 0),
        );
        //
        list($rub, $kop) = explode('.', sprintf("%015.2f", floatval($num)));
        $out = array();
        if (intval($rub) > 0) {
            foreach (str_split($rub, 3) as $uk => $v) { // by 3 symbols
                if (!intval($v))
                    continue;
                $uk = sizeof($unit) - $uk - 1; // unit key
                $gender = $unit[$uk][3];
                list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
                // mega-logic
                $out[] = $hundred[$i1]; # 1xx-9xx
                if ($i2 > 1)
                    $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3];# 20-99
                else
                    $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3];# 10-19 | 1-9
                // units without rub & kop
                if ($uk > 1)
                    $out[] = $this->morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
            } //foreach
        } else
            $out[] = $nul;
        $out[] = $this->morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]); // rub
        $out[] = $kop . ' ' . $this->morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]); // kop
        return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
    }

    /**
     * Склоняем словоформу
     * @ author runcore
     */
    private function morph($n, $f1, $f2, $f5) {
        $n = abs(intval($n)) % 100;
        if ($n > 10 && $n < 20)
            return $f5;
        $n = $n % 10;
        if ($n > 1 && $n < 5)
            return $f2;
        if ($n == 1)
            return $f1;
        return $f5;
    }

    /////////////////////////////
    //SMS FUNCTIONS
    /////////////////////////////
    
    private function phoneValidator($number){
        $num=preg_replace('/[^0-9]/','',$number);
        $num2=substr($num,-10);
        return "+7{$num2}";
    }
    public function sendTraccar( $number, $message ){
        $url = $this->Hub->pref('TRACCAR_GATEWAY');
        
        $chunk=70;
        $length=mb_strlen($message);
        if($length>$chunk){
            $count=ceil(mb_strlen($message)/$chunk);
            for($i=0;$i<$count;$i++){
                $cropped=mb_substr($message,$i*$chunk,$chunk,"UTF-8");
                $ok=$this->sendTraccar( $number, $cropped );
                if( !$ok ){
                    return false;
                }
            }
            return true;
        }
        $data = [
            'to' => $this->phoneValidator($number),
            'message' => $message
        ];
        $headers=[
            "Authorization: ".$this->Hub->pref('TRACCAR_AUTORIZATION')
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2); 

        $result = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (curl_error($curl)) {
            $this->log(curl_error($curl));
        }
        curl_close($curl);
        if( $httpcode==200 ){
            return true;
        }
        return false;
    }
    
    
    
    
    
    public $sendSms = ['to' => 'string', 'body' => 'string'];

    public function sendSms($number = null, $body = null) {
        if( $this->sendTraccar($number, $body) ){
            return true;
        }
        if (!$this->Hub->pref('SMS_SENDER') || !$this->Hub->pref('SMS_USER') || !$this->Hub->pref('SMS_PASS')) {
            $this->Hub->msg("Настройки для отправки смс не установленны");
            return false;
        }
        if (!in_array('https', stream_get_wrappers())) {
            $this->Hub->msg("Sms can not be sent. https is not available");
            return false;
        }
        if (time() - $this->Hub->svar('smsSessionTime') * 1 > 24 * 60) {
            $sid = json_decode(file_get_contents("https://integrationapi.net/rest/user/sessionId?login=" . $this->Hub->pref('SMS_USER') . "&password=" . $this->Hub->pref('SMS_PASS')));
            if (!$sid) {
                $this->Hub->msg('Authorization to SMS service failed');
                return false;
            }
            $this->Hub->svar('smsSessionId', $sid);
            $this->Hub->svar('smsSessionTime', time());
        }
        $post_vars = array(
            'SessionID' => $this->Hub->svar('smsSessionId'),
            'SourceAddress' => $this->Hub->pref('SMS_SENDER'),
            'DestinationAddresses' => $number,
            'Data' => $body
        );
        $opts = array(
            'http' => [
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => "POST",
                'content' => http_build_query($post_vars)
            ]
        );
        try{
            $response = file_get_contents('https://integrationapi.net/rest/Sms/SendBulk/', false, stream_context_create($opts));
            $msg_ids = json_decode($response);			
        } catch( Exception $e ){
            $this->log($e);
        }
        if (!$msg_ids[0]) {
            $this->Hub->msg('Sending SMS is failed');
            $this->Hub->svar('smsSessionTime', 0);
            return false;
        }
        return true;
    }

    /////////////////////////////
    //EMAIL FUNCTIONS
    /////////////////////////////
    public function sendEmail($to, $subject, $body, $file = null) {
        $this->Hub->set_level(1);
        $this->load->library('email');
        $this->email->initialize([
            'useragent' => 'iSell',
            'protocol' => 'smtp',
            'charset' => 'utf8',
            'smtp_timeout' => 10,
            'mailtype' => 'html',
            'smtp_host' => $this->Hub->pref('SMTP_SERVER'),
            'smtp_user' => $this->Hub->pref('SMTP_USER'),
            'smtp_pass' => $this->Hub->pref('SMTP_PASS'),
            'smtp_port' => $this->Hub->pref('SMTP_PORT'),
            'smtp_crypto' => $this->Hub->pref('SMTP_CRYPTO')
        ]);
        $this->email->set_newline("\r\n");
        $this->email->from($this->Hub->pref('SMTP_SENDER_MAIL'), $this->Hub->pref('SMTP_SENDER_NAME'));
        $this->email->to($to);
        if ($this->Hub->pref('SMTP_SEND_COPY')) {
            $this->email->cc($this->Hub->pref('SMTP_SENDER_MAIL'));
        }
        $this->email->subject($subject);
        $this->email->message($body);
        
        
        
        
        $body_text = strip_tags(preg_replace('/<style>[A-Za-z0-9-:\s.\/_;#\(\)\{\}%]{1,}<\/style>/', '',$body) );
        $this->email->set_alt_message($body_text);
        if ($file) {
            $this->email->attach($file['data'], 'attachment', $file['name'], $file['mime']);
        }
        $ok = $this->email->send(false);
        if (!$ok) {
            $err = $this->email->print_debugger(['headers', 'subject', 'body']);
            $this->Hub->msg($err);
        }
        return $ok;
    }

    public $postEmail = ['to' => 'string', 'subject' => 'string', 'body' => 'string', 'dump_id' => 'string', 'out_type' => 'string', 'send_file' => 'int'];

    public function postEmail($to, $subject, $body, $dump_id, $out_type, $send_file) {
        $file = $send_file ? $this->generateFile($dump_id, $out_type, $subject) : null;
        return $this->sendEmail($to, $subject, $body, $file);
    }

    private $mimes = [
        '.html' => 'text/html',
        '.xls' => 'application/vnd.ms-excel',
        '.xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        '.pdf' => 'application/pdf',
        '.xml' => 'text/xml'
    ];

    private function generateFile($dump_id, $out_type = '.xlsx', $subject = 'file') {
        $ViewManager = $this->Hub->load_model('ViewManager');
        $file_data = $ViewManager->getFile($dump_id, $out_type);
        if ($file_data) {
            return [
                'data' => $file_data,
                'mime' => $this->mimes[$out_type],
                'name' => str_replace(' ', '_', $subject) . $out_type
            ];
        }
        $this->Hub->response(0);
        return null;
    }

    /////////////////////////////
    //TREE MAINTAINANCE FUNCTIONS
    /////////////////////////////
    public $treesRecalculate = [];

    public function treesRecalculate() {
        foreach (['acc_tree', 'companies_tree', 'stock_tree'] as $table) {
            $this->treePathRecalculate($table, 0);
            $this->treeTopRecalculate($table);
        }
    }

    private function treeTopRecalculate($table) {
        $res = $this->db->query("SELECT branch_id,path FROM $table WHERE parent_id=0");
        foreach ($res->result() as $row) {
            $this->db->query("UPDATE $table SET top_id='{$row->branch_id}' WHERE path LIKE '{$row->path}%'");
        }
        $res->free_result();
    }

    private function treePathRecalculate($table, $parent_id = 0, $parent_path = '/', $parent_path_id = '/') {
        $where = "";
        if ($parent_id !== null) {
            $where = "parent_id=$parent_id";
        }
        $res = $this->db->query("SELECT * FROM $table WHERE $where");
        foreach ($res->result() as $row) {
            $current_path = $parent_path . "{$row->label}/";
            $current_path_id = $parent_path_id . "{$row->branch_id}/";
            $this->update($table, ['path' => $current_path,'path_id' => $current_path_id], ['branch_id' => $row->branch_id]);
            $this->treePathRecalculate($table, $row->branch_id, $current_path, $current_path_id);
        }
        $res->free_result();
    }

    /////////////////////////////
    //SELF PRICE FUNCTIONS
    /////////////////////////////
    public $stockQtyRecalculate = [];

    public function stockQtyRecalculate() {
        $sql = "
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

    private function selfPriceOldApiRecalculate($idate, $fdate, $active_filter) {
        $Document2 = $this->Hub->bridgeLoad('Document');
        $res = $this->db->query("SELECT doc_id,passive_company_id FROM document_list WHERE is_commited=1 AND doc_type=1 AND '$idate'<=cstamp AND cstamp<='$fdate' $active_filter ORDER BY passive_company_id");
        //echo "SELECT doc_id,passive_company_id FROM document_list WHERE is_commited=1 AND doc_type=1 AND '$idate'<=cstamp AND cstamp<='$fdate' $active_filter ORDER BY passive_company_id";
        if ($res) {
            foreach ($res->result() as $row) {
                if ($Document2->Base->pcomp('company_id') != $row->passive_company_id) {
                    $Document2->Base->selectPassiveCompany($row->passive_company_id);
                    echo " pcomp_id" . $row->passive_company_id;
                }
                $doc_id = $row->doc_id;
                $Document2->selectDoc($doc_id);
                $Document2->updateTrans('profit_only');
            }
            $res->free_result();
        }
    }

    private function dmy2iso($dmy) {
        $chunks = explode('.', $dmy);
        return "$chunks[2]-$chunks[1]-$chunks[0]";
    }

    ////////////////////////////////////////////////////////////
    // SELF RECALCULATION FUNCTIONS
    ////////////////////////////////////////////////////////////
    private function selfPriceCorrectEntries() {
        $sql_update = "UPDATE document_entries de JOIN tmp_self_calc tsc USING(doc_entry_id) SET de.self_price=tsc.sp WHERE doc_type=1;";
        $this->db->query($sql_update);
    }

    private function selfPriceCreateTable($active_filter) {
        $sql_vars = "SET @i:=0,@pointer:=0,@current_code:='',@qty_left1:=0,@qty_left:=0,@current_self_price=0.00;";
        $sql_tbl_drop = "DROP TEMPORARY TABLE IF  EXISTS tmp_self_calc;"; #TEMPORARY TEMPORARY 
        $sql_tbl_create = "CREATE TEMPORARY TABLE tmp_self_calc AS (SELECT 
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
			notcount = 0 AND is_commited = 1 AND (doc_type=2 OR doc_type=1) $active_filter
		    ORDER BY product_code ,  cstamp) t ) tt
	    ORDER BY product_code,i);";
        $this->db->query($sql_vars);
        $this->db->query($sql_tbl_drop);
        $this->db->query($sql_tbl_create);
    }

    public $selfPriceInvoiceRecalculate = ['string', 'string', 'string'];

    public function selfPriceInvoiceRecalculate($idatedmy, $fdatedmy, $active_mode = '') {
        set_time_limit(300);
        session_write_close();
        $idate = $this->dmy2iso($idatedmy) . ' 00:00:00';
        $fdate = $this->dmy2iso($fdatedmy) . ' 23:59:59';
        if ($active_mode == 'all_active') {
            $active_filter = '';
        } else {
            $active_filter = " AND active_company_id='" . $this->Hub->acomp('company_id') . "'";
        }
        $this->selfPriceCreateTable($active_filter);
        $this->selfPriceCorrectEntries();
        $this->selfPriceCalculateExtraExpenses($idate, $fdate, $active_filter);
        $this->selfPriceStockAssign();
        $this->selfPriceOldApiRecalculate($idate, $fdate, $active_filter);
    }
    
    private function selfPriceCalculateExtraExpenses($idate, $fdate, $active_filter){
        $sql="SELECT 
                doc_id,
                JSON_EXTRACT(doc_settings,'$.extra_expenses') extra_expenses,
                SUM(self_price*product_quantity) self_sum
            FROM 
                document_list 
                    JOIN
                document_entries USING(doc_id)
            WHERE 
                JSON_CONTAINS_PATH(doc_settings,'one','$.extra_expenses')
                AND '$idate'<=cstamp 
                AND cstamp<='$fdate' 
                $active_filter
            GROUP BY doc_id
            HAVING extra_expenses>0 AND self_sum>0
                ";
        $document_list=$this->get_list($sql);
        foreach($document_list as $doc){
            $expense_ratio= $doc->extra_expenses / $doc->self_sum + 1;
            echo $sql_update="
                UPDATE
                    document_entries
                SET
                    self_price=self_price*$expense_ratio
                WHERE
                    doc_id='$doc->doc_id'";
            $this->query($sql_update);
        }
    }
    
    
    private function selfPriceStockAssign() {
        $sql_vars = "SET @current_product_code:='';";
        $sql_tbl_drop = "DROP TEMPORARY TABLE IF  EXISTS tmp_stock_self;";
        $sql_tbl_create = "
	    CREATE TEMPORARY TABLE tmp_stock_self AS(
	    SELECT 
		*
	    FROM
		(SELECT product_code,sp,qty_left FROM tmp_self_calc ORDER BY i DESC) ttt
	    WHERE
		IF(@current_product_code <> product_code,@current_product_code:=product_code,0));";
        $sql_update = "UPDATE stock_entries JOIN tmp_stock_self USING(product_code) SET self_price=sp;";
        $this->db->query($sql_vars);
        $this->db->query($sql_tbl_drop);
        $this->db->query($sql_tbl_create);
        $this->db->query($sql_update);
        return $this->db->affected_rows();
    }

    public $selfPriceStockRecalculate = [];

    public function selfPriceStockRecalculate($active_mode = '') {
        if ($active_mode == 'all_active') {
            $active_filter = '';
        } else {
            $active_filter = " AND active_company_id='" . $this->Hub->acomp('company_id') . "'";
        }
        $this->selfPriceCreateTable($active_filter);
        return $this->selfPriceStockAssign();
    }

    ////////////////////////////////////////////////////////////
    // STOCK UTILS FUNCTIONS
    ////////////////////////////////////////////////////////////
    public $stockCalcMin = ['parent_id'=> 'int', 'sales_period'=>'int', 'reserve_period'=>'int', 'count_reserved'=>'bool', 'count_awaiting'=>'bool',];

    public function stockCalcMin($parent_id, $sales_period, $reserve_period,$count_reserved,$count_awaiting) {
        $branch_ids = $this->treeGetSub('stock_tree', $parent_id);
        $round_step=10;
        $stock_table = "
	    UPDATE
		stock_entries se
	    SET
		product_wrn_quantity=
		GREATEST( COALESCE(
                (SELECT ROUND( 
                        (SUM(de.product_quantity)/$sales_period*$reserve_period
                        +IF('$count_reserved',COALESCE(product_reserved,0),0)
                        -IF('$count_awaiting',COALESCE(product_awaiting,0),0))/$round_step
                    )*$round_step
		FROM
		    document_entries de
			JOIN
		    document_list dl ON de.doc_id=dl.doc_id AND dl.is_commited=1 AND dl.notcount=0 AND dl.doc_type=1
		WHERE 
		    de.product_code=se.product_code AND (TO_DAYS(NOW()) - TO_DAYS(dl.cstamp) <= $sales_period)
		GROUP BY se.product_code)
                ,0),1)
	    WHERE product_wrn_quantity>0 AND se.parent_id IN (" . implode(',', $branch_ids) . ")";
        $this->query($stock_table);
        return $this->db->affected_rows();
    }

    public $stockCalcIncomeOrder = ['parent_id' => ['int', 0], 'filter' => 'json', 'round_to' => ['string', 'bpack']];

    public function stockCalcIncomeOrder($parent_id = 0, $filter = null, $round_to = 'bpack') {
        $having = $this->makeFilter($filter);
        if ($round_to === 'spack') {
            $rounding_quantity = 'product_spack';
        } else if ($round_to === 'piece') {
            $rounding_quantity = '1';
        } else {
            $rounding_quantity = 'product_bpack';
        }
        $branch_ids = $this->treeGetSub('stock_tree', $parent_id);
        $where = "product_wrn_quantity>product_quantity AND parent_id IN (" . implode(',', $branch_ids) . ")";
        $sql = "
	    SELECT
		product_code,
		IF($rounding_quantity,CEIL((product_wrn_quantity-product_quantity)/$rounding_quantity)*$rounding_quantity,product_wrn_quantity-product_quantity) qty
	    FROM
		stock_entries
		    JOIN
		prod_list USING(product_code)
	    WHERE $where
	    HAVING $having";
        $buy_order = $this->get_list($sql);
        $DocumentItems = $this->Hub->load_model("DocumentItems");
        $doc_id = $DocumentItems->createDocument(2); //create buy document
        foreach ($buy_order as $row) {
            $DocumentItems->entryAdd(null,$row->product_code, $row->qty);
        }
        return $doc_id;
    }

}
