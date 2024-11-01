update event_list set event_date=null where event_date=0;
ALTER TABLE `event_list` 
CHANGE COLUMN `created_by` `created_by` INT NULL DEFAULT NULL ,
CHANGE COLUMN `modified_by` `modified_by` INT NULL DEFAULT NULL ;
