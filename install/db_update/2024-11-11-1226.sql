ALTER TABLE `plugin_campaign_bonus` 
ADD COLUMN `cache` JSON NULL AFTER `bonus_visibility`,
ADD COLUMN `cache_expired_at` DATETIME NULL AFTER `cache`;
