ALTER TABLE `document_list` 
CHANGE COLUMN `doc_num` `doc_num` INT(11) NOT NULL AFTER `doc_type`,
CHANGE COLUMN `cstamp` `cstamp` DATETIME NULL DEFAULT NULL AFTER `passive_company_id`,
CHANGE COLUMN `is_commited` `is_commited` TINYINT(1) NOT NULL AFTER `is_reclamation`,
CHANGE COLUMN `inernn` `inernn` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `vat_rate`,
CHANGE COLUMN `reg_stamp` `reg_stamp` TIMESTAMP NULL DEFAULT NULL AFTER `inernn`,
ADD COLUMN `parent_doc_id` INT(11) NULL AFTER `doc_id`;
