ALTER TABLE `companies_list` 
CHANGE COLUMN `company_name` `company_name` VARCHAR(255) NULL COMMENT 'Полное Название' ,
CHANGE COLUMN `company_person` `company_person` VARCHAR(255) NULL COMMENT 'Контактное лицо' ,
CHANGE COLUMN `company_director` `company_director` VARCHAR(45) NULL COMMENT 'Директор' ,
CHANGE COLUMN `company_director_title` `company_director_title` VARCHAR(45) NULL ,
CHANGE COLUMN `company_email` `company_email` VARCHAR(255) NULL COMMENT 'Емаил' ,
CHANGE COLUMN `company_web` `company_web` VARCHAR(255) NULL COMMENT 'Сайт' ,
CHANGE COLUMN `company_phone` `company_phone` VARCHAR(255) NULL COMMENT 'Телефон' ,
CHANGE COLUMN `company_fax` `company_fax` VARCHAR(255) NULL COMMENT 'Факс' ,
CHANGE COLUMN `company_mobile` `company_mobile` VARCHAR(255) NULL COMMENT 'Мобильный телефон' ,
CHANGE COLUMN `company_address` `company_address` VARCHAR(255) NULL COMMENT 'Фактический адрес' ,
CHANGE COLUMN `company_jaddress` `company_jaddress` VARCHAR(255) NULL COMMENT 'Юридический адрес' ,
CHANGE COLUMN `company_bank_id` `company_bank_id` VARCHAR(45) NULL ,
CHANGE COLUMN `company_bank_name` `company_bank_name` VARCHAR(255) NULL ,
CHANGE COLUMN `company_bank_account` `company_bank_account` VARCHAR(45) NULL ,
CHANGE COLUMN `company_tax_id` `company_tax_id` VARCHAR(45) NULL ,
CHANGE COLUMN `company_tax_id2` `company_tax_id2` VARCHAR(45) NULL ,
CHANGE COLUMN `company_code` `company_code` VARCHAR(45) NULL ,
CHANGE COLUMN `company_agreement_num` `company_agreement_num` VARCHAR(45) NULL ,
CHANGE COLUMN `company_agreement_date` `company_agreement_date` VARCHAR(45) NULL ,
CHANGE COLUMN `company_vat_rate` `company_vat_rate` INT UNSIGNED NULL ,
CHANGE COLUMN `company_description` `company_description` TEXT NULL COMMENT 'Дополнительно' ,
CHANGE COLUMN `price_label` `price_label` VARCHAR(45) NULL ,
CHANGE COLUMN `deferment` `deferment` INT UNSIGNED NULL ,
CHANGE COLUMN `debt_limit` `debt_limit` DOUBLE NULL ,
CHANGE COLUMN `is_supplier` `is_supplier` TINYINT UNSIGNED NULL ;
