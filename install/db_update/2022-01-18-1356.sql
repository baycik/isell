ALTER TABLE `acc_trans` 
DROP FOREIGN KEY `acc_credit_code`,
DROP FOREIGN KEY `acc_debit_code`,
DROP FOREIGN KEY `FK_acc_activecid`,
DROP FOREIGN KEY `FK_acc_passivecid`;
ALTER TABLE `isell_db`.`acc_trans` 
CHANGE COLUMN `trans_status` `trans_status` TINYINT(1) NULL DEFAULT 0 ,
CHANGE COLUMN `editable` `editable` TINYINT(1) NULL DEFAULT '0' ,
CHANGE COLUMN `active_company_id` `active_company_id` INT UNSIGNED NULL ,
CHANGE COLUMN `passive_company_id` `passive_company_id` INT UNSIGNED NULL ,
CHANGE COLUMN `acc_debit_code` `acc_debit_code` VARCHAR(15) NULL ,
CHANGE COLUMN `acc_credit_code` `acc_credit_code` VARCHAR(15) NULL ,
CHANGE COLUMN `amount` `amount` DOUBLE NULL DEFAULT 0 ,
CHANGE COLUMN `amount_alt` `amount_alt` DOUBLE NULL DEFAULT 0 ,
CHANGE COLUMN `description` `description` VARCHAR(255) NULL ;
ALTER TABLE `isell_db`.`acc_trans` 
ADD CONSTRAINT `acc_credit_code`
  FOREIGN KEY (`acc_credit_code`)
  REFERENCES `isell_db`.`acc_tree` (`acc_code`),
ADD CONSTRAINT `acc_debit_code`
  FOREIGN KEY (`acc_debit_code`)
  REFERENCES `isell_db`.`acc_tree` (`acc_code`),
ADD CONSTRAINT `FK_acc_activecid`
  FOREIGN KEY (`active_company_id`)
  REFERENCES `isell_db`.`companies_list` (`company_id`)
  ON DELETE CASCADE,
ADD CONSTRAINT `FK_acc_passivecid`
  FOREIGN KEY (`passive_company_id`)
  REFERENCES `isell_db`.`companies_list` (`company_id`)
  ON DELETE CASCADE;
