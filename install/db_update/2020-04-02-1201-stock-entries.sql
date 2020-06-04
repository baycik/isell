ALTER TABLE `stock_entries` 
ADD COLUMN `stock_id` INT NOT NULL DEFAULT 1 AFTER `parent_id`,
CHANGE COLUMN `party_label` `party_label` VARCHAR(45) NULL DEFAULT NULL COMMENT 'DEPRECATED' AFTER `modified_at`,
CHANGE COLUMN `vat_quantity` `vat_quantity` INT(10) NOT NULL COMMENT 'DEPRECATED' AFTER `party_label`,
CHANGE COLUMN `self_price` `self_price` DOUBLE NOT NULL COMMENT 'DEPRECATED' AFTER `vat_quantity`,
DROP INDEX `Index_3` ,
ADD INDEX `Index_3` (`product_code` ASC),
ADD UNIQUE INDEX `index4` (`product_code` ASC, `stock_id` ASC);
;
