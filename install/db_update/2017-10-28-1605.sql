ALTER TABLE `prod_list` 
CHANGE COLUMN `analyse_section` `product_article` VARCHAR(45) NULL DEFAULT NULL COMMENT 'Артикул' AFTER `product_code`,
CHANGE COLUMN `product_uktzet` `analyse_origin` VARCHAR(45) NOT NULL COMMENT 'Таможенный код' AFTER `analyse_class`,
CHANGE COLUMN `barcode` `product_barcode` VARCHAR(13) NOT NULL COMMENT 'Штрихкод' ,
CHANGE COLUMN `analyse_group` `analyse_brand` VARCHAR(45) NULL DEFAULT NULL COMMENT 'Бренд' ;
