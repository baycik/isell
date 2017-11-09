ALTER TABLE `isell_db`.`companies_list` 
CHANGE COLUMN `company_name` `company_name` VARCHAR(255) NOT NULL COMMENT 'Полное Название' ,
CHANGE COLUMN `company_person` `company_person` VARCHAR(255) NOT NULL COMMENT 'Контактное лицо' ,
CHANGE COLUMN `company_director` `company_director` VARCHAR(45) NOT NULL COMMENT 'Директор' ,
CHANGE COLUMN `company_email` `company_email` VARCHAR(255) NOT NULL COMMENT 'Емаил' ,
CHANGE COLUMN `company_web` `company_web` VARCHAR(255) NOT NULL COMMENT 'Сайт' ,
CHANGE COLUMN `company_phone` `company_phone` VARCHAR(255) NOT NULL COMMENT 'Телефон' ,
CHANGE COLUMN `company_fax` `company_fax` VARCHAR(255) NOT NULL COMMENT 'Факс' ,
CHANGE COLUMN `company_mobile` `company_mobile` VARCHAR(255) NOT NULL COMMENT 'Мобильный телефон' ,
CHANGE COLUMN `company_address` `company_address` VARCHAR(255) NOT NULL COMMENT 'Фактический адрес' ,
CHANGE COLUMN `company_jaddress` `company_jaddress` VARCHAR(255) NOT NULL COMMENT 'Юридический адрес' ,
CHANGE COLUMN `company_description` `company_description` TEXT NOT NULL COMMENT 'Дополнительно' ;


ALTER TABLE `isell_db`.`companies_tree` 
CHANGE COLUMN `label` `label` VARCHAR(45) NOT NULL COMMENT 'Короткое название' ,
CHANGE COLUMN `path` `path` TEXT NULL DEFAULT NULL COMMENT 'Путь' ;
