/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Baycik
 * Created: Nov 13, 2016
 */

ALTER TABLE `document_entries` 
ADD COLUMN `invoice_sum` DOUBLE NULL AFTER `invoice_price`,
ADD COLUMN `vat_rate` DOUBLE NULL AFTER `invoice_sum`;