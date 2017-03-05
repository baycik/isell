/**
 * Author:  Baycik
 * Created: Mar 5, 2017
 */

ALTER TABLE `document_entries` 
DROP COLUMN `invoice_sum`,
ADD COLUMN `product_quantity_left` INT NULL AFTER `product_quantity`;
