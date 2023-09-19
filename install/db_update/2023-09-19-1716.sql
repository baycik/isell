ALTER TABLE `tezkel_isell_db`.`stock_entries` 
CHANGE COLUMN `product_quantity` `product_quantity` DECIMAL(15,3) NOT NULL DEFAULT '0' COMMENT 'Остаток' ,
CHANGE COLUMN `product_reserved` `product_reserved` DECIMAL(15,3) NOT NULL DEFAULT '0' ,
CHANGE COLUMN `product_awaiting` `product_awaiting` DECIMAL(15,3) NOT NULL DEFAULT '0' ;


ALTER TABLE `tezkel_isell_db`.`document_entries` 
CHANGE COLUMN `product_quantity` `product_quantity` DECIMAL(15,3) NOT NULL DEFAULT '0' ;
