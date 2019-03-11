ALTER TABLE `stock_entries` 
ADD COLUMN `product_reserve` DOUBLE NULL AFTER `product_quantity`,
ADD COLUMN `product_awaiting` DOUBLE NULL AFTER `product_reserve`;
