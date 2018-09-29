ALTER TABLE `log_list` 
ADD COLUMN `log_class` VARCHAR(45) NULL COMMENT 'Категория' AFTER `url`;
