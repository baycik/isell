ALTER TABLE `event_list` 
ADD COLUMN `event_program` TEXT NULL AFTER `event_descr`;
CHANGE COLUMN `event_note` `event_note` VARCHAR(255) NOT NULL COMMENT '' ;
