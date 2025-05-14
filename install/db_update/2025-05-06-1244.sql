ALTER TABLE `prod_list` 
CHANGE COLUMN `ru` `ru` VARCHAR(255) NULL COMMENT 'Название Рус.' ,
CHANGE COLUMN `ua` `ua` VARCHAR(255) NULL COMMENT 'Назва Укр.' ,
CHANGE COLUMN `en` `en` VARCHAR(255) NULL COMMENT 'Name En.' ,
CHANGE COLUMN `product_barcode` `product_barcode` VARCHAR(13) NULL COMMENT 'Штрихкод' ,
CHANGE COLUMN `product_bpack` `product_bpack` INT UNSIGNED NULL DEFAULT 1 COMMENT 'Бол. упак.' ,
CHANGE COLUMN `product_spack` `product_spack` INT UNSIGNED NULL DEFAULT 1 COMMENT 'Мал. упак.' ,
CHANGE COLUMN `product_weight` `product_weight` DOUBLE NULL COMMENT 'Вес ед.' ,
CHANGE COLUMN `product_volume` `product_volume` DOUBLE NULL COMMENT 'Объем ед.' ,
CHANGE COLUMN `product_unit` `product_unit` VARCHAR(5) NULL COMMENT 'Единица' ,
CHANGE COLUMN `is_service` `is_service` TINYINT UNSIGNED NULL DEFAULT '0' COMMENT 'Услуга?' ,
CHANGE COLUMN `analyse_type` `analyse_type` VARCHAR(45) NULL COMMENT 'Тип' ,
CHANGE COLUMN `analyse_brand` `analyse_brand` VARCHAR(45) NULL COMMENT 'Бренд' ,
CHANGE COLUMN `analyse_class` `analyse_class` VARCHAR(45) NULL COMMENT 'Класс' ,
CHANGE COLUMN `analyse_origin` `analyse_origin` VARCHAR(45) NULL COMMENT 'Таможенный код' ;



ALTER TABLE `price_list` 
DROP FOREIGN KEY `FK_prodcode`;
ALTER TABLE `price_list` 
CHANGE COLUMN `product_code` `product_code` VARCHAR(45) NULL COMMENT 'Код товара' ,
CHANGE COLUMN `label` `label` VARCHAR(45) NULL COMMENT 'Категория цен' ,
CHANGE COLUMN `sell` `sell` DOUBLE NULL COMMENT 'Продажа' ,
CHANGE COLUMN `buy` `buy` DOUBLE NULL COMMENT 'Покупка' ,
CHANGE COLUMN `curr_code` `curr_code` VARCHAR(45) NULL COMMENT 'Код валюты',
ADD UNIQUE INDEX `uniqueprice` (`product_code` ASC, `label` ASC),
DROP PRIMARY KEY;
;
ALTER TABLE `price_list` 
ADD CONSTRAINT `FK_prodcode`
  FOREIGN KEY (`product_code`)
  REFERENCES `prod_list` (`product_code`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
