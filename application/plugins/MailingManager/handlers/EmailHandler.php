<?php

class EmailHandler  extends MailingManager{
    public function send( $to, $message ){
        $Utils = $this->MailingManager->Hub->load_model('Utils');
        $email_subject = $message->message_subject;
        $email_body = $message->message_body;
        if(filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return $Utils->sendEmail($to, $email_subject, $email_body);
        }
        return true;//not valid mail skip it!
    }
}