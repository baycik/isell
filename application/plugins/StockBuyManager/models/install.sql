/**
 * Author:  Baycik
 * Created: May 4, 2017
 */

CREATE TABLE `supply_list` (
  `supply_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_company_id` int(11) DEFAULT NULL,
  `product_code` varchar(45) DEFAULT NULL,
  `supply_code` varchar(45) DEFAULT NULL,
  `supply_name` varchar(255) DEFAULT NULL,
  `supply_buy` double DEFAULT NULL,
  `supply_sell` double DEFAULT NULL,
  `supply_comment` varchar(255) DEFAULT NULL,
  `supply_spack` int(11) DEFAULT NULL,
  `supply_bpack` int(11) DEFAULT NULL,
  `supply_volume` double DEFAULT NULL,
  `supply_weight` double DEFAULT NULL,
  `supply_unit` varchar(5) DEFAULT NULL,
  `supply_modified` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`supply_id`),
  UNIQUE KEY `supplier_company_id_UNIQUE` (`supplier_company_id`,`supply_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




CREATE TABLE `supplier_list` (
  `supplier_company_id` INT NOT NULL,
  `supplier_name` VARCHAR(45) NULL,
  `supplier_discount` DOUBLE NULL,
  `supplier_defferment` INT NULL,
  `supplier_expense` DOUBLE NULL,
  `supplier_delivery` INT NULL,
  PRIMARY KEY (`supplier_company_id`));
