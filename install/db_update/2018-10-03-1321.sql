ALTER TABLE `user_list` 
ADD COLUMN `user_is_staff` TINYINT NULL DEFAULT 0 AFTER `user_email`;
