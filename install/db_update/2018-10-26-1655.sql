ALTER TABLE `acc_trans` 
DROP COLUMN `acc_doc_id`,
ADD COLUMN `is_disabled` TINYINT NULL AFTER `trans_id`;
