ALTER TABLE `companies_list` 
CHANGE COLUMN `company_vat_licence_id` `company_tax_id2` VARCHAR(45) NOT NULL AFTER `company_tax_id`,
CHANGE COLUMN `company_vat_id` `company_tax_id` VARCHAR(45) NOT NULL ;
