ALTER TABLE `acc_trans` 
ADD COLUMN `trans_role` VARCHAR(20) NULL AFTER `trans_article`,
CHANGE COLUMN `trans_article` `trans_article` VARCHAR(45) NULL DEFAULT NULL AFTER `trans_ref`,
CHANGE COLUMN `is_disabled` `is_disabled` TINYINT(1) NULL DEFAULT NULL AFTER `amount_alt`,
CHANGE COLUMN `check_id` `check_id` INT(11) NULL DEFAULT NULL AFTER `is_disabled`,
CHANGE COLUMN `editable` `editable` TINYINT(1) NOT NULL DEFAULT '0' AFTER `check_id`;
