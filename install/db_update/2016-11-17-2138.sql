/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Baycik
 * Created: Nov 17, 2016
 */

ALTER TABLE `user_list` 
CHANGE COLUMN `nick` `nick` VARCHAR(45) NULL DEFAULT NULL AFTER `last_name`,
CHANGE COLUMN `company_id` `company_id` INT(11) NULL DEFAULT '1' AFTER `id_date`,
ADD COLUMN `user_phone` VARCHAR(45) NULL AFTER `user_position`;
