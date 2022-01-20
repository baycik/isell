<?php
/* Group Name: Продажи
 * User Level: 2
 * Plugin Name: Кассовые аппараты
 * Plugin URI: http://isellsoft.net
 * Version: 1.0
 * Description: Дает возможность печатать чеки с помошью ККМ Сервера
 * Author: baycik 2021
 * Author URI: http://isellsoft.net
 */
class KKMIntegrator extends PluginBase{
    public $min_level=2;
    
    function __construct() {
        parent::__construct();
        $this->pluginSettingsLoad();
        
        if( empty($this->plugin_settings->gateway_url) ){
            die('{"Error":"Gateway settings are not set!"}');
        }
    }
    
    public function index(){
        $this->load->view('kkmdashboard.html');
    }

    private function apiExecute( $data, $method="POST" ){
        $url = $this->plugin_settings->gateway_url.'/Execute';
        
        $curl = curl_init(); 
        switch( $method ){
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            case 'GET':
                $query=$data?http_build_query($data):"";
                $url .= "?$query";
                break;
        }
        //print_r($data);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Authorization: Basic ".base64_encode($this->plugin_settings->gateway_user . ":" . $this->plugin_settings->gateway_password),
            "Content-Type: application/json; charset=UTF-8"]);

        $res = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if( curl_error($curl) ){
            $this->log("KKMIntegrator API Execute error: ".curl_error($curl));
            header("HTTP/1.1 $httpcode");
            die(curl_error($curl));
        }
        curl_close($curl);
        $result=json_decode($res);
        return $result;
    }
    
    public function checkModalGet(){
        $this->load->view('checkprint.html');
    }
    
    private function S4() {
        return substr(md5(rand(1,1000)),0,4);
    }
    public function idGenerate(){
        return $this->S4()."-".$this->S4()."-".$this->S4()."-".$this->S4()."-".$this->S4();
    }
    
//    public function list( object $filter=null ){
//        $request=[
//            'Command'=> "List",
//            // Отбор по номеру устройства. Число. Если 0 или не указано то с любым номером
//            'NumDevice'=> 0,
//            // Отбор по ИНН. Строка. Если "" или не указано то первое не блокированное на сервере
//            'InnKkm'=> "",
//            // Отбор активных. Булево. Если null или не указано то активные и не активные
//            //'Active'=> true,
//            // Отбор выключенных-включенных
//            //'OnOff'=> true,
//            // Отбор наличию ошибок ОФВ. Булево. Если null или не указано то с ошибками и без
//            //'OFD_Error'=> false,
//            // Все у которых дата не переданного док. в ОФД меньше указанной. Дата-время. Если null или не указано то любое
//            //'OFD_DateErrorDoc'=> '2100-01-01T00:00:00',
//            // Все у которых дата окончания работы ФН меньше указанной. Дата-время. Если null или не указано то любое
//            //'FN_DateEnd'=> '2100-01-01T00:00:00',
//            // Все у которых заканчивается память ФН; Булево. Если null или не указано то все
//            //'FN_MemOverflowl'=> false,
//            // Фискализованные или нет ФН; Булево. Если null или не указано то все
//            //'FN_IsFiscal'=> true
//        ];
//        return $this->apiExecute( $request );
//    }
    
        
    
    private function moneyProcess( $doc_id, $Cash=0, $ElectronicPayment=0, $AdvancePayment=0, $Credit=0, $CashProvision=0 ){
        $ok=true;
        $this->db_transaction_start();
        if( $Cash ){
            $ok *= $this->moneyCommit($doc_id, $Cash, 'Cash');
        }
        if( $ElectronicPayment ){
            $ok *= $this->moneyCommit($doc_id, $ElectronicPayment, 'ElectronicPayment');
        }
        if( $AdvancePayment ){
            $ok *= $this->moneyCommit($doc_id, $AdvancePayment, 'AdvancePayment');
        }
        if( $Credit ){
            $ok *= $this->moneyCommit($doc_id, $Credit, 'Credit');
        }
        $this->db_transaction_commit();
        return $ok;
    }

    private function moneyCommit( int $doc_id, float $amount, string $type ){
        if( $amount==0 ){
            return true;
        }
        $doc_head_sql="SELECT 
                dl.*,
                DATE_FORMAT(dl.cstamp,'%d.%m.%Y') date_dmy,
                at.trans_id total_trans_id
            FROM 
                document_list dl
                    LEFT JOIN
                acc_trans at ON at.doc_id=dl.doc_id AND at.trans_role='total'
            WHERE 
                dl.doc_id='$doc_id'";
        $doc_head=$this->get_row($doc_head_sql);
        $acc_debit_code=0;
        $acc_credit_code=0;
        $description="";
        
        /*
         * bill_acc_code
         * account code where recieved money should accumulate
         */
        $bill_acc_code=null;
        if( $doc_head->doc_type==1 ){
            $bill_acc_code=$this->plugin_settings->customer_acc_code??null;
        } else 
        if( $doc_head->doc_type==5 ){
            $bill_acc_code=$this->plugin_settings->agent_acc_code??null;
        }
        if( !$bill_acc_code ){
            die("Bill acc code is not set");
        }
        switch( $type ){
            case 'Cash':
                if( $amount>0 ){
                    $acc_debit_code=$this->plugin_settings->cash_acc_code??null;
                    $acc_credit_code=$bill_acc_code;
                    $description="Оплата наличными документ №{$doc_head->doc_num} от {$doc_head->date_dmy}";
                } else {
                    $acc_debit_code=$bill_acc_code;
                    $acc_credit_code=$this->plugin_settings->cash_acc_code??null;
                    $description="Возврат оплаты наличными документ №{$doc_head->doc_num} от {$doc_head->date_dmy}";
                }
                break;
            case 'ElectronicPayment':
                if( $amount>0 ){
                    $acc_debit_code  =$this->plugin_settings->electronic_acc_code??null;
                    $acc_credit_code =$bill_acc_code;
                    $description="Оплата электронно документ №{$doc_head->doc_num} от {$doc_head->date_dmy}";
                } else {
                    $acc_debit_code  =$bill_acc_code;
                    $acc_credit_code =$this->plugin_settings->electronic_acc_code??null;
                    $description="Возврат оплаты электронно документ №{$doc_head->doc_num} от {$doc_head->date_dmy}";
                }
                break;
        }
        if( empty($acc_debit_code) || empty($acc_credit_code) || empty($description) ){
            return false;
        }
        $trans=[
            'doc_id'=>$doc_id,
            'trans_ref'=>$doc_head->total_trans_id,
            'editable'=>1,
            'active_company_id'=>$doc_head->active_company_id,
            'passive_company_id'=>$doc_head->passive_company_id,
            'acc_debit_code'=>$acc_debit_code,
            'acc_credit_code'=>$acc_credit_code,
            'description'=>$description,
            'amount'=>abs($amount),
            'cstamp'=>date('Y-m-d H:i:s')
        ];
        $AccountsCore=$this->Hub->load_model('AccountsCore');
        return (bool) $AccountsCore->transCreate($trans);
    }
    
    public function printCheckDuplicate( int $FiscalNumber ){
        $check=$this->GetDataCheck($FiscalNumber);
        if($check && !empty($check->Slip)){
            $CheckStrings=[];
            $rows=explode("\n",$check->Slip);
            foreach($rows as $row){
                $CheckStrings[]=[
                    'PrintText'=>['Text'=>$row]
                ];
            }
            $CheckStrings[]=[
                'BarCode'=>['BarcodeType'=>"QR",'Barcode'=>$check->QRCode]
            ];
            return $this->printSlip( $CheckStrings );
        }
        return [
            'Error'=>"Чек не найден"
        ];
    }
    
    public function printSlip( array $CheckStrings ){
        $this->Hub->set_level(2);
        $NumDevice=0;
        $TypeCheck=0;
        $IsBarCode=false;
        $Print=true;
        $Check = [
            'Command'=>"RegisterCheck",
            'NumDevice'=>$NumDevice,
            'IdCommand'=>$this->idGenerate(),
            'IsFiscalCheck'=>false,
            'NotPrint'=>false,
        ];
        $Check['CheckStrings']=$CheckStrings;
        return $this->apiExecute( $Check );
    }
    
    
    private function previousCheckFind( $doc_id ){
        $previous_check=[];
        $doc=$this->get_row("SELECT JSON_EXTRACT(doc_settings,'$.check_dump') check_dump, vat_rate FROM document_list WHERE doc_id='$doc_id'");
        if( !$doc || !$doc->check_dump ){
            $previous_check['status']='not_found';
            return (object) $previous_check;
        }
        $previous_check['check_object']=json_decode($doc->check_dump);
        if( !$previous_check['check_object']->data || $previous_check['check_object']->data->TypeCheck!=0 || !$previous_check['check_object']->data->CheckStrings ){
            $previous_check['status']='not_found';
            return (object) $previous_check;
        }
        $check_entries=[];
        foreach($previous_check['check_object']->data->CheckStrings as $row){
            $fiscal_data=$row->Register??null;
            if(!$fiscal_data){
                continue;
            }
            $check_entries[]="$fiscal_data->Quantity*$fiscal_data->Price";
        } 
        $current_entries_concat=$this->get_value("SELECT GROUP_CONCAT(CONCAT(product_quantity,'*',ROUND(invoice_price*($doc->vat_rate/100+1),2))) hash FROM document_entries WHERE doc_id='$doc_id'");
        $current_entries= explode(',', $current_entries_concat);
        if(array_diff($check_entries,$current_entries) || array_diff($current_entries,$check_entries)){
            $previous_check['status']='check_needs_correction';
        } else {
            $previous_check['status']='check_not_changed';
        }
        return (object) $previous_check;
    }
    
    public function printCheck( int $doc_id, float $Cash, float $ElectronicPayment, float $AdvancePayment=0, float $Credit=0, float $CashProvision=0 ){
        $cashier=$this->Hub->load_model('User')->userFetch();
        $document=$this->Hub->load_model('DocumentItems')->entryDocumentGet($doc_id);
        $document['head']=$this->Hub->load_model('DocumentItems')->headGet($doc_id);
        $active_company=$this->Hub->load_model('Company')->companyGet($document['head']->active_company_id);
        $passive_company=$this->Hub->load_model('Company')->companyGet($document['head']->passive_company_id);
        $acquiring=(object)[
            'PayByProcessing'=>false, //В тестовом чеке автоматический эквайринг выключен
            // Номер устройства для эквайринга - Null - из настроек на сервере, 0 - любое, число - номер конкретного устройства
            'NumDeviceByProcessing'=>null,
            // Номер чека для эквайринга
            'ReceiptNumber'=>"TEST-01",
            // Печатать Слип-чек после чека (а не в чеке)
            'PrintSlipAfterCheck'=>false,
            // Печатать Слип-чек дополнительно для кассира (основной слип-чек уже будет печататся в составе чека)
            'PrintSlipForCashier'=>true,
            //Если это чек возврата то возможны два поля для отмены транзакции (если не указано то по эквайрингу будет не отмена а возврат оплаты)
            'RRNCode'=>"", // RRNCode из операции эквайринга. Только для отмены оплаты! Для Оплаты или возврата оплаты не заполнять!
            'AuthorizationCode'=>"", // AuthorizationCode из операции эквайринга. Только для отмены оплаты! Для Оплаты или возврата оплаты не заполнять!
        ];
        $image=$this->plugin_settings->logo_base64;
        $Context=[
            'TypeCheck'=>0,
            'AdditionalAttribute'=>'',
            'cashier'=>$cashier,
            'document'=>$document,
            'active_company'=>$active_company,
            'passive_company'=>$passive_company,
            'acquiring'=>$acquiring,
            'Image'=>$image
        ];
        if( !$Context['document']['head']->is_commited ){
            return [
                'Error'=>"Документ не проведен"
            ];
        }
        if( $Context['document']['head']->is_reclamation || !in_array($Context['document']['head']->doc_type,[1,5]) ){
            return [
                'Error'=>"Неверный тип документа"
            ];
        }
        
        $prev_check=$this->previousCheckFind($doc_id);
        if( $prev_check->status === 'check_needs_correction' ){
            $current_total=$Context['document']['footer']->total;
            $PreviousCheck=$prev_check->check_object->data;
            if( !empty($prev_check->check_object->registration->CheckNumber) ){
                $corrected_check_ref="Корректируемый чек номер №{$prev_check->check_object->registration->CheckNumber}";
                $PreviousCheck->AdditionalAttribute=$Context['AdditionalAttribute']=$corrected_check_ref;
            }
            $PreviousCheck->AdditionalAttribute=$Context['AdditionalAttribute'];
            
            $PreviousCheck->total=round(
                     $PreviousCheck->Cash
                    +$PreviousCheck->ElectronicPayment
                    +$PreviousCheck->AdvancePayment
                    +$PreviousCheck->Credit
                    +$PreviousCheck->CashProvision
                    ,2);
            $PreviousCheck->Cash=0;
            $PreviousCheck->ElectronicPayment=0;
            $PreviousCheck->AdvancePayment=0;
            $PreviousCheck->Credit=0;
            if( $PreviousCheck->total > $current_total ){
                $PreviousCheck->CashProvision = $CashProvision = $current_total;
                $PreviousCheck->Cash = round($PreviousCheck->total-$current_total,2);
                $Cash = 0;
                $this->moneyProcess( $doc_id, -$PreviousCheck->Cash );
            }
            if( $PreviousCheck->total < $current_total ){
                $PreviousCheck->CashProvision = $CashProvision = $PreviousCheck->total;
                //$Cash = round($current_total-$PreviousCheck->total,2);
            }
            
            $registration=$this->RegisterCheckCancelPrevious( $PreviousCheck, $Context, $doc_id );
            if( $registration->Error ){
                return $registration;
            }
        }
        if( $prev_check->status === 'check_not_changed' ){
            return $this->printCheckDuplicate( $prev_check->check_object->registration->CheckNumber );
        }
        if( $prev_check->status === 'not_found' ){
            $Context['TypeCheck']=0;
        }
        //die("$prev_check->status");
        
        if( $this->moneyProcess( $doc_id, $Cash, $ElectronicPayment, $AdvancePayment, $Credit, $CashProvision ) ){
            return $this->RegisterCheck( $Context, $Cash, $ElectronicPayment, $AdvancePayment, $Credit, $CashProvision );
        }
        return [
            'Error'=>"Неудалось провести платеж. Проверьте настройки счетов учета"
        ];
    }
    
    public function refundCheck( int $doc_id ){
        $cashier=$this->Hub->load_model('User')->userFetch();
        $Context=[
            'cashier'=>$cashier
        ];
        $prev_check=$this->previousCheckFind($doc_id);
        $PreviousCheck=$prev_check->check_object->data;
        if( !empty($prev_check->check_object->registration->CheckNumber) ){
            $corrected_check_ref="Возврат прихода по чеку номер №{$prev_check->check_object->registration->CheckNumber} от {$prev_check->check_object->tstamp}";
            $PreviousCheck->AdditionalAttribute=$Context['AdditionalAttribute']=$corrected_check_ref;
        }
        $PreviousCheck->AdditionalAttribute=$Context['AdditionalAttribute'];
        $PreviousCheck->Cash+=$PreviousCheck->CashProvision;
        $PreviousCheck->CashProvision=0;
        if( $this->moneyProcess( $doc_id, 
                -$PreviousCheck->Cash,
                -$PreviousCheck->ElectronicPayment,
                -$PreviousCheck->AdvancePayment,
                -$PreviousCheck->Credit,
                -$PreviousCheck->CashProvision) ){
            return $this->RegisterCheckCancelPrevious( $PreviousCheck, $Context, $doc_id );
        }
        return [
            'Error'=>"Неудалось провести по бухгалтерии возврат платежа. Проверьте настройки счетов учета"
        ];
    }
    
    private function RegisterCheckCancelPrevious( $PreviousCheck, $Context, $doc_id ){
        if( $PreviousCheck->TypeCheck==0 ){
            $PreviousCheck->TypeCheck=1;
        }
        $PreviousCheck->CashierName=$Context['cashier']->user_sign;
        $PreviousCheck->CashierVATIN=$Context['cashier']->user_tax_id;
        $PreviousCheck->IdCommand=$this->idGenerate();
        $registration=$this->apiExecute( $PreviousCheck );
        if( !$registration->Error ){
            $this->saveCheckDump( $doc_id, (array) $PreviousCheck, $registration );
        }
        return $registration;
    }
    
    private function RegisterCheck(array $Context, float $Cash=0, float $ElectronicPayment=0, float $AdvancePayment=0, float $Credit=0, float $CashProvision=0 ) {
        $this->Hub->set_level(2);
        $NumDevice=0;
        
        $Check = [
            'Command'=>"RegisterCheck",
            'NumDevice'=>$NumDevice,
            'InnKkm'=>"",
            'KktNumber'=>"",
            'Timeout'=>30,
            'IdCommand'=>$this->idGenerate(),//$Context['document']['head']->doc_id,//
            'IsFiscalCheck'=>true,
            // Тип чека, Тег 1054;
            // 0 – продажа;                             10 – покупка;
            // 1 – возврат продажи;                     11 - возврат покупки;
            // 8 - продажа только по ЕГАИС (обычный чек ККМ не печатается)
            // 9 - возврат продажи только по ЕГАИС (обычный чек ККМ не печатается)
            'TypeCheck'=>$Context['TypeCheck'],
            'NotPrint'=>false, //true,
            'NumberCopies'=>0,
            'CashierName'=>$Context['cashier']->user_sign,
            'CashierVATIN'=>$Context['cashier']->user_tax_id,
            // Телефон или е-Майл покупателя, Тег ОФД 1008
            // Если чек не печатается (NotPrint = true) то указывать обязательно
            // Формат'=>Телефон +{Ц} или Email {С}@{C}
            'ClientAddress'=>$Context['passive_company']->company_mobile,
            // Покупатель (клиент) - наименование организации или фамилия, имя, отчество (при наличии), серия и номер паспорта покупателя(клиента). Тег 1227
            // Только с использованием наличных / электронных денежных средств и при выплате выигрыша, получении страховой премии или при страховой выплате.
            'ClientInfo'=>$Context['passive_company']->company_name,
            //'SenderEmail'=>$Context['active_company']->company_email,
            'PlaceMarket'=>"",
            'TaxVariant'=>"",
            'PayByProcessing'=>$Context['acquiring']->PayByProcessing, //В тестовом чеке автоматический эквайринг выключен
            'NumDeviceByProcessing'=>$Context['acquiring']->NumDeviceByProcessing,
            'ReceiptNumber'=>$Context['acquiring']->ReceiptNumber,
            'PrintSlipAfterCheck'=>$Context['acquiring']->PrintSlipAfterCheck,
            'PrintSlipForCashier'=>$Context['acquiring']->PrintSlipForCashier,
            'RRNCode'=>$Context['acquiring']->RRNCode, // RRNCode из операции эквайринга. Только для отмены оплаты! Для Оплаты или возврата оплаты не заполнять!
            'AuthorizationCode'=>$Context['acquiring']->AuthorizationCode,
            'AdditionalAttribute'=>$Context['AdditionalAttribute'],
            
            'Cash'=>round($Cash,2),
            'ElectronicPayment'=>round($ElectronicPayment,2),
            'AdvancePayment'=>round($AdvancePayment,2),
            'Credit'=>round($Credit,2),
            'CashProvision'=>round($CashProvision,2),
        ];
        
        if($Context['document']['head']->doc_type==55){//Agent document
            $Check['ClientInfo']='';
            $Check['AgentSign']=2;
           /* $Check['AgentData']=[
                //'PayingAgentPhone'=>'123456789',
                //'ReceivePaymentsOperatorPhone'=>$Context['active_company']->company_phone,
                'MoneyTransferOperatorPhone'=>$Context['active_company']->company_phone,
                'MoneyTransferOperatorName'=> $Context['active_company']->company_name,
                'MoneyTransferOperatorAddress'=>$Context['active_company']->company_jaddress,
                'MoneyTransferOperatorVATIN'=>$Context['active_company']->company_tax_id
            ];*/
            $Check['PurveyorData']=[
                'PurveyorPhone'=>$Context['passive_company']->company_phone,
                'PurveyorName'=>$Context['passive_company']->company_name,
                'PurveyorVATIN'=>$Context['passive_company']->company_tax_id
            ];
            
            //print_r($Check);die;
        }
        
        $Check['CheckStrings']=[];
        
        $Check['CheckStrings'][]=[
            'PrintText'=>[
                    'Text'=>"Номер накладной ".$Context['document']['head']->doc_num,
                    'Font'=>4,
                    'Intencity'=>15
                ],
            'PrintImage'=>[
                        //Картинка в Base64. Картинка будет преобразована в 2-х цветное изображение- поэтому лучше посылать 2-х цветный bmp
                        'Image'=>$Context['Image'],
                    ]
        ];
        //include APPPATH.'views/rpt/ru/doc/BlankDatatables.php';
        
        foreach($Context['document']['entries'] as $entry){
//            $CountryOfOrigin='';
//            if($entry->analyse_origin){
//                $CountryOfOrigin=country_code($entry->analyse_origin)['code'];
//            }
            $Register=[
                'Name'=>$entry->product_name,
                'Quantity'=>$entry->product_quantity,
                'Price'=>$entry->product_price,
                'Amount'=>$entry->product_sum,
                'Department'=>0,
                'Tax'=>$Context['active_company']->company_vat_rate,
                //'EAN13'=>$entry->product_barcode,
                'SignMethodCalculation'=>4,
                'SignCalculationObject'=>1,
                'MeasurementUnit'=>$entry->product_unit,
                //'CountryOfOrigin'=>$CountryOfOrigin,
                //'CustomsDeclaration'=>$entry->party_label,
                //'ExciseAmount'=>0,
                ];
            if($Context['document']['head']->doc_type==5){//Agent document
                $Register['AgentSign']=2;
                $Register['PurveyorData']=[
                    'PurveyorPhone'=>$Context['passive_company']->company_phone,
                    'PurveyorName'=>$Context['passive_company']->company_name,
                    'PurveyorVATIN'=>$Context['passive_company']->company_tax_id
                ];
            }
            $Check['CheckStrings'][]=[
                'Register'=>$Register
            ];
        }
        
        //print_r($Check);die;
        
        
        $registration=$this->apiExecute( $Check );
        
        
        
        //die("$Cash, $ElectronicPayment, $AdvancePayment, $Credit, $CashProvision");
        if( !$registration->Error ){
            $this->saveCheckDump( $Context['document']['head']->doc_id, $Check, $registration );
        } else {
            //print_r($Check);print_r($registration);
        }
        return $registration;
    }

    
//    private function stripImage($Check){
//        foreach($Check['CheckStrings'] as $String){
//            if( isset() ){
//                
//            }
//        }
//    }
    
    private function saveCheckDump( $doc_id, $Check, $registration ){
        //$Check=$this->stripImage($Check);
        $check_dump=[
            'registration'=>$registration,
            'data'=>$Check,
            'tstamp'=>date("Y-m-d H:i:s")
        ];
        $doc_settings=[];
        $doc_settings_json=$this->get_value("SELECT doc_settings FROM document_list WHERE doc_id=$doc_id");

        if( $doc_settings_json && $doc_settings_json!='null' ){
            $doc_settings=json_decode($doc_settings_json,true);
        }
        $new_settings=json_encode(array_merge( $doc_settings, ['check_dump'=>$check_dump]),JSON_UNESCAPED_UNICODE);//
        $sql="
            UPDATE 
                document_list 
            SET 
                doc_settings='".addslashes($new_settings)."',
                doc_data=CONCAT(doc_data,'\nНапечатан чек №{$check_dump['registration']->CheckNumber} ',DATE_FORMAT(NOW(),'%d.%m.%Y %H:%i:%s'),'{$check_dump['data']['CashierName']}')
            WHERE
                doc_id=$doc_id";
        return $this->query($sql);
    }
    
    public function RegisterCorrectionCheck( object $Data ){
        $this->Hub->set_level(3);
        $cashier=$this->Hub->load_model('User')->userFetch();
        $NumDevice=0;
        
        function def( $var ){
            return $var?$var:0;
        }
        
        foreach($Data as $key=>&$param){
            if( in_array($key,['CorrectionBaseDate'])){
                continue;
            }
            $param=$param??0?$param:0;
        }
        
        $Amount=($Data->Cash??0+$Data->ElectronicPayment??0+$Data->AdvancePayment??0+$Data->Credit??0+$Data->CashProvision??0);
        if( $Amount==0 ){
            return [
                'Error'=>"Сумма корректировки (электронно+наличными+предоплата+кредит+предоставлением) должна быть больше нуля!"
            ];
        }
        
        if( $Data->CorrectionBaseDate ){
            $Data->CorrectionBaseDate.="T".date("H:i:s");
        }
        
        $Check = [
            'Command'=>"RegisterCheck",
            'NumDevice'=>$NumDevice,
            'InnKkm'=>"",
            'Timeout'=>30,
            'IdCommand'=>$this->idGenerate(),//$Context['document']['head']->doc_id,
            'IsFiscalCheck'=>true,
            // Тип чека, Тег 1054;
            // Для новых ККМ:
            // 2 – корректировка приход;
            // 12 – корректировка расход;
            'TypeCheck'=>'2',//$Data->TypeCheck,
            'CashierName'=>$cashier->user_sign,
            'CashierVATIN'=>$cashier->user_tax_id,
            'ClientAddress'=>$Data->ClientAddress,
            'ClientInfo'=>$Data->ClientInfo,
            'ClientINN'=> $Data->ClientINN,
            'SenderEmail'=>"",
            'NotPrint'=>false,
            'NumberCopies'=>0,
            'TaxVariant'=>"",
            //Тип коррекции 0 - самостоятельно 1 - по предписанию, Тег 1173
            'CorrectionType'=>$Data->CorrectionType,
            'CorrectionBaseName'=>$Data->CorrectionBaseName,
            'CorrectionBaseDate'=>$Data->CorrectionBaseDate,
            'CorrectionBaseNumber'=>$Data->CorrectionBaseNumber,
            
            'Cash'=>$Data->Cash??0,
            'ElectronicPayment'=>$Data->ElectronicPayment??0,
            'AdvancePayment'=>$Data->AdvancePayment??0,
            'Credit'=>$Data->Credit??0,
            'CashProvision'=>$Data->CashProvision??0,
            'Amount'=>$Amount,
            
            'SumTaxNone'=>$Data->SumTaxNone??0,
            'SumTax20'=>$Data->SumTax20??0,
            'SumTax10'=>$Data->SumTax10??0,
            'SumTax0'=>$Data->SumTax0??0,
            'SumTax120'=>$Data->SumTax120??0,
            'SumTax110'=>$Data->SumTax110??0,
            
            'CheckStrings'=>[
                ['PrintText'=>[
                    'Text'=>"Это чек корректровки. Делается только по предписанию налоговой или главного бухгалтера."
                ]]
            ]
        ];
        //print_r($Check);
        return $this->apiExecute( $Check );
    }
    
    public function getResult( string $IdCommand ){
        $request = [
            // Команда серверу - запрос выволнеия команды
            'Command'=> "GetRezult",
            // Уникальный идентификатор ранее поданной команды
            'IdCommand'=> $IdCommand
        ];
        //sleep(2);
        // Вызываем запрос на получение результата с задержкой 2 секунды
        return $this->apiExecute( $request );
    }
    
    
    
    public function GetDataCheck( int $FiscalNumber=0 ){
        $data=[
            'Command'=>'GetDataCheck',
            'NumDevice'=>0,
            'FiscalNumber'=>$FiscalNumber,
            'IdCommand'=>$this->idGenerate()
        ];
        return $this->apiExecute($data);
    }
    
    
    public function getLineLength(){
        $data=[
            'Command'=>'GetLineLength',
            'NumDevice'=>0,
            'IdCommand'=>$this->idGenerate()
        ];
        return $this->apiExecute($data);
    }
    
    public function getStatus( int $doc_id=null, float $total=null ){
        $data=[
            'Command'=>'GetDataKKT',
            'NumDevice'=>0,
            'IdCommand'=>$this->idGenerate()
        ];
        $status=$this->apiExecute($data);
        if( $doc_id ){//look if this check was already printed
            $status->previous_check=$this->previousCheckFind( $doc_id );
        }
        return $status;
    }
    
    public function openShift(){
        $cashier=$this->Hub->load_model('User')->userFetch();
        $data=[
            'Command'=>'OpenShift',
            'NumDevice'=>0,
            'CashierName'=>$cashier->user_sign,
            'CashierVATIN'=>$cashier->user_tax_id,
            'NotPrint'=>false,
            'IdDevice'=>"",
            'IdCommand'=>$this->idGenerate()
        ];
        if( $this->apiExecute($data) ){
            return $data;
        }
        return false;
    }
    
    public function closeShift(){
        $cashier=$this->Hub->load_model('User')->userFetch();
        $data=[
            'Command'=>'CloseShift',
            'NumDevice'=>0,
            'CashierName'=>$cashier->user_sign,
            'CashierVATIN'=>$cashier->user_tax_id,
            'NotPrint'=>false,
            'IdDevice'=>"",
            'IdCommand'=>$this->idGenerate()
        ];
        return $this->apiExecute($data);
    }
    
    public function XReport(){
        $data=[
            'Command'=>'XReport',
            'NumDevice'=>0,
            'IdCommand'=>$this->idGenerate()
        ];
        return $this->apiExecute($data);
    }
    
    public function OfdReport(){
        $cashier=$this->Hub->load_model('User')->userFetch();
        $data=[
            'Command'=>'OfdReport',
            'NumDevice'=>0,
            'CashierName'=>$cashier->user_sign,
            'CashierVATIN'=>$cashier->user_tax_id,
            'IdCommand'=>$this->idGenerate()
        ];
        return $this->apiExecute($data);
    }
    
    public function depositCash( float $Amount ){
        $cashier=$this->Hub->load_model('User')->userFetch();
        $data=[
            'Command'=>'DepositingCash',
            'NumDevice'=>0,
            'CashierName'=>$cashier->user_sign,
            'CashierVATIN'=>$cashier->user_tax_id,
            'Amount'=>$Amount,
            'IdCommand'=>$this->idGenerate()
        ];
        return $this->apiExecute($data);
    }
    
    public function withdrawCash( float $Amount ){
        $cashier=$this->Hub->load_model('User')->userFetch();
        $data=[
            'Command'=>'PaymentCash',
            'NumDevice'=>0,
            'CashierName'=>$cashier->user_sign,
            'CashierVATIN'=>$cashier->user_tax_id,
            'Amount'=>$Amount,
            'IdCommand'=>$this->idGenerate()
        ];
        return $this->apiExecute($data);
    }
    
    public function openCashDrawer(){
        $data=[
            'Command'=>'OpenCashDrawer',
            'NumDevice'=>0,
            'IdCommand'=>$this->idGenerate()
        ];
        return $this->apiExecute($data);
    }
    
    
    
    
}
