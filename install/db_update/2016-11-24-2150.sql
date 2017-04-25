/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Baycik
 * Created: Nov 24, 2016
 */

ALTER TABLE `document_types` 
CHANGE COLUMN `doc_type` `doc_type` VARCHAR(45) NOT NULL ;



ALTER TABLE `document_list` 
DROP COLUMN `inernn`,
DROP COLUMN `reg_stamp`,
CHANGE COLUMN `is_commited` `is_commited` TINYINT(1) NOT NULL AFTER `doc_id`,
CHANGE COLUMN `is_reclamation` `is_reclamation` TINYINT(1) NOT NULL AFTER `is_commited`,
CHANGE COLUMN `notcount` `notcount` TINYINT(1) UNSIGNED NOT NULL AFTER `is_reclamation`,
CHANGE COLUMN `use_vatless_price` `use_vatless_price` TINYINT(1) NOT NULL AFTER `notcount`,
CHANGE COLUMN `signs_after_dot` `signs_after_dot` TINYINT(4) NOT NULL DEFAULT '3' AFTER `use_vatless_price`,
CHANGE COLUMN `vat_rate` `vat_rate` INT(11) NULL AFTER `cstamp`,
CHANGE COLUMN `doc_num` `doc_num` INT(11) NOT NULL AFTER `vat_rate`,
CHANGE COLUMN `cstamp` `cstamp` DATETIME NULL DEFAULT NULL ,
CHANGE COLUMN `doc_type` `doc_type` VARCHAR(45) NOT NULL ;
ALTER TABLE `document_list` 
