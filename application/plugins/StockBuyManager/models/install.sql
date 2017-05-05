/**
 * Author:  Baycik
 * Created: May 4, 2017
 */

CREATE TABLE `supply_list` (
  `supply_id` INT NOT NULL AUTO_INCREMENT,
  `supplier_company_id` INT NULL,
  `product_code` VARCHAR(45) NULL,
  `supply_code` VARCHAR(45) NULL,
  `supply_name` VARCHAR(255) NULL,
  `supply_buy` DOUBLE NULL,
  `supply_sell` DOUBLE NULL,
  `supply_comment` VARCHAR(255) NULL,
  `supply_spack` INT NULL,
  `supply_bpack` INT NULL,
  `supply_volume` DOUBLE NULL,
  `supply_weight` DOUBLE NULL,
  `supply_unit` VARCHAR(5) NULL,
  PRIMARY KEY (`supply_id`));

CREATE TABLE `supplier_list` (
  `supplier_company_id` INT NOT NULL,
  `supplier_name` VARCHAR(45) NULL,
  `supplier_discount` DOUBLE NULL,
  `supplier_defferment` INT NULL,
  `supplier_expense` DOUBLE NULL,
  `supplier_delivery` INT NULL,
  PRIMARY KEY (`supplier_company_id`));
