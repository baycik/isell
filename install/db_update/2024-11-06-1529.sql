ALTER TABLE `event_list` 
CHANGE COLUMN `event_is_private` `event_is_private` TINYINT(1) NOT NULL DEFAULT 0 ,
CHANGE COLUMN `event_status` `event_status` VARCHAR(20) NOT NULL DEFAULT 0 ,
CHANGE COLUMN `event_label` `event_label` VARCHAR(45) NULL ,
CHANGE COLUMN `event_name` `event_name` VARCHAR(45) NULL ,
CHANGE COLUMN `event_target` `event_target` VARCHAR(255) NULL ,
CHANGE COLUMN `event_place` `event_place` VARCHAR(255) NULL ,
CHANGE COLUMN `event_note` `event_note` VARCHAR(255) NULL ,
CHANGE COLUMN `event_descr` `event_descr` VARCHAR(500) NULL ;

