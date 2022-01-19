ALTER TABLE `stock_entries` 
DROP FOREIGN KEY `FK_stock_entries_1`;
ALTER TABLE `stock_entries` 
DROP COLUMN `vat_quantity`,
CHANGE COLUMN `product_code` `product_code` VARCHAR(45) NOT NULL DEFAULT 0 COMMENT 'Код товара' ,
CHANGE COLUMN `product_quantity` `product_quantity` DOUBLE NOT NULL DEFAULT 0 COMMENT 'Остаток' ,
CHANGE COLUMN `product_reserved` `product_reserved` DOUBLE NOT NULL DEFAULT 0 ,
CHANGE COLUMN `product_awaiting` `product_awaiting` DOUBLE NOT NULL DEFAULT 0 ,
CHANGE COLUMN `product_wrn_quantity` `product_wrn_quantity` INT UNSIGNED NOT NULL DEFAULT 0 ,
CHANGE COLUMN `self_price` `self_price` DOUBLE NULL DEFAULT 0 COMMENT 'DEPRECATED',
CHANGE COLUMN `fetch_count` `fetch_count` INT NULL DEFAULT 0 ;
ALTER TABLE `stock_entries` 
ADD CONSTRAINT `FK_stock_entries_1`
  FOREIGN KEY (`product_code`)
  REFERENCES `prod_list` (`product_code`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
