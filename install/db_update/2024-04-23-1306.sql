ALTER TABLE `user_list` 
ADD COLUMN `selected_passive_company_id` INT(11) NULL DEFAULT 0 COMMENT 'last selected passive company_id' AFTER `company_id`,
CHANGE COLUMN `company_id` `company_id` INT(11) NULL DEFAULT '1' COMMENT 'last selected active company_id' ;
