CREATE TABLE `log_list` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `cstamp` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата',
  `url` varchar(65) DEFAULT NULL COMMENT 'Адрес запроса',
  `message` varchar(150) DEFAULT NULL COMMENT 'Сообщение',
  PRIMARY KEY (`entry_id`),
  KEY `tstamp` (`cstamp`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
