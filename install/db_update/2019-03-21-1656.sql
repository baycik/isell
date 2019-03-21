ALTER TABLE `stock_entries` 
CHANGE COLUMN `product_reserved` `product_reserved` DOUBLE NOT NULL ,
CHANGE COLUMN `product_awaiting` `product_awaiting` DOUBLE NOT NULL ;
