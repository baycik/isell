ALTER TABLE `price_list` 
DROP FOREIGN KEY `FK_prodcode`;



ALTER TABLE `isell_db`.`prod_list` 
CHANGE COLUMN `product_code` `product_code` VARCHAR(45) CHARACTER SET 'utf8' COLLATE 'utf8_bin' NOT NULL COMMENT 'Код товара' ;


ALTER TABLE `isell_db`.`document_entries` 
CHANGE COLUMN `product_code` `product_code` VARCHAR(45) CHARACTER SET 'utf8' COLLATE 'utf8_bin' NULL DEFAULT NULL ;
