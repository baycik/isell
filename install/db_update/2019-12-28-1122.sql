ALTER TABLE `plugin_sync_entries` 
ADD COLUMN `local_deleted` TINYINT NULL AFTER `local_tstamp`,
ADD COLUMN `remote_deleted` TINYINT NULL AFTER `remote_tstamp`,
CHANGE COLUMN `local_id` `local_id` INT(11) NULL DEFAULT 0 ,
CHANGE COLUMN `remote_id` `remote_id` INT(11) NULL DEFAULT 0 ,
ADD INDEX `index3` (`sync_destination` ASC);
