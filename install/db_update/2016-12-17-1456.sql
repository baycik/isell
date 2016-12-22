/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Baycik
 * Created: Dec 17, 2016
 */

ALTER TABLE `document_list` 
ADD COLUMN `doc_settings` JSON NULL AFTER `doc_ratio`;
