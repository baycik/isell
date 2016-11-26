/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Baycik
 * Created: Nov 26, 2016
 */

ALTER TABLE `acc_trans` 
CHANGE COLUMN `cstamp` `cstamp` DATETIME NULL DEFAULT NULL ,
CHANGE COLUMN `tstamp` `tstamp` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ,
ADD COLUMN `doc_id` INT NULL AFTER `trans_id`;



