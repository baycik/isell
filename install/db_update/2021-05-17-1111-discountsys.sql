ALTER TABLE `companies_discounts` 
DROP FOREIGN KEY `FK_companies_discounts_1`;
ALTER TABLE `companies_discounts` 
CHARACTER SET = utf8 ,
ADD COLUMN `discount_id` INT NOT NULL AUTO_INCREMENT FIRST,
ADD COLUMN `analyse_brand` VARCHAR(45) NULL AFTER `branch_id`,
ADD COLUMN `round_to` FLOAT NULL AFTER `discount`,
CHANGE COLUMN `company_id` `company_id` INT(10) UNSIGNED NULL ,
CHANGE COLUMN `branch_id` `branch_id` INT(10) UNSIGNED NULL ,
CHANGE COLUMN `discount` `discount` FLOAT NULL ,
DROP PRIMARY KEY,
ADD PRIMARY KEY (`discount_id`),
DROP INDEX `FK_companies_discounts_2` ,
ADD INDEX `FK_companies_discounts_2` (`branch_id` ASC),
ADD UNIQUE INDEX `branch` (`company_id` ASC, `branch_id` ASC),
ADD UNIQUE INDEX `brand` (`company_id` ASC, `analyse_brand` ASC);
;
ALTER TABLE `companies_discounts` 
ADD CONSTRAINT `FK_companies_discounts_1`
  FOREIGN KEY (`company_id`)
  REFERENCES `isell_db`.`companies_list` (`company_id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;


ALTER TABLE `companies_discounts` 
ADD CONSTRAINT `FK_companies_discounts_2`
  FOREIGN KEY (`branch_id`)
  REFERENCES `stock_tree` (`branch_id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;
