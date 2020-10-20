ALTER TABLE `stock_entries` 
DROP COLUMN `product_sector`;

DELETE FROM `document_view_types` WHERE `view_tpl`='../../plugins/StockSectorManager/views/stockBill.xlsx';
