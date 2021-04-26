ALTER TABLE `acc_check_list` 
ADD COLUMN `transaction_id` INT NULL AFTER `assumption_date`,
DROP INDEX `unq` ,
ADD UNIQUE INDEX `unq` (`number` ASC, `transaction_date` ASC, `correspondent_code` ASC, `debit_amount` ASC, `credit_amount` ASC, `active_company_id` ASC, `assignment` ASC, `transaction_id` ASC);
;
