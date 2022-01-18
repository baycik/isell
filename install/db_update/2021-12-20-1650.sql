INSERT INTO `document_types` (`doc_type`, `doc_type_name`, `icon_name`) VALUES ('5', 'Агентский чек', 'agentsell');


ALTER TABLE `document_entries` 
ADD COLUMN `doc_entry_text` VARCHAR(200) NULL AFTER `product_code`,
CHANGE COLUMN `product_code` `product_code` VARCHAR(45) NULL ;

