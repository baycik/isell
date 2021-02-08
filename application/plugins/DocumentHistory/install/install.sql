CREATE TABLE `plugin_doc_history_list` (
  `change_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'HIDDEN',
  `active_company_label` varchar(45) DEFAULT NULL COMMENT 'Компания',
  `passive_company_label` varchar(45) DEFAULT NULL COMMENT 'Контрагент',
  `user_label` varchar(15) DEFAULT NULL COMMENT 'Пользователь',
  `entry_type` varchar(10) DEFAULT NULL COMMENT 'Тип',
  `entry_stamp` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время',
  `entry_doc_id` int(11) DEFAULT NULL COMMENT 'HIDDEN',
  `entry_doc_num` varchar(10) DEFAULT NULL COMMENT 'Номер документа',
  `entry_change_qty` float DEFAULT NULL COMMENT 'Колличество',
  `entry_change_name` varchar(200) DEFAULT NULL COMMENT 'Изменен',
  PRIMARY KEY (`change_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
