CREATE TABLE `checkout_entries` (
  `checkout_entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `checkout_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_quantity` int(11) DEFAULT '0',
  `product_quantity_verified` int(11) DEFAULT '0',
  `verification_status` int(11) DEFAULT '0',
  PRIMARY KEY (`checkout_entry_id`),
  UNIQUE KEY `checkout_entries_idx` (`checkout_id`,`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



CREATE TABLE `checkout_list` (
  `checkout_id` int(11) NOT NULL AUTO_INCREMENT,
  `checkout_name` varchar(45) DEFAULT NULL,
  `parent_doc_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `cstamp` datetime DEFAULT NULL,
  `checkout_status` varchar(45) DEFAULT 'not_checked',
  `checkout_photos` mediumtext NOT NULL,
  PRIMARY KEY (`checkout_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;




CREATE TABLE `checkout_log` (
  `checkout_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `checkout_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `operation_quantity` int(11) DEFAULT NULL,
  `cstamp` datetime DEFAULT NULL,
  PRIMARY KEY (`checkout_log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
