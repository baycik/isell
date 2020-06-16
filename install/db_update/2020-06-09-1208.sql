ALTER TABLE `checkout_entries` 
ADD COLUMN `product_comment` VARCHAR(200) NULL AFTER `product_quantity_verified`;
