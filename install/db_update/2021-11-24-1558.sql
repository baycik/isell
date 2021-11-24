ALTER TABLE `document_entries` 
CHANGE COLUMN `party_label` `party_label` VARCHAR(45) NOT NULL DEFAULT '-' ,
CHANGE COLUMN `product_quantity` `product_quantity` DOUBLE NOT NULL DEFAULT 0 ,
CHANGE COLUMN `self_price` `self_price` DOUBLE NOT NULL DEFAULT 0 ,
CHANGE COLUMN `breakeven_price` `breakeven_price` DOUBLE NOT NULL DEFAULT 0 ,
CHANGE COLUMN `invoice_price` `invoice_price` DOUBLE NOT NULL DEFAULT 0 ;
