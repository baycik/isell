ALTER TABLE `prod_list` 
CHANGE COLUMN `ru` `ru` VARCHAR(1000) NOT NULL COMMENT 'Название Рус.' ,
CHANGE COLUMN `ua` `ua` VARCHAR(1000) NOT NULL COMMENT 'Назва Укр.' ,
CHANGE COLUMN `en` `en` VARCHAR(1000) NOT NULL COMMENT 'Name En.' ,

CHANGE COLUMN `product_barcode` `product_barcode` VARCHAR(13) NOT NULL COMMENT 'Штрихкод' ,
CHANGE COLUMN `product_bpack` `product_bpack` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Бол. упак.' ,
CHANGE COLUMN `product_spack` `product_spack` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Мал. упак.' ,
CHANGE COLUMN `product_weight` `product_weight` DOUBLE NOT NULL DEFAULT 0 COMMENT 'Вес ед.' ,
CHANGE COLUMN `product_volume` `product_volume` DOUBLE NOT NULL DEFAULT 0 COMMENT 'Объем ед.' ,
CHANGE COLUMN `product_unit` `product_unit` VARCHAR(5) NOT NULL DEFAULT 'шт' COMMENT 'Единица' ;
