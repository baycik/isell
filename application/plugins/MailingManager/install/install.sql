CREATE TABLE `plugin_message_list` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `message_handler` varchar(45) DEFAULT NULL COMMENT 'Sms, Email, Viber',
  `message_note` varchar(45) DEFAULT NULL,
  `message_status` varchar(45) DEFAULT NULL,
  `message_recievers` varchar(255) DEFAULT NULL,
  `message_subject` varchar(45) DEFAULT NULL,
  `message_body` varchar(1024) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modified_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
