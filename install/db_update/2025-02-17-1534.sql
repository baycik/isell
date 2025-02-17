ALTER TABLE `document_list` 
ADD COLUMN `notreckon` TINYINT UNSIGNED NULL DEFAULT 0 COMMENT 'reckon in statistics or not' AFTER `notcount`;
