ALTER TABLE `plugin_campaign_bonus` 
ADD COLUMN `campaign_queue` TINYINT NULL DEFAULT 1 AFTER `campaign_grouping_interval`;
