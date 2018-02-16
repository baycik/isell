ALTER TABLE `acc_trans` 
ADD COLUMN `trans_article` VARCHAR(45) NULL AFTER `trans_status`;


ALTER TABLE `acc_article_list` 
ADD COLUMN `article_group` VARCHAR(45) NULL AFTER `article_name`;
