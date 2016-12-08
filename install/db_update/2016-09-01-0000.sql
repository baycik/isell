/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Baycik
 * Created: Dec 8, 2016
 */

ALTER TABLE `acc_check_list` 
CHANGE COLUMN `date` `date` DATETIME NULL DEFAULT NULL ,
CHANGE COLUMN `value_date` `value_date` DATETIME NULL DEFAULT NULL ,
CHANGE COLUMN `assumption_date` `assumption_date` DATETIME NULL DEFAULT NULL ,
CHANGE COLUMN `transaction_date` `transaction_date` DATETIME NULL DEFAULT NULL ;

ALTER TABLE `event_list` 
CHANGE COLUMN `event_date` `event_date` DATETIME NULL DEFAULT NULL ;

ALTER TABLE `price_list` 
ADD COLUMN `label` VARCHAR(45) NOT NULL COMMENT 'Категория цен';

ALTER TABLE `companies_list` 
ADD COLUMN `price_label` VARCHAR(45) NULL DEFAULT NULL AFTER `curr_code`;
