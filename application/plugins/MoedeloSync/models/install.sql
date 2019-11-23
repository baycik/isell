CREATE TABLE `sync_entries` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `sync_destination` varchar(445) DEFAULT NULL,
  `entry_action` varchar(45) DEFAULT NULL,
  `local_id` int(11) DEFAULT NULL,
  `local_hash` varchar(64) DEFAULT NULL,
  `local_tstamp` datetime DEFAULT NULL,
  `remote_id` int(11) DEFAULT NULL,
  `remote_hash` varchar(64) DEFAULT NULL,
  `remote_tstamp` datetime DEFAULT NULL,
  PRIMARY KEY (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
