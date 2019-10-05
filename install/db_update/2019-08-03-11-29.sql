ALTER TABLE `isell_db`.`attribute_values` 
ADD COLUMN `attribute_value_hash` VARCHAR(32) GENERATED ALWAYS AS (MD5(CONCAT(attribute_id,'|', attribute_value))) STORED AFTER `attribute_value`;
