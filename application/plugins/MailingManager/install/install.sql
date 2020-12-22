CREATE TABLE `plugin_message_list` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `message_batch_label` varchar(32),
  `message_handler` varchar(45) NOT NULL COMMENT 'Sms, Email, Viber',
  `message_reason` varchar(45) NOT NULL,
  `message_note` varchar(45) NOT NULL,
  `message_status` varchar(45) NOT NULL,
  `message_recievers` varchar(255) NOT NULL,
  `message_subject` varchar(200) NOT NULL,
  `message_body` varchar(10000) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sent_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
