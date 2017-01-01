/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Baycik
 * Created: Dec 29, 2016
 */

ALTER TABLE `plugin_list` 
ADD COLUMN `trigger_before` TEXT NULL AFTER `plugin_settings`;
ADD COLUMN `trigger_after` TEXT NULL AFTER `trigger_before`;
