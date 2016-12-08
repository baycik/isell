/**
 * Author:  Baycik
 * Created: Nov 27, 2016
 */

CREATE TABLE `plugin_list` (
  `plugin_system_name` VARCHAR(45) NOT NULL,
  `plugin_settings` TEXT NULL,
  `is_installed` TINYINT NULL,
  `is_activated` TINYINT NULL,
  PRIMARY KEY (`plugin_system_name`));