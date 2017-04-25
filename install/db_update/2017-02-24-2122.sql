/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Baycik
 * Created: Feb 24, 2017
 */

ALTER TABLE `document_list` 
ADD COLUMN `parent_doc_id` INT(11) NULL AFTER `doc_id`;
