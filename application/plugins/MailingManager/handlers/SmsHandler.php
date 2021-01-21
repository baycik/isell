<?php

class SmsHandler  extends MailingManager{
    public function send( $number, $message ){
        $Utils = $this->MailingManager->Hub->load_model('Utils');
        $body = htmlspecialchars_decode(strip_tags($message->message_body));
        return $Utils->sendSms($number, $body);
    }
}