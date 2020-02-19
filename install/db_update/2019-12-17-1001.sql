ALTER TABLE `acc_check_list` 
DROP INDEX `unq` ,
ADD UNIQUE INDEX `unq` (`number` ASC, `transaction_date` ASC, `correspondent_code` ASC, `debit_amount` ASC, `credit_amount` ASC, `active_company_id` ASC, `assignment` ASC);
