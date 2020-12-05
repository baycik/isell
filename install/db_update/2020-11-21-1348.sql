ALTER TABLE `acc_trans` 
ADD COLUMN `doc_id` INT NULL AFTER `trans_role`,
CHANGE COLUMN `check_id` `check_id` INT(11) NULL DEFAULT NULL AFTER `doc_id`,
CHANGE COLUMN `is_disabled` `is_disabled` TINYINT(1) NULL DEFAULT NULL AFTER `check_id`,
CHANGE COLUMN `editable` `editable` TINYINT(1) NOT NULL DEFAULT '0' AFTER `is_disabled`,
ADD INDEX `doc_id` (`doc_id` ASC);
