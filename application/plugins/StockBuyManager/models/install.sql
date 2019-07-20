/**
 * Author:  Baycik
 * Created: May 4, 2017
 */
CREATE TABLE `supply_list` (
  `supply_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) DEFAULT NULL,
  `product_code` varchar(45) DEFAULT NULL,
  `supply_code` varchar(45) DEFAULT NULL,
  `supply_name` varchar(255) DEFAULT NULL,
  `supply_leftover` int(11) NOT NULL,
  `supply_buy` double DEFAULT '0',
  `supply_sell` double DEFAULT '0',
  `supply_sell_ratio` double DEFAULT '0',
  `supply_comment` varchar(255) DEFAULT NULL,
  `supply_spack` int(11) DEFAULT '1',
  `supply_bpack` int(11) DEFAULT '1',
  `supply_volume` double DEFAULT NULL,
  `supply_weight` double DEFAULT NULL,
  `supply_unit` varchar(5) DEFAULT 'шт',
  `supply_modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`supply_id`),
  UNIQUE KEY `supplier_id_UNIQUE` (`supply_code`,`supplier_id`),
  KEY `product_code_INDEX` (`product_code`),
  KEY `supply_code_INDEX` (`supply_code`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


CREATE TABLE `supplier_list` (
  `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_company_id` int(11) NOT NULL,
  `supplier_name` varchar(45) DEFAULT NULL,
  `supplier_defferment` int(11) DEFAULT NULL,
  `supplier_delivery` int(11) DEFAULT NULL,
  `supplier_buy_expense` double DEFAULT '0',
  `supplier_buy_discount` double DEFAULT '0',
  `supplier_sell_discount` double DEFAULT '0',
  `supplier_sell_gain` double DEFAULT '0',
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `supply_order` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `supply_id` int(11) DEFAULT NULL,
  `product_code` varchar(45) DEFAULT NULL,
  `product_quantity` int(11) DEFAULT NULL,
  `product_comment` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
