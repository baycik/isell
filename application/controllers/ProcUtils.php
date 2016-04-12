<?php

require_once('iSellBase.php');

class ProcUtils extends iSellBase {

    public function ProcUtils() {
        $this->ProcessorBase(1);
    }

    public function onGetBankName() {
        $mfo = $this->request('mfo', 1);
        if (!$mfo)
            $this->response("");
        $html = file_get_contents("http://kozakplus.com/ru/guidance/mfo-codes/catalog?search=$mfo");
        $from = strpos($html, "$mfo</td>");
        if (!$from) {
            $this->msg("Банк с МФО:$mfo не найден!");
            $this->response("");
        }
        $to = strpos($html, '</tr>', $from);

        $fragment = substr($html, $from, ($to - $from));
        preg_match_all('"<td[^<]*>(.+)</td>"', $fragment, $out);
        $this->response($out[1][0]);
    }

    public function onSendSms() {
        $number = $this->request('to');
        $subject = $this->request('subject', 4);
        $body = $this->request('body');
        $copy = $this->request('copy', 1);

        $this->LoadClass('Utils');
        if ($this->Utils->sendSms($number, $subject, $body)) {
            $this->msg("Sms было успешно отправленно на\n$number.");
            $body.="\n\nSms было успешно отправленно на\n$number.";
            $this->Utils->sendEmail(($copy ? BAY_SMTP_SENDER_MAIL : BAY_SMTP_PRIVATE_MAIL), $subject, $body, NULL, 'nocopy');
        } else {
            $this->msg("Отправка не удалась!");
        }
	$this->response();
    }

    public function onSendEmail() {
        $to = $this->request('to');
        $subject = $this->request('subject');
        $body = $this->request('body');
        $copy = $this->request('copy', 1);
        if ($this->request('send_file')) {
            $fgenerator = $this->request('fgenerator');
            $doc_view_id = $this->request('doc_view_id', 1);
            $file_name = $this->request('file_name');
            $ext = substr($file_name, strrpos($file_name, '.'));
            if ($fgenerator == 'Accounts') {
                $this->LoadClass('Accounts');
                $fileData = $this->Accounts->getViewPage($doc_view_id, $ext);
            }
            if ($fgenerator == 'Companies') {
                $this->LoadClass('Companies');
                $fileData = $this->Companies->getViewPage($doc_view_id, $ext);
            }
            $file = array(
                'name' => str_replace(' ', '_', $file_name),
                'data' => $fileData
            );
        } else
            $file = NULL;
        $this->LoadClass('Utils');
        if ($this->Utils->sendEmail($to, $subject, $body, $file, $copy ? 'share' : 'private' )) {
            $this->msg("Сообщение было успешно отправленно на\n$to.");
        } else {
            $this->msg("Отправка не удалась!");
        }
	$this->response();
    }

    function onSpellNumber() {
        $number = $this->request('number', 1);
        $unit = $this->request('unit');
        $units[0] = array('', '', '');
        $units[1] = array($unit, $unit, $unit);
        $units[2] = array('тисяча', 'тисячи', 'тисяч');
        $units[3] = array('мільон', 'мільони', 'мільонів');
        $this->LoadClass('Utils');
        $spell = $this->Utils->spellAmount($number, $units, false);
        $this->response($spell);
    }

    public function onSelfRecalculate() {
	$active_company_id=$this->Base->acomp('company_id');
        $this->set_level(3);
        $offset = $this->request('offset', 1, 0);
        $idate = $this->request('idate'); //"2013-01-01";
        $fdate = $this->request('fdate'); //"2014-01-01";
        $step = $this->svar('step');
        if (!$step || $step == 1) {
            $limit = 200;
            $res = $this->query("SELECT DISTINCT product_code FROM document_entries JOIN document_list USING(doc_id)
				WHERE active_company_id='$active_company_id' AND is_commited=1 AND (doc_type=1 OR doc_type=2) AND product_code NOT IN (SELECT product_code FROM stock_entries) 
				LIMIT $limit OFFSET $offset");
            while ($row = mysql_fetch_assoc($res)) {
                $product_code = $row['product_code'];
                $this->query("INSERT LOW_PRIORITY INTO stock_entries SET product_code='$product_code'");
                $left_more_rows = true;
            }
            if ($left_more_rows) {
                $msg = '<meta http-equiv="refresh" content="0;URL=./?mod=Utils&rq=SelfRecalculate&offset=' . ($limit + $offset) . '&idate='.$idate.'&fdate='.$fdate.'">';
                $msg.="Найдено и восстановленно удаленых со склада позиций: " . mysql_num_rows($res);
            } else {
                $msg = '<meta http-equiv="refresh" content="5;URL=./?mod=Utils&rq=SelfRecalculate&offset=0&idate='.$idate.'&fdate='.$fdate.'">';
                $msg.='Восстановление утраченных позиций окончено! Перехожу к пересчету склада.';
                $this->svar('step', 2);
            }
            mysql_free_result($res);
        }
        if ($step == 2) {
            $limit = 200;
            $res = $this->query("SELECT product_code FROM stock_entries LIMIT $limit OFFSET $offset");
            $this->LoadClass('Pref');
            $ratios = $this->Pref->prefGet();
            $usd_ratio = $ratios["usd_ratio"];

            while ($row = mysql_fetch_assoc($res)) {
                $product_code = $row['product_code'];
                $avg_self = $this->get_row("SELECT SUM(product_quantity*invoice_price)/SUM(product_quantity)
					FROM document_entries JOIN document_list USING(doc_id) 
					WHERE active_company_id='$active_company_id' AND is_commited=1 AND doc_type=2 AND product_code='$product_code' AND '$idate'<=cstamp AND cstamp<='$fdate'", 0);
                if ($avg_self == 0) {
                    $this->LoadClass('Document');
                    $price = $this->Document->getRawProductPrice($product_code, $usd_ratio);
                    $new_stock_self = $price['buy'] ? $price['buy'] : 0;
                } else {
                    $new_stock_self = $avg_self;
                }
                $vat_quantity = $this->get_row("SELECT SUM(IF(doc_type=1,-product_quantity,product_quantity))
					FROM document_entries JOIN document_list USING(doc_id) 
					WHERE active_company_id='$active_company_id' AND is_commited=1 AND product_code='$product_code' AND '$idate'<=cstamp AND cstamp<='$fdate'", 0);
                /*
                 * Correcting vat_quantity for deleted products
                 */
                $this->query("UPDATE LOW_PRIORITY document_entries JOIN document_list USING(doc_id) SET  self_price=$new_stock_self WHERE active_company_id='$active_company_id' AND doc_type=1 AND product_code='$product_code'");
                $this->query("UPDATE LOW_PRIORITY stock_entries SET self_price='$new_stock_self',vat_quantity='$vat_quantity' WHERE product_code='$product_code'");
                $left_more_rows = true;
            }
            mysql_free_result($res);
            $msg = "";
            if ($left_more_rows) {
                $msg = '<meta http-equiv="refresh" content="0;URL=./?mod=Utils&rq=SelfRecalculate&offset=' . ($limit + $offset) . '&idate='.$idate.'&fdate='.$fdate.'">';
                $msg.="Наименований товара перерасчитано: " . ($limit + $offset);
            } else {
                $msg = '<meta http-equiv="refresh" content="5;URL=./?mod=Utils&rq=SelfRecalculate&offset=0&idate='.$idate.'&fdate='.$fdate.'">';
                $msg.='Все наименования товара перерасчитаны! Перехожу к пересчету проводок.';
                $this->svar('step', 3);
            }
        }
        if ($step == 3) {
            $limit = 50;
            $this->LoadClass('Document');
            $res = $this->query("SELECT doc_id,passive_company_id FROM document_list WHERE is_commited=1 AND doc_type=1 AND '$idate'<=cstamp AND cstamp<='$fdate' ORDER BY passive_company_id LIMIT $limit OFFSET $offset");
            while ($row = mysql_fetch_assoc($res)) {
                if ($this->pcomp('company_id') != $row['passive_company_id'])
                    $this->selectPassiveCompany($row['passive_company_id']);
                $doc_id = $row['doc_id'];
                $this->Document->selectDoc($doc_id);
                $this->Document->updateTrans();
                $left_more_rows = true;
            }
            mysql_free_result($res);
            if ($left_more_rows) {
                $msg = '<meta http-equiv="refresh" content="0;URL=./?mod=Utils&rq=SelfRecalculate&offset=' . ($limit + $offset) . '&idate='.$idate.'&fdate='.$fdate.'">';
                $msg.="Расходных накладных перерасчитано: " . ($limit + $offset);
            } else {
                $msg = 'finish!';
                $this->svar('step', 1);
            }
        }
        header('Content-Type: text/html;charset=utf-8');
        die($msg);
    }

    public function onCalcTop() {
        set_time_limit(600);
        $this->LoadClass('Data');
        $table_name = 'companies_tree';

        $branches = $this->get_list("SELECT * FROM $table_name WHERE parent_id=0");
        foreach ($branches as $branch) {
            $sub_parents_ids = $this->Data->getSubBranchIds($table_name, $branch['branch_id']);
            $sub_parents_where = "branch_id='" . implode("' OR branch_id='", $sub_parents_ids) . "'";
            $this->query("UPDATE $table_name SET top_id={$branch['branch_id']} WHERE $sub_parents_where");
        }
    }

    public function onCalcPath() {
        set_time_limit(600);
        $this->LoadClass('Data');
        $table_name = 'companies_tree';

        $branches = $this->Data->getSubBranchIds($table_name, 0);

        foreach ($branches as $branch_id) {
            $this->Data->updateTreeBranchPath('companies_tree', $branch_id);
        }
    }

    public function onStartEDRSession() {
        $post_vars = array(
            'query' => $this->request('company_code', 1),
            'type' => $this->request('type', 1)
        );
        $opts = array(
            'http' => array(
                'method' => "POST",
                'content' => http_build_query($post_vars)
            )
        );
        $html = file_get_contents('http://irc.gov.ua/ua/Poshuk-v-YeDR.html', false, stream_context_create($opts));
        preg_match_all('/<input type=\"hidden\" name=\"reqid\" value=\"(\d+)\">/', $html, $out);
        $reqid = $out[1][0];
        foreach ($http_response_header as $hdr)
            if (preg_match('/^Set-Cookie:\s*([^;]+)/', $hdr, $matches))
                $cookies = $matches[1];
        $opts = array(
            'http' => array(
                'header' => "Cookie: $cookies\r\n"
            )
        );
        $img = file_get_contents('http://irc.gov.ua/captchaimage.php', false, stream_context_create($opts));
        $this->response(array(
            'cookies' => $cookies,
            'reqid' => $reqid,
            'img64' => base64_encode($img)
        ));
    }

    public function onSearchEDR() {
        $reqid = $this->request('reqid', 1);
        $captcha = $this->request('captcha', 1);
        $cookies = $this->request('cookies');
        $post_vars = array('reqid' => $reqid, 'captcha' => $captcha);
        $this->trace($post_vars);

        $opts = array(
            'http' => array(
                'method' => "POST",
                'content' => http_build_query($post_vars),
                'header' => "Cookie: $cookies\r\n"
            )
        );
        $html = file_get_contents('http://irc.gov.ua/ua/Poshuk-v-YeDR.html', false, stream_context_create($opts));
        file_put_contents('edr.html', $html);
        $patt = array();
        $patt[] = "Ідентифікаційний код юридичної особи <\/td><td><b>";
        $patt[] = "скорочене у разі його наявності <\/td><td>";
        $patt[] = "Місцезнаходження юридичної особи <\/td><td>";
        $patt[] = "дані про наявність обмежень щодо представництва від імені юридичної особи\) <\/td><td>";
        $patt[] = "Інформація про здійснення зв’язку з юридичною особою<\/td><td>";
        $patt_str = implode('|', $patt);
        preg_match_all("/($patt_str)([^<]*)/u", $html, $out);
        $this->response($out[2]);
    }

    public function onStartSTASession() {
        $img = file_get_contents('http://minrd.gov.ua/passremind.php?captcha=giveme');
        foreach ($http_response_header as $hdr)
            if (preg_match('/^Set-Cookie:\s*([^;]+)/', $hdr, $matches))
                $cookies = $matches[1];
        $this->response(array(
            'cookies' => $cookies,
            'img64' => base64_encode($img)
        ));
    }

    public function onSearchSTA() {
        $kod = $this->request('company_code');
        $kod_pdv = $this->request('company_vat_id');
        $nom_svd = $this->request('company_vat_licence_id');
        $captcha = $this->request('captcha');
        $cookies = $this->request('cookies');
        $post_vars = array(
            'logic' => 0,
            'action' => 'req',
            'ozn' => 0,
            'kod' => $kod,
            'nom_svd' => $nom_svd,
            'name' => '',
            'kod_pdv' => $kod_pdv,
            'captcha' => $captcha
        );
        $opts = array(
            'http' => array(
                'method' => "POST",
                'content' => http_build_query($post_vars),
                'header' => "Cookie: $cookies\r\n"
            )
        );
        $html = file_get_contents('http://minrd.gov.ua/reestr', false, stream_context_create($opts));
        $notfound = (bool) strpos($html, "відсутня в базі зареєстрованих платників ПДВ.");
        $canceled = (bool) strpos($html, "наявна в базі <a href=\"/anulir\">«Анульовані свідоцтва платників ПДВ»");
        $wrongcaptcha = (bool) strpos($html, "Ви не вiрно заповнили захистний код!");
        if ($notfound || $wrongcaptcha)
            $result = array();
        else {
            $patt = array();
            $patt[] = "Код ЄДРПОУ\s*–\s*";
            $patt[] = "Індивідуальний податковий номер\s*--\s*";
            $patt[] = "Номер свiдоцтва платника ПДВ\s*--\s*";
            $patt[] = "Найменування або прiзвище, iм`я та по батьковi\s*–\s*";
            $patt_str = implode('|', $patt);
            preg_match_all("/($patt_str)([^<]*)/u", $html, $out);
            if ($out && count($out[2])) {
                $result = array(
                    'company_code' => trim($out[2][1]),
                    'company_vat_id' => trim($out[2][0]),
                    'company_name' => trim($out[2][2]),
                    'company_vat_licence_id' => trim($out[2][3]),
                );
                $canceled = 0;
            } else {
                $readerror = true;
            }
        }
        //if( $readerror )
        //	file_put_contents ('log.html', $html);
        $this->response(array(
            'status' =>
            $notfound ? 'notfound' : (
                    $canceled ? 'canceled' : (
                            $wrongcaptcha ? 'wrongcaptcha' : (
                                    $readerror ? 'readerror' : 'ok'
                                    ))),
            'result' => $result
        ));
    }
}