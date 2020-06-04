/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  bayci
 * Created: Jun 1, 2020
 */

ALTER TABLE `plugin_campaign_list` 
ADD COLUMN `campaign_fixed_payment` VARCHAR(45) NULL AFTER `campaign_name`;
