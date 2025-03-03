ALTER TABLE `companies_list` 
ADD COLUMN `deferment_restr` INT NULL AFTER `debt_limit`,
ADD COLUMN `debt_limit_restr` INT NULL AFTER `deferment_restr`,
CHANGE COLUMN `debt_limit` `debt_limit` INT NULL DEFAULT NULL ;
