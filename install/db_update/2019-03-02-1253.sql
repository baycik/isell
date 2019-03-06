ALTER TABLE `stock_entries` 
CHANGE COLUMN `product_reserve` `product_reserved` DOUBLE NULL DEFAULT NULL ,
ADD COLUMN `product_awaiting` DOUBLE NULL AFTER `product_reserved`;
