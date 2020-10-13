ALTER TABLE `stock_entries` 
ADD COLUMN `product_sector` VARCHAR(10) NULL AFTER `product_img`;


INSERT INTO `document_view_types` (`doc_types`, `blank_set`, `view_name`, `view_tpl`) VALUES ('/1/2/', 'ru', 'Складская накладная', '../../plugins/StockSectorManager/views/stockBill.xlsx');
