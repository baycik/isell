SET NAMES 'utf8';

ALTER TABLE `document_trans` 
ADD COLUMN `trans_role` VARCHAR(45) NULL DEFAULT NULL AFTER `type`;
UPDATE document_trans SET trans_role='profit' WHERE `type`='791_441';
UPDATE document_trans SET trans_role='total' WHERE `type`='361_702' OR  `type`='28_631' OR  `type`='361_44' OR `type`='44_631';
UPDATE document_trans SET trans_role='vatless' WHERE `type`='702_791' OR  `type`='281_28' OR  `type`='44_441' OR `type`='441_44';
UPDATE document_trans SET trans_role='vat' WHERE `type`='702_641' OR  `type`='641_28' OR  `type`='44_641' OR `type`='641_44';
UPDATE document_trans SET trans_role='self' WHERE `type`='791_281';


ALTER TABLE `document_view_list` 
ADD COLUMN `view_role` VARCHAR(45) NULL DEFAULT NULL AFTER `freezed`;
UPDATE document_view_list dvl JOIN document_view_types dvt USING(view_type_id) SET dvl.view_role=dvt.view_role;

INSERT INTO `prod_list` (`product_code`, `ru`, `ua`, `en`, `is_service`,product_unit) VALUES ('аренда', 'Аренда помешений', 'Оренда приміщень', 'Rent for building', '1','м2');
INSERT INTO `prod_list` (`product_code`, `ru`, `ua`, `en`, `is_service`,product_unit) VALUES ('топливо', 'Топливо', 'Паливо', 'Fuel', '1','л');
INSERT INTO `prod_list` (`product_code`, `ru`, `ua`, `en`, `is_service`,product_unit) VALUES ('интернет', 'Интернет', 'Інтернет', 'Internet', '1','мес');
INSERT INTO `prod_list` (`product_code`, `ru`, `ua`, `en`, `is_service`,product_unit) VALUES ('эл-во', 'Электроэнергия', 'Електроенергія', 'Electricity', '1','кВт*ч');
INSERT INTO `prod_list` (`product_code`, `ru`, `ua`, `en`, `is_service`,product_unit) VALUES ('канц', 'Канц. товары', 'Канц. товари', 'Stationery', '1','шт');
INSERT INTO `prod_list` (`product_code`, `ru`, `ua`, `en`, `is_service`,product_unit) VALUES ('офис', 'Материалы для офиса', 'Матеріали для офісу', 'Items for office', '1','шт');
INSERT INTO `prod_list` (`product_code`, `ru`, `ua`, `en`, `is_service`,product_unit) VALUES ('телефон', 'Телефонная связь', 'Телефонний з`вязок', 'Phone', '1','мес');
INSERT INTO `prod_list` (`product_code`, `ru`, `ua`, `en`, `is_service`,product_unit) VALUES ('ремонт', 'Ремонт авто или помещений', 'Ремонт авто чи приміщень', 'Repair of vehicle or office', '1','шт');
INSERT INTO `prod_list` (`product_code`, `ru`, `ua`, `en`, `is_service`,product_unit) VALUES ('услуга', 'Услуга', 'Послуга', 'Service', '1','шт');
INSERT INTO `stock_tree` (`label`, `path`) VALUES ( 'Услуги', '/Услуги/');
SET @parent_id=LAST_INSERT_ID();
UPDATE stock_tree SET top_id=@parent_id WHERE branch_id=@parent_id;
INSERT INTO `stock_entries` (`product_code`, `parent_id`) VALUES ('аренда', @parent_id);
INSERT INTO `stock_entries` (`product_code`, `parent_id`) VALUES ('топливо', @parent_id);
INSERT INTO `stock_entries` (`product_code`, `parent_id`) VALUES ('интернет', @parent_id);
INSERT INTO `stock_entries` (`product_code`, `parent_id`) VALUES ('эл-во', @parent_id);
INSERT INTO `stock_entries` (`product_code`, `parent_id`) VALUES ('канц', @parent_id);
INSERT INTO `stock_entries` (`product_code`, `parent_id`) VALUES ('офис', @parent_id);
INSERT INTO `stock_entries` (`product_code`, `parent_id`) VALUES ('телефон', @parent_id);
INSERT INTO `stock_entries` (`product_code`, `parent_id`) VALUES ('ремонт', @parent_id);
INSERT INTO `stock_entries` (`product_code`, `parent_id`) VALUES ('услуга', @parent_id);

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

ALTER TABLE `document_view_types` 
DROP FOREIGN KEY `FK_document_view_types_1`;

ALTER TABLE `companies_tree` 
CHANGE COLUMN `branch_id` `branch_id` INT(10) UNSIGNED NULL DEFAULT NULL AUTO_INCREMENT ;

ALTER TABLE `document_view_types` 
CHANGE COLUMN `doc_type` `doc_types` VARCHAR(20) NOT NULL ,
DROP INDEX `FK_document_view_types_1`;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;


UPDATE document_view_types SET doc_types=CONCAT('/',doc_types,'/');
UPDATE document_view_types SET doc_types='/1/3/' WHERE view_type_id=22;
UPDATE document_view_types SET doc_types='/1/3/' WHERE view_type_id=27;
INSERT INTO `document_view_types` (`doc_types`, `view_name`, `view_tpl`) VALUES ('/3/', 'Акт выполенных работ', 'ua/doc/service_invoice.xlsx');
INSERT INTO `document_view_types` (`doc_types`, `view_name`, `view_tpl`) VALUES ('/4/', 'Акт выполенных работ (Вхідний)', 'ua/doc/service_invoice.xlsx');
INSERT INTO `document_view_types` (`doc_types`, `view_name`, `view_role`, `view_efield_labels`, `view_tpl`) VALUES ('/2/4/', 'Податкова Накладна (Вхідна)', 'tax_bill', '{\"sign\":\"Выписал\",\"type_of_reason\":\"Тип причины\"}', 'ua/doc/podatkova_nakladna2015_1.html');


