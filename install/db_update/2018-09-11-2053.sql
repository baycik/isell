ALTER TABLE `event_list` 
ADD COLUMN `event_date_done` DATETIME NULL AFTER `event_date`,
ADD COLUMN `event_date_created` DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER `event_date_done`;
