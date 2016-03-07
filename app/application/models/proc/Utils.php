<?php

require_once 'Data.php';

class Utils extends Data {

    public function sendEmail($to, $subject, $body, $file, $copy = 'share', $encoding = 'utf-8') {
	if (!BAY_SMTP_SERVER || !BAY_SMTP_USER || !BAY_SMTP_PASS || !BAY_SMTP_SENDER_MAIL) {
	    $this->Base->msg("Настройки для отправки email не установленны!");
	    return false;
	}
	if (!$to) {
	    $this->Base->msg('Не указанны получатели письма!');
	    return false;
	}
	$sender_mail = BAY_SMTP_SENDER_MAIL;
	$recep = explode(',', $to);
	/*
	 * $copy==share add to recep 
	 * $copy==private add to recep 
	 * $copy==nocopy add nothing to recep
	 */
	if ($copy == 'share') {
	    $recep[] = $sender_mail;
	} else if ($copy == 'private') {
	    $recep[] = $sender_mail = BAY_SMTP_PRIVATE_MAIL;
	}
	try {
	    require_once 'application/libraries/swift/swift_required.php';
	    $message = Swift_Message::newInstance()
		    ->setFrom(array($sender_mail => BAY_SMTP_SENDER_NAME))
		    ->setSubject($subject)
		    ->setTo($recep)
		    ->setBody($body, 'text/plain', $encoding)
		    ->setEncoder(Swift_Encoding::get8BitEncoding());
	    if ($file) {
		$attachment = Swift_Attachment::newInstance()
			->setFilename($file['name'])
			->setBody($file['data'])
		;
		$message->attach($attachment);
	    }
	    $transport = Swift_SmtpTransport::newInstance(BAY_SMTP_SERVER, 587)
		    ->setUsername(BAY_SMTP_USER)
		    ->setPassword(BAY_SMTP_PASS)
		    ->setTimeout(10);
	    if( defined(BAY_SMTP_CRYPTO) && defined(BAY_SMTP_PORT) ){
		$transport->setEncryption(BAY_SMTP_CRYPTO)->setPort(BAY_SMTP_PORT);
	    }
	    $mailer = Swift_Mailer::newInstance($transport);

	    if (!$mailer->send($message, $failures)) {
		$this->Base->msg("Сообщение не было отправленно на:\n" . implode("\n", $failures));
		return false;
	    }
	} catch (Exception $error_string) {
	    $this->Base->msg("Ошибка связи с SMTP сервером {BAY_SMTP_SERVER!}\n\nПроверьте соединение с Интернетом.");
	    $this->Base->msg($error_string);
	    return false;
	}
	return true;
    }

    public function sendSms($number, $delete_me, $body) {
	if (!BAY_SMS_SENDER || !BAY_SMS_USER || !BAY_SMS_PASS) {
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
		$sid = json_decode(file_get_contents("https://integrationapi.net/rest/User/SessionId?login=" . BAY_SMS_USER . "&password=" . BAY_SMS_PASS));
		$this->Base->svar('smsSessionId', $sid);
	    }
	    $post_vars = array(
		'sessionId' => $this->Base->svar('smsSessionId'),
		'sourceAddress' => BAY_SMS_SENDER,
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

    public function fill_length($word, $length, $delim = ' ') {
	$chars = preg_split('/(?<!^)(?!$)/u', $word);
	return array_pad($chars, mb_strlen($chars, 'UTF-8') - $length, $delim);
    }

    public function getAllDetails($comp) {
	$all = "$comp[company_name] \n$comp[company_jaddress]";
	if ($comp['company_phone'])
	    $all.=", тел.:$comp[company_phone]";
	if ($comp['company_bank_account'])
	    $all.=", Р/р:$comp[company_bank_account]";
	if ($comp['company_bank_name'])
	    $all.=" в $comp[company_bank_name]";
	if ($comp['company_bank_id'])
	    $all.=" МФО:$comp[company_bank_id]";
	if ($comp['company_vat_id'])
	    $all.=", IПН:$comp[company_vat_id]";
	if ($comp['company_vat_licence_id'])
	    $all.=" Номер свiдоцтва:$comp[company_vat_licence_id]";
	if ($comp['company_code'])
	    $all.=" ЄДРПОУ:$comp[company_code]";
	$all.=" $comp[web]";
	if ($comp['email'])
	    $all.=" E-mail:$comp[email]";
	return $all;
    }

    public function spellNumber($number, $units, $ret_number = false) {
	$hundreds_i = $this->getNumberPosition($number, 100, 1);
	$tens_i = $this->getNumberPosition($number, 10, 1);
	$ones_i = $this->getNumberPosition($number, 1, 1);
	if (!($hundreds_i || $tens_i || $ones_i) && !$ret_number)
	    return '';
	if ($ones_i == 1 && $tens_i != 1)
	    $unit = $units[0];
	else if ($ones_i > 1 && $ones_i < 5)
	    $unit = $units[1];
	else
	    $unit = $units[2];
	if ($ret_number) {
	    if ($number < 10)
		return "0$number $unit";
	    return "$number $unit";
	}

	$ones = array("", "одна", "дві", "три", "чотири", "п'ять", "шість", "сім", "вісім", "дев'ять");
	$tens = array("", "десять", "двадцять", "тридцять", "сорок", "п'ятдесят", "шістдесят", "сімдесят", "вісімдесят", "дев'яносто");
	$teens = array("", "одинадцять", "дванадцять", "тринадцять", "чотирнадцять", "п'ятнадцять", "шістнадцять", "сімнадцять", "вісімнадцять", "дев'ятнадцять");
	$hundreds = array("", "сто", "двісті", "триста", "чотириста", "п'ятсот", "шістсот", "сімсот", "вісімсот", "дев'ятсот");

	if ($tens_i == 1)
	    return "$hundreds[$hundreds_i] $teens[$ones_i] $unit";
	else
	    return "$hundreds[$hundreds_i] $tens[$tens_i] $ones[$ones_i] $unit";
    }

    public function getNumberPosition($number, $position, $range = 1) {//DEPRECATED
	$number-=$position * 10 * $range * floor($number / $position / 10 / $range);
	return floor($number / $position);
    }

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

    public function getLocalDate($tstamp) {
	$time = strtotime($tstamp);
	$months = array("січня", "лютого", "березня", "квітня", "травня", "червня", "липня", "серпня", "вересня", "жовтня", "листопада", "грудня");
	return date('d', $time) . ' ' . $months[date('m', $time) - 1] . ' ' . date('Y', $time);
    }

}

?>