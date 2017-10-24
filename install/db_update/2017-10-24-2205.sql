ALTER TABLE `event_list` 
DROP FOREIGN KEY `userid`;
ALTER TABLE `isell_db`.`event_list` 
CHANGE COLUMN `event_user_id` `event_creator_user_id` INT(11) NULL DEFAULT NULL ,
CHANGE COLUMN `event_user_liable` `event_liable_user_id` VARCHAR(45) NULL DEFAULT NULL ,


ADD COLUMN `doc_id` INT NULL AFTER `event_id`,


CHANGE COLUMN `event_descr` `event_descr` VARCHAR(500) NOT NULL;