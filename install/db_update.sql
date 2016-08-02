ALTER TABLE `companies_list` 
ADD COLUMN `price_label` VARCHAR(45) NULL DEFAULT NULL AFTER `curr_code`;
ALTER TABLE `price_list` 
CHANGE COLUMN `label` `label` VARCHAR(45) NOT NULL COMMENT 'Категория цен';
