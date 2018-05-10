ALTER TABLE `prod_list` 
CHANGE COLUMN `analyse_type` `analyse_type` VARCHAR(45) NOT NULL COMMENT 'Тип' ,
CHANGE COLUMN `analyse_brand` `analyse_brand` VARCHAR(45) NOT NULL COMMENT 'Бренд' ,
CHANGE COLUMN `analyse_class` `analyse_class` VARCHAR(45) NOT NULL COMMENT 'Класс' ,
CHANGE COLUMN `analyse_origin` `analyse_origin` VARCHAR(45) NOT NULL COMMENT 'Таможенный код' ;
