DELETE FROM price_list WHERE label IS NULL;
ALTER TABLE `price_list` 
CHANGE COLUMN `label` `label` VARCHAR(45) NOT NULL DEFAULT '' COMMENT 'Категория цен' ;
